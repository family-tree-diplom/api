<?php

namespace OpenCCK\Infrastructure\Model;

use Doctrine\Common\Collections\ArrayCollection;
use OpenCCK\Infrastructure\Model\DB\Query;

use Doctrine\DBAL\Exception;
use Throwable;


abstract class DefaultModel extends AbstractModel {
    abstract protected function getBaseQuery(string|array $select): Query;
    abstract protected function getSearchPredicate(): string|array;

    /**
     * @param string|array|null $select
     * @param string|array $where
     * @param string $search
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @return ArrayCollection
     * @throws Throwable
     */
    public function getList(
        string|array|null $select = null,
        string|array $where = [],
        string $search = '',
        array $order = [],
        int $limit = 0,
        int $offset = 0
    ): ArrayCollection {
        $query = ($select ? $this->getBaseQuery($select) : $this->getBaseQuery())
            ->order($order)
            ->where($where);
        if ($search) {
            $query->where($this->getSearchPredicate($search));
        }
        if ($limit) {
            $query->limit($limit, $offset);
        }
        //App::getLogger()->notice($query->getQuery()->getSQL());

        return $query->fetch();
    }

    /**
     * @param string|array $where
     * @param string $search
     * @return int
     * @throws Exception
     * @throws Throwable
     */
    public function getListCount(string|array $where = [], string $search = ''): int {
        return $this->getBaseQuery()
            ->select('count(*) as count')
            ->where($where)
            ->where($this->getSearchPredicate($search))
            ->fetch()
            ->first()['count'];
    }
}
