<?php
// controllers/AuthController.php
require_once __DIR__ . "/../models/StoreOwner.php";
require_once __DIR__ . "/../utils/Response.php";

class AuthController {
    private $storeOwner;
    private $db;

    public function __construct() {
        try {
            $database = new Database();
            $this->db = $database->getConnection();
            $this->storeOwner = new StoreOwner($this->db);
        } catch (Exception $e) {
            Response::error(500, "Database connection failed: " . $e->getMessage());
        }
    }

    public function register() {
        try {
            $data = json_decode(file_get_contents("php://input"));

            if (!$this->validateRegisterData($data)) {
                Response::error(400, "Missing or invalid required fields");
                return;
            }

            // Check if email exists
            if ($this->storeOwner->findByEmail($data->email)) {
                Response::error(400, "Email already registered");
                return;
            }

            // Prepare user data
            $userData = [
                'name' => $data->name,
                'email' => $data->email,
                'password' => password_hash($data->password, PASSWORD_DEFAULT),
                'location' => $data->location,
                'address' => $data->address,
                'contact_info' => $data->contact_info
            ];

            $userId = $this->storeOwner->create($userData);

            if ($userId) {
                $token = $this->generateToken($userId);
                Response::success(201, [
                    'message' => 'Registration successful',
                    'token' => $token,
                    'user' => [
                        'id' => $userId,
                        'name' => $data->name,
                        'email' => $data->email
                    ]
                ]);
            } else {
                Response::error(500, "Failed to create account");
            }
        } catch (Exception $e) {
            Response::error(500, $e->getMessage());
        }
    }

    public function login() {
        try {
            $data = json_decode(file_get_contents("php://input"));

            if (!isset($data->email) || !isset($data->password)) {
                Response::error(400, "Email and password are required");
                return;
            }

            $user = $this->storeOwner->findByEmail($data->email);

            if (!$user) {
                Response::error(401, "Invalid email or password");
                return;
            }

            if (!password_verify($data->password, $user['password'])) {
                Response::error(401, "Invalid email or password");
                return;
            }

            // Generate token
            $token = $this->generateToken($user['id']);

            Response::success(200, [
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } catch (Exception $e) {
            Response::error(500, $e->getMessage());
        }
    }

    private function validateRegisterData($data) {
        return (
            isset($data->name) &&
            isset($data->email) &&
            isset($data->password) &&
            isset($data->location) &&
            isset($data->address) &&
            isset($data->contact_info) &&
            filter_var($data->email, FILTER_VALIDATE_EMAIL) &&
            strlen($data->password) >= 6
        );
    }

    private function generateToken($userId) {
        $payload = [
            'user_id' => $userId,
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ];
        return base64_encode(json_encode($payload));
    }

    // Optional: Add method to verify token
    public function verifyToken() {
        try {
            $headers = getallheaders();
            $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
            
            if (!$token) {
                Response::error(401, "No token provided");
                return;
            }

            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || !isset($payload['user_id']) || $payload['exp'] < time()) {
                Response::error(401, "Invalid or expired token");
                return;
            }

            $user = $this->storeOwner->findById($payload['user_id']);
            
            if (!$user) {
                Response::error(401, "User not found");
                return;
            }

            Response::success(200, [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } catch (Exception $e) {
            Response::error(500, $e->getMessage());
        }
    }
    
}