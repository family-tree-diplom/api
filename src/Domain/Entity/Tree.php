<?php

namespace OpenCCK\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\Annotated\Annotation\Table\Index;

use OpenCCK\Domain\Repository\TreeRepository;

#[Entity(repository: TreeRepository::class, table: TreeRepository::TABLE)]
final class Tree implements EntityInterface {
    public function __construct(
        #[Column(type: 'primary')] public int $id,
        #[Column(type: 'string(255)')] public string $title,
        #[Column(type: 'string(255)')] public string $slug,
        #[
            BelongsTo(target: User::class, innerKey: 'users_id', cascade: true, nullable: true)
        ]
        public ?int $users_id = null
    ) {
    }

    public function getPrimaryKey(): array {
        return ['id' => $this->id];
    }

    public function toArray(): array {
        return (array) $this;
    }

    public function toObject(): object {
        return (object) $this;
    }
}
