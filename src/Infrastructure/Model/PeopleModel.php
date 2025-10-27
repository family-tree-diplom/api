<?php

namespace OpenCCK\Infrastructure\Model;

use Doctrine\DBAL\Exception;
use OpenCCK\Domain\Entity\PeopleToTree;
use OpenCCK\Domain\Repository\PeopleRepository;
use OpenCCK\Domain\Repository\PeopleToTreeRepository;
use OpenCCK\Domain\Repository\RelationRepository;
use OpenCCK\Infrastructure\Model\DB\Query;

final class PeopleModel extends DefaultModel {
    /**
     * @throws Exception
     */
    protected function getBaseQuery(string|array $select = 'a.*'): Query {
        return $this->getQuery()
            ->select($select)
            ->from(PeopleRepository::TABLE, 'a');
    }

    protected function getSearchPredicate(string $search = ''): string|array {
        return [];
    }

    public function getPeopleWithRelation(string|array $select = 'a.*, r.peoples_id_to, r.type') {
        $query =  $this->getQuery()
            ->select($select)
            ->from(PeopleRepository::TABLE, 'a')
            ->leftJoin('a',RelationRepository::TABLE,  'r', 'a.id=r.peoples_id_from');
        return $query->fetch()->toArray();
    }

    public function getPeoplesByTree(int $trees_id, string|array $select = 'a.*'){
        $query = $this->getQuery()
            ->select($select)
            ->from(PeopleRepository::TABLE, 'a')
            ->leftJoin('a',PeopleToTreeRepository::TABLE,  'r', 'r.trees_id='.$trees_id)
            ->where('a.id=r.peoples_id');
        return $query->fetch()->toArray();
    }
}
