<?php

namespace OpenCCK\Infrastructure\Model;

use Doctrine\DBAL\Exception;
use OpenCCK\Domain\Repository\ConfigRepository;
use OpenCCK\Infrastructure\Model\DB\Query;

final class ConfigModel extends DefaultModel {
    /**
     * @throws Exception
     */
    protected function getBaseQuery(string|array $select = 'a.*'): Query {
        return $this->getQuery()
            ->select($select)
            ->from(ConfigRepository::TABLE, 'a');
    }

    protected function getSearchPredicate(string $search = ''): string|array {
        return [];
    }
}
