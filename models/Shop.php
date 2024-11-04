<?php
// models/Shop.php
class Shop {
    private $conn;
    private $table_name = "shops";

    public $shop_id;
    public $store_owner_id;
    public $name;
    public $location;
    public $address;
    public $contact_info;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (name, location, address, contact_info, store_owner_id, created_at)
                    VALUES
                    (:name, :location, :address, :contact_info, :store_owner_id, :created_at)";

            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $name = htmlspecialchars(strip_tags($data['name']));
            $location = htmlspecialchars(strip_tags($data['location']));
            $address = htmlspecialchars(strip_tags($data['address']));
            $contact_info = htmlspecialchars(strip_tags($data['contact_info']));
            $store_owner_id = $data['store_owner_id'];
            $created_at = date('Y-m-d H:i:s');

            // Bind parameters
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":location", $location);
            $stmt->bindParam(":address", $address);
            $stmt->bindParam(":contact_info", $contact_info);
            $stmt->bindParam(":store_owner_id", $store_owner_id);
            $stmt->bindParam(":created_at", $created_at);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            throw new Exception("Failed to create shop: " . $e->getMessage());
        }
    }

    public function readAll() {
        try {
            $query = "SELECT 
                        s.*,
                        CONCAT(so.name, ' (', so.email, ')') as owner_info
                     FROM " . $this->table_name . " s
                     LEFT JOIN store_owners so ON s.store_owner_id = so.id
                     ORDER BY s.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Failed to read shops: " . $e->getMessage());
        }
    }

    public function readOne($id) {
        try {
            $query = "SELECT 
                        s.*,
                        CONCAT(so.name, ' (', so.email, ')') as owner_info
                     FROM " . $this->table_name . " s
                     LEFT JOIN store_owners so ON s.store_owner_id = so.id
                     WHERE s.shop_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw new Exception("Failed to read shop: " . $e->getMessage());
        }
    }

    public function readNearby($userLat, $userLng, $radius = 5) {
        try {
            // Simplified distance calculation using bounding box first
            $lat_range = $radius / 111.32; // 1 degree = 111.32 km
            $lng_range = $radius / (111.32 * COS(deg2rad($userLat)));

            $min_lat = $userLat - $lat_range;
            $max_lat = $userLat + $lat_range;
            $min_lng = $userLng - $lng_range;
            $max_lng = $userLng + $lng_range;

            $query = "SELECT 
                        s.*,
                        CONCAT(so.name, ' (', so.email, ')') as owner_info,
                        (
                            6371 * acos(
                                cos(radians(:lat)) * 
                                cos(radians(SUBSTRING_INDEX(s.location, ',', 1))) * 
                                cos(radians(SUBSTRING_INDEX(s.location, ',', -1)) - radians(:lng)) + 
                                sin(radians(:lat)) * 
                                sin(radians(SUBSTRING_INDEX(s.location, ',', 1)))
                            )
                        ) AS distance
                    FROM " . $this->table_name . " s
                    LEFT JOIN store_owners so ON s.store_owner_id = so.id
                    WHERE 
                        SUBSTRING_INDEX(s.location, ',', 1) BETWEEN :min_lat AND :max_lat
                        AND SUBSTRING_INDEX(s.location, ',', -1) BETWEEN :min_lng AND :max_lng
                    HAVING distance <= :radius
                    ORDER BY distance";

            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":lat", $userLat);
            $stmt->bindParam(":lng", $userLng);
            $stmt->bindParam(":min_lat", $min_lat);
            $stmt->bindParam(":max_lat", $max_lat);
            $stmt->bindParam(":min_lng", $min_lng);
            $stmt->bindParam(":max_lng", $max_lng);
            $stmt->bindParam(":radius", $radius);
            
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Failed to get nearby shops: " . $e->getMessage());
        }
    }

    public function update($data) {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET 
                        name = :name,
                        location = :location,
                        address = :address,
                        contact_info = :contact_info
                    WHERE shop_id = :shop_id 
                    AND store_owner_id = :store_owner_id";

            $stmt = $this->conn->prepare($query);

            // Sanitize inputs
            $name = htmlspecialchars(strip_tags($data['name']));
            $location = htmlspecialchars(strip_tags($data['location']));
            $address = htmlspecialchars(strip_tags($data['address']));
            $contact_info = htmlspecialchars(strip_tags($data['contact_info']));

            // Bind parameters
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":location", $location);
            $stmt->bindParam(":address", $address);
            $stmt->bindParam(":contact_info", $contact_info);
            $stmt->bindParam(":shop_id", $data['shop_id']);
            $stmt->bindParam(":store_owner_id", $data['store_owner_id']);

            if($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
            return false;
        } catch(PDOException $e) {
            throw new Exception("Failed to update shop: " . $e->getMessage());
        }
    }

    public function delete($shop_id, $owner_id) {
        try {
            // First check if there are any items for this shop
            $checkItems = "SELECT COUNT(*) FROM items WHERE shop_id = ?";
            $stmt = $this->conn->prepare($checkItems);
            $stmt->bindParam(1, $shop_id);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete shop with existing items");
            }

            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE shop_id = ? AND store_owner_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $shop_id);
            $stmt->bindParam(2, $owner_id);
            
            if($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
            return false;
        } catch(PDOException $e) {
            throw new Exception("Failed to delete shop: " . $e->getMessage());
        }
    }

    public function getItems($shop_id) {
        try {
            $query = "SELECT 
                        i.*,
                        s.name as shop_name,
                        CASE 
                            WHEN TIME(NOW()) < i.prep_start_time THEN 'Upcoming'
                            WHEN TIME(NOW()) BETWEEN i.prep_start_time AND ADDTIME(i.prep_start_time, SEC_TO_TIME(i.prep_duration * 60)) THEN 'Fresh'
                            ELSE 'Ready'
                        END as status,
                        TIMESTAMPDIFF(MINUTE, 
                            CONCAT(CURRENT_DATE(), ' ', i.prep_start_time),
                            NOW()
                        ) as minutes_since_prep
                     FROM items i
                     JOIN " . $this->table_name . " s ON i.shop_id = s.shop_id
                     WHERE i.shop_id = ?
                     ORDER BY i.prep_start_time DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $shop_id);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Failed to get shop items: " . $e->getMessage());
        }
    }

    public function verifyOwner($shop_id, $owner_id) {
        try {
            $query = "SELECT COUNT(*) FROM " . $this->table_name . "
                    WHERE shop_id = ? AND store_owner_id = ?";
                    
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $shop_id);
            $stmt->bindParam(2, $owner_id);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            throw new Exception("Failed to verify shop owner: " . $e->getMessage());
        }
    }

    public function getShopsByOwner($owner_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . "
                    WHERE store_owner_id = ?
                    ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $owner_id);
            $stmt->execute();
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Failed to get owner's shops: " . $e->getMessage());
        }
    }
}