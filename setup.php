<?php
require_once 'classes/database.php';
$db = new Database();
$conn = $db->getConnection();

$school_id = 'ADMIN-001';
$name = 'System Administrator';
$password = 'admin123'; // The password you will use to log in
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'Admin';

$stmt = $conn->prepare("INSERT INTO users (school_id, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
$stmt->execute([$school_id, $name, $hash, $role]);

echo "Admin user created successfully! You can now log in and delete this file.";
?>