<?php
// middleware/AuthMiddleware.php
require_once __DIR__ . "/../utils/JWT.php";
require_once __DIR__ . "/../utils/Response.php";

class AuthMiddleware {
    public static function authenticate() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            Response::json(401, ['error' => 'No authorization token provided']);
            exit;
        }

        $authHeader = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $authHeader);

        $decoded = JWT::decode($token);
        
        if (!$decoded || !isset($decoded['user_id'])) {
            Response::json(401, ['error' => 'Invalid token']);
            exit;
        }

        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            Response::json(401, ['error' => 'Token expired']);
            exit;
        }

        return $decoded;
    }
}