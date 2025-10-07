<?php

namespace OpenCCK\Infrastructure\API;

use Amp\Future;
use Amp\Websocket\WebsocketClient;
use Amp\Websocket\Server\Internal;
use function Amp\async;
use function array_flip;

final class WebsocketClientGateway implements AlterableWebsocketGateway {
    /** @var array<int, WebsocketClient> Indexed by client ID. */
    private array $clients = [];

    /** @var array<int, Internal\SendQueue> Senders indexed by client ID. */
    private array $senders = [];

    public function addClient(WebsocketClient $client): void {
        $id = $client->getId();
        $this->clients[$id] = $client;
        $this->senders[$id] = new Internal\SendQueue($client);

        $client->onClose(fn() => $this->removeClient($client));
    }

    public function hasClient(WebsocketClient $client): bool {
        $id = $client->getId();
        return isset($this->clients[$id]);
    }

    public function removeClient(WebsocketClient $client): void {
        $id = $client->getId();
        unset($this->clients[$id], $this->senders[$id]);
    }

    public function broadcastText(string $data, array $excludedClientIds = []): Future {
        return $this->broadcastData($data, false, $excludedClientIds);
    }

    public function broadcastBinary(string $data, array $excludedClientIds = []): Future {
        return $this->broadcastData($data, true, $excludedClientIds);
    }

    private function broadcastData(string $data, bool $binary, array $excludedClientIds = []): Future {
        $exclusionLookup = array_flip($excludedClientIds);

        $futures = [];
        foreach ($this->senders as $id => $sender) {
            if (isset($exclusionLookup[$id])) {
                continue;
            }
            $futures[$id] = $sender->send($data, $binary);
        }

        return async(Future\awaitAll(...), $futures);
    }

    public function multicastText(string $data, array $clientIds): Future {
        return $this->multicastData($data, false, $clientIds);
    }

    public function multicastBinary(string $data, array $clientIds): Future {
        return $this->multicastData($data, true, $clientIds);
    }

    private function multicastData(string $data, bool $binary, array $clientIds): Future {
        $futures = [];
        foreach ($clientIds as $id) {
            $sender = $this->senders[$id] ?? null;
            if (!$sender) {
                continue;
            }
            $futures[$id] = $sender->send($data, $binary);
        }

        return async(Future\awaitAll(...), $futures);
    }

    public function sendText(string $data, int $clientId): Future {
        return $this->sendData($data, false, $clientId);
    }

    public function sendBinary(string $data, int $clientId): Future {
        return $this->sendData($data, true, $clientId);
    }

    private function sendData(string $data, bool $binary, int $clientId): Future {
        $sender = $this->senders[$clientId] ?? null;
        if (!$sender) {
            return Future::complete();
        }

        return $sender->send($data, $binary);
    }

    public function getClients(): array {
        return $this->clients;
    }
}
