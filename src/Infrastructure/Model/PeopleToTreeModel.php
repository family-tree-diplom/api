<?php

namespace OpenCCK\Infrastructure\Model;

use Doctrine\DBAL\Exception;
use OpenCCK\Domain\Repository\PeopleToTreeRepository;
use OpenCCK\Domain\Repository\PeopleRepository;
use OpenCCK\Domain\Repository\TreeRepository;
use OpenCCK\Infrastructure\Model\DB\Query;

final class PeopleToTreeModel extends DefaultModel {
    /**
     * @throws Exception
     */
    protected function getBaseQuery(string|array $select = 'a.*, t.title as title, CONCAT(p.id, " " , p.surname, " ", p.name, " ", p.birth_day) as people'): Query {
        return $this->getQuery()
            ->select($select)
            ->from(PeopleToTreeRepository::TABLE, 'a')
            ->leftJoin('a', TreeRepository::TABLE, 't', 't.id = a.trees_id')
            ->leftJoin('a', PeopleRepository::TABLE, 'p', 'p.id = a.peoples_id');
    }

    protected function getSearchPredicate(string $search = ''): string|array {
        return [];
    }
}
