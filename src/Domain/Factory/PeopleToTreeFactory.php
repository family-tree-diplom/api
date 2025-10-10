<?php

namespace OpenCCK\Domain\Factory;

use OpenCCK\Domain\Entity\PeopleToTree;
use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Infrastructure\API\Input;

final class PeopleToTreeFactory implements FactoryInterface {
    /**
     * @param array $data
     * @return PeopleToTree
     */
    public function create(array $data = []): EntityInterface {
        $item = new Input($data);
        return new PeopleToTree(
            id: $item->get('id', 0, Input\Filter::INT),
            peoples_id: $item->get('peoples_id', null, Input\Filter::INT),
            trees_id: $item->get('trees_id', null, Input\Filter::INT)
        );
    }
}
