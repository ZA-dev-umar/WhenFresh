<?php
// controllers/ShopController.php
require_once __DIR__ . "/../models/Shop.php";
require_once __DIR__ . "/../utils/Response.php";

class ShopController {
    private $shop;
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->shop = new Shop($this->db);
    }

    // Create new shop

    public function create() {
        try {
            // Get token payload from the request
            $headers = getallheaders();
            $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
            
            if (!$token) {
                Response::error(401, "No authorization token provided");
                return;
            }

            // Decode token
            $tokenPayload = json_decode(base64_decode($token), true);
            if (!$tokenPayload || !isset($tokenPayload['user_id'])) {
                Response::error(401, "Invalid token");
                return;
            }

            // Get request data
            $data = json_decode(file_get_contents("php://input"));
            
            if (!$this->validateShopData($data)) {
                Response::error(400, "Invalid shop data");
                return;
            }

            // Create shop with owner ID from token
            $shopData = [
                'name' => $data->name,
                'location' => $data->location,
                'address' => $data->address,
                'contact_info' => $data->contact_info,
                'store_owner_id' => $tokenPayload['user_id']
            ];

            $shop_id = $this->shop->create($shopData);

            if ($shop_id) {
                Response::success(201, [
                    'message' => 'Shop created successfully',
                    'shop_id' => $shop_id
                ]);
            } else {
                Response::error(500, "Failed to create shop");
            }
        } catch (Exception $e) {
            Response::error(500, $e->getMessage());
        }
    }

    // Get all shops (public endpoint)
    public function getAll() {
        try {
            $owner_id = $_REQUEST['user']['id']; // Get from token
            $shops = $this->shop->getShopsByOwner($owner_id);
            
            Response::success(200, [
                'message' => 'Shops retrieved successfully',
                'data' => $shops
            ]);
        } catch (Exception $e) {
            Response::error(500, $e->getMessage());
        }
    }

    // Get shops by owner (protected endpoint)
    public function getMyShops() {
        try {
            $owner_id = $_REQUEST['user']['user_id'];
            $result = $this->shop->getShopsByOwner($owner_id);
            $shops = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                array_push($shops, $row);
            }

            Response::json(200, $shops);
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to get shops: ' . $e->getMessage()]);
        }
    }

    // Get single shop details (public endpoint)
    public function getOne($id) {
        try {
            $result = $this->shop->readOne($id);
            if ($result) {
                Response::json(200, $result);
            } else {
                Response::json(404, ['error' => 'Shop not found']);
            }
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to get shop: ' . $e->getMessage()]);
        }
    }

    // Update shop (protected endpoint)
    public function update($id) {
        try {
            $data = json_decode(file_get_contents("php://input"));
            $owner_id = $_REQUEST['user']['user_id'];
            
            // Verify ownership
            if (!$this->shop->verifyOwner($id, $owner_id)) {
                Response::json(403, ['error' => 'Not authorized to update this shop']);
                return;
            }

            if (!$this->validateShopData($data)) {
                Response::json(400, ['error' => 'Invalid data provided']);
                return;
            }

            $this->shop->shop_id = $id;
            $this->shop->name = $data->name;
            $this->shop->location = $data->location;
            $this->shop->address = $data->address;
            $this->shop->contact_info = $data->contact_info;

            if ($this->shop->update()) {
                Response::json(200, ['message' => 'Shop updated successfully']);
            } else {
                Response::json(500, ['error' => 'Unable to update shop']);
            }
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to update shop: ' . $e->getMessage()]);
        }
    }

    // Delete shop (protected endpoint)
    public function delete($id) {
        try {
            $owner_id = $_REQUEST['user']['user_id'];
            
            // Verify ownership
            if (!$this->shop->verifyOwner($id, $owner_id)) {
                Response::json(403, ['error' => 'Not authorized to delete this shop']);
                return;
            }

            if ($this->shop->delete($id)) {
                Response::json(200, ['message' => 'Shop deleted successfully']);
            } else {
                Response::json(500, ['error' => 'Unable to delete shop']);
            }
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to delete shop: ' . $e->getMessage()]);
        }
    }

    // Get nearby shops (public endpoint)
    public function getNearby() {
        try {
            $lat = $_GET['lat'] ?? null;
            $lng = $_GET['lng'] ?? null;
            $radius = $_GET['radius'] ?? 5;

            if (!$lat || !$lng) {
                Response::json(400, ['error' => 'Location parameters required']);
                return;
            }

            $result = $this->shop->readNearby($lat, $lng, $radius);
            $shops = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                array_push($shops, $row);
            }

            Response::json(200, $shops);
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to get nearby shops: ' . $e->getMessage()]);
        }
    }

    // Get shop items (public endpoint)
    public function getItems($shop_id) {
        try {
            $result = $this->shop->getItems($shop_id);
            $items = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                array_push($items, $row);
            }

            Response::json(200, $items);
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to get shop items: ' . $e->getMessage()]);
        }
    }

    private function validateShopData($data) {
        return isset($data->name) && 
               isset($data->location) && 
               isset($data->address) && 
               isset($data->contact_info) &&
               !empty($data->name) &&
               !empty($data->location) &&
               !empty($data->address) &&
               !empty($data->contact_info);
    }
} 