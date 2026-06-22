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

$message = '';

// --- PROCESS FORM SUBMISSIONS (CREATE, UPDATE, DELETE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $category_id = (int)$_POST['category_id'];
        $code = trim(strtoupper($_POST['unique_asset_code'])); // Force uppercase for consistency
        $status = $_POST['status'];
        $notes = trim($_POST['condition_notes']);

        try {
            $stmt = $conn->prepare("INSERT INTO equipment_assets (category_id, unique_asset_code, status, condition_notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category_id, $code, $status, $notes]);
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'>Asset <strong>$code</strong> added successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Catch Duplicate Entry
                $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>Error: The Asset Code <strong>$code</strong> is already in use!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>Database Error: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    } 
    elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $category_id = (int)$_POST['category_id'];
        $code = trim(strtoupper($_POST['unique_asset_code']));
        $status = $_POST['status'];
        $notes = trim($_POST['condition_notes']);

        try {
            $stmt = $conn->prepare("UPDATE equipment_assets SET category_id=?, unique_asset_code=?, status=?, condition_notes=? WHERE id=?");
            $stmt->execute([$category_id, $code, $status, $notes, $id]);
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'>Asset updated successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (PDOException $e) {
             if ($e->getCode() == 23000) { 
                $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>Error: The Asset Code <strong>$code</strong> belongs to another item!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    } 
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM equipment_assets WHERE id=?");
        if ($stmt->execute([$id])) {
            $message = "<div class='alert alert-secondary alert-dismissible fade show shadow-sm mb-4'>Physical Asset permanently deleted.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// --- FETCH DATA FOR UI ---
// 1. Fetch Categories for the Dropdown menus
$stmt_cats = $conn->query("SELECT id, category_name FROM equipment_categories ORDER BY category_name ASC");
$categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Assets with their Category Names for the Table
$query = "
    SELECT a.*, c.category_name 
    FROM equipment_assets a
    JOIN equipment_categories c ON a.category_id = c.id
    ORDER BY a.created_at DESC
";
$stmt_assets = $conn->query($query);
$assets = $stmt_assets->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Physical Assets | LabBorrow</title>
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
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Physical Assets Inventory</h5>
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
                <p class="text-muted mb-0">Register specific items using their sticker IDs/Barcodes.</p>
                <?php if(count($categories) > 0): ?>
                    <button class="btn btn-custom rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-upc-scan me-1"></i> Register New Asset
                    </button>
                <?php else: ?>
                    <div class="alert alert-warning py-2 mb-0">Please add a Category first!</div>
                <?php endif; ?>
            </div>

            <?= $message ?>

            <div class="table-card shadow-sm border-0 bg-white rounded-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Asset Code</th>
                                <th class="border-bottom-0 pb-3 pt-4">Category</th>
                                <th class="border-bottom-0 pb-3 pt-4">Condition Notes</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">Status</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($assets) > 0): ?>
                                <?php foreach ($assets as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bolder font-monospace" style="color: var(--ccs-primary);">
                                        <i class="bi bi-qr-code me-2 text-muted"></i><?= htmlspecialchars($row['unique_asset_code']) ?>
                                    </td>
                                    <td class="fw-medium text-dark">
                                        <?= htmlspecialchars($row['category_name']) ?>
                                    </td>
                                    <td class="text-muted small" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= $row['condition_notes'] ? htmlspecialchars($row['condition_notes']) : '<span class="text-light-subtle fst-italic">None</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                            // Dynamic Badge Colors based on status
                                            $bClass = 'bg-success';
                                            if($row['status'] == 'Borrowed') $bClass = 'bg-primary';
                                            if($row['status'] == 'Broken') $bClass = 'bg-danger';
                                            if($row['status'] == 'Maintenance') $bClass = 'bg-warning text-dark';
                                            if($row['status'] == 'Lost') $bClass = 'bg-dark';
                                        ?>
                                        <span class="badge <?= $bClass ?> rounded-pill px-3 py-2 fw-semibold shadow-sm">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-light border rounded px-3 me-1 text-primary fw-medium shadow-sm"
                                            onclick="editAsset(<?= $row['id'] ?>, <?= $row['category_id'] ?>, '<?= addslashes($row['unique_asset_code']) ?>', '<?= $row['status'] ?>', '<?= addslashes($row['condition_notes']) ?>')">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this specific asset?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border rounded px-3 text-danger fw-medium shadow-sm">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-5">No physical assets registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);">Register Physical Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">EQUIPMENT CATEGORY</label>
                    <select name="category_id" class="form-select bg-light fw-medium" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">UNIQUE ASSET CODE (Sticker/Barcode ID)</label>
                    <input type="text" name="unique_asset_code" class="form-control bg-light font-monospace" placeholder="e.g., FLASK-001" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CURRENT STATUS</label>
                    <select name="status" class="form-select bg-light" required>
                        <option value="Available" selected>Available</option>
                        <option value="Maintenance">Maintenance / Needs Cleaning</option>
                        <option value="Broken">Broken</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CONDITION NOTES (Optional)</label>
                    <textarea name="condition_notes" class="form-control bg-light" rows="2" placeholder="e.g., Slight scratch on the base..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom rounded-pill px-4">Register Asset</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);">Edit Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">EQUIPMENT CATEGORY</label>
                    <select name="category_id" id="edit_category_id" class="form-select bg-light fw-medium" required>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">UNIQUE ASSET CODE</label>
                    <input type="text" name="unique_asset_code" id="edit_code" class="form-control bg-light font-monospace" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CURRENT STATUS</label>
                    <select name="status" id="edit_status" class="form-select bg-light" required>
                        <option value="Available">Available</option>
                        <option value="Borrowed">Borrowed</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Broken">Broken</option>
                        <option value="Lost">Lost</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CONDITION NOTES</label>
                    <textarea name="condition_notes" id="edit_notes" class="form-control bg-light" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom rounded-pill px-4">Update Asset</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() { 
        document.getElementById('sidebar').classList.toggle('collapsed'); 
    });

    function editAsset(id, category_id, code, status, notes) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_category_id').value = category_id;
        document.getElementById('edit_code').value = code;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_notes').value = notes;
        
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    }
</script>
</body>
</html>