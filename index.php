<?php
session_start();
require_once 'classes/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $conn = $db->getConnection();

    $school_id = trim($_POST['school_id']);
    $password = $_POST['password'];

    // Fetch user by username
    $stmt = $conn->prepare("SELECT id, full_name, password_hash FROM users WHERE username = :username");
    $stmt->execute(['username' => $school_id]); // Keeping your variable name as $school_id is fine, just map it to username
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        // Remove the role check, just send them to admin
        header("Location: admin/dashboard.php");
        exit;
    } else {
        $error = "Invalid School ID or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LabBorrow</title>
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gradient-custom d-flex align-items-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">

                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none fw-bolder fs-3 text-white d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-cpu" style="color: var(--ccs-primary);"></i>
                        LabBorrow
                    </a>
                </div>

                <div class="glass-panel p-4 p-md-5 rounded-4 position-relative overflow-hidden">

                    <div style="position: absolute; top: -50px; left: -50px; width: 150px; height: 150px; background: var(--ccs-primary); filter: blur(60px); opacity: 0.5; z-index: -1;"></div>

                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-white mb-1">Welcome Back</h4>
                        <p class="text-white-50 small">Sign in to continue to the system</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 border-0 bg-danger bg-opacity-25 text-white text-center small rounded-3">
                            <i class="bi bi-exclamation-circle me-1"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="school_id" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">SCHOOL ID</label>
                            <div class="input-group">
                                <span class="input-group-text glass-input border-end-0 text-white-50">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control glass-input border-start-0 ps-0" name="school_id" placeholder="Enter your ID" required autocomplete="off">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="password" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">PASSWORD</label>
                            <div class="input-group">
                                <span class="input-group-text glass-input border-end-0 text-white-50">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control glass-input border-start-0 ps-0" name="password" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-custom w-100 py-2 rounded-pill fw-bold shadow-lg mb-4 text-uppercase" style="letter-spacing: 1px;">Sign In</button>
                    </form>

                </div>

            </div>
        </div>
    </div>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>