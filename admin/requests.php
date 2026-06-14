<?php

require_once '../classes/database.php';
$db = new Database();
$conn = $db->getConnection();

$message = '';

// --- PROCESS ACTIONS (Approve, Reject, Mark Returned) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $transaction_id = (int)$_POST['transaction_id'];
    $equipment_id = (int)$_POST['equipment_id'];

    if ($action === 'approve') {
        try {
            $conn->beginTransaction();
            // Check stock
            $stmt = $conn->prepare("UPDATE equipment SET stock_quantity = stock_quantity - 1 WHERE id = ? AND stock_quantity > 0");
            $stmt->execute([$equipment_id]);
            
            if ($stmt->rowCount() > 0) {
                $stmt2 = $conn->prepare("UPDATE transactions SET status = 'Approved' WHERE id = ?");
                $stmt2->execute([$transaction_id]);
                $conn->commit();
                $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'>Request approved and stock deducted!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $conn->rollBack();
                $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>Cannot approve: Item out of stock.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>Error processing request.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE transactions SET status = 'Rejected' WHERE id = ?");
        if ($stmt->execute([$transaction_id])) {
            $message = "<div class='alert alert-secondary alert-dismissible fade show shadow-sm mb-4'>Request has been rejected.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } elseif ($action === 'return') {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("UPDATE transactions SET status = 'Returned', actual_return_date = NOW() WHERE id = ?");
            $stmt->execute([$transaction_id]);
            
            $stmt2 = $conn->prepare("UPDATE equipment SET stock_quantity = stock_quantity + 1 WHERE id = ?");
            $stmt2->execute([$equipment_id]);
            
            $conn->commit();
            $message = "<div class='alert alert-info alert-dismissible fade show shadow-sm mb-4'>Item marked as returned and stock restored.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>Error returning item.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// --- FETCH ALL TRANSACTIONS & GROUP THEM ---
$query = "
    SELECT 
        t.id AS transaction_id, t.request_date, t.expected_return_date, t.status, 
        u.school_id, u.full_name, 
        e.id AS equipment_id, e.item_name 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN equipment e ON t.equipment_id = e.id
    ORDER BY t.request_date DESC
";
$stmt = $conn->query($query);
$all_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending = [];
$active = [];
$history = [];

foreach ($all_requests as $row) {
    if ($row['status'] === 'Pending') {
        $pending[] = $row;
    } elseif ($row['status'] === 'Approved' || $row['status'] === 'Overdue') {
        $active[] = $row;
    } else {
        $history[] = $row;
    }
}

// Function to render table rows to keep the HTML clean
function renderTableRows($rows, $showActions = false) {
    if (count($rows) === 0) {
        echo "<tr><td colspan='5' class='text-center text-muted py-4'>No records found in this category.</td></tr>";
        return;
    }
    
    foreach ($rows as $row) {
        $dateFormatted = date('M d, Y', strtotime($row['expected_return_date'])) . "<br>" . date('h:i A', strtotime($row['expected_return_date']));
        $statusColor = 'text-muted';
        if ($row['status'] === 'Overdue') $statusColor = 'text-danger fw-bold';
        if ($row['status'] === 'Approved') $statusColor = 'text-success';
        
        echo "<tr>";
        echo "  <td class='ps-4'>";
        echo "      <div class='fw-bold' style='color: var(--ccs-darkest);'>" . htmlspecialchars($row['full_name']) . "</div>";
        echo "      <div class='text-muted small'>" . htmlspecialchars($row['school_id']) . "</div>";
        echo "  </td>";
        echo "  <td><span class='fw-medium'>" . htmlspecialchars($row['item_name']) . "</span></td>";
        
        // Note: 'Purpose' isn't in our DB yet, using a placeholder for now
        echo "  <td class='text-muted small' style='max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>Academic requirement.</td>";
        
        echo "  <td class='small $statusColor'>{$dateFormatted}</td>";
        echo "  <td class='pe-4 text-end'>";
        
        if ($row['status'] === 'Pending') {
            echo "      <form method='POST' class='d-inline'>
                            <input type='hidden' name='transaction_id' value='{$row['transaction_id']}'>
                            <input type='hidden' name='equipment_id' value='{$row['equipment_id']}'>
                            <button type='submit' name='action' value='approve' class='btn btn-sm btn-custom rounded px-3 me-1 shadow-sm' style='background-color: var(--ccs-primary); color: white;'><i class='bi bi-check2'></i> Approve</button>
                        </form>";
            echo "      <form method='POST' class='d-inline'>
                            <input type='hidden' name='transaction_id' value='{$row['transaction_id']}'>
                            <input type='hidden' name='equipment_id' value='{$row['equipment_id']}'>
                            <button type='submit' name='action' value='reject' class='btn btn-sm btn-light border rounded px-3 text-danger fw-medium shadow-sm'><i class='bi bi-x-lg'></i></button>
                        </form>";
        } elseif ($row['status'] === 'Approved' || $row['status'] === 'Overdue') {
            echo "      <form method='POST' class='d-inline' onsubmit=\"return confirm('Mark as returned?');\">
                            <input type='hidden' name='transaction_id' value='{$row['transaction_id']}'>
                            <input type='hidden' name='equipment_id' value='{$row['equipment_id']}'>
                            <button type='submit' name='action' value='return' class='btn btn-sm border rounded px-3 text-success fw-medium shadow-sm'><i class='bi bi-arrow-return-left'></i> Mark Returned</button>
                        </form>";
        } else {
             echo "     <span class='badge bg-light text-dark border'>" . htmlspecialchars($row['status']) . "</span>";
        }
        
        echo "  </td>";
        echo "</tr>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests | Laboratory Equipment Borrowing</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4 btn btn-light border-0"><i class="bi bi-list fs-4"></i></button>
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Borrowing Requests</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--ccs-darkest);"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>

        <div class="filter-bar border-bottom">
            <ul class="nav nav-pills mb-0" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4" id="pills-pending-tab" data-bs-toggle="pill" data-bs-target="#pills-pending" type="button" role="tab">
                        Pending <span class="badge bg-danger ms-1 rounded-pill"><?= count($pending) ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4" id="pills-active-tab" data-bs-toggle="pill" data-bs-target="#pills-active" type="button" role="tab">
                        Active Borrows
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4" id="pills-history-tab" data-bs-toggle="pill" data-bs-target="#pills-history" type="button" role="tab">
                        Returned / History
                    </button>
                </li>
            </ul>
        </div>

        <div class="content-area p-4 p-md-5">
            <?= $message ?>
            
            <div class="table-card shadow-sm border-0 bg-white rounded">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Student Info</th>
                                <th class="border-bottom-0 pb-3 pt-4">Equipment</th>
                                <th class="border-bottom-0 pb-3 pt-4">Purpose</th>
                                <th class="border-bottom-0 pb-3 pt-4">Return Due</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Action</th>
                            </tr>
                        </thead>
                        
                        <tbody class="tab-content" id="pills-tabContent">
                            <tbody class="tab-pane fade show active" id="pills-pending" role="tabpanel">
                                <?php renderTableRows($pending); ?>
                            </tbody>
                            
                            <tbody class="tab-pane fade" id="pills-active" role="tabpanel">
                                <?php renderTableRows($active); ?>
                            </tbody>
                            
                            <tbody class="tab-pane fade" id="pills-history" role="tabpanel">
                                <?php renderTableRows($history); ?>
                            </tbody>
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