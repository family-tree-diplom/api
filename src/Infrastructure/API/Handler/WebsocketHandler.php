<?php

namespace OpenCCK\Infrastructure\API\Handler;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Websocket\Server\AllowOriginAcceptor;
use Amp\Websocket\Server\Rfc6455Acceptor;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketAcceptor;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketGateway;
use Amp\Http\Server\Middleware;

use OpenCCK\Infrastructure\API\Session\SessionMiddleware;
use OpenCCK\Infrastructure\API\WebsocketClientGateway;
use OpenCCK\Infrastructure\Mapper\MapperInterface;

use Psr\Log\LoggerInterface;

final class WebsocketHandler extends Handler implements HandlerInterface {
    private Websocket $requestHandler;
    public WebsocketGateway $gateway;

    private function __construct(
        private readonly HttpServer $httpServer,
        private readonly LoggerInterface $logger,
        private readonly MapperInterface $mapper,
        private SessionFactory $sessionFactory,
        WebsocketGateway $gateway = null,
        WebsocketAcceptor $handshakeHandler = null
    ) {
        $this->sessionFactory = $sessionFactory;
        $this->gateway = $gateway ?? new WebsocketClientGateway();

        $this->requestHandler = new Websocket(
            httpServer: $httpServer,
            logger: $logger,
            acceptor: $handshakeHandler,
            clientHandler: $this->handler()
        );
    }

    public static function getInstance(
        HttpServer $httpServer,
        LoggerInterface $logger,
        MapperInterface $mapper,
        SessionFactory $sessionFactory,
        WebsocketGateway $gateway = null,
        WebsocketAcceptor $handshakeHandler = null
    ): WebsocketHandler {
        return new self(
            $httpServer,
            $logger,
            $mapper,
            $sessionFactory,
            $gateway ?? new WebsocketClientGateway(),
            $handshakeHandler ??
                (new Rfc6455Acceptor() ??
                    new AllowOriginAcceptor([
                        'http://localhost:3000',
                        'http://localhost:8080',
                        'http://127.0.0.1:3000',
                        'http://127.0.0.1:8080',
                        'http://[::1]:3000',
                        'http://[::1]:8080',
                    ]))
        );
    }

    /**
     * @return RequestHandler
     */
    public function getHandler(): RequestHandler {
        return Middleware\stackMiddleware($this->requestHandler, new SessionMiddleware($this->sessionFactory));
    }

    /**
     * @return WebsocketClientHandler
     */
    private function handler(): WebsocketClientHandler {
        return new UserWebsocketClientHandler($this->logger, $this->mapper, $this->gateway);
    }
}
