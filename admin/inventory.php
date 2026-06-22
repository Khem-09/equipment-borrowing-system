<?php
session_start();

// Protect the page: Only logged-in users (Admins) allowed
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
        $name = trim($_POST['category_name']);
        $desc = trim($_POST['description']);

        $stmt = $conn->prepare("INSERT INTO equipment_categories (category_name, description) VALUES (?, ?)");
        if ($stmt->execute([$name, $desc])) {
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'>Category added successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } 
    elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['category_name']);
        $desc = trim($_POST['description']);

        $stmt = $conn->prepare("UPDATE equipment_categories SET category_name=?, description=? WHERE id=?");
        if ($stmt->execute([$name, $desc, $id])) {
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'>Category updated successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } 
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM equipment_categories WHERE id=?");
        if ($stmt->execute([$id])) {
            $message = "<div class='alert alert-secondary alert-dismissible fade show shadow-sm mb-4'>Category deleted successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// --- FETCH ALL CATEGORIES ---
// We also count how many physical assets belong to each category so the Admin knows their total stock
$query = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM equipment_assets a WHERE a.category_id = c.id) as total_assets,
           (SELECT COUNT(*) FROM equipment_assets a WHERE a.category_id = c.id AND a.status = 'Available') as available_assets
    FROM equipment_categories c 
    ORDER BY c.category_name ASC
";
$stmt = $conn->query($query);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Categories | LabBorrow</title>
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
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Equipment Categories</h5>
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
                <p class="text-muted mb-0">Manage general types of laboratory equipment.</p>
                <button class="btn btn-custom rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-lg me-1"></i> New Category
                </button>
            </div>

            <?= $message ?>

            <div class="table-card shadow-sm border-0 bg-white rounded-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Category Name</th>
                                <th class="border-bottom-0 pb-3 pt-4">Description</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">Total Items</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">Available</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold" style="color: var(--ccs-darkest);">
                                        <?= htmlspecialchars($row['category_name']) ?>
                                    </td>
                                    <td class="text-muted small" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($row['description']) ?>
                                    </td>
                                    <td class="text-center fw-semibold"><?= $row['total_assets'] ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $row['available_assets'] > 0 ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                            <?= $row['available_assets'] ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-light border rounded px-3 me-1 text-primary fw-medium shadow-sm edit-category-btn"
                                            data-id="<?= $row['id'] ?>"
                                            data-name="<?= htmlspecialchars($row['category_name']) ?>"
                                            data-desc="<?= htmlspecialchars($row['description'] ?? '') ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('WARNING: Deleting this category will delete ALL physical assets attached to it. Are you sure?');">
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
                                <tr><td colspan="5" class="text-center text-muted py-5">No equipment categories added yet.</td></tr>
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
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);">Add Equipment Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CATEGORY NAME</label>
                    <input type="text" name="category_name" class="form-control bg-light" placeholder="e.g., 250mL Erlenmeyer Flask" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DESCRIPTION (Optional)</label>
                    <textarea name="description" class="form-control bg-light" rows="3" placeholder="Brief details about this type of equipment..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom rounded-pill px-4">Save Category</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CATEGORY NAME</label>
                    <input type="text" name="category_name" id="edit_name" class="form-control bg-light" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DESCRIPTION</label>
                    <textarea name="description" id="edit_desc" class="form-control bg-light" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom rounded-pill px-4">Update Category</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() { 
        // This toggles the 'collapsed' class on the sidebar
        document.getElementById('sidebar').classList.toggle('collapsed'); 
    });

    // Safely handle Edit button clicks using data attributes
    document.querySelectorAll('.edit-category-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Get data from the clicked button
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const desc = this.getAttribute('data-desc');

            // Inject data into the modal inputs
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_desc').value = desc;
            
            // Show the modal
            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        });
    });
</script>
</body>
</html>