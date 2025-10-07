<?php

namespace OpenCCK\App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Amp\Http\Server\Session\SessionFactory;
use OpenCCK\Infrastructure\Mapper\MapperInterface;

interface AbstractControllerInterface extends ControllerInterface {
    /**
     * @param Request $request
     * @param MapperInterface $mapper
     * @param Session $session
     * @param array $headers
     */
    public function __construct(
        Request $request,
        MapperInterface $mapper,
        Session $session,
        array $headers
    );
}
