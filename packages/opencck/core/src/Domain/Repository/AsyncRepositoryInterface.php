<?php

namespace OpenCCK\Domain\Repository;

use OpenCCK\Domain\Entity\EntityInterface;
use React\Promise\PromiseInterface;

interface AsyncRepositoryInterface {
    /**
     * @param array $data
     * @return EntityInterface
     */
    public function create(array $data = []): EntityInterface;

    /**
     * @param EntityInterface $entity
     * @return PromiseInterface<?int>
     */
    public function save(EntityInterface $entity): PromiseInterface;

    /**
     * @param string|array $predicate
     * @return PromiseInterface<EntityInterface>
     */
    public function get(string|array $predicate): PromiseInterface;

    /**
     * @param string|array $predicate
     * @return PromiseInterface<EntityInterface[]>
     */
    public function read(string|array $predicate): PromiseInterface;

    /**
     * @param EntityInterface $entity
     * @return PromiseInterface<?int>
     */
    public function update(EntityInterface $entity): PromiseInterface;

    /**
     * @param EntityInterface $entity
     * @return PromiseInterface<bool>
     */
    public function delete(EntityInterface $entity): PromiseInterface;

    /**
     * @param EntityInterface $entity
     * @param array $data
     * @return PromiseInterface<?int>
     */
    public function patch(EntityInterface $entity, array $data): PromiseInterface;
}
