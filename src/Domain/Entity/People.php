<?php

namespace OpenCCK\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\Annotated\Annotation\Table\Index;

use OpenCCK\Domain\Repository\PeopleRepository;

#[Entity(repository: PeopleRepository::class, table: PeopleRepository::TABLE)]
final class People implements EntityInterface {
    public function __construct(
        #[Column(type: 'primary')] public int $id,
        #[Column(type: 'string(255)')] public string $name,
        #[Column(type: 'string(255)')] public string $surname,
        #[Column(type: 'string(255)')] public string $birth_day,
        #[Column(type: 'string(255)')] public string $death,
        #[Column(type: 'enum(man, woman, unknown)')] public string $gender = 'unknown',
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
