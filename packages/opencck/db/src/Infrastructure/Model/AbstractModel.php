<?php

namespace OpenCCK\Infrastructure\Model;

use Doctrine\DBAL\Exception;
use OpenCCK\Infrastructure\Model\DB\Query;

abstract class AbstractModel {
    protected DB $db;

    public function __construct() {
        $this->db = DB::getInstance();
    }

    /**
     * @return Query
     * @throws Exception
     */
    public function getQuery(): Query {
        return new Query($this->db);
    }
}
