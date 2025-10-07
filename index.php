<?php

use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\DefaultHttpDriverFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Sync\LocalSemaphore;

use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\API\Server;
use OpenCCK\Infrastructure\API\RouterFactory;
use OpenCCK\Infrastructure\Mapper\JsonRPCMapper;
use OpenCCK\Infrastructure\API\Handler\HTTPErrorHandler;

use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use function OpenCCK\parseBytes;

require __DIR__ . '/vendor/autoload.php';

//define('PATH_ROOT', __DIR__);
App::getInstance()
    ->addHandler(
        /**
         * @throws \Amp\Redis\RedisException
         */ fn(App $app) => Server::getInstance(
            httpServer: new SocketHttpServer(
                logger: $app->getLogger(),
                serverSocketFactory: new ConnectionLimitingServerSocketFactory(
                    new LocalSemaphore($app->getConnectionLimit())
                ),
                clientFactory: new ConnectionLimitingClientFactory(
                    new SocketClientFactory($app->getLogger()),
                    $app->getLogger(),
                    $app->getConnectionPerIpLimit()
                ),
                httpDriverFactory: new DefaultHttpDriverFactory(
                    logger: $app->getLogger(),
                    streamTimeout: 60,
                    bodySizeLimit: parseBytes(ini_get('post_max_size'))
                )
            ),
            mapper: new JsonRPCMapper(),
            routerFactory: new RouterFactory(),
            errorHandler: new HTTPErrorHandler(),
            bindContext: (new Socket\BindContext())->withoutTlsContext(),
            logger: $app->getLogger()
        )
    )
    ->start();
