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

final class SitemapMapper implements MapperInterface {
    /**
     * @param AbstractController $controller
     * @param ?string $method
     * @return string
     * @throws BufferException
     * @throws ClientException
     */
    public static function map(AbstractController $controller, string $method = null): string {
        $request = $controller->getRequest();
        $method = $method ?? ($request->getQueryParameter('method') ?? 'default');

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
        return ['content-type' => 'text/xml; charset=utf-8'];
    }

    public static function mapBody(mixed $data): string {
        return '<?xml version="1.0" encoding="UTF-8"?>' . $data;
    }

    public static function result(mixed $data): array {
        return $data;
    }

    public static function error(mixed $data): array {
        return $data;
    }

    public static function getExecutions(
        AbstractController $controller,
        mixed $data,
        string $defaultMethod = 'default'
    ): array {
        return [async(fn() => $controller->execute($defaultMethod))];
    }
}
