<?php
session_start();
// Kick them out if they aren't logged in OR if they aren't an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Laboratory Equipment Borrowing</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .admin-stat-card { border-left: 4px solid var(--ccs-primary); }
        .admin-stat-card.accent-dark { border-left-color: var(--ccs-darkest); }
        .admin-stat-card.accent-light { border-left-color: var(--ccs-accent); }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">System Dashboard</h5>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--ccs-darkest);">Maya</div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=Maya&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40" alt="Profile">
            </div>
        </div>

        <div class="filter-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="fw-bold me-3 text-muted small text-uppercase"><i class="bi bi-lightning-charge me-1"></i> Quick Actions</span>
                <button class="btn btn-sm btn-custom px-3 rounded-pill shadow-sm"><i class="bi bi-plus-circle me-1"></i> Add Equipment</button>
                <button class="btn btn-sm btn-light border px-3 rounded-pill text-muted fw-medium ms-2"><i class="bi bi-person-plus me-1"></i> Register Student</button>
            </div>
            
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" placeholder="Search inventory or users...">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            
            <div class="row g-4 mb-5">
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 admin-stat-card accent-dark bg-white">
                        <div class="card-body p-4">
                            <p class="text-muted mb-2 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Total Equipment</p>
                            <h2 class="mb-0 fw-bold" style="color: var(--ccs-darkest);">342</h2>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 admin-stat-card bg-white">
                        <div class="card-body p-4">
                            <p class="text-muted mb-2 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Active Borrows</p>
                            <h2 class="mb-0 fw-bold" style="color: var(--ccs-primary);">45</h2>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 admin-stat-card accent-light" style="background-color: #f8fcf8;">
                        <div class="card-body p-4">
                            <p class="text-muted mb-2 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Pending Requests</p>
                            <h2 class="mb-0 fw-bold" style="color: var(--ccs-accent);">12</h2>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100 shadow-sm border-0 admin-stat-card" style="border-left-color: #dee2e6;">
                        <div class="card-body p-4">
                            <p class="text-muted mb-2 small fw-bold text-uppercase" style="letter-spacing: 0.5px;">Registered Students</p>
                            <h2 class="mb-0 fw-bold text-secondary">1,204</h2>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mb-3 fw-bold" style="color: var(--ccs-darkest);">Requires Attention</h5>
            <div class="table-card shadow-sm border-0 mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Student</th>
                                <th class="border-bottom-0 pb-3 pt-4">Equipment Requested</th>
                                <th class="border-bottom-0 pb-3 pt-4">Date</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold" style="color: var(--ccs-darkest);">Juan Dela Cruz</div>
                                    <div class="text-muted small">BSCS-2A</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3 border"><i class="bi bi-cpu" style="color: var(--ccs-primary);"></i></div>
                                        <span class="fw-medium">Raspberry Pi 4</span>
                                    </div>
                                </td>
                                <td class="text-muted small">Today, 10:04 AM</td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-custom rounded px-3 me-2 shadow-sm"><i class="bi bi-check2"></i> Approve</button>
                                    <button class="btn btn-sm btn-light border rounded px-3 text-danger fw-medium shadow-sm"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold" style="color: var(--ccs-darkest);">Maria Clara</div>
                                    <div class="text-muted small">BSIT-3B</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3 border"><i class="bi bi-projector" style="color: var(--ccs-primary);"></i></div>
                                        <span class="fw-medium">Epson Projector x41</span>
                                    </div>
                                </td>
                                <td class="text-muted small">Yesterday, 04:30 PM</td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-custom rounded px-3 me-2 shadow-sm"><i class="bi bi-check2"></i> Approve</button>
                                    <button class="btn btn-sm btn-light border rounded px-3 text-danger fw-medium shadow-sm"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });
</script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>