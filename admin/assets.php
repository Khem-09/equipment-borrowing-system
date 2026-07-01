<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../classes/database.php';
$db = new Database();
$conn = $db->getConnection();

$message = '';

// --- PROCESS FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $category_id = (int)$_POST['category_id'];
        $spec_id = (int)$_POST['spec_id'];
        $asset_code = trim($_POST['unique_asset_code']);
        $status = trim($_POST['status']);
        $notes = trim($_POST['condition_notes']);

        // 1. Check if the asset code already exists in the database
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM equipment_assets WHERE unique_asset_code = ?");
        $check_stmt->execute([$asset_code]);
        $code_exists = $check_stmt->fetchColumn();

        if ($code_exists > 0) {
            // 2. If it exists, block the insert and show a warning message
            $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>
                            <strong><i class='bi bi-exclamation-triangle me-2'></i>Registration Failed:</strong>
                            The asset code '<b>" . htmlspecialchars($asset_code) . "</b>' is already registered in the system.
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        } else {
            // 3. If it is unique, proceed with the insert safely
            $stmt = $conn->prepare("INSERT INTO equipment_assets (category_id, specification_id, unique_asset_code, status, condition_notes) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$category_id, $spec_id, $asset_code, $status, $notes])) {
                $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'>
                                <i class='bi bi-check-circle me-2'></i>Asset registered successfully!
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                            </div>";
            }
        }
    }
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM equipment_assets WHERE id=?");
        if ($stmt->execute([$id])) {
            $message = "<div class='alert alert-secondary alert-dismissible fade show shadow-sm mb-4'>Asset removed successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// --- FETCH ALL ASSETS FOR THE TABLE ---
// We join the categories and specifications tables so we can display their names
$query = "
    SELECT a.*, c.category_name, s.specification_name
    FROM equipment_assets a
    JOIN equipment_categories c ON a.category_id = c.id
    JOIN equipment_specifications s ON a.specification_id = s.id
    ORDER BY a.created_at DESC
";
$stmt = $conn->query($query);
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for the dropdown
$cat_stmt = $conn->query("SELECT id, category_name FROM equipment_categories ORDER BY category_name ASC");
$categories_list = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--ccs-darkest);"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name'] ?? 'Admin') ?>&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <p class="text-muted mb-0">Register specific items using their sticker IDs/Barcodes.</p>
                <div class="d-flex align-items-center gap-3">
                    <div class="input-group shadow-sm" style="max-width: 300px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="tableSearchInput" class="form-control border-start-0 ps-0" placeholder="Search assets...">
                    </div>
                    <button class="btn btn-custom rounded-pill px-4 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                        <i class="bi bi-upc-scan me-1"></i> Register New Asset
                    </button>
                </div>
            </div>

            <?= $message ?>

            <div class="table-card shadow-sm border-0 bg-white rounded-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Asset Code</th>
                                <th class="border-bottom-0 pb-3 pt-4">Category</th>
                                <th class="border-bottom-0 pb-3 pt-4">Specification</th>
                                <th class="border-bottom-0 pb-3 pt-4">Condition Notes</th>
                                <th class="border-bottom-0 pb-3 pt-4">Status</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($assets) > 0): ?>
                                <?php foreach ($assets as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold" style="color: var(--ccs-primary);">
                                        <i class="bi bi-qr-code me-2 text-muted"></i><?= htmlspecialchars($row['unique_asset_code']) ?>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($row['category_name']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($row['specification_name']) ?></td>
                                    <td class="text-muted small fst-italic">
                                        <?= !empty($row['condition_notes']) ? htmlspecialchars($row['condition_notes']) : 'None' ?>
                                    </td>
                                    <td>
                                        <?php if($row['status'] === 'Available'): ?>
                                            <span class="badge bg-success rounded-pill px-3">Available</span>
                                        <?php elseif($row['status'] === 'Borrowed'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3">Borrowed</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill px-3"><?= htmlspecialchars($row['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-light border rounded px-3 me-1 text-primary shadow-sm"><i class="bi bi-pencil-square"></i></button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Remove this asset from inventory?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border rounded px-3 text-danger shadow-sm"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-5">No physical assets registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAssetModal" tabindex="-1">
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
                    <select name="category_id" id="categorySelect" class="form-select bg-light" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach($categories_list as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SPECIFICATION (SIZE/MODEL)</label>
                    <select name="spec_id" id="specSelect" class="form-select bg-light" required disabled>
                        <option value="">-- Select a Category First --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">UNIQUE ASSET CODE (Sticker/Barcode ID)</label>
                    <input type="text" name="unique_asset_code" class="form-control bg-light" placeholder="e.g., FLASK-001" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CURRENT STATUS</label>
                    <select name="status" class="form-select bg-light" required>
                        <option value="Available">Available</option>
                        <option value="Maintenance">Maintenance</option>
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

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });

    // --- NEW: FRONTEND TABLE FILTER LOGIC ---
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filterValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.table tbody tr');

            tableRows.forEach(row => {
                // Do not filter the "No data found" empty state row
                if (row.querySelector('td').colSpan > 1) return;

                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(filterValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // --- DYNAMIC DEPENDENT DROPDOWN LOGIC ---
    document.getElementById('categorySelect').addEventListener('change', function() {
        const categoryId = this.value;
        const specSelect = document.getElementById('specSelect');

        // Reset the specs dropdown immediately when category changes
        specSelect.innerHTML = '<option value="">Loading...</option>';
        specSelect.disabled = true;

        if (categoryId) {
            // Ping the backend to get the specs for this category
            fetch(`get_specs.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    specSelect.innerHTML = '<option value="">-- Select Specification --</option>';

                    if(data.length > 0) {
                        data.forEach(spec => {
                            specSelect.innerHTML += `<option value="${spec.id}">${spec.specification_name}</option>`;
                        });
                        specSelect.disabled = false;
                    } else {
                        specSelect.innerHTML = '<option value="">No specifications found for this category.</option>';
                    }
                })
                .catch(error => console.error('Error fetching specs:', error));
        } else {
            specSelect.innerHTML = '<option value="">-- Select a Category First --</option>';
        }
    });
</script>
</body>
</html>