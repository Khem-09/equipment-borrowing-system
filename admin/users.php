<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | CCS Borrowing</title>
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
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Student Directory</h5>
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
                <button class="btn btn-sm btn-light border px-4 py-2 rounded-pill fw-medium"><i class="bi bi-person-plus me-2"></i>Register Student</button>
            </div>
            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" placeholder="Search by name or School ID...">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            <div class="table-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">School ID</th>
                                <th class="border-bottom-0 pb-3 pt-4">Full Name</th>
                                <th class="border-bottom-0 pb-3 pt-4">Course & Year</th>
                                <th class="border-bottom-0 pb-3 pt-4 text-center">Active Borrows</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4 fw-medium text-muted">2024-01234</td>
                                <td class="fw-bold" style="color: var(--ccs-darkest);">Juan Dela Cruz</td>
                                <td>BSCS-2A</td>
                                <td class="text-center"><span class="badge bg-warning text-dark rounded-pill px-3">1 Active</span></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-light border text-primary me-1" title="Edit User"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-light border text-danger" title="Suspend User"><i class="bi bi-slash-circle"></i></button>
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
    document.getElementById('sidebarToggle').addEventListener('click', function() { document.getElementById('sidebar').classList.toggle('collapsed'); });
</script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>