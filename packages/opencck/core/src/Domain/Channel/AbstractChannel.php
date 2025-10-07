<?php

namespace OpenCCK\Domain\Channel;

use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\WebsocketClient;
use OpenCCK\Infrastructure\API\WebsocketClientGateway;
use OpenCCK\Infrastructure\Mapper\JsonRPCMapper;
use OpenCCK\Infrastructure\Mapper\MapperInterface;

abstract class AbstractChannel implements ChannelInterface {
    protected static self $_instance;

    private function __construct(protected WebsocketClientGateway $gateway, protected MapperInterface $mapper) {
    }

    public static function getInstance(
        WebsocketGateway $gateway = new WebsocketClientGateway(),
        MapperInterface $mapper = new JsonRPCMapper()
    ): self {
        return self::$_instance ??= new static($gateway, $mapper);
    }

    public function getGateway(): WebsocketClientGateway {
        return $this->gateway;
    }

    public function addClient(WebsocketClient $client): void {
        $this->gateway->addClient($client);
    }

    abstract public function getEventHandler(): callable;
}
