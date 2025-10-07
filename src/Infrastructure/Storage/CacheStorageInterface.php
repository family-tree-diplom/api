<?php

namespace OpenCCK\Infrastructure\Storage;

interface CacheStorageInterface {
    public function getData(string $key): mixed;
    public function setData(string $key, mixed $data, int $ttl = 60): bool;
}
