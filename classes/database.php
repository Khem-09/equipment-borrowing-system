<?php
class Database {
    // XAMPP Default Database Credentials
    private $host = "localhost";
    private $db_name = "equipment_borrowing_system_db";
    private $username = "root";
    private $password = ""; 
    
    public $conn;

    // Method to establish and return the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // Build the Data Source Name (DSN)
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // Instantiate the PDO object
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Set PDO to throw exceptions on errors (Crucial for debugging)
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode to Associative Arrays for easier data handling
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            // Catch and display connection errors cleanly
            die("Database Connection Error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}