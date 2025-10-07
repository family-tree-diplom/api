<?php

namespace OpenCCK\Domain\Repository;

use Doctrine\DBAL\Exception;

final class ConfigRepository extends AbstractRepository {
    public const TABLE = 'config';


    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function getConfig() {
        $response = $this->get([]);
        return json_decode($response->data);
    }
}
