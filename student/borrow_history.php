<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrows | Laboratory Equipment Borrowing</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="wrapper">
    <?php include '../includes/student_sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
             <div class="d-flex align-items-center">
                 <button id="sidebarToggle" class="me-4">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--ccs-darkest);">Maya</div>
                    <div class="text-muted" style="font-size: 0.75rem;">BSCS-2A</div>
                </div>
                <img src="../assets/images/profile.jpg" class="rounded-circle shadow-sm" width="40" height="40" alt="Profile">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            <h4 class="mb-4 fw-bold" style="color: var(--ccs-darkest);">Borrowing History</h4>
            
            <div class="table-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-bottom-0 pb-3 ps-4">Item Name</th>
                                <th class="border-bottom-0 pb-3">Requested On</th>
                                <th class="border-bottom-0 pb-3">Expected Return</th>
                                <th class="border-bottom-0 pb-3 pe-4 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-medium ps-4" style="color: var(--ccs-darkest);">Raspberry Pi 4</td>
                                <td class="text-muted small">Oct 24, 2026 - 10:00 AM</td>
                                <td class="text-muted small">Oct 26, 2026 - 05:00 PM</td>
                                <td class="pe-4 text-end"><span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-medium">Pending</span></td>
                            </tr>
                            <tr>
                                <td class="fw-medium ps-4" style="color: var(--ccs-darkest);">System Unit (Lab 3)</td>
                                <td class="text-muted small">Oct 20, 2026 - 08:00 AM</td>
                                <td class="text-muted small">Oct 20, 2026 - 04:00 PM</td>
                                <td class="pe-4 text-end"><span class="badge px-3 py-2 rounded-pill fw-medium" style="background-color: rgba(31, 125, 83, 0.1); color: var(--ccs-accent);">Returned</span></td>
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