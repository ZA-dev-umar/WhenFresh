<?php
// test/utils/TestHelper.php
class TestHelper {
    private $base_url;

    public function __construct($base_url = 'http://localhost/whenfresh') {
        $this->base_url = $base_url;
    }

    public function makeRequest($method, $endpoint, $data = null, $headers = []) {
        $curl = curl_init();
        $url = $this->base_url . $endpoint;
        
        echo "\nRequest Details:\n";
        echo "URL: $url\n";
        echo "Method: $method\n";
        if ($data) {
            echo "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }

        $defaultHeaders = ['Content-Type: application/json'];
        $headers = array_merge($defaultHeaders, $headers);

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers
        ];

        if ($data && ($method === 'POST' || $method === 'PUT')) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            echo "Curl Error: " . curl_error($curl) . "\n";
        }
        
        curl_close($curl);

        return [
            'status' => $httpCode,
            'response' => json_decode($response, true),
            'raw_response' => $response
        ];
    }

    public function printResult($testName, $result) {
        echo "\n=== Test: $testName ===\n";
        echo "Status Code: " . $result['status'] . "\n";
        if ($result['response']) {
            echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Raw Response: " . $result['raw_response'] . "\n";
        }
        echo "===============================\n";
    }
}