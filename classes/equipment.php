<?php
require_once 'database.php';

class Equipment {
    private $conn;
    private $table_name = "equipment";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Adding new equipment with an image
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

    // Updating equipment. Dynamically updates image ONLY if a new one is provided.
    public function updateEquipment($id, $item_name, $description, $stock_quantity, $new_image_path = null) {
        // Base query
        $query = "UPDATE " . $this->table_name . " 
                  SET item_name = :item_name, 
                      description = :description, 
                      stock_quantity = :stock_quantity";
        
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

    // Get a single equipment by ID
    public function getEquipmentById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update only the stock quantity
    public function updateStock($id, $new_stock) {
        $query = "UPDATE " . $this->table_name . " SET stock_quantity = :stock_quantity WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $new_stock = htmlspecialchars(strip_tags($new_stock));

        $stmt->bindParam(':stock_quantity', $new_stock, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function deleteEquipment($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Search Equipment
    public function getAllEquipment($search_term = "") {
        if (!empty($search_term)) {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE item_name LIKE :search 
                      OR description LIKE :search 
                      OR id = :search_id
                      ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            
            $clean_search = htmlspecialchars(strip_tags($search_term));
            
           $search_wildcard = "%" . $clean_search . "%";
            
            $search_id = (int) str_ireplace('EQ-', '', $clean_search);
            
            // Bind parameters
            $stmt->bindParam(':search', $search_wildcard);
            $stmt->bindParam(':search_id', $search_id, PDO::PARAM_INT);
            
        } else {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get Inventory with Status
    public function getInventoryWithStats() {
    // We use COALESCE to ensure if no transactions exist, we get 0 instead of NULL
    $query = "SELECT e.id, e.item_name, e.description, e.stock_quantity, e.image_path, e.status,
              COALESCE(SUM(CASE WHEN t.status = 'Borrowed' THEN 1 ELSE 0 END), 0) as borrowed_count,
              COALESCE(SUM(CASE WHEN t.status = 'Overdue' THEN 1 ELSE 0 END), 0) as overdue_count
              FROM equipment e
              LEFT JOIN transactions t ON e.id = t.equipment_id
              GROUP BY e.id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>