<?php
class Analytics {
    private $db;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    // --- UPDATED: Fetch options based on current selections for dependent filtering ---
    public function getFilterOptions($filters = []) {
        $options = ['categories' => [], 'assets' => [], 'specs' => []];
        
        // 1. Fetch Categories (This works because category_name is confirmed)
        $stmt = $this->db->query("SELECT id, category_name FROM equipment_categories ORDER BY category_name ASC");
        if ($stmt) $options['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Fetch Specs - Filter by Category if selected
        // NOTE: Change 'id' to your actual name column (e.g., 'specification_name' or 'model') to show names
        $specQuery = "SELECT id FROM equipment_specifications"; 
        $specParams = [];
        if (!empty($filters['category_id'])) {
            $specQuery .= " WHERE category_id = :cat_id";
            $specParams[':cat_id'] = $filters['category_id'];
        }
        $specQuery .= " ORDER BY id ASC";
        $stmt = $this->db->prepare($specQuery);
        $stmt->execute($specParams);
        $options['specs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch Assets - Filter by Spec and/or Category if selected
        // NOTE: Change 'id' to your actual name/serial column (e.g., 'serial_number') to show names
        $assetQuery = "SELECT id FROM equipment_assets"; 
        $assetConditions = [];
        $assetParams = [];
        if (!empty($filters['spec_id'])) {
            $assetConditions[] = "specification_id = :spec_id";
            $assetParams[':spec_id'] = $filters['spec_id'];
        }
        if (!empty($filters['category_id'])) {
            $assetConditions[] = "category_id = :cat_id";
            $assetParams[':cat_id'] = $filters['category_id'];
        }

        if (!empty($assetConditions)) {
            $assetQuery .= " WHERE " . implode(" AND ", $assetConditions);
        }
        $assetQuery .= " ORDER BY id ASC";
        
        $stmt = $this->db->prepare($assetQuery);
        $stmt->execute($assetParams);
        $options['assets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $options;
    }

    // --- Helper to build dynamic WHERE clauses (Same as before, safe) ---
    private function buildFilterQuery($filters, $requireJoin = false) {
        $conditions = [];
        $params = [];

        if (!empty($filters['category_id'])) {
            $conditions[] = "a.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        if (!empty($filters['asset_id'])) {
            $conditions[] = "a.id = :asset_id";
            $params[':asset_id'] = $filters['asset_id'];
        }
        if (!empty($filters['spec_id'])) {
            $conditions[] = "a.specification_id = :spec_id"; 
            $params[':spec_id'] = $filters['spec_id'];
        }

        $joinClause = "";
        if ($requireJoin && count($conditions) > 0) {
            $joinClause = " JOIN slip_items si ON slips.id = si.slip_id JOIN equipment_assets a ON si.asset_id = a.id ";
        }

        $whereClause = count($conditions) > 0 ? " WHERE " . implode(" AND ", $conditions) : "";
        return ['join' => $joinClause, 'where' => $whereClause, 'params' => $params];
    }

    public function getQuickMetrics($filters = []) {
        $metrics = ['total_assets' => 0, 'total_slips' => 0];
        $filterData = $this->buildFilterQuery($filters);

        $query1 = "SELECT COUNT(*) as count FROM equipment_assets a " . $filterData['where'];
        $stmt1 = $this->db->prepare($query1);
        $stmt1->execute($filterData['params']);
        if ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
            $metrics['total_assets'] = $row['count'];
        }

        $filterDataSlips = $this->buildFilterQuery($filters, true);
        $query2 = "SELECT COUNT(DISTINCT slips.id) as count FROM slips " . $filterDataSlips['join'] . $filterDataSlips['where'];
        $stmt2 = $this->db->prepare($query2);
        $stmt2->execute($filterDataSlips['params']);
        if ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $metrics['total_slips'] = $row['count'];
        }
        return $metrics;
    }

    public function getPopularCategories($filters = []) {
        $filterData = $this->buildFilterQuery($filters);
        $query = "
            SELECT c.category_name, COUNT(si.id) as borrow_count 
            FROM slip_items si 
            JOIN equipment_assets a ON si.asset_id = a.id 
            JOIN equipment_categories c ON a.category_id = c.id 
            " . $filterData['where'] . "
            GROUP BY c.id 
            ORDER BY borrow_count DESC 
            LIMIT 5
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute($filterData['params']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentBorrowingVolume($filters = []) {
        $filterData = $this->buildFilterQuery($filters, true);
        $query = "
            SELECT DATE(slips.issue_date) as borrow_date, COUNT(DISTINCT slips.id) as daily_count 
            FROM slips 
            " . $filterData['join'] . "
            " . $filterData['where'] . "
            GROUP BY DATE(slips.issue_date) 
            ORDER BY borrow_date DESC 
            LIMIT 7
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute($filterData['params']);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($data); 
    }
}
?>