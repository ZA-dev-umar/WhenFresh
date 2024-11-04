<?php // controllers/ItemController.php
require_once __DIR__ . "/../models/Item.php";
require_once __DIR__ . "/../utils/Response.php";

class ItemController {
    private $item;
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->item = new Item($this->db);
    }

    // Create new item
    public function create() {
        try {
            $data = json_decode(file_get_contents("php://input"));
            
            if (!$this->validateItemData($data)) {
                Response::json(400, [
                    'error' => 'Invalid data provided',
                    'required_fields' => ['shop_id', 'item_name', 'prep_start_time', 'prep_duration']
                ]);
                return;
            }

            $owner_id = $_REQUEST['user']['user_id'];
            
            $this->item->shop_id = $data->shop_id;
            $this->item->item_name = $data->item_name;
            $this->item->prep_start_time = $data->prep_start_time;
            $this->item->prep_duration = $data->prep_duration;

            if ($this->item->create($owner_id)) {
                Response::json(201, [
                    'message' => 'Item created successfully',
                    'item_id' => $this->item->item_id
                ]);
            } else {
                Response::json(403, ['error' => 'Not authorized to add items to this shop']);
            }
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to create item: ' . $e->getMessage()]);
        }
    }

    // Get all items for a shop (public endpoint)
    public function getAll($shop_id) {
        try {
            $result = $this->item->readAll($shop_id);
            $items = [];

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                // Add freshness calculation
                $row['freshness'] = $this->calculateFreshness(
                    $row['prep_start_time'],
                    $row['prep_duration']
                );
                array_push($items, $row);
            }

            Response::json(200, $items);
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to get items: ' . $e->getMessage()]);
        }
    }

    // Get single item details (public endpoint)
    public function getOne($id) {
        try {
            $result = $this->item->readOne($id);
            if ($result) {
                $result['freshness'] = $this->calculateFreshness(
                    $result['prep_start_time'],
                    $result['prep_duration']
                );
                Response::json(200, $result);
            } else {
                Response::json(404, ['error' => 'Item not found']);
            }
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to get item: ' . $e->getMessage()]);
        }
    }

    // Update item (protected endpoint)
    public function update($id) {
        try {
            $data = json_decode(file_get_contents("php://input"));
            $owner_id = $_REQUEST['user']['user_id'];
            
            // Verify ownership
            if (!$this->item->verifyOwnership($id, $owner_id)) {
                Response::json(403, ['error' => 'Not authorized to update this item']);
                return;
            }

            if (!$this->validateItemData($data)) {
                Response::json(400, ['error' => 'Invalid data provided']);
                return;
            }

            $this->item->item_id = $id;
            $this->item->item_name = $data->item_name;
            $this->item->prep_start_time = $data->prep_start_time;
            $this->item->prep_duration = $data->prep_duration;

            if ($this->item->update()) {
                Response::json(200, ['message' => 'Item updated successfully']);
            } else {
                Response::json(500, ['error' => 'Unable to update item']);
            }
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to update item: ' . $e->getMessage()]);
        }
    }

    // Delete item (protected endpoint)
    public function delete($id) {
        try {
            $owner_id = $_REQUEST['user']['user_id'];
            
            // Verify ownership
            if (!$this->item->verifyOwnership($id, $owner_id)) {
                Response::json(403, ['error' => 'Not authorized to delete this item']);
                return;
            }

            if ($this->item->delete($id)) {
                Response::json(200, ['message' => 'Item deleted successfully']);
            } else {
                Response::json(500, ['error' => 'Unable to delete item']);
            }
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to delete item: ' . $e->getMessage()]);
        }
    }

    // Update preparation timing (protected endpoint)
    public function updateTiming($id) {
        try {
            $data = json_decode(file_get_contents("php://input"));
            $owner_id = $_REQUEST['user']['user_id'];
            
            if (!$this->item->verifyOwnership($id, $owner_id)) {
                Response::json(403, ['error' => 'Not authorized to update this item']);
                return;
            }

            if (!isset($data->prep_start_time) || !isset($data->prep_duration)) {
                Response::json(400, ['error' => 'Preparation time and duration required']);
                return;
            }

            $this->item->item_id = $id;
            $this->item->prep_start_time = $data->prep_start_time;
            $this->item->prep_duration = $data->prep_duration;

            if ($this->item->updateTiming()) {
                Response::json(200, ['message' => 'Preparation timing updated successfully']);
            } else {
                Response::json(500, ['error' => 'Unable to update preparation timing']);
            }
        } catch (Exception $e) {
            Response::json(500, ['error' => 'Failed to update timing: ' . $e->getMessage()]);
        }
    }

    private function validateItemData($data) {
        return isset($data->shop_id) && 
               isset($data->item_name) && 
               isset($data->prep_start_time) && 
               isset($data->prep_duration) &&
               !empty($data->item_name) &&
               !empty($data->prep_start_time) &&
               $data->prep_duration > 0;
    }

    private function calculateFreshness($prep_start_time, $prep_duration) {
        $now = new DateTime();
        $prep_time = new DateTime($prep_start_time);
        $prep_end = clone $prep_time;
        $prep_end->add(new DateInterval('PT' . $prep_duration . 'M'));
        
        if ($now < $prep_time) {
            return 'Not prepared yet';
        } elseif ($now <= $prep_end) {
            return 'Fresh - In preparation';
        } else {
            $time_since = $now->diff($prep_end);
            return 'Prepared ' . $time_since->format('%h hours %i minutes ago');
        }
    }
}
?>