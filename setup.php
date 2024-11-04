<?php
// setup.php

function createDirectory($path) {
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
        echo "Created directory: $path\n";
    } else {
        echo "Directory exists: $path\n";
    }
}

function createFile($path, $content) {
    if (!file_exists($path)) {
        file_put_contents($path, $content);
        echo "Created file: $path\n";
    } else {
        echo "File exists: $path\n";
    }
}

// Base directory
$baseDir = __DIR__;

// Create directories
$directories = [
    $baseDir . '/config',
    $baseDir . '/controllers',
    $baseDir . '/models',
    $baseDir . '/routes',
    $baseDir . '/utils',
    $baseDir . '/test',
    $baseDir . '/test/api/endpoints',
    $baseDir . '/test/utils'
];

foreach ($directories as $dir) {
    createDirectory($dir);
}

// Create .htaccess
$htaccessContent = "RewriteEngine On
RewriteBase /whenfresh/
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]";

createFile($baseDir . '/.htaccess', $htaccessContent);

// Create base index.php
$indexContent = '<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . "/config/Database.php";
require_once __DIR__ . "/routes/Router.php";

$router = new Router();
$router->handleRequest();';

createFile($baseDir . '/index.php', $indexContent);

echo "\nSetup completed!\n";
echo "Please remember to copy your controller, model, and other files to their respective directories.\n";
?>