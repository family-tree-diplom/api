<?php

namespace OpenCCK\Domain\Repository;

use Amp\Http\HttpStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Drift\DBAL\Result;
use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Domain\Factory\FactoryInterface;
use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\EventDispatcher\EventDispatcher;
use OpenCCK\Infrastructure\Model\DB;
use OpenCCK\Infrastructure\Model\DB\Query;
use React\Promise\PromiseInterface;

use Throwable;
use function React\Async\await;

abstract class AbstractRepository implements RepositoryInterface {
    public const TABLE = '';
    protected DB $db;
    protected EventDispatcher $dispatcher;

    public function __construct() {
        $this->db = DB::getInstance();
        $this->dispatcher = App::getInstance()->getDispatcher();
    }

    /**
     * @return Query
     * @throws Exception
     */
    public function getQuery(): Query {
        return new Query($this->db);
    }

    /**
     * @return FactoryInterface
     * @throws Exception
     */
    public function getFactory(): FactoryInterface {
        $className = explode('\\', substr($this::class, 0, strlen($this::class) - 10) . 'Factory');
        $className = '\\OpenCCK\\Domain\\Factory\\' . array_pop($className);
        if (!class_exists($className)) {
            throw new Exception($className . ' not found', HttpStatus::NOT_FOUND);
        }
        return new $className();
    }

    /**
     * @param string|array $predicate
     * @return bool
     * @throws Exception
     * @throws Throwable
     */
    public function exists(string|array $predicate): bool {
        return !!$this->getQuery()
            ->select('*')
            ->from($this::TABLE)
            ->where($predicate)
            ->limit(1)
            ->fetch()
            ->count();
    }

    /**
     * @return EntityInterface[]
     * @throws Exception
     * @throws Throwable
     */
    public function getAll(): array {
        return $this->getQuery()
            ->select('*')
            ->from($this::TABLE)
            ->fetch()
            ->map(fn($item) => $this->create($item))
            ->toArray();
    }

    /**
     * @param array $data
     * @return EntityInterface
     * @throws Exception
     */
    public function create(array $data = []): EntityInterface {
        return $this->getFactory()->create($data);
    }

    /**
     * @param string|array $predicate
     * @return ?EntityInterface
     * @throws Exception
     * @throws Throwable
     */
    public function get(string|array $predicate): ?EntityInterface {
        $result = $this->getQuery()
            ->select('*')
            ->from($this::TABLE)
            ->where($predicate)
            ->fetch();
        return $result->count() ? $this->create($result->first()) : null;
    }

    /**
     * @param string|array $predicate
     * @return EntityInterface[]
     * @throws Exception
     * @throws Throwable
     */
    public function read(string|array $predicate, int $limit = null, int $offset = 0, array $order = []): array {
        $query = $this->getQuery()
            ->select('*')
            ->from($this::TABLE)
            ->where($predicate);

        if ($limit) {
            $query->limit($limit, $offset);
        }

        if ($order) {
            $query->order($order);
        }

        return $query
            ->fetch()
            ->map(fn($item) => $this->create($item))
            ->toArray();
    }

    /**
     * @param EntityInterface $entity
     * @param bool $isNew
     * @return ?int
     * @throws Exception
     * @throws Throwable
     */
    public function save(EntityInterface $entity, bool $isNew = false): ?int {
        return $this->getQuery()
            ->insert($this::TABLE)
            ->values($isNew ? $entity->toArray() : array_diff_key($entity->toArray(), $entity->getPrimaryKey()))
            ->execute()
            ->getLastInsertedId();
    }

    /**
     * @param EntityInterface $entity
     * @return ?int
     * @throws Exception
     * @throws Throwable
     */
    public function update(EntityInterface $entity): ?int {
        return $this->getQuery()
            ->update($this::TABLE)
            ->set($entity->toArray())
            ->where($entity->getPrimaryKey())
            ->execute()
            ->getAffectedRows();
    }

    /**
     * @param EntityInterface $entity
     * @return ?int
     * @throws Exception
     * @throws Throwable
     */
    public function delete(EntityInterface $entity): ?int {
        return $this->getQuery()
            ->delete($this::TABLE)
            ->where($entity->getPrimaryKey())
            ->execute()
            ->getAffectedRows();
    }

    /**
     * @param EntityInterface $entity
     * @param array $data
     * @return bool
     * @throws Exception
     * @throws Throwable
     */
    public function patch(EntityInterface $entity, array $data): bool {
        return !!$this->getQuery()
            ->update($this::TABLE)
            ->set($data)
            ->where($entity->getPrimaryKey())
            ->execute()
            ->getAffectedRows();
    }
}
