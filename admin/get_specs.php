<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
// admin/get_specs.php
require_once '../classes/database.php';
$db = new Database();
$conn = $db->getConnection();

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Fetch specifications that belong ONLY to the requested category
$stmt = $conn->prepare("SELECT id, specification_name FROM equipment_specifications WHERE category_id = ? ORDER BY specification_name ASC");
$stmt->execute([$category_id]);
$specs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the data as JSON so JavaScript can read it
header('Content-Type: application/json');
echo json_encode($specs);
?>