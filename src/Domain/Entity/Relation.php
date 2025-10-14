<?php

namespace OpenCCK\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\Annotated\Annotation\Table\Index;

use OpenCCK\Domain\Repository\RelationRepository;

#[Entity(repository: RelationRepository::class, table: RelationRepository::TABLE)]
#[Index(columns: ['peoples_id_from', 'peoples_id_to'], unique: true)]
final class Relation implements EntityInterface {
    public function __construct(
        #[Column(type: 'primary')] public int $id,
        #[
            BelongsTo(target: People::class, innerKey: 'peoples_id_from', cascade: true, nullable: true)
        ]
        public ?int $peoples_id_from = null,
        #[
            BelongsTo(target: People::class, innerKey: 'peoples_id_to', cascade: true, nullable: true)
        ]
        public ?int $peoples_id_to = null,
        #[Column(type: 'enum(parent, marriage, unknown)')] public string $type = 'unknown',
        #[
            BelongsTo(target: Tree::class, innerKey: 'trees_id', cascade: true, nullable: true)
        ]
        public ?int $trees_id = null
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
