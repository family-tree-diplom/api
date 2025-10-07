<?php

namespace OpenCCK\Domain\Factory;

use OpenCCK\Domain\Entity\EntityInterface;

interface FactoryInterface {
    public function create(array $data = []): EntityInterface;
}
