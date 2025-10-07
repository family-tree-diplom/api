<?php

namespace OpenCCK\Infrastructure\Mapper;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Exception;
use OpenCCK\App\Controller\AbstractController;
use function Amp\async;
use function Amp\Future\await;

final class PlainTextMapper implements MapperInterface {
    /**
     * @param AbstractController $controller
     * @param string $method
     * @return string
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public static function map(AbstractController $controller, string $method): string {
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
        return ['content-type' => 'text/plain; charset=utf-8'];
    }

    public static function mapBody(mixed $data): string {
        return print_r($data, true);
    }

    public static function result(mixed $data): array {
        return $data;
    }

    public static function error(mixed $data): array {
        return $data;
    }

    public static function getExecutions(AbstractController $controller, mixed $data, string $defaultMethod): array {
        return [async(fn() => $controller->execute($defaultMethod))];
    }
}
