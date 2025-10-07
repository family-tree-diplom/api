<?php

namespace OpenCCK\Infrastructure\API\Handler;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use OpenCCK\App\Controller\AbstractController;
use OpenCCK\Infrastructure\Mapper\MapperInterface;

use Exception;

abstract class Handler implements HandlerInterface {
    /**
     * @param string $name
     * @param Request $request
     * @param MapperInterface $mapper
     * @param Session $session
     * @param ?string[] $headers
     * @return AbstractController
     * @throws Exception
     */
    protected function getController(
        string $name,
        Request $request,
        MapperInterface $mapper,
        Session $session,
        array $headers = null
    ): AbstractController {
        $className = '\\OpenCCK\\App\\Controller\\' . ucfirst($name) . 'Controller';
        if (!class_exists($className)) {
            throw new Exception('Controller ' . $className . ' not found', HttpStatus::NOT_FOUND);
        }
        return new $className($request, $mapper, $session, $headers ?? $mapper::getDefaultHeaders());
    }
}
