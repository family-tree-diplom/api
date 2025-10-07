<?php

namespace OpenCCK\Infrastructure\Storage;

use Amp\Redis\Command\Option\SetOptions;
use Amp\Redis\RedisClient;
use Amp\Redis\RedisConfig;
use Amp\Redis\RedisException;
use function Amp\Redis\createRedisClient;
use function OpenCCK\getEnv;

final class RedisCacheStorage implements CacheStorageInterface {
    private static RedisCacheStorage $_instance;
    private RedisClient $client;

    /**
     * @param int $ttl
     * @throws RedisException
     */
    private function __construct(private readonly int $ttl = 60) {
        [$host, $port, $password, $db] = [
            getEnv('CACHE_REDIS_HOST'),
            getEnv('CACHE_REDIS_PORT') ?? '6379',
            getEnv('CACHE_REDIS_PASSWORD') ?? '',
            getEnv('CACHE_REDIS_DB') ?? '1',
        ];

        $this->client = createRedisClient(
            RedisConfig::fromUri('redis://' . $host . ':' . $port)
                ->withPassword($password)
                ->withDatabase($db)
        );
    }

    /**
     * @return RedisCacheStorage
     */
    public static function getInstance(): RedisCacheStorage {
        return self::$_instance ??= new self();
    }

    /**
     * @return RedisClient
     */
    public function getClient(): RedisClient {
        return $this->client;
    }

    /**
     * @param string $key
     * @return ?string
     */
    public function get(string $key): ?string {
        return $this->client->get($key);
    }

    /**
     * @param string $key
     * @param string $value
     * @param ?SetOptions $options
     * @return bool
     */
    public function set(string $key, string $value, ?SetOptions $options = null): bool {
        $options = $options ?? (new SetOptions())->withTtl($this->ttl);
        return $this->client->set($key, $value, $options);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getData(string $key): mixed {
        return json_decode($this->get($key));
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param int $ttl
     * @return bool
     */
    public function setData(string $key, mixed $data, int $ttl = 60): bool {
        return $this->set($key, json_encode($data), (new SetOptions())->withTtl($ttl));
    }
}
