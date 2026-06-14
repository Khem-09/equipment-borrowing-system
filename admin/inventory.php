<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/equipment.php';

$database = new Database();
$db = $database->getConnection();
$equipmentObj = new Equipment($db);

// File Upload (image)
function handleFileUpload($fileInputName) {
    // 1. Check if the file was sent at all
    if (!isset($_FILES[$fileInputName])) {
        return null; 
    }

    $fileError = $_FILES[$fileInputName]['error'];

    // 2. If the user just didn't select a file, that's fine. Return null so it uses default.
    if ($fileError === UPLOAD_ERR_NO_FILE) {
        return null; 
    }

    // 3. Catch specific PHP upload errors (like file being too big)
    if ($fileError !== UPLOAD_ERR_OK) {
        die("UPLOAD ERROR STOP: PHP Error Code " . $fileError . " (Code 1 means file exceeds 2MB XAMPP limit).");
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $_FILES[$fileInputName]['type'];
    
    // 4. Catch invalid file types
    if (!in_array($fileType, $allowedTypes)) {
        die("UPLOAD ERROR STOP: Invalid file format. System saw: " . $fileType);
    }

    $ext = pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION);
    $newName = uniqid('eq_') . '.' . $ext; 
    
    $targetDir = '../assets/images/equipment/';
    
    // 5. Catch missing folders
    if (!is_dir($targetDir)) {
        die("UPLOAD ERROR STOP: The folder '" . $targetDir . "' does not exist. You need to create it.");
    }
    
    $destination = $targetDir . $newName;
    
    // 6. Catch permission issues
    if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $destination)) {
        return $newName;
    } else {
        die("UPLOAD ERROR STOP: Failed to move file. XAMPP might not have write permissions to the folder.");
    }
}

// --- POST REQUEST HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        if ($_POST['action'] === 'add') {
            $imageName = handleFileUpload('equipment_photo');
            $imageName = $imageName ? $imageName : 'default.png'; // Fallback
            $equipmentObj->addEquipment($_POST['item_name'], $_POST['description'], $_POST['stock_quantity'], $imageName);
        } 
        elseif ($_POST['action'] === 'edit') {
            $newImageName = handleFileUpload('equipment_photo');
            // If $newImageName is null, the Equipment class knows to keep the old image
            $equipmentObj->updateEquipment($_POST['equipment_id'], $_POST['item_name'], $_POST['description'], $_POST['stock_quantity'], $newImageName);
        }
        elseif ($_POST['action'] === 'delete') {
            $equipmentObj->deleteEquipment($_POST['equipment_id']);
        }
        
        header("Location: inventory.php");
        exit();
    }
}

$inventoryList = $equipmentObj->getAllEquipment();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | CCS Borrowing</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .eq-thumbnail {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4"><i class="bi bi-list"></i></button>
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Inventory Management</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--ccs-darkest);">Maya</div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=Maya&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>

        <div class="filter-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <button type="button" data-bs-toggle="modal" data-bs-target="#addEquipmentModal" class="btn btn-sm btn-custom px-4 py-2 rounded-pill shadow-sm fw-medium">
                    <i class="bi bi-plus-lg me-2"></i>Add New Equipment
                </button>
            </div>
            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" placeholder="Search inventory...">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            <div class="table-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Item</th>
                                <th class="border-bottom-0 pb-3 pt-4">Description</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">Total Stock</th>
                                <th class="border-bottom-0 pb-3 pt-4">Status</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($inventoryList)): ?>
                                <?php foreach($inventoryList as $item): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/images/equipment/<?= htmlspecialchars($item['image_path'] ?? 'default.png'); ?>" class="eq-thumbnail me-3" alt="Item Image">
                                                <div>
                                                    <div class="fw-bold" style="color: var(--ccs-darkest);"><?= htmlspecialchars($item['item_name']); ?></div>
                                                    <div class="text-muted small">EQ-<?= sprintf('%03d', $item['id']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-muted small" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= htmlspecialchars($item['description']); ?>
                                        </td>
                                        <td class="text-center fw-bold text-dark fs-5">
                                            <?= htmlspecialchars($item['stock_quantity']); ?>
                                        </td>
                                        <td>
                                            <?php if($item['status'] === 'Available'): ?>
                                                <span class="badge rounded-pill fw-normal" style="background-color: rgba(31, 125, 83, 0.1); color: var(--ccs-accent);">Available</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill fw-normal bg-danger bg-opacity-10 text-danger"><?= htmlspecialchars($item['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <button class="btn btn-sm btn-light border text-primary me-1 edit-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editEquipmentModal"
                                                    data-id="<?= $item['id'] ?>"
                                                    data-name="<?= htmlspecialchars($item['item_name']) ?>"
                                                    data-stock="<?= htmlspecialchars($item['stock_quantity']) ?>"
                                                    data-desc="<?= htmlspecialchars($item['description']) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <form action="inventory.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this item completely?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="equipment_id" value="<?= $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No equipment found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background-color: var(--ccs-darkest); color: white;">
                <h5 class="modal-title">Add New Equipment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="inventory.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Upload Photo</label>
                        <input type="file" name="equipment_photo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Item Name</label>
                        <input type="text" name="item_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Initial Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom px-4">Save Equipment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editEquipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);">Edit Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="inventory.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="equipment_id" id="edit_id">
                
                <div class="modal-body p-4 pt-2">
                    <div class="alert alert-info border-0 bg-opacity-10 small">
                        <i class="bi bi-info-circle me-1"></i> Leave the photo field blank to keep the current image.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Replace Photo (Optional)</label>
                        <input type="file" name="equipment_photo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Item Name</label>
                        <input type="text" name="item_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Total Stock Quantity</label>
                        <input type="number" name="stock_quantity" id="edit_stock" class="form-control" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Description</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-custom px-4">Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Sidebar Toggle
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });

    // Populate Edit Modal Data
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_stock').value = this.getAttribute('data-stock');
            document.getElementById('edit_desc').value = this.getAttribute('data-desc');
        });
    });
</script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>