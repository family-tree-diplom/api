<?php

namespace OpenCCK\Infrastructure\API;

use Closure;
use Dotenv\Dotenv;
use Monolog\Logger;
use OpenCCK\Infrastructure\EventDispatcher\EventDispatcher;
use OpenCCK\Infrastructure\Model\DB;
use Revolt\EventLoop;
use Revolt\EventLoop\UnsupportedFeatureException;
use function Amp\trapSignal;
use function OpenCCK\getEnv;
use function sprintf;

final class App {
    private static App $_instance;

    private EventDispatcher $dispatcher;

    /**
     * @param array<AppModuleInterface> $modules
     */
    private array $modules;

    private bool $isEventLoopStarted = false;

    private int $connectionLimit = 1000;

    private int $connectionPerIpLimit = 10;

    /**
     * @param ?Logger $logger
     */
    private function __construct(private ?Logger $logger = null) {
        if (!defined('PATH_ROOT')) {
            define('PATH_ROOT', dirname(__DIR__, 6));
        }

        $dotenv = Dotenv::createImmutable(PATH_ROOT);
        $dotenv->safeLoad();

        if ($timezone = getEnv('SYS_TIMEZONE')) {
            date_default_timezone_set($timezone);
        }

        DB::setDefaultOptions([
            'host' => getEnv('MYSQL_HOST') ?? 'mysql',
            'port' => getEnv('MYSQL_PORT') ?? '3306',
            'user' => getEnv('MYSQL_USER') ?? 'mysql',
            'password' => getEnv('MYSQL_PASSWORD') ?? 'mysql',
            'db' => getEnv('MYSQL_DB') ?? 'db',
        ]);

        $this->logger = $logger ?? new Logger(getEnv('COMPOSE_PROJECT_NAME') ?? 'server');
        if ($connectionLimit = (int) getEnv('SYS_CONNECTION_LIMIT')) {
            $this->connectionLimit = $connectionLimit;
        }
        if ($limitPerIp = (int) getEnv('SYS_CONNECTION_LIMIT_PER_IP')) {
            $this->connectionPerIpLimit = $limitPerIp;
        }

        $this->dispatcher = new EventDispatcher();

        EventLoop::setErrorHandler(function ($e) {
            $this->logger->error($e->getMessage());
        });
    }
    public static function getInstance(?Logger $logger = null): self {
        return self::$_instance ??= new self($logger);
    }

    public function getDispatcher(): EventDispatcher {
        return $this->dispatcher;
    }

    /**
     * @param Closure<AppModuleInterface> $handler
     * @return $this
     */
    public function addHandler(Closure $handler): self {
        $module = $handler($this);
        $this->modules[$module::class] = $module;
        return $this;
    }
    public function getModule($className) {
        return $this->modules[$className];
    }
    public function getModules(): array {
        return $this->modules;
    }
    public static function getLogger(): ?Logger {
        return self::$_instance->logger;
    }

    public function start(): void {
        foreach ($this->getModules() as $module) {
            $module->start();
        }
        if (defined('SIGINT') && defined('SIGTERM')) {
            // Await SIGINT or SIGTERM to be received.
            try {
                $signal = trapSignal([SIGINT, SIGTERM]);
                $this->logger->info(sprintf('Received signal %d, stopping server', $signal));
            } catch (UnsupportedFeatureException $e) {
                $this->logger->error($e->getMessage());
            }
            $this->stop();
        } else {
            if (!$this->isEventLoopStarted) {
                $this->isEventLoopStarted = true;
                EventLoop::run();
            }
        }
    }

    public function stop(): void {
        foreach ($this->modules as $module) {
            $module->stop();
        }
    }

    /**
     * @return int
     */
    public function getConnectionLimit(): int {
        return $this->connectionLimit;
    }

    /**
     * @return int
     */
    public function getConnectionPerIpLimit(): int {
        return $this->connectionPerIpLimit;
    }
}
