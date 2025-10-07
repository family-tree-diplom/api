<?php

namespace OpenCCK\Infrastructure\API;

use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\WebsocketClient;

interface AlterableWebsocketGateway extends WebsocketGateway {
    public function addClient(WebsocketClient $client): void;
    public function hasClient(WebsocketClient $client): bool;
    public function removeClient(WebsocketClient $client): void;
}
