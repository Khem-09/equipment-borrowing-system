<?php
session_start();

// Protect the page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../classes/database.php';
$db = new Database();
$conn = $db->getConnection();

// --- 1. FETCH ASSET METRICS (Optimized) ---
// Using subqueries to safely grab accurate counts across tables
$stmt_metrics = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM equipment_assets) as total_assets,
        (SELECT COUNT(*) FROM equipment_assets WHERE status = 'Available') as available_assets,
        (SELECT COUNT(*) FROM slips WHERE status = 'Active') as active_slips,
        (SELECT COUNT(*) FROM equipment_assets WHERE status IN ('Broken', 'Lost')) as issue_assets
");
$metrics = $stmt_metrics->fetch(PDO::FETCH_ASSOC);

// --- 2. FETCH RECENT ACTIVITY ---
// Grab the 5 most recent active slips for the quick-view table
$stmt_recent = $conn->query("
    SELECT slip_number, student_name, course_section, issue_date 
    FROM slips 
    WHERE status = 'Active' 
    ORDER BY issue_date DESC 
    LIMIT 5
");
$recent_slips = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Equipment Borrowing System</title>
    
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        .metric-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
            border-radius: 12px;
        }
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
        }
        .icon-box {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
        }
        .icon-box {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.5rem;
        background-color: rgba(var(--bs-primary-rgb), 0.1); /* Use RGB variables for better blending */
        color: var(--bs-primary);
    }
    </style>
</head>
<body class="bg-light">

    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <main class="container-fluid py-4 px-4" style="margin: 20px 200px 0 260px; width: calc(100% - 250px);">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0" style="color: var(--ccs-darkest);">Dashboard</h2>
                <p class="text-muted small mb-0">Overview of your equipment and borrowing activities.</p>
            </div>
            <p class="text-muted small mb-0"><i class="bi bi-calendar3 me-1"></i> <?= date('F j, Y') ?></p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="card metric-card shadow-sm h-100 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="icon-box bg-success bg-opacity-10 text-success">
                                <i class="bi bi-inboxes"></i>
                            </div>
                        </div>
                        <h6 class="text-muted fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Total Assets</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($metrics['total_assets']) ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-sm-6">
                <div class="card metric-card shadow-sm h-100 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="icon-box bg-success bg-opacity-10 text-success">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                        </div>
                        <h6 class="text-muted fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Available</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($metrics['available_assets']) ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="card metric-card shadow-sm h-100 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-journal-arrow-up"></i>
                            </div>
                        </div>
                        <h6 class="text-muted fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Active Borrows</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($metrics['active_slips']) ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="card metric-card shadow-sm h-100 bg-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="icon-box bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                        <h6 class="text-muted fw-semibold mb-1 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.8rem;">Broken / Lost</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($metrics['issue_assets']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Active Slips</h6>
                    </div>
                    <div class="card-body px-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                                    <tr>
                                        <th class="border-bottom-0 pb-2">Slip No.</th>
                                        <th class="border-bottom-0 pb-2">Student</th>
                                        <th class="border-bottom-0 pb-2">Issued</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_slips) > 0): ?>
                                        <?php foreach ($recent_slips as $slip): ?>
                                            <tr>
                                                <td class="font-monospace text-primary fw-bold small"><?= htmlspecialchars($slip['slip_number']) ?></td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($slip['student_name']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($slip['course_section']) ?></div>
                                                </td>
                                                <td class="text-muted small"><?= date('M d, h:i A', strtotime($slip['issue_date'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">No recent borrowing activity.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary bg-opacity-10">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-primary mb-4"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h6>
                        
                        <div class="d-grid gap-3">
                            <a href="new_slip.php" class="btn btn-primary rounded-pill py-2 shadow-sm text-start ps-4 fw-medium">
                                <i class="bi bi-plus-circle me-2"></i> Create New Slip
                            </a>
                            <a href="active_slips.php" class="btn bg-white text-primary border-0 rounded-pill py-2 shadow-sm text-start ps-4 fw-medium">
                                <i class="bi bi-arrow-return-left me-2"></i> Process Returns
                            </a>
                            <a href="inventory.php" class="btn bg-white text-primary border-0 rounded-pill py-2 shadow-sm text-start ps-4 fw-medium">
                                <i class="bi bi-box me-2"></i> View Inventory
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>