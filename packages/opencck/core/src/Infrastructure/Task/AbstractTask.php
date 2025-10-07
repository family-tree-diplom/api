<?php

namespace OpenCCK\Infrastructure\Task;

use OpenCCK\Infrastructure\API\App;

abstract class AbstractTask implements TaskInterface {
    public function __construct() {
        $this->init();
    }

    public function init() {
        App::getInstance();
    }
}
