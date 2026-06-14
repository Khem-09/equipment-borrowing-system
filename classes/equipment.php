<?php
require_once 'database.php';

class Equipment {
    private $conn;
    private $table_name = "equipment";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllEquipment() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handles Adding new equipment WITH an image
    public function addEquipment($item_name, $description, $stock_quantity, $image_path = 'default.png') {
        $query = "INSERT INTO " . $this->table_name . " 
                  (item_name, description, stock_quantity, status, image_path) 
                  VALUES (:item_name, :description, :stock_quantity, 'Available', :image_path)";
        
        $stmt = $this->conn->prepare($query);

        $item_name = htmlspecialchars(strip_tags($item_name));
        $description = htmlspecialchars(strip_tags($description));
        $stock_quantity = htmlspecialchars(strip_tags($stock_quantity));
        $image_path = htmlspecialchars(strip_tags($image_path));

        $stmt->bindParam(':item_name', $item_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':image_path', $image_path);

        return $stmt->execute();
    }

    // Handles Updating equipment. Dynamically updates image ONLY if a new one is provided.
    public function updateEquipment($id, $item_name, $description, $stock_quantity, $new_image_path = null) {
        // Base query
        $query = "UPDATE " . $this->table_name . " 
                  SET item_name = :item_name, 
                      description = :description, 
                      stock_quantity = :stock_quantity";
        
        // Add image to query ONLY if a new file was uploaded
        if ($new_image_path !== null) {
            $query .= ", image_path = :image_path";
        }
        
        $query .= " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $item_name = htmlspecialchars(strip_tags($item_name));
        $description = htmlspecialchars(strip_tags($description));
        $stock_quantity = htmlspecialchars(strip_tags($stock_quantity));

        $stmt->bindParam(':item_name', $item_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($new_image_path !== null) {
            $new_image_path = htmlspecialchars(strip_tags($new_image_path));
            $stmt->bindParam(':image_path', $new_image_path);
        }

        return $stmt->execute();
    }

    public function deleteEquipment($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>