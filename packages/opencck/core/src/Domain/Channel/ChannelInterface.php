<?php

namespace OpenCCK\Domain\Channel;

use Amp\Websocket\WebsocketClient;
use OpenCCK\Infrastructure\API\AlterableWebsocketGateway;

interface ChannelInterface {
    public function addClient(WebsocketClient $client): void;
    public function getGateway(): AlterableWebsocketGateway;
    public static function getInstance(): self;
}
