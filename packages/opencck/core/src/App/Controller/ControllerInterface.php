<?php

namespace OpenCCK\App\Controller;

interface ControllerInterface {
    public function execute(string $method, array|object $params = []): mixed;
    public function __call(string $method, array $arguments): mixed;
}
