<?php
session_start();
require_once '../classes/database.php';

// Ensure only logged-in Admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$student_id = $_GET['id'];
$error = '';
$success = '';

// Fetch existing data
$stmt = $conn->prepare("SELECT id, school_id, first_name, last_name, course, year_level, section, account_status FROM users WHERE id = :id AND role = 'Student'");
$stmt->execute(['id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: users.php");
    exit;
}

// Map the student's status to the correct tab for return/redirect purposes
$return_tab = 'pending';
if ($student['account_status'] == 'Active') $return_tab = 'active';
elseif ($student['account_status'] == 'Suspended') $return_tab = 'suspended';

// Handle Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $school_id = trim($_POST['school_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $course = $_POST['course'];
    $year = $_POST['year_level'];
    $section = $_POST['section'];

    if (empty($school_id) || empty($first_name) || empty($last_name)) {
        $error = "All fields are required.";
    } else {
        $update_query = "UPDATE users SET school_id = :school_id, first_name = :first_name, last_name = :last_name, course = :course, year_level = :year, section = :section WHERE id = :id";
        $update_stmt = $conn->prepare($update_query);

        if ($update_stmt->execute([
            'school_id' => $school_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'course' => $course,
            'year' => $year,
            'section' => $section,
            'id' => $student_id
        ])) {
            // Redirect back to the correct tab after save
            header("Location: users.php?tab=" . urlencode($return_tab));
            exit;
        } else {
            $error = "Failed to update information. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | Laboratory Equipment Borrowing</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        /* Consistency with users.php */
        :root {
            --bg-main: #f4f6f8;
            --bg-card: #ffffff;
            --border-color: #e5e7eb;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --primary-dark: #111827;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg-main);
            margin: 0;
            padding: 0;
            color: var(--text-main);
        }

        .wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-main);
            overflow-y: auto;
        }

        .topbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .content-area {
            padding: 2rem;
            max-width: 900px; /* Kept tight for forms */
            margin: 0 auto;
            width: 100%;
        }

        /* Clean Enterprise Form Styling */
        .card-form {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .form-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            background-color: #f9fafb;
            font-size: 0.95rem;
            border-radius: 8px;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: #ffffff;
            border-color: #adb5bd;
            box-shadow: 0 0 0 4px rgba(0,0,0,0.03);
            outline: none;
        }

        /* Buttons */
        .btn-custom-outline {
            background: #ffffff;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            font-weight: 500;
        }
        .btn-custom-outline:hover { background: #f3f4f6; }

        .btn-custom-dark {
            background: var(--primary-dark);
            border: none;
            color: #ffffff;
            font-weight: 500;
        }
        .btn-custom-dark:hover { background: #1f2937; color: #ffffff; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Keep the sidebar present and active -->
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main-content" id="mainContent">

        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-3 border-0 bg-transparent text-muted fs-5"><i class="bi bi-list"></i></button>
                <h5 class="m-0 fw-bold" style="color: var(--primary-dark);">Student Directory</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--primary-dark);"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name'] ?? 'Admin') ?>&background=111827&color=fff&bold=true" class="rounded-circle shadow-sm" width="38" height="38">
            </div>
        </div>

        <div class="content-area py-5">

            <div class="mb-4 d-flex justify-content-between align-items-end border-bottom pb-3 border-light">
                <div>
                    <h4 class="m-0 fw-bold text-dark">Edit Student Record</h4>
                    <p class="text-muted small mt-1 mb-0">Update information and classification details.</p>
                </div>
            </div>

            <div class="card card-form">
                <div class="card-body p-4 p-md-5">

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 px-3 small rounded-3 border-0 bg-danger bg-opacity-10 text-danger mb-4">
                            <i class="bi bi-exclamation-circle me-1"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form id="editStudentForm" method="POST" action="">
                        <input type="hidden" name="update_student" value="1">

                        <div class="mb-4">
                            <label class="form-label">School ID</label>
                            <input type="text" class="form-control" name="school_id" value="<?= htmlspecialchars($student['school_id']) ?>" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($student['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($student['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-5">
                            <div class="col-md-4 mb-4 mb-md-0">
                                <label class="form-label">Course</label>
                                <select class="form-select" name="course" required>
                                    <option value="BSCS" <?= $student['course'] == 'BSCS' ? 'selected' : '' ?>>BSCS</option>
                                    <option value="BSIT" <?= $student['course'] == 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                                    <option value="ACT" <?= $student['course'] == 'ACT' ? 'selected' : '' ?>>ACT</option>
                                    <option value="AppDev" <?= $student['course'] == 'AppDev' ? 'selected' : '' ?>>AppDev</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-4 mb-md-0">
                                <label class="form-label">Year Level</label>
                                <select class="form-select" name="year_level" required>
                                    <option value="1" <?= $student['year_level'] == '1' ? 'selected' : '' ?>>1st</option>
                                    <option value="2" <?= $student['year_level'] == '2' ? 'selected' : '' ?>>2nd</option>
                                    <option value="3" <?= $student['year_level'] == '3' ? 'selected' : '' ?>>3rd</option>
                                    <option value="4" <?= $student['year_level'] == '4' ? 'selected' : '' ?>>4th</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Section</label>
                                <select class="form-select" name="section" required>
                                    <option value="A" <?= $student['section'] == 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= $student['section'] == 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= $student['section'] == 'C' ? 'selected' : '' ?>>C</option>
                                    <option value="D" <?= $student['section'] == 'D' ? 'selected' : '' ?>>D</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end border-top pt-4 mt-2">
                            <button type="button" class="btn btn-custom-outline px-4 me-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#discardModal">Discard Changes</button>
                            <button type="button" class="btn btn-custom-dark px-4 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#saveModal">Save Updates</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Confirmation Modal -->
<div class="modal fade" id="saveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Confirm Updates</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted py-4">
                Are you sure you want to save the updated information for this student?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4 rounded-pill" onclick="document.getElementById('editStudentForm').submit();">Confirm & Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Discard Confirmation Modal -->
<div class="modal fade" id="discardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">Discard Changes</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted py-4">
                Are you sure you want to discard your changes? Any unsaved edits will be permanently lost.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Keep Editing</button>
                <a href="users.php?tab=<?= $return_tab ?>" class="btn btn-danger px-4 rounded-pill">Discard & Exit</a>
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