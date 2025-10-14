<?php

namespace OpenCCK\Domain\Repository;

use Doctrine\DBAL\Exception;

final class RelationRepository extends AbstractRepository {
    public const TABLE = 'relations';


    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function getPeople() {
        $response = $this->get([]);
        return json_decode($response->data);
    }
}
