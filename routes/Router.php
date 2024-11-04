<?php
// routes/Router.php
class Router {
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => []
    ];
    private $basePath;

    public function __construct() {
        $this->basePath = dirname(__DIR__);
        
        // Auth routes
    $this->routes['POST']['/api/auth/register'] = ['AuthController', 'register'];
    $this->routes['POST']['/api/auth/login'] = ['AuthController', 'login'];
    $this->routes['GET']['/api/auth/verify'] = ['AuthController', 'verifyToken'];

        // Shop routes
        $this->routes['GET']['/api/shops'] = ['ShopController', 'getAll'];
        $this->routes['POST']['/api/shops'] = ['ShopController', 'create'];
        $this->routes['GET']['/api/shops/nearby'] = ['ShopController', 'getNearby'];
        $this->routes['GET']['/api/shops/{id}'] = ['ShopController', 'getOne'];
        $this->routes['PUT']['/api/shops/{id}'] = ['ShopController', 'update'];
        $this->routes['DELETE']['/api/shops/{id}'] = ['ShopController', 'delete'];
        
        // Item routes
        $this->routes['POST']['/api/items'] = ['ItemController', 'create'];
        $this->routes['GET']['/api/items/{id}'] = ['ItemController', 'getOne'];
        $this->routes['PUT']['/api/items/{id}'] = ['ItemController', 'update'];
        $this->routes['DELETE']['/api/items/{id}'] = ['ItemController', 'delete'];
    }

    public function handleRequest() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $request_method = $_SERVER['REQUEST_METHOD'];

        // Debug information
        error_log("Request URI: " . $request_uri);
        error_log("Request Method: " . $request_method);

        // Handle preflight CORS requests
        if ($request_method === 'OPTIONS') {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");
            header("HTTP/1.1 200 OK");
            exit();
        }

        // Remove query string and base path
        $request_uri = parse_url($request_uri, PHP_URL_PATH);
        $base_path = '/whenfresh';
        $request_uri = str_replace($base_path, '', $request_uri);

        error_log("Processed URI: " . $request_uri);

        if (!isset($this->routes[$request_method])) {
            $this->sendResponse(405, ['error' => 'Method not allowed']);
            return;
        }

        foreach ($this->routes[$request_method] as $route => $handler) {
            $pattern = $this->convertRouteToRegex($route);
            if (preg_match($pattern, $request_uri, $matches)) {
                array_shift($matches); // Remove the full match
                return $this->executeHandler($handler, $matches);
            }
        }

        $this->sendResponse(404, ['error' => 'Route not found']);
    }

    private function convertRouteToRegex($route) {
        return '@^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route) . '$@D';
    }

    private function executeHandler($handler, $params) {
        [$controller_name, $method] = $handler;
        
        $controller_file = $this->basePath . "/controllers/{$controller_name}.php";
        
        if (!file_exists($controller_file)) {
            $this->sendResponse(500, ['error' => "Controller not found: {$controller_name}"]);
            return;
        }

        require_once $controller_file;
        
        if (!class_exists($controller_name)) {
            $this->sendResponse(500, ['error' => "Controller class not found: {$controller_name}"]);
            return;
        }

        $controller = new $controller_name();
        
        if (!method_exists($controller, $method)) {
            $this->sendResponse(500, ['error' => "Method not found: {$method}"]);
            return;
        }

        return call_user_func_array([$controller, $method], $params);
    }

    private function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}