<?php

namespace OpenCCK\Infrastructure\Model;

use Doctrine\DBAL\Exception;
use OpenCCK\Domain\Repository\PeopleRepository;
use OpenCCK\Domain\Repository\RelationRepository;
use OpenCCK\Domain\Repository\TreeRepository;
use OpenCCK\Infrastructure\Model\DB\Query;

final class RelationModel extends DefaultModel {
    /**
     * @throws Exception
     */
    protected function getBaseQuery(
        string|array $select = 'a.*, t.title as title, CONCAT(p.id, " " , p.surname, " ", p.name, " ", p.birth_day) as people_from, CONCAT(p2.id, " " , p2.surname, " ", p2.name, " ", p2.birth_day) as people_to'
    ): Query {
        return $this->getQuery()
            ->select($select)
            ->from(RelationRepository::TABLE, 'a')
            ->leftJoin('a', TreeRepository::TABLE, 't', 't.id = a.trees_id')
            ->leftJoin('a', PeopleRepository::TABLE, 'p', 'p.id = a.peoples_id_from')
            ->leftJoin('a', PeopleRepository::TABLE, 'p2', 'p2.id = a.peoples_id_to');
    }

    protected function getSearchPredicate(string $search = ''): string|array {
        return [];
    }
}
