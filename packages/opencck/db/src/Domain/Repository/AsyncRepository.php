<?php

namespace OpenCCK\Domain\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Drift\DBAL\Result;
use OpenCCK\Domain\Entity\EntityInterface;
use React\Promise\PromiseInterface;
use Throwable;

/**
 * Декоратор для репозитория
 */
final class AsyncRepository implements AsyncRepositoryInterface {
    public function __construct(private readonly RepositoryInterface $repository) {
    }

    /**
     * @param array $primaryKey
     * @return PromiseInterface<?EntityInterface>
     * @throws Exception
     * @throws Throwable
     */
    public function exists(array $primaryKey): PromiseInterface {
        return $this->repository
            ->getQuery()
            ->select('*')
            ->from($this->repository::TABLE)
            ->where($primaryKey)
            ->limit(1)
            ->asyncFetch()
            ->then(fn(ArrayCollection $result) => !!$result->count());
    }

    /**
     * @return PromiseInterface<EntityInterface[]>
     * @throws Exception
     * @throws Throwable
     */
    public function getAll(): PromiseInterface {
        return $this->repository
            ->getQuery()
            ->select('*')
            ->from($this->repository::TABLE)
            ->asyncFetch()
            ->then(fn(ArrayCollection $result) => $result->map(fn($item) => $this->create($item))->toArray());
    }

    /**
     * @param array $data
     * @return EntityInterface
     * @throws Exception
     */
    public function create(array $data = []): EntityInterface {
        return $this->repository->getFactory()->create($data);
    }

    /**
     * @param string|array $predicate
     * @return PromiseInterface<?EntityInterface>
     * @throws Exception
     * @throws Throwable
     */
    public function get(string|array $predicate): PromiseInterface {
        return $this->repository
            ->getQuery()
            ->select('*')
            ->from($this->repository::TABLE)
            ->where($predicate)
            ->asyncFetch()
            ->then(fn(ArrayCollection $result) => $result->count() ? $this->create($result->first()) : null);
    }

    /**
     * @param string|array $predicate
     * @return PromiseInterface<EntityInterface[]>
     * @throws Exception
     * @throws Throwable
     */
    public function read(string|array $predicate): PromiseInterface {
        return $this->repository
            ->getQuery()
            ->select('*')
            ->from($this->repository::TABLE)
            ->where($predicate)
            ->asyncFetch()
            ->then(fn(ArrayCollection $result) => $result->map(fn($item) => $this->create($item))->toArray());
    }

    /**
     * @param EntityInterface $entity
     * @return PromiseInterface<int|null>
     * @throws Exception
     */
    public function save(EntityInterface $entity): PromiseInterface {
        return $this->repository
            ->getQuery()
            ->insert($this->repository::TABLE)
            ->values($entity->toArray())
            ->asyncExecute()
            ->then(fn(Result $result) => $result->getLastInsertedId());
    }

    /**
     * @param EntityInterface $entity
     * @return PromiseInterface<?int>
     * @throws Exception
     */
    public function update(EntityInterface $entity): PromiseInterface {
        return $this->repository
            ->getQuery()
            ->update($this->repository::TABLE)
            ->set($entity->toArray())
            ->where($entity->getPrimaryKey())
            ->asyncExecute()
            ->then(fn(Result $result) => $result->getAffectedRows());
    }

    /**
     * @param EntityInterface $entity
     * @return PromiseInterface<?int>
     * @throws Exception
     */
    public function delete(EntityInterface $entity): PromiseInterface {
        return $this->repository
            ->getQuery()
            ->delete($this->repository::TABLE)
            ->where($entity->getPrimaryKey())
            ->asyncExecute()
            ->then(fn(Result $result) => $result->getAffectedRows());
    }

    /**
     * @param EntityInterface $entity
     * @param array $data
     * @return PromiseInterface<?int>
     * @throws Exception
     */
    public function patch(EntityInterface $entity, array $data): PromiseInterface {
        return $this->repository
            ->getQuery()
            ->update($this->repository::TABLE)
            ->set($data)
            ->where($entity->getPrimaryKey())
            ->asyncExecute()
            ->then(fn(Result $result) => $result->getAffectedRows());
    }
}
