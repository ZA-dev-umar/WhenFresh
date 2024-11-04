<?php
// utils/Response.php
class Response {
    public static function json($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    public static function error($statusCode, $message) {
        self::json($statusCode, [
            'error' => true,
            'message' => $message
        ]);
    }

    public static function success($statusCode, $data) {
        self::json($statusCode, [
            'error' => false,
            'data' => $data
        ]);
    }
}