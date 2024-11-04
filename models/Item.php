<?php
// models/Item.php
class Item {
    private $conn;
    private $table_name = "items";

    public $item_id;
    public $shop_id;
    public $item_name;
    public $prep_start_time;
    public $prep_duration;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create item with ownership verification
    public function create($owner_id) {
        // First verify shop ownership
        $shop_query = "SELECT COUNT(*) FROM shops 
                      WHERE shop_id = ? AND store_owner_id = ?";
        $shop_stmt = $this->conn->prepare($shop_query);
        $shop_stmt->bindParam(1, $this->shop_id);
        $shop_stmt->bindParam(2, $owner_id);
        $shop_stmt->execute();
        
        if ($shop_stmt->fetchColumn() == 0) {
            return false; // Not authorized to add items to this shop
        }

        // If authorized, create the item
        $query = "INSERT INTO " . $this->table_name . "
                  SET 
                    shop_id = :shop_id,
                    item_name = :item_name,
                    prep_start_time = :prep_start_time,
                    prep_duration = :prep_duration,
                    updated_at = :updated_at";

        $stmt = $this->conn->prepare($query);

        $this->item_name = htmlspecialchars(strip_tags($this->item_name));
        $this->prep_start_time = htmlspecialchars(strip_tags($this->prep_start_time));
        $this->prep_duration = htmlspecialchars(strip_tags($this->prep_duration));
        $this->updated_at = date('Y-m-d H:i:s');

        $stmt->bindParam(":shop_id", $this->shop_id);
        $stmt->bindParam(":item_name", $this->item_name);
        $stmt->bindParam(":prep_start_time", $this->prep_start_time);
        $stmt->bindParam(":prep_duration", $this->prep_duration);
        $stmt->bindParam(":updated_at", $this->updated_at);

        return $stmt->execute();
    }

    // Verify item ownership through shop
    public function verifyOwnership($item_id, $owner_id) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " i
                 JOIN shops s ON i.shop_id = s.shop_id
                 WHERE i.item_id = ? AND s.store_owner_id = ?";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $item_id);
        $stmt->bindParam(2, $owner_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
}