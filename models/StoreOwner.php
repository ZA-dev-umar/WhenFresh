<?php
// models/StoreOwner.php
class StoreOwner {
    private $conn;
    private $table_name = "store_owners";

    public $id;
    public $name;
    public $email;
    public $password;
    public $location;
    public $address;
    public $contact_info;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (name, email, password, location, address, contact_info, created_at)
                    VALUES
                    (:name, :email, :password, :location, :address, :contact_info, :created_at)";

            $stmt = $this->conn->prepare($query);

            // Sanitize and validate input
            $name = htmlspecialchars(strip_tags($data['name'] ?? ''));
            $email = htmlspecialchars(strip_tags($data['email'] ?? ''));
            $password = $data['password']; // Already hashed
            $location = htmlspecialchars(strip_tags($data['location'] ?? ''));
            $address = htmlspecialchars(strip_tags($data['address'] ?? ''));
            $contact_info = htmlspecialchars(strip_tags($data['contact_info'] ?? ''));
            $created_at = date('Y-m-d H:i:s');

            // Bind parameters
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":location", $location);
            $stmt->bindParam(":address", $address);
            $stmt->bindParam(":contact_info", $contact_info);
            $stmt->bindParam(":created_at", $created_at);

            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            throw new Exception("Failed to create store owner: " . $e->getMessage());
        }
    }

    public function findByEmail($email) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
            $stmt = $this->conn->prepare($query);

            $email = htmlspecialchars(strip_tags($email));
            $stmt->bindParam(':email', $email);

            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw new Exception("Failed to find store owner: " . $e->getMessage());
        }
    }

    public function validateLogin($email, $password) {
        try {
            $user = $this->findByEmail($email);
            if($user && password_verify($password, $user['password'])) {
                return $user;
            }
            return false;
        } catch(PDOException $e) {
            throw new Exception("Login validation failed: " . $e->getMessage());
        }
    }
}