<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../classes/database.php';
$db = new Database();
$conn = $db->getConnection();

// --- 1. VALIDATE CATEGORY ID ---
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if ($category_id === 0) {
    header("Location: inventory.php");
    exit;
}

$cat_stmt = $conn->prepare("SELECT * FROM equipment_categories WHERE id = ?");
$cat_stmt->execute([$category_id]);
$current_category = $cat_stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_category) {
    header("Location: inventory.php");
    exit;
}

$message = '';

// --- 2. PROCESS FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $spec_name = trim($_POST['specification_name']);

        $stmt = $conn->prepare("INSERT INTO equipment_specifications (category_id, specification_name) VALUES (?, ?)");
        if ($stmt->execute([$category_id, $spec_name])) {
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'>Specification added successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } 
    elseif ($action === 'edit') {
        $spec_id = (int)$_POST['spec_id'];
        $spec_name = trim($_POST['specification_name']);

        $stmt = $conn->prepare("UPDATE equipment_specifications SET specification_name=? WHERE id=? AND category_id=?");
        if ($stmt->execute([$spec_name, $spec_id, $category_id])) {
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'>Specification updated successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } 
    elseif ($action === 'delete') {
        $spec_id = (int)$_POST['spec_id'];
        $stmt = $conn->prepare("DELETE FROM equipment_specifications WHERE id=? AND category_id=?");
        if ($stmt->execute([$spec_id, $category_id])) {
            $message = "<div class='alert alert-secondary alert-dismissible fade show shadow-sm mb-4'>Specification deleted!</div>";
        }
    }
}

// --- 3. FETCH SPECIFICATIONS FOR THIS CATEGORY ---
$query = "
    SELECT s.*, 
           (SELECT COUNT(*) FROM equipment_assets a WHERE a.specification_id = s.id) as total_assets,
           (SELECT COUNT(*) FROM equipment_assets a WHERE a.specification_id = s.id AND a.status = 'Available') as available_assets
    FROM equipment_specifications s 
    WHERE s.category_id = ?
    ORDER BY s.specification_name ASC
";
$stmt = $conn->prepare($query);
$stmt->execute([$category_id]);
$specifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specifications | LabBorrow</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4"><i class="bi bi-list"></i></button>
                <h5 class="m-0 fw-bold">Manage Specifications</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name'] ?? 'Admin') ?>&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="inventory.php" class="text-decoration-none" style="color: var(--ccs-primary);">Categories</a></li>
                    <li class="breadcrumb-item active fw-bold" aria-current="page"><?= htmlspecialchars($current_category['category_name']) ?></li>
                </ol>
            </nav>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-1" style="color: var(--ccs-darkest);"><?= htmlspecialchars($current_category['category_name']) ?> Specs</h4>
                    <p class="text-muted mb-0 small">Add specific models or sizes (e.g., 250mL, 500mL) for this category.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="input-group shadow-sm" style="max-width: 300px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="tableSearchInput" class="form-control border-start-0 ps-0" placeholder="Search specs...">
                    </div>
                    <button class="btn btn-custom rounded-pill px-4 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#addSpecModal">
                        <i class="bi bi-plus-lg me-1"></i> New Spec
                    </button>
                </div>
            </div>

            <?= $message ?>

            <div class="table-card shadow-sm border-0 bg-white rounded-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Specification Name</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">Total Assets</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">Available</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($specifications) > 0): ?>
                                <?php foreach ($specifications as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold" style="color: var(--ccs-darkest);">
                                        <?= htmlspecialchars($row['specification_name']) ?>
                                    </td>
                                    <td class="text-center fw-semibold"><?= $row['total_assets'] ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $row['available_assets'] > 0 ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                            <?= $row['available_assets'] ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="assets.php?spec_id=<?= $row['id'] ?>&category_id=<?= $category_id ?>" class="btn btn-sm btn-custom rounded px-3 me-2 fw-medium shadow-sm" title="Manage Physical Assets">
                                            <i class="bi bi-box-seam me-1"></i> Assets
                                        </a>
                                        <button class="btn btn-sm btn-light border rounded px-3 me-1 text-primary fw-medium shadow-sm edit-spec-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($row['specification_name']) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('WARNING: Deleting this specification deletes all physical assets under it. Are you sure?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="spec_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border rounded px-3 text-danger fw-medium shadow-sm">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-5">No specifications added yet. Click 'New Spec' to add one.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addSpecModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);">Add Specification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SPECIFICATION NAME / SIZE</label>
                    <input type="text" name="specification_name" class="form-control bg-light" placeholder="e.g., 250mL" required>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom rounded-pill px-4">Save Spec</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editSpecModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);">Edit Specification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="spec_id" id="edit_spec_id">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SPECIFICATION NAME / SIZE</label>
                    <input type="text" name="specification_name" id="edit_spec_name" class="form-control bg-light" required>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom rounded-pill px-4">Update Spec</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() { 
        document.getElementById('sidebar').classList.toggle('collapsed'); 
    });

    document.querySelectorAll('.edit-spec-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_spec_id').value = this.getAttribute('data-id');
            document.getElementById('edit_spec_name').value = this.getAttribute('data-name');
            var editModal = new bootstrap.Modal(document.getElementById('editSpecModal'));
            editModal.show();
        });
    });

    // --- NEW: FRONTEND TABLE FILTER LOGIC ---
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filterValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.table tbody tr');

            tableRows.forEach(row => {
                if (row.querySelector('td').colSpan > 1) return; // Skip empty state row
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(filterValue) ? '' : 'none';
            });
        });
    }
</script>
</body>
</html>