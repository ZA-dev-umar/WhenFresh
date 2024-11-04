<?php
// test/api/endpoints/ShopTest.php
class ShopTest {
    private $helper;
    private $auth_token;

    public function __construct() {
        $this->helper = new TestHelper();
        $this->auth_token = $this->getAuthToken();
    }

    private function getAuthToken() {
        // First try to register
        $registerData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'Test Owner'
        ];

        $this->helper->makeRequest('POST', '/api/auth/register', $registerData);

        // Then login to get token
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $result = $this->helper->makeRequest('POST', '/api/auth/login', $loginData);
        return $result['response']['token'] ?? null;
    }

    public function runTests() {
        $this->testCreateShop();
        $this->testGetShops();
        $this->testGetNearbyShops();
    }

    private function testCreateShop() {
        $data = [
            'name' => 'Test Bakery ' . time(),
            'location' => '40.7128,-74.0060',
            'address' => '123 Test Street',
            'contact_info' => '123-456-7890'
        ];

        $headers = ['Authorization: Bearer ' . $this->auth_token];
        $result = $this->helper->makeRequest('POST', '/api/shops', $data, $headers);
        $this->helper->printResult('Create Shop', $result);
    }

    private function testGetShops() {
        $result = $this->helper->makeRequest('GET', '/api/shops');
        $this->helper->printResult('Get All Shops', $result);
    }

    private function testGetNearbyShops() {
        $result = $this->helper->makeRequest(
            'GET', 
            '/api/shops/nearby?lat=40.7128&lng=-74.0060&radius=5'
        );
        $this->helper->printResult('Get Nearby Shops', $result);
    }
}