<?php
session_start();

// Protect the page: Only Admins allowed
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

// --- FETCH HISTORY SLIPS ---
// Fetch slips that are 'Returned' or 'Incomplete', along with the name of the Admin who processed it
$query = "
    SELECT s.*, u.full_name as admin_name
    FROM slips s
    LEFT JOIN users u ON s.processed_by = u.id
    WHERE s.status IN ('Returned', 'Incomplete')
    ORDER BY s.issue_date DESC
";
$stmt = $conn->query($query);
$history_slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch items for these slips
$slip_items = [];
if (count($history_slips) > 0) {
    $slip_ids = array_column($history_slips, 'id');
    $placeholders = str_repeat('?,', count($slip_ids) - 1) . '?';

    $query_items = "
        SELECT si.*, a.unique_asset_code, c.category_name
        FROM slip_items si
        JOIN equipment_assets a ON si.asset_id = a.id
        JOIN equipment_categories c ON a.category_id = c.id
        WHERE si.slip_id IN ($placeholders)
    ";
    $stmt_items = $conn->prepare($query_items);
    $stmt_items->execute($slip_ids);
    $all_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Group items by slip_id
    foreach ($all_items as $item) {
        $slip_items[$item['slip_id']][] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip History | LabBorrow</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">

        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4 btn btn-light border-0"><i class="bi bi-list fs-4"></i></button>
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Borrowing History</h5>
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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="text-muted mb-0">A permanent ledger of all completed and incomplete borrowing transactions.</p>
            </div>

            <div class="table-card shadow-sm border-0 bg-white rounded-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Slip No.</th>
                                <th class="border-bottom-0 pb-3 pt-4">Student Info</th>
                                <th class="border-bottom-0 pb-3 pt-4">Subject</th>
                                <th class="border-bottom-0 pb-3 pt-4">Issue Date</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">Status</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($history_slips) > 0): ?>
                                <?php foreach ($history_slips as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bolder font-monospace text-primary">
                                        <?= htmlspecialchars($row['slip_number']) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold" style="color: var(--ccs-darkest);"><?= htmlspecialchars($row['student_name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($row['student_id']) ?> &bull; <?= htmlspecialchars($row['course_section']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark"><?= htmlspecialchars($row['subject_code']) ?></div>
                                        <div class="text-muted small">Prof. <?= htmlspecialchars($row['instructor_name']) ?></div>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('M d, Y', strtotime($row['issue_date'])) ?><br>
                                        <?= date('h:i A', strtotime($row['issue_date'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'Returned'): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2 fw-semibold shadow-sm">
                                                <i class="bi bi-check-circle me-1"></i> Completed
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2 fw-semibold shadow-sm">
                                                <i class="bi bi-exclamation-triangle me-1"></i> Incomplete
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-light border rounded-pill px-3 text-primary fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#viewModal_<?= $row['id'] ?>">
                                            <i class="bi bi-eye me-1"></i> View Log
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-5">No borrowing history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DYNAMIC VIEW MODALS -->
<?php foreach ($history_slips as $slip): ?>
    <div class="modal fade" id="viewModal_<?= $slip['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom pb-3">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Transaction Log: <span class="font-monospace text-primary"><?= $slip['slip_number'] ?></span></h5>
                        <div class="small text-muted">Processed by Admin: <?= htmlspecialchars($slip['admin_name']) ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted small text-uppercase">Student Details</h6>
                            <div class="bg-white p-3 rounded border">
                                <strong><?= htmlspecialchars($slip['student_name']) ?></strong><br>
                                ID: <?= htmlspecialchars($slip['student_id']) ?><br>
                                Section: <?= htmlspecialchars($slip['course_section']) ?>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <h6 class="fw-bold text-muted small text-uppercase">Class Info</h6>
                            <div class="bg-white p-3 rounded border">
                                <strong><?= htmlspecialchars($slip['subject_code']) ?></strong><br>
                                Instructor: <?= htmlspecialchars($slip['instructor_name']) ?><br>
                                Time: <?= htmlspecialchars($slip['class_time']) ?>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted small text-uppercase mb-3">Returned Equipment Condition</h6>
                    <div class="table-responsive bg-white rounded border">
                        <table class="table align-middle mb-0">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Item Type</th>
                                    <th>Time Returned</th>
                                    <th>Condition Logged</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $items = $slip_items[$slip['id']] ?? [];
                                foreach ($items as $item):
                                ?>
                                    <tr>
                                        <td class="font-monospace fw-bold text-dark"><?= $item['unique_asset_code'] ?></td>
                                        <td class="small fw-medium"><?= htmlspecialchars($item['category_name']) ?></td>
                                        <td class="small text-muted">
                                            <?= $item['return_date'] ? date('M d, h:i A', strtotime($item['return_date'])) : 'N/A' ?>
                                        </td>
                                        <td>
                                            <?php if ($item['return_status'] === 'Returned_Intact'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-lg me-1"></i> Intact</span>
                                            <?php elseif ($item['return_status'] === 'Returned_Broken'): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-lg me-1"></i> Broken</span>
                                            <?php elseif ($item['return_status'] === 'Lost'): ?>
                                                <span class="badge bg-dark bg-opacity-10 text-dark border border-dark"><i class="bi bi-question-lg me-1"></i> Lost</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= $item['return_status'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer border-top-0 pt-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <!-- Optional: You can add a window.print() trigger here if you want to allow re-printing slips -->
                    <button type="button" class="btn btn-custom rounded-pill px-4" onclick="window.print()"><i class="bi bi-printer me-2"></i> Print Log</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });
</script>
</body>
</html>