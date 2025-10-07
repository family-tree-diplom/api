<?php

namespace OpenCCK\Infrastructure\API;

use Exception;
use OpenCCK\Message;
use function base64_decode;
use function base64_encode;
use function explode;
use function hash_hmac;
use function json_decode;
use function json_encode;
use function str_replace;
use function OpenCCK\getEnv;

final class JWT {
    /**
     * @param string $data
     * @return string
     */
    private static function base64url(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * @param array $payload
     * @param ?string $secret
     * @return string
     */
    public static function getToken(array $payload, string $secret = null): string {
        $header = self::base64url(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadData = self::base64url(json_encode($payload));
        $signature = self::base64url(
            hash_hmac('sha256', $header . '.' . $payloadData, $secret ?: getEnv('SYS_SECRET'), true)
        );

        return "{$header}.{$payloadData}.{$signature}";
    }

    private static function explodeToken(string $token): array {
        $parts = explode('.', $token);
        return [
            json_decode(self::base64url_decode($parts[0] ?? '')),
            json_decode(self::base64url_decode($parts[1] ?? '')),
            $parts[2] ?? '',
        ];
    }

    private static function base64url_decode(string $data): string {
        return base64_decode(str_replace(['-', '_', ''], ['+', '/', '='], $data));
    }

    public static function checkToken(string $token, string $secret = null): bool {
        [$header, $payload, $signature] = explode('.', $token);
        return $signature ==
            self::base64url(hash_hmac('sha256', $header . '.' . $payload, $secret ?: getEnv('SYS_SECRET'), true));
    }

    /**
     * @throws Exception
     */
    public static function getPayload(string $token, string $secret = null): Input {
        [, $payload] = self::explodeToken($token);
        $payload = (array) $payload;

        if (!self::checkToken($token, $secret)) {
            throw new Exception(Message::TOKEN_ERROR_SIGNATURE . ': ' . $token);
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception(Message::TOKEN_ERROR_EXPIRED . ': ' . $payload['exp'] . '<' . time());
        }

        return new Input($payload);
    }
}
