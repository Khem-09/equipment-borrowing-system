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

// --- PROCESS RETURNS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_return') {
    $slip_id = (int)$_POST['slip_id'];
    $item_statuses = $_POST['item_status']; // Array of slip_item_id => condition
    $asset_ids = $_POST['asset_id']; // Array of slip_item_id => asset_id

    try {
        $conn->beginTransaction();

        $stmt_update_item = $conn->prepare("UPDATE slip_items SET return_status = ?, return_date = NOW() WHERE id = ?");
        $stmt_update_asset = $conn->prepare("UPDATE equipment_assets SET status = ? WHERE id = ?");

        $all_intact = true;

        // Loop through everything the admin inspected on the tray
        foreach ($item_statuses as $slip_item_id => $condition) {
            $a_id = $asset_ids[$slip_item_id];

            // 1. Update the record on the slip
            $stmt_update_item->execute([$condition, $slip_item_id]);

            // 2. Update the actual physical asset's status
            if ($condition === 'Returned_Intact') {
                $stmt_update_asset->execute(['Available', $a_id]);
            } elseif ($condition === 'Returned_Broken') {
                $stmt_update_asset->execute(['Broken', $a_id]);
                $all_intact = false;
            } elseif ($condition === 'Lost') {
                $stmt_update_asset->execute(['Lost', $a_id]);
                $all_intact = false;
            }
        }

        // 3. Close the Slip
        $final_slip_status = $all_intact ? 'Returned' : 'Incomplete'; // Mark Incomplete if something was broken/lost
        $stmt_close_slip = $conn->prepare("UPDATE slips SET status = ? WHERE id = ?");
        $stmt_close_slip->execute([$final_slip_status, $slip_id]);

        $conn->commit();
        $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'><i class='bi bi-check-circle me-2'></i>Slip closed successfully. Asset statuses updated.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";

    } catch (Exception $e) {
        $conn->rollBack();
        $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>Error processing return: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// --- FETCH ACTIVE SLIPS ---
$stmt = $conn->query("SELECT * FROM slips WHERE status = 'Active' ORDER BY issue_date ASC");
$active_slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch items for these slips
$slip_items = [];
if (count($active_slips) > 0) {
    // Get all slip IDs to query their items at once
    $slip_ids = array_column($active_slips, 'id');
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

    // Group items by slip_id for easy display
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
    <title>Active Returns | LabBorrow</title>
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
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Active Borrows (Return Desk)</h5>
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
            <p class="text-muted mb-4">Select an active slip to process returning equipment.</p>
            <?= $message ?>

            <div class="row g-4">
                <?php if (count($active_slips) > 0): ?>
                    <?php foreach ($active_slips as $slip): ?>
                        <div class="col-xl-4 col-lg-6">
                            <div class="card shadow-sm border-0 rounded-4 h-100 border-top border-4 border-primary">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill mb-2 border border-primary border-opacity-25"><?= $slip['slip_number'] ?></span>
                                            <h5 class="fw-bold mb-1" style="color: var(--ccs-darkest);"><?= htmlspecialchars($slip['student_name']) ?></h5>
                                            <p class="text-muted small mb-0"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($slip['student_id']) ?> &bull; <?= htmlspecialchars($slip['course_section']) ?></p>
                                        </div>
                                    </div>

                                    <hr class="bg-light">

                                    <div class="mb-3 small text-muted">
                                        <div class="mb-1"><i class="bi bi-book me-2"></i><strong>Class:</strong> <?= htmlspecialchars($slip['subject_code']) ?></div>
                                        <div class="mb-1"><i class="bi bi-person-workspace me-2"></i><strong>Prof:</strong> <?= htmlspecialchars($slip['instructor_name']) ?></div>
                                        <div class="mb-1"><i class="bi bi-clock me-2"></i><strong>Time:</strong> <?= htmlspecialchars($slip['class_time']) ?></div>
                                    </div>

                                    <div class="bg-light rounded p-3 mb-4">
                                        <h6 class="fw-bold fs-6 mb-2">Items Borrowed:</h6>
                                        <ul class="list-unstyled small mb-0 font-monospace">
                                            <?php
                                            $items_for_this_slip = $slip_items[$slip['id']] ?? [];
                                            foreach ($items_for_this_slip as $item) {
                                                echo "<li><i class='bi bi-dot'></i> <span class='text-primary fw-bold'>{$item['unique_asset_code']}</span> - {$item['category_name']}</li>";
                                            }
                                            ?>
                                        </ul>
                                    </div>

                                    <button class="btn btn-custom w-100 rounded-pill fw-bold" onclick="openReturnModal(<?= $slip['id'] ?>)">
                                        <i class="bi bi-box-arrow-in-down me-1"></i> Process Return
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-check2-circle display-1 text-success opacity-50 mb-3 d-block"></i>
                        <h4 class="text-muted">All clear!</h4>
                        <p class="text-muted">There are currently no active borrowing slips.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php foreach ($active_slips as $slip): ?>
    <div class="modal fade" id="returnModal_<?= $slip['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="" class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Process Return: <?= $slip['slip_number'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="process_return">
                    <input type="hidden" name="slip_id" value="<?= $slip['id'] ?>">

                    <div class="alert alert-info py-2 small mb-4">
                        <i class="bi bi-info-circle me-1"></i> Inspect the tray and log the condition of each item.
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Item Type</th>
                                    <th width="200">Return Condition</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $items = $slip_items[$slip['id']] ?? [];
                                foreach ($items as $item):
                                ?>
                                    <tr>
                                        <td class="font-monospace fw-bold text-primary"><?= $item['unique_asset_code'] ?></td>
                                        <td class="small fw-medium"><?= htmlspecialchars($item['category_name']) ?></td>
                                        <td>
                                            <input type="hidden" name="asset_id[<?= $item['id'] ?>]" value="<?= $item['asset_id'] ?>">

                                            <select name="item_status[<?= $item['id'] ?>]" class="form-select form-select-sm border-secondary" required>
                                                <option value="Returned_Intact" selected>✅ Intact / Good</option>
                                                <option value="Returned_Broken">❌ Broken / Damaged</option>
                                                <option value="Lost">❓ Missing / Lost</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Confirm & Close Slip</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });

    // Function to open the correct modal
    function openReturnModal(slipId) {
        var modal = new bootstrap.Modal(document.getElementById('returnModal_' + slipId));
        modal.show();
    }
</script>
</body>
</html>