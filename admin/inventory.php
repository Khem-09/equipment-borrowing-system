<?php
// --- 1. BACKEND HANDLER LOGIC ---
session_start();
require_once '../classes/database.php';
require_once '../classes/equipment.php';

// Initialize Database and Equipment objects
$database = new Database();
$db = $database->getConnection();
$equipmentObj = new Equipment($db);

// Process Form Submissions (POST Requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check which action was triggered
    if (isset($_POST['action'])) {
        
        if ($_POST['action'] === 'add') {
            // The method inside equipment.php automatically sanitizes and binds these inputs
            $equipmentObj->addEquipment($_POST['item_name'], $_POST['description'], $_POST['stock_quantity']);
        } 
        elseif ($_POST['action'] === 'delete') {
            $equipmentObj->deleteEquipment($_POST['equipment_id']);
        }
        
        // Post/Redirect/Get (PRG) Pattern: Redirect to the same page to clear POST data
        header("Location: inventory.php");
        exit();
    }
}

// Fetch all inventory for the HTML table
$inventoryList = $equipmentObj->getAllEquipment();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | Laboratory Equipment Borrowing</title>
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
                <input type="text" class="form-control border-start-0 ps-0" placeholder="Search by item name or ID...">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            <div class="table-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Item ID</th>
                                <th class="border-bottom-0 pb-3 pt-4">Item Name</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">In Stock</th>
                                <th class="border-bottom-0 pb-3 pt-4">Status</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($inventoryList)): ?>
                                <?php foreach($inventoryList as $item): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small">EQ-<?= sprintf('%03d', $item['id']); ?></td>
                                        
                                        <td class="fw-bold" style="color: var(--ccs-darkest);">
                                            <?= htmlspecialchars($item['item_name']); ?>
                                        </td>
                                        
                                        <td class="text-center fw-bold">
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
                                            <button class="btn btn-sm btn-light border text-primary me-1" title="Edit"><i class="bi bi-pencil"></i></button>
                                            
                                            <form action="inventory.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="equipment_id" value="<?= $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No equipment found in the inventory.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background-color: var(--ccs-darkest); color: white;">
                <h5 class="modal-title" id="addEquipmentModalLabel">Add New Equipment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="inventory.php" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Item Name</label>
                        <input type="text" name="item_name" class="form-control" required placeholder="e.g., Oscilloscope">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" required min="1" placeholder="e.g., 5">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Hardware specifications or details..."></textarea>
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

<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });
</script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>