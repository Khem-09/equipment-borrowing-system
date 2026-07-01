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
<body>

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4"><i class="bi bi-list"></i></button>
                <h5 class="m-0 fw-bold">Equipment Categories</h5>
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
            
            <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                <div class="row gx-3 gy-3 align-items-center">
                    <div class="col-lg-7">
                        <p class="text-muted mb-3">Manage general types of laboratory equipment.</p>
                        <div class="input-group shadow-sm rounded-pill overflow-hidden" style="max-width: 100%;">
                            <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="tableSearchInput" class="form-control border-0 ps-0" placeholder="Search categories...">
                        </div>
                    </div>
                    <div class="col-lg-auto d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
                        <div class="d-flex align-items-center gap-2 bg-light rounded-pill px-3 py-2 shadow-sm">
                            <span class="small text-muted">Rows:</span>
                            <select id="paginationSize" class="form-select form-select-sm w-auto border-0 bg-transparent" style="min-width: 90px; max-width: 120px;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <button class="btn btn-custom rounded-pill px-4 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bi bi-plus-lg me-1"></i> New Category
                        </button>
                    </div>
                </div>
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
                                        <a href="specifications.php?category_id=<?= $row['id'] ?>" class="btn btn-sm btn-light border rounded px-3 me-1 text-success fw-medium shadow-sm" title="Manage Specifications">
                                            <i class="bi bi-list-nested"></i> Specs
                                        </a>

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
                                <tr class="no-results-row"><td colspan="5" class="text-center text-muted py-5">No equipment categories added yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 p-3 border-top">
                        <div class="text-muted small" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="paginationPrev">Previous</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="paginationNext">Next</button>
                        </div>
                    </div>
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
                    <input type="text" name="category_name" class="form-control bg-light" placeholder="e.g., Erlenmeyer Flask" required>
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
        document.getElementById('sidebar').classList.toggle('collapsed'); 
    });

    document.querySelectorAll('.edit-category-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_desc').value = this.getAttribute('data-desc');
            
            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        });
    });

    // --- NEW: FRONTEND TABLE FILTER LOGIC ---
    const searchInput = document.getElementById('tableSearchInput');
    const paginationSize = document.getElementById('paginationSize');
    const paginationPrev = document.getElementById('paginationPrev');
    const paginationNext = document.getElementById('paginationNext');
    const paginationInfo = document.getElementById('paginationInfo');
    const tableRows = Array.from(document.querySelectorAll('.table tbody tr')).filter(row => !row.querySelector('td').colSpan || row.querySelector('td').colSpan === 1);
    let currentPage = 1;
    let rowsPerPage = Number(paginationSize?.value || 10);

    function updatePagination() {
        const filteredRows = tableRows.filter(row => row.style.display !== 'none');
        const totalRows = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));
        currentPage = Math.min(currentPage, totalPages);

        filteredRows.forEach((row, index) => {
            const start = (currentPage - 1) * rowsPerPage;
            row.style.display = index >= start && index < start + rowsPerPage ? '' : 'none';
        });

        const startEntry = totalRows === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
        const endEntry = Math.min(totalRows, currentPage * rowsPerPage);
        paginationInfo.textContent = `Showing ${startEntry} to ${endEntry} of ${totalRows} entries`;
        paginationPrev.disabled = currentPage === 1;
        paginationNext.disabled = currentPage === totalPages;
    }

    function applySearchFilter() {
        const filterValue = searchInput.value.toLowerCase();
        tableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(filterValue) ? '' : 'none';
        });
        currentPage = 1;
        updatePagination();
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            applySearchFilter();
        });
    }

    if (paginationSize) {
        paginationSize.addEventListener('change', function() {
            rowsPerPage = Number(this.value);
            currentPage = 1;
            updatePagination();
        });
    }

    if (paginationPrev) {
        paginationPrev.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage -= 1;
                updatePagination();
            }
        });
    }

    if (paginationNext) {
        paginationNext.addEventListener('click', function() {
            currentPage += 1;
            updatePagination();
        });
    }

    updatePagination();
</script>
</body>
</html>