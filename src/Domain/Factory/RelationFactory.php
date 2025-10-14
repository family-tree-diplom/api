<?php

namespace OpenCCK\Domain\Factory;

use OpenCCK\Domain\Entity\Relation;
use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Infrastructure\API\Input;

final class RelationFactory implements FactoryInterface {
    /**
     * @param array $data
     * @return Relation
     */
    public function create(array $data = []): EntityInterface {
        $item = new Input($data);
        return new Relation(
            id: $item->get('id', 0, Input\Filter::INT),
            peoples_id_from: $item->get('peoples_id_from', null, Input\Filter::INT),
            peoples_id_to: $item->get('peoples_id_to', null, Input\Filter::INT),
            type: $item->get('type', 'unknown', Input\Filter::STR),
            trees_id: $item->get('trees_id', null, Input\Filter::INT)
        );
    }
}
