<?php

namespace OpenCCK\Domain\Repository;

use OpenCCK\Domain\Entity\EntityInterface;
use React\Promise\PromiseInterface;

interface RepositoryInterface {
    /**
     * @param array $data
     * @return EntityInterface
     */
    public function create(array $data = []): EntityInterface;

    /**
     * @param EntityInterface $entity
     * @return ?int
     */
    public function save(EntityInterface $entity): ?int;

    /**
     * @param string|array $predicate
     * @return ?EntityInterface
     */
    public function get(string|array $predicate): ?EntityInterface;

    /**
     * @param string|array $predicate
     * @return EntityInterface[]
     */
    public function read(string|array $predicate): array;

    /**
     * @param EntityInterface $entity
     * @return ?int
     */
    public function update(EntityInterface $entity): ?int;

    /**
     * @param EntityInterface $entity
     * @return ?int
     */
    public function delete(EntityInterface $entity): ?int;

    /**
     * @param EntityInterface $entity
     * @param array $data
     * @return bool
     */
    public function patch(EntityInterface $entity, array $data): bool;
}
