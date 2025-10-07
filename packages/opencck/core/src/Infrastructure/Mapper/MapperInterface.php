<?php

namespace OpenCCK\Infrastructure\Mapper;

use OpenCCK\App\Controller\AbstractController;

interface MapperInterface {
    /**
     * @param AbstractController $controller
     * @param string $method
     * @return string
     */
    public static function map(AbstractController $controller, string $method): string;
    public static function getExecutions(AbstractController $controller, mixed $data, string $defaultMethod): array;
    public static function result(mixed $data): array;
    public static function error(mixed $data): array;
    public static function mapBody(mixed $data): string;
    public static function getDefaultHeaders(): array;
}
