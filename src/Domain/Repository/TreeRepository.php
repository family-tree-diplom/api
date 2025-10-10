<?php

namespace OpenCCK\Domain\Repository;

use Doctrine\DBAL\Exception;

final class TreeRepository extends AbstractRepository {
    public const TABLE = 'trees';


    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function getTree() {
        $response = $this->get([]);
        return json_decode($response->data);
    }
}
