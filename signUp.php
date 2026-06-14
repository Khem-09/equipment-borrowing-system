<?php
session_start();
require_once 'classes/database.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') header("Location: admin/dashboard.php");
    else header("Location: student/dashboard.php");
    exit;
}

$error = '';
$success = '';

// Initialize variables to retain form data
$school_id = '';
$first_name = '';
$last_name = '';
$course = '';
$year = '';
$section = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $conn = $db->getConnection();

    // Sanitize and trim inputs for the new database structure
    $school_id = trim($_POST['school_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $course = $_POST['course'] ?? '';
    $year = $_POST['year'] ?? '';
    $section = $_POST['section'] ?? '';
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if (strlen($school_id) !== 9) {
        $error = "School ID must be exactly 9 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if the School ID already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE school_id = :school_id");
        $check_stmt->execute(['school_id' => $school_id]);

        if ($check_stmt->rowCount() > 0) {
            $error = "This School ID is already registered.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert using the new columns
            $insert_query = "INSERT INTO users (school_id, first_name, last_name, course, year_level, section, password_hash)
                             VALUES (:school_id, :first_name, :last_name, :course, :year, :section, :password_hash)";

            $insert_stmt = $conn->prepare($insert_query);

            if ($insert_stmt->execute([
                'school_id' => $school_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'course' => $course,
                'year' => $year,
                'section' => $section,
                'password_hash' => $hashed_password
            ])) {
                $success = "Registration successful! Please wait for the Administrator to verify and activate your account.";

                // Clear variables on success so the form resets
                $school_id = $first_name = $last_name = $course = $year = $section = '';
                $_POST['password'] = $_POST['confirm_password'] = '';
            } else {
                $error = "Registration failed. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | LabBorrow</title>
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Custom Input Styling based on your color reference */
        .glass-input {
            background-color: #1A3224 !important; /* Deep green background */
            border: 1px solid rgba(235, 241, 249, 0.2) !important; /* Subtle light border */
            color: #EBF1F9 !important; /* Light ice-blue text */
        }

        .glass-input::placeholder {
            color: rgba(235, 241, 249, 0.5) !important; /* Faded light blue for placeholders */
        }

        .glass-input:focus {
            background-color: #122419 !important; /* Slightly darker green on click */
            border-color: #EBF1F9 !important; /* Solid light blue border on click */
            box-shadow: 0 0 0 0.25rem rgba(26, 50, 36, 0.5) !important;
            color: #EBF1F9 !important;
        }

        /* Ensures dropdown text is readable inside the inputs */
        select.glass-input option {
            background-color: #1A3224;
            color: #EBF1F9;
        }

        /* Cursor pointer for password eye icon */
        .password-toggle-icon {
            cursor: pointer;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-gradient-custom d-flex align-items-center min-vh-100 py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">

                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none fw-bolder fs-3 text-white d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-cpu" style="color: var(--ccs-primary);"></i>
                        LabBorrow
                    </a>
                </div>

                <div class="glass-panel p-4 p-md-5 rounded-4 position-relative overflow-hidden">

                    <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: var(--ccs-primary); filter: blur(60px); opacity: 0.5; z-index: -1;"></div>

                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-white mb-1">Create Account</h4>
                        <p class="text-white-50 small">Register to request laboratory equipment</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 border-0 bg-danger bg-opacity-25 text-white text-center small rounded-3">
                            <i class="bi bi-exclamation-circle me-1"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success py-3 border-0 bg-success bg-opacity-25 text-white text-center small rounded-3">
                            <i class="bi bi-check-circle-fill fs-4 d-block mb-2 text-success"></i>
                            <?= $success ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-custom px-5 py-2 rounded-pill shadow-sm">Go to Login</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="school_id" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">SCHOOL ID</label>
                                <input type="text" class="form-control glass-input text-white" name="school_id" placeholder="e.g. 202401334" required autocomplete="off" value="<?= htmlspecialchars($school_id) ?>">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="first_name" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">FIRST NAME</label>
                                    <input type="text" class="form-control glass-input text-white" name="first_name" placeholder="Juan" required autocomplete="off" value="<?= htmlspecialchars($first_name) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">LAST NAME</label>
                                    <input type="text" class="form-control glass-input text-white" name="last_name" placeholder="Dela Cruz" required autocomplete="off" value="<?= htmlspecialchars($last_name) ?>">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-4">
                                    <label for="course" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">COURSE</label>
                                    <select class="form-select glass-input text-white" name="course" required>
                                        <option value="" <?= $course == '' ? 'selected' : '' ?> disabled>Select</option>
                                        <option value="BSCS" <?= $course == 'BSCS' ? 'selected' : '' ?>>BSCS</option>
                                        <option value="BSIT" <?= $course == 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                                        <option value="ACT" <?= $course == 'ACT' ? 'selected' : '' ?>>ACT</option>
                                        <option value="AppDev" <?= $course == 'AppDev' ? 'selected' : '' ?>>AppDev</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label for="year" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">YEAR</label>
                                    <select class="form-select glass-input text-white" name="year" required>
                                        <option value="" <?= $year == '' ? 'selected' : '' ?> disabled>Select</option>
                                        <option value="1" <?= $year == '1' ? 'selected' : '' ?>>1st</option>
                                        <option value="2" <?= $year == '2' ? 'selected' : '' ?>>2nd</option>
                                        <option value="3" <?= $year == '3' ? 'selected' : '' ?>>3rd</option>
                                        <option value="4" <?= $year == '4' ? 'selected' : '' ?>>4th</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label for="section" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">SECTION</label>
                                    <select class="form-select glass-input text-white" name="section" required>
                                        <option value="" <?= $section == '' ? 'selected' : '' ?> disabled>Select</option>
                                        <option value="A" <?= $section == 'A' ? 'selected' : '' ?>>A</option>
                                        <option value="B" <?= $section == 'B' ? 'selected' : '' ?>>B</option>
                                        <option value="C" <?= $section == 'C' ? 'selected' : '' ?>>C</option>
                                        <option value="D" <?= $section == 'D' ? 'selected' : '' ?>>D</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">PASSWORD</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control glass-input text-white pe-5" id="password" name="password" placeholder="••••••••" required value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
                                    <i class="bi bi-eye-slash text-white position-absolute top-50 end-0 translate-middle-y me-3 password-toggle-icon" id="togglePassword"></i>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label text-white-50 small fw-semibold mb-1" style="letter-spacing: 0.5px;">CONFIRM PASSWORD</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control glass-input text-white pe-5" id="confirm_password" name="confirm_password" placeholder="••••••••" required value="<?= htmlspecialchars($_POST['confirm_password'] ?? '') ?>">
                                    <i class="bi bi-eye-slash text-white position-absolute top-50 end-0 translate-middle-y me-3 password-toggle-icon" id="toggleConfirmPassword"></i>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-custom w-100 py-2 rounded-pill fw-bold shadow-lg mb-4 text-uppercase" style="letter-spacing: 1px;">Register Account</button>

                            <div class="text-center">
                                <span class="text-white-50 small">Already have an account?</span>
                                <a href="login.php" class="text-white text-decoration-none fw-semibold small ms-1" style="transition: opacity 0.2s;">Sign in here</a>
                            </div>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to toggle password visibility
        function setupPasswordToggle(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            icon.addEventListener('click', function () {
                // Toggle the type attribute
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);

                // Toggle the eye icon
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        }

        // Initialize toggle for both fields
        setupPasswordToggle('password', 'togglePassword');
        setupPasswordToggle('confirm_password', 'toggleConfirmPassword');
    </script>
</body>
</html>