<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/equipment.php';

$database = new Database();
$db = $database->getConnection();
$equipmentObj = new Equipment($db);

// File Upload (image)
function handleFileUpload($fileInputName) {
    if (!isset($_FILES[$fileInputName])) {
        return null; 
    }

    $fileError = $_FILES[$fileInputName]['error'];

    if ($fileError === UPLOAD_ERR_NO_FILE) {
        return null; 
    }

    if ($fileError !== UPLOAD_ERR_OK) {
        die("UPLOAD ERROR STOP: PHP Error Code " . $fileError . " (Code 1 means file exceeds 2MB XAMPP limit).");
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $_FILES[$fileInputName]['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        die("UPLOAD ERROR STOP: Invalid file format. System saw: " . $fileType);
    }

    $ext = pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION);
    $newName = uniqid('eq_') . '.' . $ext; 
    
    $targetDir = '../assets/images/equipment/';
    
    if (!is_dir($targetDir)) {
        die("UPLOAD ERROR STOP: The folder '" . $targetDir . "' does not exist. You need to create it.");
    }
    
    $destination = $targetDir . $newName;
    
    if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $destination)) {
        return $newName;
    } else {
        die("UPLOAD ERROR STOP: Failed to move file. XAMPP might not have write permissions to the folder.");
    }
}

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
        elseif ($_POST['action'] === 'manage_stock') {
            // Update stock quantity only
            if (isset($_POST['equipment_id']) && isset($_POST['new_stock'])) {
                $equipmentObj->updateStock($_POST['equipment_id'], $_POST['new_stock']);
            }
        }
        elseif ($_POST['action'] === 'delete') {
            $equipmentObj->deleteEquipment($_POST['equipment_id']);
        }
        
        header("Location: inventory.php");
        exit();
    }
}
$searchTerm = isset($_GET['search']) ? $_GET['search'] : "";

$inventoryList = $equipmentObj->getAllEquipment($searchTerm);
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
           <form action="inventory.php" method="GET" class="input-group" style="max-width: 350px;">
                <button type="submit" class="input-group-text bg-white border-end-0 text-muted border" style="cursor: pointer;">
                    <i class="bi bi-search"></i>
                </button>
                
                <input type="text" name="search" class="form-control border-start-0 ps-0" 
                       placeholder="Search inventory..." 
                       value="<?= htmlspecialchars($searchTerm) ?>">
                
                <?php if(!empty($searchTerm)): ?>
                    <a href="inventory.php" class="btn btn-outline-secondary border text-muted" title="Clear Search">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="content-area p-4 p-md-5">
            <div class="table-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="text-uppercase small text-muted">
                            <tr>
                                <th>Item</th>
                                <th>Total</th>
                                <th>Available</th>
                                <th>Borrowed</th>
                                <th>Overdue</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($inventoryList as $item): 
                                $borrowed = $item['borrowed_count'] ?? 0;
                                $overdue = $item['overdue_count'] ?? 0;
                                $available = $item['stock_quantity'] - $borrowed;
                            ?>
                            <tr>
                                <td class="fw-bold">
                                    <?php $img = !empty($item['image_path']) ? $item['image_path'] : 'default.png'; ?>
                                    <div class="d-flex align-items-center">
                                        <img src="../assets/images/equipment/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" class="eq-thumbnail me-2">
                                        <div><?= htmlspecialchars($item['item_name']); ?></div>
                                    </div>
                                </td>
                                <td><?= $item['stock_quantity']; ?></td>
                                <td><?= $available; ?></td>
                                <td><?= $borrowed; ?></td>
                                <td><?= $overdue; ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center">
                                        <a class="btn btn-sm btn-light border me-1 manage-stock-btn" href="#" data-bs-toggle="modal" data-bs-target="#manageStockModal"
                                           data-id="<?= $item['id'] ?>" data-stock="<?= $item['stock_quantity'] ?>" data-name="<?= htmlspecialchars($item['item_name']) ?>" title="Manage Stock">
                                           <i class="bi bi-list-check"></i>
                                        </a>

                                        <a class="btn btn-sm btn-light border me-1 edit-btn" href="#" data-bs-toggle="modal" data-bs-target="#editEquipmentModal"
                                           data-id="<?= $item['id'] ?>" data-name="<?= htmlspecialchars($item['item_name']) ?>" data-desc="<?= htmlspecialchars($item['description']) ?>" data-stock="<?= $item['stock_quantity'] ?>" title="Edit">
                                           <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form method="POST" action="inventory.php" onsubmit="return confirm('Delete this equipment type?');" style="display:inline-block; margin:0;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="equipment_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

        <div class="modal fade" id="manageStockModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light border-0">
                        <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);">Manage Stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="inventory.php" method="POST">
                        <input type="hidden" name="action" value="manage_stock">
                        <input type="hidden" name="equipment_id" id="manage_id">
                        <div class="modal-body p-4 pt-2">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Item</label>
                                <div id="manage_name" class="fw-bold"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Current Stock</label>
                                <div id="manage_current_stock" class="mb-2"></div>
                                <label class="form-label fw-bold small text-muted">Set New Total Stock</label>
                                <input type="number" name="new_stock" id="manage_new_stock" class="form-control" required min="0">
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-custom px-4">Update Stock</button>
                        </div>
                    </form>
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
                    <input type="hidden" name="stock_quantity" id="edit_stock">
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

    // Populate Manage Stock Modal
    const manageButtons = document.querySelectorAll('.manage-stock-btn');
    manageButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('manage_id').value = this.getAttribute('data-id');
            document.getElementById('manage_name').textContent = this.getAttribute('data-name');
            document.getElementById('manage_current_stock').textContent = this.getAttribute('data-stock');
            document.getElementById('manage_new_stock').value = this.getAttribute('data-stock');
        });
    });
</script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>