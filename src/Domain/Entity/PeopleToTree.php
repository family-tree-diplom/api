<?php

namespace OpenCCK\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\Annotated\Annotation\Table\Index;

use OpenCCK\Domain\Repository\PeopleToTreeRepository;

#[Entity(repository: PeopleToTreeRepository::class, table: PeopleToTreeRepository::TABLE)]
final class PeopleToTree implements EntityInterface {
    public function __construct(
        #[Column(type: 'primary')] public int $id,
        #[BelongsTo(target: People::class, innerKey: 'peoples_id', cascade: true, nullable: true)] public ?int $peoples_id=null,
        #[BelongsTo(target: Tree::class, innerKey: 'trees_id', cascade: true, nullable: true)] public ?int $trees_id=null,
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
