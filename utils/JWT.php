<?php
// utils/JWT.php
class JWT {
    private static $secret_key = "your-secret-key"; // Change this to a secure key
    private static $algorithm = 'HS256';

    public static function encode($payload) {
        // Header
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ]);
        $header = self::base64UrlEncode($header);

        // Payload
        $payload = json_encode($payload);
        $payload = self::base64UrlEncode($payload);

        // Signature
        $signature = hash_hmac('sha256', 
            $header . "." . $payload, 
            self::$secret_key, 
            true
        );
        $signature = self::base64UrlEncode($signature);

        return $header . "." . $payload . "." . $signature;
    }

    public static function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return null;
        }

        list($header, $payload, $signature) = $parts;

        $valid = hash_hmac('sha256', 
            $header . "." . $payload, 
            self::$secret_key, 
            true
        );
        $valid = self::base64UrlEncode($valid);

        if ($signature !== $valid) {
            return null;
        }

        return json_decode(self::base64UrlDecode($payload), true);
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . 
            str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}