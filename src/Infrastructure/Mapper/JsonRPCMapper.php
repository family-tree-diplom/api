<?php

namespace OpenCCK\Infrastructure\Mapper;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\Payload;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\Router;
use Exception;
use OpenCCK\App\Controller\AbstractController;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use Throwable;
use function Amp\async;
use function Amp\Future\await;
use function OpenCCK\getEnv;

class JsonRPCMapper implements MapperInterface {
    /**
     * @param AbstractController $controller
     * @param string $data
     * @param string $defaultMethod
     * @return array
     */
    public static function getExecutions(AbstractController $controller, mixed $data, string $defaultMethod): array {
        $executions = [];
        if ($json = $data) {
            if (is_iterable($json)) {
                foreach ($json as $jsonRequest) {
                    $methodParts = explode('.', $jsonRequest->method);
                    $executions[] = [
                        'id' => $jsonRequest->id ?? null,
                        'closure' => fn() => self::result(
                            $controller->execute(array_pop($methodParts), $jsonRequest->params ?? []),
                            $jsonRequest->id ?? null
                        ),
                    ];
                }
            }
            if (is_object($json) && isset($json->method)) {
                $executions[] = [
                    'id' => $json->id ?? null,
                    'closure' => fn() => self::result(
                        $controller->execute($json->method, $json->params ?? []),
                        $json->id ?? null
                    ),
                ];
            }
        } else {
            $executions[] = [
                'id' => null,
                'closure' => fn() => self::result($controller->execute($defaultMethod)),
            ];
        }
        return $executions;
    }

    /**
     * @param AbstractController $controller
     * @param ?string $method
     * @return string
     * @throws BufferException
     * @throws ClientException
     * @throws StreamException
     */
    public static function map(AbstractController $controller, string $method = null): string {
        $request = $controller->getRequest();
        $args = $request->getAttribute(Router::class);
        $method = $method ?? ($args['method'] ?? 'default');

        $executions = self::getExecutions($controller, json_decode($request->getBody()->buffer()), $method);
        $results = await(
            array_map(
                fn($execution) => async($execution['closure'])->catch(
                    fn(Throwable $e) => self::error(
                        array_merge(
                            ['message' => $e->getMessage(), 'code' => $e->getCode()],
                            getEnv('DEBUG') === 'true'
                                ? ['file' => $e->getFile() . ':' . $e->getLine(), 'trace' => $e->getTrace()]
                                : []
                        ),
                        $execution['id']
                    )
                ),
                $executions
            )
        );

        ksort($results);
        return self::mapBody($results);
    }

    private static function mergeResultId(array $responseData, int|string $id = null): array {
        return array_merge($responseData, is_null($id) ? [] : ['id' => $id]);
    }

    public static function result(mixed $data, int|string $id = null): array {
        return self::mergeResultId(['jsonrpc' => '2.0', 'result' => $data], $id);
    }

    public static function error(mixed $data, int|string $id = null): array {
        return self::mergeResultId(['jsonrpc' => '2.0', 'error' => $data], $id);
    }

    public static function getDefaultHeaders(): array {
        return ['content-type' => 'application/json; charset=utf-8'];
    }

    public static function mapBody(mixed $data): string {
        return is_array($data) && count($data) > 0 && (isset($data['params']) || isset($data['error']))
            ? json_encode([$data])
            : json_encode($data);
    }
}
