<?php

namespace OpenCCK\Infrastructure\Mapper;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use Exception;
use OpenCCK\App\Controller\AbstractController;
use function Amp\async;
use function Amp\Future\await;
use function OpenCCK\dbg;

class RequestMapper extends JsonRPCMapper implements MapperInterface {
    /**
     * @param AbstractController $controller
     * @param ?string $method
     * @return string
     */
    public static function map(AbstractController $controller, string $method = null): string {
        $request = $controller->getRequest();
        $args = $request->getAttribute(Router::class);
        $method = $method ?? ($args['method'] ?? 'default');

        $results = await(self::getExecutions($controller, null, $method));
        ksort($results);

        return self::mapBody($results);
    }

    /**
     * @param AbstractController $controller
     * @param Request $data
     * @param string $defaultMethod
     * @return array
     */
    public static function getExecutions(AbstractController $controller, mixed $data, string $defaultMethod): array {
        return [async(fn() => self::result($controller->execute($defaultMethod, [])))];
    }
}
