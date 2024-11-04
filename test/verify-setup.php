<?php
// verify-setup.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Verifying setup...\n\n";

// Check required files
$required_files = [
    'controllers/ShopController.php' => 'ShopController',
    'controllers/ItemController.php' => 'ItemController',
    'models/Shop.php' => 'Shop',
    'models/Item.php' => 'Item',
    'utils/Response.php' => 'Response',
    'routes/Router.php' => 'Router'
];

foreach ($required_files as $file => $class) {
    echo "Checking $file... ";
    if (file_exists($file)) {
        require_once $file;
        if (class_exists($class)) {
            echo "✓ File exists and class is defined\n";
        } else {
            echo "✗ File exists but class $class is not defined\n";
        }
    } else {
        echo "✗ File not found\n";
    }
}

// Check database connection
echo "\nTesting database connection...\n";
try {
    require_once 'config/Database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\nVerification complete!\n";