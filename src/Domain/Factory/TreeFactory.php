<?php

namespace OpenCCK\Domain\Factory;

use OpenCCK\Domain\Entity\Tree;
use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Infrastructure\API\Input;

final class TreeFactory implements FactoryInterface {
    /**
     * @param array $data
     * @return Tree
     */
    public function create(array $data = []): EntityInterface {
        $item = new Input($data);
        return new Tree(
            id: $item->get('id', 0, Input\Filter::INT),
            title: $item->get('title', '', Input\Filter::STR),
            users_id: $item->get('users_id', null, Input\Filter::INT)
        );
    }
}
