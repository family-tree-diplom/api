<?php

namespace OpenCCK\Domain\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\Annotated\Annotation\Table\Index;

use OpenCCK\Domain\Repository\ConfigRepository;

#[Entity(repository: ConfigRepository::class, table: ConfigRepository::TABLE)]
final class Config implements EntityInterface {
    public function __construct(
        #[Column(type: 'string(255)', primary: true)] public string $id,
        #[Column(type: 'longText')] public string $data
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
