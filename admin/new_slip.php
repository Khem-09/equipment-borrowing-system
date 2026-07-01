<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../classes/database.php';
$db = new Database();
$conn = $db->getConnection();

// Validate that the logged-in user actually exists in the db (handles truncated/restructured users database edge cases)
$stmt_check_user = $conn->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
$stmt_check_user->execute([$_SESSION['user_id']]);
if ($stmt_check_user->fetchColumn() == 0) {
    session_destroy();
    header("Location: ../index.php?error=SessionExpired");
    exit;
}

$message = '';
$show_form = false;
$generated_slip = null;

// --- PROCESS THE CHECKOUT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_slip'])) {
    $show_form = true;
    $student_id = trim($_POST['student_id']);
    $student_name = trim($_POST['student_name']);
    $course_section = trim($_POST['course_section']);
    $subject_code = trim($_POST['subject_code']);
    $instructor_name = trim($_POST['instructor_name']);
    $class_time = trim($_POST['class_time']);
    $processed_by = $_SESSION['user_id'];

    $asset_ids = json_decode($_POST['asset_ids'], true);

    // --- SUSPENSION CHECK ---
    $stmt_check = $conn->prepare("
        SELECT COUNT(*) as overdue_count
        FROM slip_items si
        JOIN slips s ON si.slip_id = s.id
        WHERE s.student_id = ?
          AND si.penalty_status = 'Pending'
    ");
    $stmt_check->execute([$student_id]);
    $overdue_check = $stmt_check->fetch();

    if ($overdue_check['overdue_count'] > 0) {
        $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm text-dark bg-danger bg-opacity-25 border border-danger border-opacity-50'><i class='bi bi-exclamation-triangle-fill me-2 text-danger'></i><strong>Borrowing Blocked!</strong> Student <strong>$student_id</strong> has unresolved penalties (e.g. broken or lost equipment). They must resolve these before borrowing again.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else if (empty($asset_ids)) {
        $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm text-dark bg-danger bg-opacity-25 border border-danger border-opacity-50'>You must add at least one item to the slip!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        try {
            $conn->beginTransaction();

            // Restrict checkout if the student has pending/unresolved item penalties
            $stmt_check_overdue = $conn->prepare("
                SELECT COUNT(*)
                FROM slip_items si
                JOIN slips s ON si.slip_id = s.id
                WHERE s.student_id = ? AND si.penalty_status = 'Pending'
            ");
            $stmt_check_overdue->execute([$student_id]);
            if ($stmt_check_overdue->fetchColumn() > 0) {
                throw new Exception("Student has unresolved penalties (incomplete or damaged returns) and cannot borrow equipment at this time.");
            }

            $slip_number = 'SLP-' . date('Ymd-His');

            $stmt = $conn->prepare("INSERT INTO slips (slip_number, student_id, student_name, course_section, subject_code, instructor_name, class_time, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$slip_number, $student_id, $student_name, $course_section, $subject_code, $instructor_name, $class_time, $processed_by]);
            $slip_id = $conn->lastInsertId();

            $stmt_item = $conn->prepare("INSERT INTO slip_items (slip_id, asset_id, return_status) VALUES (?, ?, 'Pending')");
            $stmt_update_asset = $conn->prepare("UPDATE equipment_assets SET status = 'Borrowed' WHERE id = ?");

            foreach ($asset_ids as $a_id) {
                $stmt_item->execute([$slip_id, $a_id]);
                $stmt_update_asset->execute([$a_id]);
            }

            // Retrieve asset details for the print/view slip display
            $stmt_get_assets = $conn->prepare("
                SELECT a.unique_asset_code, c.category_name, s.specification_name
                FROM equipment_assets a
                JOIN equipment_categories c ON a.category_id = c.id
                JOIN equipment_specifications s ON a.specification_id = s.id
                WHERE a.id IN (" . implode(',', array_fill(0, count($asset_ids), '?')) . ")
            ");
            $stmt_get_assets->execute($asset_ids);
            $borrowed_assets_details = $stmt_get_assets->fetchAll(PDO::FETCH_ASSOC);

            $generated_slip = [
                'slip_id' => $slip_id,
                'slip_number' => $slip_number,
                'student_id' => $student_id,
                'student_name' => $student_name,
                'course_section' => $course_section,
                'subject_code' => $subject_code,
                'instructor_name' => $instructor_name,
                'class_time' => $class_time,
                'assets' => $borrowed_assets_details
            ];

            $conn->commit();
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm border border-success border-opacity-50 text-dark' style='background-color: rgba(31, 125, 83, 0.15);'><strong>Success!</strong> Borrowing Slip <strong>$slip_number</strong> has been processed successfully. <a href='active_slips.php' class='alert-link fw-bold' style='color: var(--ccs-primary);'>View Active Borrows</a><button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            $show_form = false;

        } catch (Exception $e) {
            $conn->rollBack();
            $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm text-dark bg-danger bg-opacity-25 border border-danger border-opacity-50'>Transaction Failed: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// --- FETCH UNIQUE RECENT BORROWERS FOR THE TABLE ---
$borrowers_query = "SELECT s.student_id, s.student_name, s.course_section, s.subject_code AS last_subject, s.instructor_name AS last_instructor,
                           CASE
                               WHEN (SELECT COUNT(*) FROM slips s3 JOIN slip_items si ON s3.id = si.slip_id WHERE s3.student_id = s.student_id AND si.penalty_status = 'Pending') > 0 THEN 'Overdue'
                               WHEN (SELECT COUNT(*) FROM slips s3 WHERE s3.student_id = s.student_id AND s3.status = 'Active') > 0 THEN 'Active'
                               ELSE 'Cleared'
                           END AS borrower_status
                    FROM slips s
                    WHERE s.id = (
                        SELECT MAX(s2.id)
                        FROM slips s2
                        WHERE s2.student_id = s.student_id
                    )
                    ORDER BY s.id DESC";
$borrowers_stmt = $conn->query($borrowers_query);
$borrowers = $borrowers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Extract unique courses for filtering
$courses = array_filter(array_unique(array_column($borrowers, 'course_section')));
sort($courses);

// Extract unique instructors for filtering
$instructors = array_filter(array_unique(array_column($borrowers, 'last_instructor')));
sort($instructors);

// --- FETCH AVAILABLE ASSETS ---
$query = "SELECT a.id, a.unique_asset_code, c.category_name, s.specification_name
          FROM equipment_assets a
          JOIN equipment_categories c ON a.category_id = c.id
          JOIN equipment_specifications s ON a.specification_id = s.id
          WHERE a.status = 'Available'
          ORDER BY c.category_name ASC, s.specification_name ASC, a.unique_asset_code ASC";
$stmt = $conn->query($query);
$available_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group assets by category for the Browse Modal
$grouped_assets = [];
foreach ($available_assets as $asset) {
    $grouped_assets[$asset['category_name']][] = $asset;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Borrowing Slip | LabBorrow</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        /* Small hover effect for the catalog items */
        .catalog-item { cursor: pointer; transition: 0.2s; }
        .catalog-item:hover { background-color: var(--ccs-primary) !important; color: white !important; border-color: var(--ccs-primary) !important; }

        /* Elegant pointer cursor & brand secondary brand hover tint for the table rows */
        .cursor-pointer tr { cursor: pointer; transition: background-color 0.15s ease; }
        .cursor-pointer tr:hover { background-color: rgba(26, 100, 67, 0.08) !important; }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4"><i class="bi bi-list"></i></button>
                <h5 class="m-0 fw-bold">Borrowing Slips</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            <?= $message ?>

            <!-- Table View Container -->
            <div id="tableViewContainer" class="<?= $show_form ? 'd-none' : '' ?>">
                <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col">
                            <h5 class="fw-bold mb-1 text-dark">Quick Borrowers Directory</h5>
                            <p class="text-muted mb-0 small">Select any previous borrower below to start checkout, or register a new transaction slip.</p>
                        </div>
                        <div class="col-auto">
                            <button type="button" id="btnNewSlip" class="btn btn-custom rounded-pill px-4 shadow-sm">
                                <i class="bi bi-file-earmark-plus me-1"></i> New Borrowing Slip
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Search Bar -->
                        <div class="col-lg-4 col-md-12">
                            <label for="borrowerSearch" class="form-label text-muted small fw-bold mb-1">Search</label>
                            <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border">
                                <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="borrowerSearch" class="form-control border-0 bg-light-time" placeholder="Search ID, Name...">
                            </div>
                        </div>

                        <!-- Course Filter -->
                        <div class="col-lg-3 col-md-4">
                            <label for="courseFilter" class="form-label text-muted small fw-bold mb-1">Course & Section</label>
                            <select id="courseFilter" class="form-select form-select-sm shadow-sm rounded-pill border">
                                <option value="">All Courses & Sections</option>
                                <?php foreach($courses as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-lg-2 col-md-4">
                            <label for="statusFilter" class="form-label text-muted small fw-bold mb-1">Status</label>
                            <select id="statusFilter" class="form-select form-select-sm shadow-sm rounded-pill border">
                                <option value="">All Statuses</option>
                                <option value="Active">Active (Borrowed)</option>
                                <option value="Cleared">Cleared (Returned)</option>
                                <option value="Overdue">Overdue (Incomplete/Damaged)</option>
                            </select>
                        </div>

                        <!-- Instructor Filter -->
                        <div class="col-lg-3 col-md-4">
                            <label for="instructorFilter" class="form-label text-muted small fw-bold mb-1">Instructor</label>
                            <select id="instructorFilter" class="form-select form-select-sm shadow-sm rounded-pill border">
                                <option value="">All Instructors</option>
                                <?php foreach($instructors as $inst): ?>
                                    <option value="<?= htmlspecialchars($inst) ?>"><?= htmlspecialchars($inst) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-card shadow-sm border-0 bg-white rounded-4">
                    <div class="table-responsive">
                       <table class="table table-hover align-middle mb-0">
                            <thead class="bg-transparent text-dark fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                                <tr>
                                    <th class="border-bottom-0 pb-3 ps-4 pt-4 fw-bold text-dark">Student ID</th>
                                    <th class="border-bottom-0 pb-3 pt-4 fw-bold text-dark">Full Name</th>
                                    <th class="border-bottom-0 pb-3 pt-4 fw-bold text-dark">Course & Section</th>
                                    <th class="border-bottom-0 pb-3 pt-4 fw-bold text-dark">Last Subject</th>
                                    <th class="border-bottom-0 pb-3 pt-4 fw-bold text-dark">Last Instructor</th>
                                    <th class="border-bottom-0 pb-3 pe-4 pt-4 fw-bold text-dark text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="cursor-pointer" id="borrowersTableBody">
                                <?php if (count($borrowers) > 0): ?>
                                    <?php foreach ($borrowers as $row): ?>
                                    <tr onclick="autofillBorrower('<?= htmlspecialchars($row['student_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['student_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['course_section'], ENT_QUOTES) ?>', '<?= htmlspecialchars($row['borrower_status']) ?>')"
                                        data-status="<?= htmlspecialchars($row['borrower_status']) ?>"
                                        data-instructor="<?= htmlspecialchars($row['last_instructor'], ENT_QUOTES) ?>">
                                        <td class="ps-4 font-monospace text-secondary student-id-col">
                                            <?= htmlspecialchars($row['student_id']) ?>
                                        </td>
                                        <td class="text-secondary student-name-col">
                                            <?= htmlspecialchars($row['student_name']) ?>
                                        </td>
                                        <td class="text-secondary student-course-col">
                                            <?= htmlspecialchars($row['course_section']) ?>
                                        </td>
                                        <td class="text-muted student-subject-col">
                                            <?= htmlspecialchars($row['last_subject']) ?>
                                        </td>
                                        <td class="text-muted student-instructor-col">
                                            <?= htmlspecialchars($row['last_instructor']) ?>
                                        </td>
                                        <td class="pe-4 text-center student-status-col">
                                            <?php if ($row['borrower_status'] === 'Active'): ?>
                                                <span class="badge bg-danger rounded-pill px-3">Active</span>
                                            <?php elseif ($row['borrower_status'] === 'Overdue'): ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-3">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-success rounded-pill px-3">Cleared</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-5 text-uppercase fw-semibold" style="letter-spacing: 0.5px;">No previous borrowers found.</td></tr>
                                <?php endif; ?>
                                <tr id="noResultsRow" class="d-none">
                                    <td colspan="6" class="text-center text-muted py-5 fw-medium">
                                        <i class="bi bi-search fs-4 d-block mb-2"></i>
                                        No matching borrowers found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Form View Container -->
            <div id="formViewContainer" class="<?= $show_form ? '' : 'd-none' ?>">
                <div class="d-flex align-items-center mb-4">
                    <button type="button" id="btnBackToList" class="btn btn-light border rounded-pill px-3 shadow-sm me-3">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </button>
                    <div>
                        <h4 class="fw-bold mb-0" style="color: var(--ccs-darkest);">New Borrowing Slip Form</h4>
                        <p class="text-muted small mb-0">Fill out details and add available equipment to generate a checkout slip.</p>
                    </div>
                </div>

                <form method="POST" action="" id="slipForm">
                    <div class="row g-4">

                        <div class="col-lg-5">
                            <div class="card shadow-sm border-0 rounded-4 mb-4">
                                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                                    <h6 class="fw-bold" style="color: var(--ccs-primary);"><i class="bi bi-person-badge me-2"></i>Borrower Details</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">STUDENT ID</label>
                                        <input type="text" name="student_id" id="student_id_input" class="form-control bg-light" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">FULL NAME</label>
                                        <input type="text" name="student_name" id="student_name_input" class="form-control bg-light" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">COURSE & SECTION</label>
                                        <input type="text" name="course_section" id="course_section_input" class="form-control bg-light" placeholder="e.g. BSChem-2A" required>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm border-0 rounded-4">
                                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                                    <h6 class="fw-bold" style="color: var(--ccs-primary);"><i class="bi bi-journal-bookmark me-2"></i>Class Details</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">SUBJECT CODE</label>
                                        <input type="text" name="subject_code" id="subject_code_input" class="form-control bg-light" placeholder="e.g. CHEM201" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">INSTRUCTOR NAME</label>
                                        <input type="text" name="instructor_name" class="form-control bg-light" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">CLASS TIME</label>
                                        <input type="hidden" name="class_time" id="class_time_hidden" required>

                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="input-group input-group-sm shadow-sm">
                                                    <span class="input-group-text bg-white text-muted"><i class="bi bi-clock"></i></span>
                                                    <input type="time" id="class_start_time" class="form-control border-start-0 ps-0 bg-light-time" required style="font-size: 0.85rem;">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="input-group input-group-sm shadow-sm">
                                                    <span class="input-group-text bg-white text-muted">to</span>
                                                    <input type="time" id="class_end_time" class="form-control border-start-0 bg-light-time" required style="font-size: 0.85rem;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card shadow-sm border-0 rounded-4 h-100 d-flex flex-column">
                                <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0" style="color: var(--ccs-primary);"><i class="bi bi-cart3 me-2"></i>Equipment Cart</h6>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#browseModal">
                                        <i class="bi bi-search me-1"></i> Browse Catalog
                                    </button>
                                </div>

                                <div class="card-body p-4 flex-grow-1 d-flex flex-column">

                                    <div class="mb-4">
                                        <label class="form-label text-muted small fw-bold">SCAN OR TYPE ASSET CODE</label>
                                        <div class="input-group shadow-sm rounded-pill overflow-hidden">
                                            <input type="text" id="assetInput" list="assetSuggestions" class="form-control border-0 bg-light px-4" placeholder="e.g., FLASK-001" autocomplete="off">
                                            <datalist id="assetSuggestions">
                                                </datalist>

                                            <button type="button" id="addBtn" class="btn btn-custom px-4"><i class="bi bi-plus-lg"></i> Add</button>
                                        </div>
                                        <div id="scanError" class="text-danger small mt-2 d-none"><i class="bi bi-exclamation-circle me-1"></i> Asset not found or already in cart.</div>
                                    </div>

                                    <div class="table-responsive flex-grow-1 border rounded bg-light p-2 mb-4" style="min-height: 250px;">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="text-muted small text-uppercase">
                                                <tr>
                                                    <th>Asset Code</th>
                                                    <th>Category</th>
                                                    <th class="text-end">Remove</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cartBody">
                                                <tr id="emptyCartRow"><td colspan="3" class="text-center text-muted py-5">Cart is empty. Scan or browse items.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <input type="hidden" name="asset_ids" id="hiddenAssetIds" value="[]">

                                    <button type="submit" name="process_slip" class="btn btn-custom btn-lg w-100 rounded-pill shadow-sm mt-auto fw-bold" id="submitBtn" disabled>
                                        <i class="bi bi-printer me-2"></i> Process & Generate Slip
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="browseModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);"><i class="bi bi-boxes me-2"></i>Available Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-4" id="catalogContainer">

                <?php if(empty($grouped_assets)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-exclamation-circle fs-1 d-block mb-3"></i>
                        No equipment is currently available.
                    </div>
                <?php else: ?>
                    <?php foreach($grouped_assets as $category => $assets): ?>
                        <div class="mb-4 catalog-group">
                            <h6 class="fw-bold text-muted small text-uppercase mb-2 border-bottom pb-1"><?= htmlspecialchars($category) ?></h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach($assets as $asset): ?>
                                    <div class="badge bg-white text-dark border p-2 catalog-item shadow-sm"
                                         id="catalog-item-<?= $asset['id'] ?>"
                                         onclick="addFromCatalog('<?= htmlspecialchars($asset['unique_asset_code']) ?>')">
                                        <i class="bi bi-qr-code me-1"></i> <strong class="text-primary"><?= htmlspecialchars($asset['unique_asset_code']) ?></strong> <span class="ms-1 text-muted fw-normal">(<?= htmlspecialchars($asset['specification_name']) ?>)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php if ($generated_slip): ?>
<div class="modal fade" id="generatedSlipModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-0" id="generatedSlipContent">
                <!-- PRINT CSS OVERLAY -->
                <style>
                    @media print {
                        .wrapper, #browseModal, .modal-backdrop, .btn-close {
                            display: none !important;
                        }
                        .no-print {
                            display: none !important;
                        }
                        #generatedSlipModal {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 100%;
                            margin: 0 !important;
                            padding: 0 !important;
                            background: transparent !important;
                            box-shadow: none !important;
                            border: none !important;
                        }
                        #generatedSlipModal .modal-dialog {
                            max-width: 100% !important;
                            width: 100% !important;
                            margin: 0 !important;
                            padding: 0 !important;
                        }
                        #generatedSlipModal .modal-content {
                            box-shadow: none !important;
                            border: none !important;
                            background: transparent !important;
                        }
                        #generatedSlipContentPrintable {
                            padding: 10px;
                        }
                    }
                    .receipt-dash {
                        border-top: 2px dashed #e9ecef;
                        height: 1px;
                        margin: 20px 0;
                    }
                </style>

                <div id="generatedSlipContentPrintable">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-2" style="width: 60px; height: 60px;">
                            <i class="bi bi-receipt fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1" style="color: var(--ccs-darkest);">LabBorrow Equipment Slip</h4>
                        <span class="badge bg-success bg-gradient px-3 rounded-pill">Borrowing Active</span>
                        <div class="font-monospace text-muted mt-2 fw-medium" style="font-size: 0.9rem;"><?= htmlspecialchars($generated_slip['slip_number']) ?></div>
                    </div>

                    <div class="receipt-dash"></div>

                    <!-- Student & Class Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Student ID</span>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($generated_slip['student_id']) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Student Name</span>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($generated_slip['student_name']) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Course & Section</span>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($generated_slip['course_section']) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Subject Code</span>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($generated_slip['subject_code']) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Instructor</span>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($generated_slip['instructor_name']) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Class Time</span>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($generated_slip['class_time']) ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Borrow Date</span>
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= date('F j, Y') ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small text-uppercase fw-semibold" style="font-size: 0.75rem;">Return Deadline</span>
                            <span class="fw-bold text-danger" style="font-size: 0.9rem;"><i class="bi bi-clock-history me-1"></i><?= date('F j, Y') ?> (Today)</span>
                        </div>
                    </div>

                    <div class="receipt-dash"></div>

                    <!-- Equipment List -->
                    <h6 class="fw-bold text-muted small text-uppercase mb-3" style="font-size: 0.75rem;">Borrowed Equipment</h6>
                    <div class="border rounded bg-light p-3 mb-4">
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($generated_slip['assets'] as $idx => $asset): ?>
                                <li class="d-flex justify-content-between align-items-start <?= $idx > 0 ? 'mt-2 pt-2 border-top' : '' ?>">
                                    <div>
                                        <span class="fw-bold text-dark" style="font-size: 0.85rem;"><?= htmlspecialchars($asset['unique_asset_code']) ?></span>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($asset['category_name']) ?></div>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill"><?= htmlspecialchars($asset['specification_name']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="receipt-dash"></div>

                    <!-- QR Code and Stamp -->
                    <div class="text-center mt-4">
                        <span class="text-muted d-block small text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Scan QR to Return</span>
                        <div class="d-inline-block bg-white p-2 rounded shadow-sm border mb-2">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&color=1f7d53&data=<?= urlencode($generated_slip['slip_number']) ?>" alt="QR Code" width="140" height="140">
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">Issued via LabBorrow system. Keep this slip until returned.</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4 no-print">
                    <button type="button" class="btn btn-outline-secondary w-100 rounded-pill py-2" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-custom w-100 rounded-pill py-2" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print Slip</button>
                </div>

            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });

    // Toggle views
    const btnNewSlip = document.getElementById('btnNewSlip');
    const btnBackToList = document.getElementById('btnBackToList');
    const tableViewContainer = document.getElementById('tableViewContainer');
    const formViewContainer = document.getElementById('formViewContainer');

    if (btnNewSlip) {
        btnNewSlip.addEventListener('click', function() {
            // When opening a completely new slip manually, clear any autofilled fields to avoid confusion
            document.getElementById('student_id_input').value = '';
            document.getElementById('student_name_input').value = '';
            document.getElementById('course_section_input').value = '';

            tableViewContainer.classList.add('d-none');
            formViewContainer.classList.remove('d-none');
        });
    }

    if (btnBackToList) {
        btnBackToList.addEventListener('click', function() {
            tableViewContainer.classList.remove('d-none');
            formViewContainer.classList.add('d-none');
        });
    }

    // Autofill Borrower details on row click and redirect/switch views
    function autofillBorrower(studentId, fullName, courseSection, status) {
        if (status === 'Overdue') {
            alert("Blocked: This student has OVERDUE items (damaged or incomplete returns) and is restricted from borrowing new equipment until cleared.");
            return;
        }

        document.getElementById('student_id_input').value = studentId;
        document.getElementById('student_name_input').value = fullName;
        document.getElementById('course_section_input').value = courseSection;

        tableViewContainer.classList.add('d-none');
        formViewContainer.classList.remove('d-none');

        // Focus the next section (Subject Code) for quick input
        document.getElementById('subject_code_input').focus();
    }

    // Main arrays
    let availableAssets = <?= json_encode($available_assets) ?>;
    let cart = [];

    const assetInput = document.getElementById('assetInput');
    const datalist = document.getElementById('assetSuggestions');
    const addBtn = document.getElementById('addBtn');
    const scanError = document.getElementById('scanError');
    const cartBody = document.getElementById('cartBody');
    const hiddenAssetIds = document.getElementById('hiddenAssetIds');
    const submitBtn = document.getElementById('submitBtn');
    const emptyCartRow = document.getElementById('emptyCartRow');

    // Populate the HTML Datalist for autocomplete
    function updateDatalist() {
        datalist.innerHTML = '';
        availableAssets.forEach(asset => {
            let option = document.createElement('option');
            option.value = asset.unique_asset_code;
            option.text = asset.category_name;
            datalist.appendChild(option);
        });
    }

    // Function to add item to cart (From Input OR Catalog)
    window.addFromCatalog = function(codeToSearch) {
        // If coming from a catalog click, close the modal first (optional, but clean)
        let browseModal = bootstrap.Modal.getInstance(document.getElementById('browseModal'));
        if(browseModal) browseModal.hide();

        processCode(codeToSearch);
    };

    function processCode(code) {
        if (!code) return;

        const assetIndex = availableAssets.findIndex(a => a.unique_asset_code.toUpperCase() === code.toUpperCase());

        if (assetIndex !== -1) {
            const item = availableAssets[assetIndex];
            cart.push(item);

            // Remove from JS available array
            availableAssets.splice(assetIndex, 1);

            // Hide the item in the visual Catalog Modal so they can't click it again
            const catalogItem = document.getElementById('catalog-item-' + item.id);
            if(catalogItem) catalogItem.style.display = 'none';

            updateCartUI();
            updateDatalist();

            assetInput.value = '';
            scanError.classList.add('d-none');
            assetInput.focus();
        } else {
            scanError.classList.remove('d-none');
        }
    }

    // Event listener for the "Add" button and Enter key
    addBtn.addEventListener('click', () => processCode(assetInput.value.trim()));
    assetInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            processCode(assetInput.value.trim());
        }
    });

    // Remove from Cart
    window.removeFromCart = function(cartIndex) {
        const item = cart[cartIndex];

        availableAssets.push(item);

        // Un-hide it in the Catalog Modal
        const catalogItem = document.getElementById('catalog-item-' + item.id);
        if(catalogItem) catalogItem.style.display = 'inline-block';

        cart.splice(cartIndex, 1);

        updateCartUI();
        updateDatalist();
    };

    function updateCartUI() {
        cartBody.innerHTML = '';

        if (cart.length === 0) {
            cartBody.appendChild(emptyCartRow);
            submitBtn.disabled = true;
            hiddenAssetIds.value = "[]";
            return;
        }

        submitBtn.disabled = false;
        let idsForPHP = [];

        cart.forEach((item, index) => {
            idsForPHP.push(item.id);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="font-monospace fw-bold" style="color: var(--ccs-primary);">${item.unique_asset_code}</td>
                <td class="small fw-medium">${item.category_name}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="removeFromCart(${index})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            `;
            cartBody.appendChild(tr);
        });

        hiddenAssetIds.value = JSON.stringify(idsForPHP);
    }

    // Initialize datalist on page load
    updateDatalist();
    assetInput.focus();

    // Borrower Directory multi-filtering (Search, Course, Status, & Instructor)
    const borrowerSearch = document.getElementById('borrowerSearch');
    const courseFilter = document.getElementById('courseFilter');
    const statusFilter = document.getElementById('statusFilter');
    const instructorFilter = document.getElementById('instructorFilter');

    function filterBorrowers() {
        const query = borrowerSearch ? borrowerSearch.value.toLowerCase().trim() : '';
        const courseVal = courseFilter ? courseFilter.value.toLowerCase().trim() : '';
        const statusVal = statusFilter ? statusFilter.value.toLowerCase().trim() : '';
        const instructorVal = instructorFilter ? instructorFilter.value.toLowerCase().trim() : '';

        const rows = document.querySelectorAll('#borrowersTableBody tr');
        let matchedAny = false;

        rows.forEach(row => {
            if (row.id === 'noResultsRow') return;

            const studentId = row.querySelector('.student-id-col')?.textContent.toLowerCase() || '';
            const fullName = row.querySelector('.student-name-col')?.textContent.toLowerCase() || '';
            const courseSection = row.querySelector('.student-course-col')?.textContent.toLowerCase() || '';
            const lastSubject = row.querySelector('.student-subject-col')?.textContent.toLowerCase() || '';
            const instructor = row.getAttribute('data-instructor')?.toLowerCase() || '';
            const status = row.getAttribute('data-status')?.toLowerCase() || '';

            const matchesQuery = query === '' ||
                                 studentId.includes(query) ||
                                 fullName.includes(query) ||
                                 courseSection.includes(query) ||
                                 lastSubject.includes(query) ||
                                 instructor.includes(query) ||
                                 status.includes(query);

            const matchesCourse = courseVal === '' || courseSection.trim() === courseVal.trim();
            const matchesStatus = statusVal === '' || status === statusVal;
            const matchesInstructor = instructorVal === '' || instructor === instructorVal;

            if (matchesQuery && matchesCourse && matchesStatus && matchesInstructor) {
                row.classList.remove('d-none');
                matchedAny = true;
            } else {
                row.classList.add('d-none');
            }
        });

        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            if (!matchedAny && (query !== '' || courseVal !== '' || statusVal !== '' || instructorVal !== '')) {
                noResultsRow.classList.remove('d-none');
            } else {
                noResultsRow.classList.add('d-none');
            }
        }
    }

    if (borrowerSearch) {
        borrowerSearch.addEventListener('input', filterBorrowers);
    }
    if (courseFilter) {
        courseFilter.addEventListener('change', filterBorrowers);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterBorrowers);
    }
    if (instructorFilter) {
        instructorFilter.addEventListener('change', filterBorrowers);
    }

    // Class Time-Calendar format builder
    const classStartTime = document.getElementById('class_start_time');
    const classEndTime = document.getElementById('class_end_time');
    const classTimeHidden = document.getElementById('class_time_hidden');

    function updateClassTimeString() {
        const startVal = classStartTime ? classStartTime.value : '';
        const endVal = classEndTime ? classEndTime.value : '';

        if (startVal && endVal) {
            const formatTime = (timeStr) => {
                const parts = timeStr.split(':');
                let hour = parseInt(parts[0], 10);
                const minutes = parts[1];
                let period = 'AM';
                if (hour >= 12) {
                    period = 'PM';
                    if (hour > 12) hour -= 12;
                }
                if (hour === 0) hour = 12;
                return `${hour}:${minutes} ${period}`;
            };

            const formattedStart = formatTime(startVal);
            const formattedEnd = formatTime(endVal);

            // e.g. "1:00 PM - 4:00 PM"
            classTimeHidden.value = `${formattedStart} - ${formattedEnd}`;
        } else {
            classTimeHidden.value = '';
        }
    }

    if (classStartTime) classStartTime.addEventListener('change', updateClassTimeString);
    if (classEndTime) classEndTime.addEventListener('change', updateClassTimeString);

    // Map student ID to status to restrict manual entry of overdue students
    const borrowerStatusMap = <?= json_encode(array_column($borrowers, 'borrower_status', 'student_id')) ?>;

    const slipForm = document.getElementById('slipForm');
    if (slipForm) {
        slipForm.addEventListener('submit', function(e) {
            const enteredId = document.getElementById('student_id_input').value.trim();
            if (borrowerStatusMap[enteredId] === 'Overdue') {
                e.preventDefault();
                alert("Blocked: This student has OVERDUE items (damaged or incomplete returns) and is restricted from borrowing new equipment until cleared.");
                return false;
            }
        });
    }

    // Auto-trigger generated slip receipt modal if present
    <?php if ($generated_slip): ?>
    document.addEventListener("DOMContentLoaded", function() {
        var generatedModal = new bootstrap.Modal(document.getElementById('generatedSlipModal'));
        generatedModal.show();
    });
    <?php endif; ?>
</script>
</body>
</html>