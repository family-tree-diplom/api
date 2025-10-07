<?php

namespace OpenCCK;

use function html_entity_decode;
use function strip_tags;
use function strlen;
use function strtr;
use function substr;

/**
 * @param int|string $val
 * @return int
 */
function parseBytes(int|string $val): int {
    $val = trim($val);

    $units = ['g' => 1_073_741_824, 'm' => 1_048_576, 'k' => 1024];
    $unit = strtolower($val[strlen($val) - 1]);

    return intval($val) * $units[$unit];
}

/**
 * @param string $urlQuery
 * @return array
 */
function parseURLQuery(string $urlQuery): array {
    if (!$urlQuery) {
        return [];
    }
    $queryArray = [];
    $queryPairs = explode('&', $urlQuery);
    foreach ($queryPairs as $pair) {
        $pairArray = explode('=', $pair);
        $queryArray[$pairArray[0]] = $pairArray[1];
    }
    return $queryArray;
}

/**
 * Debug function
 * @param mixed $mixed
 * @param bool $exit
 * @return void
 */
function dbg(mixed $mixed, bool $exit = true): void {
    if (php_sapi_name() == 'cli') {
        echo json_encode($mixed, JSON_PRETTY_PRINT);
    } else {
        echo '<pre>' . print_r($mixed, true) . '</pre>';
    }
    if ($exit) {
        exit();
    }
}

/**
 * Checking that the string starts with
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function startsWith(string $haystack, string $needle): bool {
    return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}

/**
 * Checking that the string ends with
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function endsWith(string $haystack, string $needle): bool {
    return substr_compare($haystack, $needle, -1 * strlen($needle)) === 0;
}

/**
 * @param int $length
 * @return string
 */
function genPassword(int $length = 6): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * @param string $key
 * @return ?string
 */
function getEnv(string $key): ?string {
    if (!isset($_ENV[$key])) {
        return null;
    }
    if (preg_match('/^"(.*)"$/i', $_ENV[$key], $matches)) {
        return $matches[1];
    }
    return $_ENV[$key];
}

function stripTags(
    string $string,
    array $allowed_tags = ['<b>', '<a>', '<strike>', '<del>', '<i>', '<s>', '<code>', '<pre>']
): string {
    $string = strtr($string, ['</p>' => '<br>', '<strong>' => '<b>', '</strong>' => '</b>']);
    $string = strtr($string, ['<br>' => "\n", '<br/>' => "\n", '<br />' => "\n"]);
    $string = rtrim(strip_tags($string, $allowed_tags), "\n");
    $tail = '&nbsp;';
    if (endsWith($string, $tail)) {
        $string = rtrim(substr($string, 0, strlen($string) - strlen($tail)), "\n");
    }
    return html_entity_decode($string);
}
