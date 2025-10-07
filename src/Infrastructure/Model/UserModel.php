<?php

namespace OpenCCK\Infrastructure\Model;

use Doctrine\DBAL\Exception;
use OpenCCK\Domain\Repository\AirlineRepository;
use OpenCCK\Domain\Repository\MealRepository;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\Model\DB\Query;

final class UserModel extends DefaultModel {
    /**
     * @throws Exception
     */
    protected function getBaseQuery(string|array $select = 'a.*'): Query {
        return $this->getQuery()
            ->select($select)
            ->from(UserRepository::TABLE, 'a');
    }

    protected function getSearchPredicate(string $search = ''): string|array {
        return $search
            ? implode(' OR ', [
                "a.name like '%" . addslashes($search) . "%'",
                "a.email like '%" . addslashes($search) . "%'",
                "a.username like '%" . addslashes($search) . "%'",
            ])
            : [];
    }
}
