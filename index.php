<?php
session_start();
// Include your database connection (adjust the path/class name to match yours)
require_once 'classes/database.php'; 

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') header("Location: admin/dashboard.php");
    else header("Location: student/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $conn = $db->getConnection();

    $school_id = $_POST['school_id'];
    $password = $_POST['password'];

    // Fetch user by school_id
    $stmt = $conn->prepare("SELECT id, full_name, password_hash, role FROM users WHERE school_id = :school_id");
    $stmt->execute(['school_id' => $school_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password and set sessions
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'Admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: student/dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid School ID or Password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Equipment Borrowing System</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">System Login</h4>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="school_id" class="form-label">School ID</label>
                                <input type="text" class="form-control" name="school_id" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>