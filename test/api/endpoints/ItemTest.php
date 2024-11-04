<?php
// test/api/endpoints/ItemTest.php
class ItemTest {
    private $helper;

    public function __construct() {
        $this->helper = new TestHelper();
    }

    public function runTests() {
        $this->testCreateItem();
        $this->testGetShopItems();
    }

    private function testCreateItem() {
        $data = [
            'shop_id' => 1,
            'item_name' => 'Test Croissant ' . time(),
            'prep_start_time' => '08:00:00',
            'prep_duration' => 45
        ];

        $result = $this->helper->makeRequest('POST', '/api/items', $data);
        $this->helper->printResult('Create Item', $result);
    }

    private function testGetShopItems() {
        $result = $this->helper->makeRequest('GET', '/api/shops/1/items');
        $this->helper->printResult('Get Shop Items', $result);
    }
}