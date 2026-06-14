<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | CCS Borrowing</title>
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
                <button class="btn btn-sm btn-custom px-4 py-2 rounded-pill shadow-sm fw-medium"><i class="bi bi-plus-lg me-2"></i>Add New Equipment</button>
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
                                <th class="border-bottom-0 pb-3 pt-4">Category</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">In Stock</th>
                                <th class="border-bottom-0 pb-3 pt-4">Status</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4 text-muted small">EQ-001</td>
                                <td class="fw-bold" style="color: var(--ccs-darkest);">Arduino Uno R3</td>
                                <td><span class="badge bg-light text-dark border">Microcontroller</span></td>
                                <td class="text-center fw-bold">12 / 15</td>
                                <td><span class="badge rounded-pill fw-normal" style="background-color: rgba(31, 125, 83, 0.1); color: var(--ccs-accent);">Available</span></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-light border text-primary me-1"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4 text-muted small">EQ-002</td>
                                <td class="fw-bold" style="color: var(--ccs-darkest);">Crimping Tool</td>
                                <td><span class="badge bg-light text-dark border">Networking Tool</span></td>
                                <td class="text-center fw-bold">0 / 5</td>
                                <td><span class="badge rounded-pill fw-normal bg-danger bg-opacity-10 text-danger">Out of Stock</span></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-light border text-primary me-1"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
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