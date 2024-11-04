// test/run-tests.php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'utils/TestHelper.php';
require_once 'api/endpoints/ShopTest.php';
require_once 'api/endpoints/ItemTest.php';

echo "Starting API Tests...\n";
echo "==========================================\n";

// Run Shop Tests
echo "\nRunning Shop Tests...\n";
$shopTest = new ShopTest();
$shopTest->runTests();

// Run Item Tests
echo "\nRunning Item Tests...\n";
$itemTest = new ItemTest();
$itemTest->runTests();

echo "\nAll tests completed!\n";