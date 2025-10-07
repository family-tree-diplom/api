<?php

namespace OpenCCK\Infrastructure\Mapper;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\Router;
use Exception;
use OpenCCK\App\Controller\AbstractController;
use function Amp\async;
use function Amp\Future\await;
use function OpenCCK\dbg;

final class DefaultMapper implements MapperInterface {
    /**
     * @param AbstractController $controller
     * @param ?string $method
     * @return string
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public static function map(AbstractController $controller, string $method = null): string {
        $request = $controller->getRequest();
        $args = $request->getAttribute(Router::class);
        $method = $method ?? ($args['method'] ?? 'default');

        $results = await(
            self::getExecutions(
                $controller,
                $controller
                    ->getRequest()
                    ->getBody()
                    ->buffer(),
                $method
            )
        );
        ksort($results);
        return self::mapBody($results[0]);
    }

    public static function getDefaultHeaders(): array {
        return [];
    }

    public static function mapBody(mixed $data): string {
        return is_string($data) ? $data : '';
    }

    public static function result(mixed $data): array {
        return (array) $data;
    }

    public static function error(mixed $data): array {
        return (array) $data;
    }

    public static function getExecutions(AbstractController $controller, mixed $data, string $defaultMethod): array {
        return [async(fn() => $controller->execute($defaultMethod, [$data]))];
    }
}
