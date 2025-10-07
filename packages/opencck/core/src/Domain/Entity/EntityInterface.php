<?php

namespace OpenCCK\Domain\Entity;

interface EntityInterface {
    function getPrimaryKey(): array;
    function toArray(): array;
    function toObject(): object;
}
