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

// --- PROCESS THE CHECKOUT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_slip'])) {
    $student_id = trim($_POST['student_id']);
    $student_name = trim($_POST['student_name']);
    $course_section = trim($_POST['course_section']);
    $subject_code = trim($_POST['subject_code']);
    $instructor_name = trim($_POST['instructor_name']);
    $class_time = trim($_POST['class_time']);
    $processed_by = $_SESSION['user_id'];
    
    $asset_ids = json_decode($_POST['asset_ids'], true);

    if (empty($asset_ids)) {
        $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm'>You must add at least one item to the slip!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        try {
            $conn->beginTransaction();
            
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

            $conn->commit();
            $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm'><strong>Success!</strong> Borrowing Slip <strong>$slip_number</strong> has been processed successfully. <a href='active_slips.php' class='alert-link'>View Active Borrows</a><button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm'>Transaction Failed: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// --- FETCH AVAILABLE ASSETS ---
$query = "SELECT a.id, a.unique_asset_code, c.category_name 
          FROM equipment_assets a 
          JOIN equipment_categories c ON a.category_id = c.id 
          WHERE a.status = 'Available'
          ORDER BY c.category_name ASC, a.unique_asset_code ASC";
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
    </style>
</head>
<body class="bg-light">

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4 btn btn-light border-0"><i class="bi bi-list fs-4"></i></button>
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">New Borrowing Slip</h5>
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            <?= $message ?>

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
                                    <input type="text" name="student_id" class="form-control bg-light" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">FULL NAME</label>
                                    <input type="text" name="student_name" class="form-control bg-light" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">COURSE & SECTION</label>
                                    <input type="text" name="course_section" class="form-control bg-light" placeholder="e.g. BSChem-2A" required>
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
                                    <input type="text" name="subject_code" class="form-control bg-light" placeholder="e.g. CHEM201" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">INSTRUCTOR NAME</label>
                                    <input type="text" name="instructor_name" class="form-control bg-light" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">CLASS TIME</label>
                                    <input type="text" name="class_time" class="form-control bg-light" placeholder="e.g. 1:00 PM - 4:00 PM" required>
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
                                        <i class="bi bi-qr-code me-1"></i> <?= htmlspecialchars($asset['unique_asset_code']) ?>
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

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() { 
        document.getElementById('sidebar').classList.toggle('collapsed'); 
    });

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
</script>
</body>
</html>