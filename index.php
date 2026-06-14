<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') header("Location: admin/dashboard.php");
    else header("Location: student/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Equipment Borrowing System</title>
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gradient-custom d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg glass-panel sticky-top border-top-0 border-end-0 border-start-0 py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-cpu" style="color: var(--ccs-primary); font-size: 1.5rem;"></i>
                Laboratory Equipment Borrowing System
            </a>
            <div class="d-flex">
                <a href="login.php" class="btn btn-custom px-4 rounded-pill shadow-sm">Sign In <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 d-flex align-items-center">
        <div class="container py-5">
            <div class="row align-items-center justify-content-center text-center text-lg-start">
                
                <div class="col-lg-6 mb-5 mb-lg-0">
                    
                    <h1 class="display-4 fw-bolder mb-4 text-white" style="line-height: 1.2;">
                        Next-Generation <br>
                        <span style="color: var(--ccs-primary);">Equipment Management.</span>
                    </h1>
                    <p class="lead mb-5 text-white-50" style="max-width: 500px; margin: 0 auto; margin-lg-start: 0;">
                        Streamline your laboratory workflow. Request equipment, track active borrows in real-time, and maintain absolute accountability across all departments.
                    </p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start">
                        <a href="login.php" class="btn btn-custom btn-lg px-5 rounded-pill shadow">Get Started</a>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row g-4">
                        <div class="col-md-6 mt-lg-5">
                            <div class="glass-panel p-4 rounded-4 h-100 text-start">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background: rgba(31, 125, 83, 0.2);">
                                    <i class="bi bi-laptop fs-4 text-white"></i>
                                </div>
                                <h5 class="fw-bold text-white">Easy Requesting</h5>
                                <p class="text-white-50 small mb-0">Browse real-time inventory and submit your borrowing requests instantly.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="glass-panel p-4 rounded-4 h-100 text-start">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background: rgba(31, 125, 83, 0.2);">
                                    <i class="bi bi-clock-history fs-4 text-white"></i>
                                </div>
                                <h5 class="fw-bold text-white">Live Tracking</h5>
                                <p class="text-white-50 small mb-0">Monitor expected returns, active statuses, and prevent overdue equipment.</p>
                            </div>
                        </div>
                        <div class="col-md-6 mt-lg-n5">
                            <div class="glass-panel p-4 rounded-4 h-100 text-start">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px; background: rgba(31, 125, 83, 0.2);">
                                    <i class="bi bi-shield-lock fs-4 text-white"></i>
                                </div>
                                <h5 class="fw-bold text-white">Secure RBAC</h5>
                                <p class="text-white-50 small mb-0">Strict separation of Admin and Student environments ensures data integrity.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer class="py-4 glass-panel border-bottom-0 border-end-0 border-start-0 text-center text-white-50 small mt-auto">
        <div class="container">
            &copy; 2026 Laboratory Equipment Borrowing System. All Rights Reserved.
        </div>
    </footer>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>