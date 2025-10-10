<?php

namespace OpenCCK\Domain\Factory;

use OpenCCK\Domain\Entity\People;
use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Infrastructure\API\Input;

final class PeopleFactory implements FactoryInterface {
    /**
     * @param array $data
     * @return People
     */
    public function create(array $data = []): EntityInterface {
        $item = new Input($data);
        return new People(
            id: $item->get('id', 0, Input\Filter::INT),
            name: $item->get('name', '', Input\Filter::STR),
            surname: $item->get('surname', '', Input\Filter::STR),
            birth_day: $item->get('birth_day', '', Input\Filter::STR),
            death: $item->get('death', '', Input\Filter::STR),
            gender: $item->get('gender', null, Input\Filter::BOOLEAN),

        );
    }
}
