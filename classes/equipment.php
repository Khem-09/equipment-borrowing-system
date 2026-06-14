<?php
require_once 'database.php';

class Equipment {
    private $conn;
    private $table_name = "equipment";

    // Inject the database connection when the class is instantiated
    public function __construct($db) {
        $this->conn = $db;
    }

    // READ: Fetch all equipment for the admin table
    public function getAllEquipment() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ: Fetch a single item by ID (useful for populating an Edit form)
    
    public function getEquipmentById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

     // CREATE: Add new equipment securely
   
    public function addEquipment($item_name, $description, $stock_quantity) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (item_name, description, stock_quantity, status) 
                  VALUES (:item_name, :description, :stock_quantity, 'Available')";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize input to prevent XSS
        $item_name = htmlspecialchars(strip_tags($item_name));
        $description = htmlspecialchars(strip_tags($description));
        $stock_quantity = htmlspecialchars(strip_tags($stock_quantity));

        // Bind parameters safely to prevent SQL Injection
        $stmt->bindParam(':item_name', $item_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);

        // Execute and return boolean success/failure
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // UPDATE: Modify existing equipment details

    public function updateEquipment($id, $item_name, $description, $stock_quantity, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET item_name = :item_name, 
                      description = :description, 
                      stock_quantity = :stock_quantity, 
                      status = :status 
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $item_name = htmlspecialchars(strip_tags($item_name));
        $description = htmlspecialchars(strip_tags($description));
        $status = htmlspecialchars(strip_tags($status));

        // Bind parameters
        $stmt->bindParam(':item_name', $item_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    //  DELETE: Remove an item entirely
     
    public function deleteEquipment($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>