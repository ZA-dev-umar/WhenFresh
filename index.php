<?php

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable HTML error output

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Custom error handler to return JSON
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'error' => 'Internal Server Error',
        'message' => $errstr,
        'code' => $errno
    ];
    
    http_response_code(500);
    echo json_encode($response);
    exit();
}

set_error_handler('handleError');

// Custom exception handler
function handleException($e) {
    $response = [
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ];
    
    http_response_code(500);
    echo json_encode($response);
    exit();
}

set_exception_handler('handleException');

// Load required files
require_once "config/Database.php";
require_once "routes/Router.php";

// Initialize and handle the request
try {
    $router = new Router();
    $router->handleRequest();
} catch (Exception $e) {
    handleException($e);
}