<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests | Laboratory Equipment Borrowing</title>
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
                <h5 class="m-0 fw-bold" style="color: var(--ccs-darkest);">Borrowing Requests</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--ccs-darkest);">Maya</div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=Maya&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>

        <div class="filter-bar border-bottom">
            <ul class="nav nav-pills mb-0" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4" style="background-color: var(--ccs-primary);" data-bs-toggle="pill">Pending</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-muted rounded-pill px-4" data-bs-toggle="pill">Active Borrows</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-muted rounded-pill px-4" data-bs-toggle="pill">Returned / History</button>
                </li>
            </ul>
        </div>

        <div class="content-area p-4 p-md-5">
            <div class="table-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4 pt-4">Student Name</th>
                                <th class="border-bottom-0 pb-3 pt-4">Equipment</th>
                                <th class="border-bottom-0 pb-3 pt-4">Purpose</th>
                                <th class="border-bottom-0 pb-3 pt-4">Return Due</th>
                                <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold" style="color: var(--ccs-darkest);">Juan Dela Cruz</div>
                                    <div class="text-muted small">BSCS-2A</div>
                                </td>
                                <td><span class="fw-medium">Raspberry Pi 4</span></td>
                                <td class="text-muted small" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">For System Integration project regarding sensor arrays.</td>
                                <td class="text-muted small">Oct 26, 2026<br>05:00 PM</td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-custom rounded px-3 me-1 shadow-sm"><i class="bi bi-check2"></i> Approve</button>
                                    <button class="btn btn-sm btn-light border rounded px-3 text-danger fw-medium shadow-sm"><i class="bi bi-x-lg"></i></button>
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