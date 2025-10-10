<?php

namespace OpenCCK\Domain\Repository;

use Doctrine\DBAL\Exception;

final class PeopleToTreeRepository extends AbstractRepository {
    public const TABLE = 'peoples_to_trees';


    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function getPeopleToTree() {
        $response = $this->get([]);
        return json_decode($response->data);
    }
}
