<?php

namespace OpenCCK\Domain\Repository;

use Doctrine\DBAL\Exception;

final class PeopleRepository extends AbstractRepository {
    public const TABLE = 'peoples';


    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function getPeople() {
        $response = $this->get([]);
        return json_decode($response->data);
    }
}
