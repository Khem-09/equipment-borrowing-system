<?php
require_once 'database.php';

class Equipment {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- CATEGORY METHODS ---
    public function addCategory($category_name, $description) {
        $query = "INSERT INTO equipment_categories (category_name, description) VALUES (:name, :desc)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', htmlspecialchars(strip_tags($category_name)));
        $stmt->bindParam(':desc', htmlspecialchars(strip_tags($description)));
        return $stmt->execute();
    }

    // --- SPECIFICATION METHODS ---
    public function addSpecification($category_id, $spec_name) {
        $query = "INSERT INTO equipment_specifications (category_id, specification_name) VALUES (:cat_id, :spec_name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cat_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':spec_name', htmlspecialchars(strip_tags($spec_name)));
        return $stmt->execute();
    }

    public function getSpecsByCategory($category_id) {
        $query = "SELECT * FROM equipment_specifications WHERE category_id = :cat_id ORDER BY specification_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cat_id', $category_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- ASSET METHODS ---
    public function addAsset($category_id, $specification_id, $unique_code, $condition = 'Good') {
        $query = "INSERT INTO equipment_assets (category_id, specification_id, unique_asset_code, status, condition_notes) 
                  VALUES (:cat_id, :spec_id, :code, 'Available', :condition)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':cat_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':spec_id', $specification_id, PDO::PARAM_INT);
        $stmt->bindParam(':code', htmlspecialchars(strip_tags($unique_code)));
        $stmt->bindParam(':condition', htmlspecialchars(strip_tags($condition)));
        return $stmt->execute();
    }

    // --- LEGACY SUPPORT (Keeps your frontend catalog working) ---
    public function getAllEquipment($search_term = "") {
        // Acts as a unified view for the frontend catalog, showing categories
        if (!empty($search_term)) {
            $query = "SELECT id, category_name as item_name, description 
                      FROM equipment_categories 
                      WHERE category_name LIKE :search OR description LIKE :search 
                      ORDER BY category_name ASC";
            $stmt = $this->conn->prepare($query);
            $clean = htmlspecialchars(strip_tags($search_term));
            $wildcard = "%" . $clean . "%";
            $stmt->bindParam(':search', $wildcard);
        } else {
            $query = "SELECT id, category_name as item_name, description FROM equipment_categories ORDER BY category_name ASC";
            $stmt = $this->conn->prepare($query);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEquipmentById($id) {
        $query = "SELECT id, category_name as item_name, description FROM equipment_categories WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>