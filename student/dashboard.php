<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | CCS Borrowing</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="wrapper">
    <?php include '../includes/student_sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Inventory Catalog</h5>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--ccs-darkest);">Maya</div>
                    <div class="text-muted" style="font-size: 0.75rem;">BSCS-2A</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=Maya&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40" alt="Profile">
            </div>
        </div>

        <div class="filter-bar">
            <span class="fw-bold me-2 text-muted small d-none d-md-inline"><i class="bi bi-funnel me-1"></i>Filters:</span>
            <button class="filter-btn active">All Items</button>
            <button class="filter-btn">Microcontrollers</button>
            <button class="filter-btn">Networking Tools</button>
            
            <div class="input-group ms-auto" style="max-width: 300px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" placeholder="Search equipment...">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            <div class="row g-4">
                
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card equipment-card h-100 bg-white">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center px-4">
                            <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;"><i class="bi bi-cpu me-1"></i> Board</span>
                            <span class="badge rounded-pill fw-normal" style="background-color: rgba(31, 125, 83, 0.1); color: var(--ccs-accent);">12 In Stock</span>
                        </div>
                        <div class="card-body px-4">
                            <h5 class="card-title fw-bold mt-1 mb-2" style="color: var(--ccs-darkest);">Arduino Uno R3</h5>
                            <p class="card-text text-muted small" style="line-height: 1.6;">Standard microcontroller board for physical computing and electronics prototyping.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-4 pt-0 px-4">
                            <a href="request_form.php?id=1" class="btn btn-custom w-100 py-2">
                                Request to Borrow
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card equipment-card h-100 bg-white">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center px-4">
                            <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;"><i class="bi bi-tools me-1"></i> Tool</span>
                            <span class="badge rounded-pill fw-normal" style="background-color: rgba(31, 125, 83, 0.1); color: var(--ccs-accent);">5 In Stock</span>
                        </div>
                        <div class="card-body px-4">
                            <h5 class="card-title fw-bold mt-1 mb-2" style="color: var(--ccs-darkest);">Crimping Tool</h5>
                            <p class="card-text text-muted small" style="line-height: 1.6;">Heavy-duty network cable crimper used for RJ45 termination in lab exercises.</p>
                        </div>
                        <div class="card-footer bg-transparent border-0 pb-4 pt-0 px-4">
                            <a href="request_form.php?id=2" class="btn btn-custom w-100 py-2">
                                Request to Borrow
                            </a>
                        </div>
                    </div>
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