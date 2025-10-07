<?php

namespace OpenCCK\Infrastructure\API;

use Amp\CompositeException;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\DefaultHttpDriverFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Router;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\RedisSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\SocketHttpServer;
use Amp\ByteStream\WritableResourceStream;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Redis\RedisConfig;
use Amp\Redis\RedisException;
use Amp\Redis\Sync\RedisMutex;
use Amp\Socket;
use Amp\Socket\BindContext;
use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\LocalSemaphore;
use Monolog\Logger;

use OpenCCK\Infrastructure\Mapper\JsonRPCMapper;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Task\MigrationsTask;

use Psr\Log\LogLevel;
use function Amp\Parallel\Worker\createWorker;
use function Amp\Redis\createRedisClient;
use function OpenCCK\getEnv;

/**
 * @see \OpenCCK\Infrastructure\API\ServerTest
 */
final class Server implements AppModuleInterface {
    private static Server $_instance;

    private Router $router;
    public static string $baseURL = 'http://localhost:8080';

    /**
     * @param ?HttpServer $httpServer
     * @param ?MapperInterface $mapper
     * @param ?RouterFactoryInterface $routerFactory
     * @param ?SessionFactory $sessionFactory
     * @param ?ErrorHandler $errorHandler
     * @param ?BindContext $bindContext
     * @param ?Logger $logger
     * @throws RedisException
     */
    private function __construct(
        private ?HttpServer $httpServer,
        private ?MapperInterface $mapper,
        private ?RouterFactoryInterface $routerFactory,
        private ?SessionFactory $sessionFactory,
        private ?ErrorHandler $errorHandler,
        private ?Socket\BindContext $bindContext,
        private ?Logger $logger
    ) {
        ini_set('memory_limit', getEnv('SYS_MEMORY_LIMIT') ?? '2048M');

        $this->logger = $logger ?? new Logger(getEnv('COMPOSE_PROJECT_NAME') ?? 'server');
        $logHandler = new StreamHandler(new WritableResourceStream(STDOUT));
        $logHandler->setFormatter(new ConsoleFormatter());
        $logHandler->setLevel(getEnv('DEBUG') === 'false' ? LogLevel::ERROR : LogLevel::INFO);
        $this->logger->pushHandler($logHandler);

        $serverSocketFactory = new ConnectionLimitingServerSocketFactory(
            new LocalSemaphore(App::getInstance()->getConnectionLimit())
        );
        $clientFactory = new ConnectionLimitingClientFactory(
            new SocketClientFactory($this->logger),
            $this->logger,
            App::getInstance()->getConnectionPerIpLimit()
        );
        $this->httpServer =
            $httpServer ??
            new SocketHttpServer(
                logger: $this->logger,
                serverSocketFactory: $serverSocketFactory,
                clientFactory: $clientFactory,
                httpDriverFactory: new DefaultHttpDriverFactory(logger: $this->logger, streamTimeout: 60)
            );
        $this->bindContext = $bindContext ?? (new Socket\BindContext())->withoutTlsContext();
        $this->errorHandler = $errorHandler ?? new DefaultErrorHandler();
        $this->mapper = $mapper ?? new JsonRPCMapper();

        if (is_null($sessionFactory)) {
            if ($redisHost = getEnv('SESSION_REDIS_HOST')) {
                [$host, $port, $password, $db] = [
                    $redisHost,
                    getEnv('SESSION_REDIS_PORT') ?? '6379',
                    getEnv('SESSION_REDIS_PASSWORD') ?? '',
                    getEnv('SESSION_REDIS_DB') ?? '0',
                ];

                $redisClient = createRedisClient(
                    RedisConfig::fromUri('redis://' . $host . ':' . $port)
                        ->withPassword($password)
                        ->withDatabase($db)
                );
                $this->sessionFactory = new SessionFactory(
                    new RedisMutex($redisClient),
                    new RedisSessionStorage(
                        client: $redisClient,
                        sessionLifetime: getEnv('SESSION_REDIS_TIMEOUT') ?? 3600 * 24
                    )
                );
            } else {
                $this->sessionFactory = new SessionFactory(new LocalKeyedMutex(), new LocalSessionStorage());
            }
        }

        $this->router = ($routerFactory ?: new RouterFactory())->create(
            $this->httpServer,
            $this->mapper,
            $this->sessionFactory,
            $this->errorHandler,
            $this->logger
        );

        self::$baseURL = getEnv('SYS_BASE_URL') ?? self::$baseURL;

        $worker = createWorker();
        $task = new MigrationsTask([PATH_ROOT . '/src/Domain/Entity']);
        $execution = $worker->submit($task);

        $this->logger->notice(
            'migrations',
            array_keys(array_map(fn($migration) => $migration['1'], $execution->await()))
        );
    }

    /**
     * Получить экземпляр веб-сервера
     * @param ?HttpServer $httpServer
     * @param ?MapperInterface $mapper
     * @param ?RouterFactoryInterface $routerFactory
     * @param ?SessionFactory $sessionFactory
     * @param ?ErrorHandler $errorHandler
     * @param ?BindContext $bindContext
     * @param ?Logger $logger
     * @return Server
     * @throws RedisException
     */
    public static function getInstance(
        HttpServer $httpServer = null,
        MapperInterface $mapper = null,
        RouterFactoryInterface $routerFactory = null,
        SessionFactory $sessionFactory = null,
        ErrorHandler $errorHandler = null,
        Socket\BindContext $bindContext = null,
        Logger $logger = null
    ): Server {
        return self::$_instance ??= new self(
            $httpServer,
            $mapper,
            $routerFactory,
            $sessionFactory,
            $errorHandler,
            $bindContext,
            $logger
        );
    }

    /**
     * Запуск веб-сервера
     * @return void
     */
    public function start(): void {
        try {
            $this->httpServer->expose(
                new Socket\InternetAddress(getEnv('HTTP_HOST') ?? '0.0.0.0', getEnv('HTTP_PORT') ?? 8080),
                $this->bindContext
            );
            //$this->socketHttpServer->expose(
            //    new Socket\InternetAddress('[::]', $_ENV['HTTP_PORT'] ?? 8080),
            //    $this->bindContext
            //);
            $this->httpServer->start($this->router, $this->errorHandler);
        } catch (Socket\SocketException | CompositeException $e) {
            $this->logger->warning($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public static function getLogger(): ?Logger {
        return self::$_instance->logger;
    }

    /**
     * @return void
     */
    public function stop(): void {
        $this->httpServer->stop();
    }

    /**
     * @return HttpServerStatus
     */
    public function getStatus(): HttpServerStatus {
        return $this->httpServer->getStatus();
    }
}
