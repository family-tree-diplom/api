<?php

namespace OpenCCK\Infrastructure\API\Handler;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use OpenCCK\Infrastructure\Mapper\JsonRPCMapper;
use OpenCCK\Infrastructure\Mapper\MapperInterface;

final class HTTPErrorHandler implements ErrorHandler {
    public function __construct(private ?MapperInterface $mapper = null) {
        $this->mapper = $mapper ?? new JsonRPCMapper();
    }

    public function handleError(int $status, ?string $reason = null, ?Request $request = null): Response {
        return new Response(
            status: $status,
            headers: $headers ?? $this->mapper::getDefaultHeaders(),
            body: $this->mapper::mapBody($this->mapper::error(['message' => $reason, 'code' => $status]))
        );
    }
}
