<?php
require_once 'classes/database.php';
$db = new Database();
$conn = $db->getConnection();

$username = 'admin';
$name = 'System Administrator';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, full_name, password_hash) VALUES (?, ?, ?)");
$stmt->execute([$username, $name, $hash]);

echo "Admin user created successfully! You can now log in and delete this file.";
?>