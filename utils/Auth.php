<?php
class Auth {
    private static $secret_key;
    
    public static function init() {
        $config = require '../config/Config.php';
        self::$secret_key = $config['jwt']['secret'];
    }
    
    public static function generateToken($user_id) {
        $payload = [
            'user_id' => $user_id,
            'exp' => time() + 3600
        ];
        
        return JWT::encode($payload, self::$secret_key);
    }
    
    public static function validateToken($token) {
        try {
            $decoded = JWT::decode($token, self::$secret_key, ['HS256']);
            return $decoded->user_id;
        } catch (Exception $e) {
            return false;
        }
    }
}