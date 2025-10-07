<?php

namespace OpenCCK\Infrastructure\API;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Router;
use Amp\Http\Server\Session\SessionFactory;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use Psr\Log\LoggerInterface;

interface RouterFactoryInterface {
    public function create(
        HttpServer $httpServer,
        MapperInterface $mapper,
        SessionFactory $sessionFactory,
        ErrorHandler $errorHandler,
        LoggerInterface $logger
    ): Router;
}
