<?php

namespace OpenCCK\Domain\Factory;

use OpenCCK\Domain\Entity\Config;
use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Infrastructure\API\Input;

final class ConfigFactory implements FactoryInterface {
    /**
     * @param array $data
     * @return Config
     */
    public function create(array $data = []): EntityInterface {
        $item = new Input($data);
        return new Config(
            id: $item->get('id', 'generic', Input\Filter::STR),
            data: $item->get('data', '{}', Input\Filter::RAW)
        );
    }
}
