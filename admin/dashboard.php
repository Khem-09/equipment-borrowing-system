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

// --- 1. FETCH ASSET METRICS ---
// Group by status to get a count of everything efficiently
$stmt_assets = $conn->query("SELECT status, COUNT(*) as count FROM equipment_assets GROUP BY status");
$asset_data = $stmt_assets->fetchAll(PDO::FETCH_KEY_PAIR); 

$available = $asset_data['Available'] ?? 0;
$borrowed = $asset_data['Borrowed'] ?? 0;
$maintenance = $asset_data['Maintenance'] ?? 0;
$broken_lost = ($asset_data['Broken'] ?? 0) + ($asset_data['Lost'] ?? 0);
$total_assets = array_sum($asset_data);

// --- 2. FETCH ACTIVE SLIPS COUNT ---
$stmt_active_slips = $conn->query("SELECT COUNT(*) FROM slips WHERE status = 'Active'");
$active_slips_count = $stmt_active_slips->fetchColumn();

// --- 3. FETCH RECENT ACTIVITY (Last 5 Slips) ---
$stmt_recent = $conn->query("SELECT slip_number, student_name, subject_code, status, issue_date FROM slips ORDER BY issue_date DESC LIMIT 5");
$recent_slips = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | LabBorrow</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .metric-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .metric-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .icon-box { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 15px; font-size: 1.8rem; }
    </style>
</head>
<body class="bg-light">

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4 btn btn-light border-0"><i class="bi bi-list fs-4"></i></button>
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">System Dashboard</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--ccs-darkest);"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            
            <div class="mb-4 pb-2 border-bottom d-flex justify-content-between align-items-end">
                <div>
                    <h2 class="fw-bolder mb-1" style="color: var(--ccs-darkest);">Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?>!</h2>
                    <p class="text-muted mb-0">Here is what is happening in the stockroom today.</p>
                </div>
                <div class="text-muted small fw-bold">
                    <i class="bi bi-calendar3 me-1"></i> <?= date('l, F j, Y') ?>
                </div>
            </div>

            <div class="row g-4 mb-5">
                
                <div class="col-xl-3 col-sm-6">
                    <div class="card shadow-sm border-0 rounded-4 metric-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="icon-box bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <span class="badge bg-light text-dark border">Available</span>
                            </div>
                            <h2 class="fw-bolder mb-0"><?= $available ?></h2>
                            <p class="text-muted small mb-0 mt-1">Ready for checkout</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card shadow-sm border-0 rounded-4 metric-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-box-seam-fill"></i>
                                </div>
                                <span class="badge bg-light text-dark border">Borrowed</span>
                            </div>
                            <h2 class="fw-bolder mb-0"><?= $borrowed ?></h2>
                            <p class="text-muted small mb-0 mt-1">Items currently out</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card shadow-sm border-0 rounded-4 metric-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                    <i class="bi bi-journal-arrow-up"></i>
                                </div>
                                <span class="badge bg-light text-dark border">Active Slips</span>
                            </div>
                            <h2 class="fw-bolder mb-0"><?= $active_slips_count ?></h2>
                            <p class="text-muted small mb-0 mt-1">Students waiting to return</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card shadow-sm border-0 rounded-4 metric-card h-100 border-bottom border-4 border-danger">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="icon-box bg-danger bg-opacity-10 text-danger">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <span class="badge bg-light text-dark border">Attention</span>
                            </div>
                            <h2 class="fw-bolder mb-0 text-danger"><?= $broken_lost ?></h2>
                            <p class="text-muted small mb-0 mt-1">Items broken or lost</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-4">
                
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-4 h-100" style="background-color: var(--ccs-primary);">
                        <div class="card-body p-4 p-xl-5 text-white d-flex flex-column justify-content-center text-center">
                            <div class="mb-4">
                                <i class="bi bi-upc-scan display-1 opacity-75"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Student at the window?</h4>
                            <p class="text-white-50 mb-4">Start a new borrowing session, scan their items, and generate a liability slip instantly.</p>
                            <a href="new_slip.php" class="btn btn-light btn-lg rounded-pill fw-bold text-success w-100 shadow-sm">
                                Open Kiosk <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0" style="color: var(--ccs-darkest);">Recent Transactions</h6>
                            <a href="slip_history.php" class="text-decoration-none small" style="color: var(--ccs-primary);">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-muted small text-uppercase">
                                        <tr>
                                            <th class="ps-4">Slip No.</th>
                                            <th>Student</th>
                                            <th>Class</th>
                                            <th>Status</th>
                                            <th class="pe-4 text-end">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($recent_slips) > 0): ?>
                                            <?php foreach ($recent_slips as $slip): ?>
                                                <tr>
                                                    <td class="ps-4 font-monospace fw-bold text-primary small"><?= $slip['slip_number'] ?></td>
                                                    <td class="fw-medium text-dark"><?= htmlspecialchars($slip['student_name']) ?></td>
                                                    <td class="text-muted small"><?= htmlspecialchars($slip['subject_code']) ?></td>
                                                    <td>
                                                        <?php 
                                                            $badge = 'bg-success';
                                                            if ($slip['status'] === 'Active') $badge = 'bg-warning text-dark';
                                                            if ($slip['status'] === 'Incomplete') $badge = 'bg-danger';
                                                        ?>
                                                        <span class="badge <?= $badge ?> rounded-pill px-2"><?= $slip['status'] ?></span>
                                                    </td>
                                                    <td class="pe-4 text-end text-muted small">
                                                        <?= date('h:i A', strtotime($slip['issue_date'])) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center text-muted py-5">No recent transactions.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() { 
        document.getElementById('sidebar').classList.toggle('collapsed'); 
    });
</script>
</body>
</html>