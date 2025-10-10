<?php

namespace OpenCCK\Infrastructure\Model;

use Doctrine\DBAL\Exception;
use OpenCCK\Domain\Repository\TreeRepository;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\Model\DB\Query;

final class TreeModel extends DefaultModel {
    /**
     * @throws Exception
     */
    protected function getBaseQuery(string|array $select = 'a.*, u.username as username'): Query {
        return $this->getQuery()
            ->select($select)
            ->from(TreeRepository::TABLE, 'a')
            ->leftJoin('a', UserRepository::TABLE, 'u', 'u.id = a.users_id');
    }

    protected function getSearchPredicate(string $search = ''): string|array {
        return [];
    }
}
