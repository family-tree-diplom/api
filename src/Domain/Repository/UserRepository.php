<?php

namespace OpenCCK\Domain\Repository;

use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Domain\Entity\User;

use React\Promise\PromiseInterface;

final class UserRepository extends AbstractRepository {
    public const TABLE = 'users';

    public function update(User|EntityInterface $entity): ?int {
        $entity->setDateModify(new \DateTimeImmutable());
        return parent::update($entity);
    }
}
