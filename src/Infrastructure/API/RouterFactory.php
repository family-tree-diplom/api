<?php

namespace OpenCCK\Infrastructure\API;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\StaticContent\DocumentRoot;

use OpenCCK\Infrastructure\API\Handler\HTTPHandler;
use OpenCCK\Infrastructure\Mapper\MapperInterface;

use Psr\Log\LoggerInterface;
use function OpenCCK\getEnv;

final class RouterFactory implements RouterFactoryInterface {
    public function create(
        HttpServer $httpServer,
        MapperInterface $mapper,
        SessionFactory $sessionFactory,
        ErrorHandler $errorHandler,
        LoggerInterface $logger
    ): Router {
        $router = new Router($httpServer, $logger, $errorHandler);

        $httpHandler = HTTPHandler::getInstance($logger, $mapper, $sessionFactory);
        $router->addRoute('POST', '/api/admin/upload', $httpHandler->getUploadHandler());

        $router->addRoute('GET', '/api/{controller}', $httpHandler->getHandler());
        $router->addRoute('GET', '/api/{namespace}/{controller}', $httpHandler->getHandler());
        $router->addRoute('GET', '/api/{namespace}/{controller}/{method}', $httpHandler->getHandler());

        $router->addRoute('POST', '/api/{controller}', $httpHandler->getHandler());
        $router->addRoute('POST', '/api/{namespace}/{controller}', $httpHandler->getHandler());
        $router->addRoute('POST', '/api/{namespace}/{controller}/{method}', $httpHandler->getHandler());

        $fallback = new DocumentRoot(
            $httpServer,
            $errorHandler,
            PATH_ROOT . '/' . (getEnv('HTTP_DOCUMENT_ROOT') ?? 'public')
        );
        $router->setFallback($fallback);

        return $router;
    }
}
