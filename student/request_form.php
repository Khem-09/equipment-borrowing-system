<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Equipment | Laboratory Equipment Borrowing</title>
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
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card table-card shadow-sm border-0">
                        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2 px-4 px-md-5">
                            <h4 class="mb-0 fw-bold" style="color: var(--ccs-darkest);">
                                Borrow Equipment
                            </h4>
                            <p class="text-muted small mt-1">Please specify your project details.</p>
                        </div>
                        
                        <div class="card-body p-4 p-md-5 pt-0">
                            <form action="process_request.php" method="POST">
                                <input type="hidden" name="equipment_id" value="1"> 
                                
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold text-uppercase">Item Requested</label>
                                    <input type="text" class="form-control bg-light" value="Arduino Uno R3" readonly style="color: var(--ccs-dark);">
                                </div>

                                <div class="mb-4">
                                    <label for="expected_return" class="form-label text-muted small fw-bold text-uppercase">Expected Return</label>
                                    <input type="datetime-local" class="form-control" id="expected_return" name="expected_return_date" required>
                                </div>

                                <div class="mb-4">
                                    <label for="purpose" class="form-label text-muted small fw-bold text-uppercase">Purpose of Borrowing</label>
                                    <textarea class="form-control" id="purpose" name="purpose" rows="4" required placeholder="e.g., For System Integration project regarding sensor arrays..."></textarea>
                                </div>

                                <div class="d-flex gap-3 mt-5">
                                    <button type="submit" class="btn btn-custom flex-grow-1 py-2">Submit Request</button>
                                    <a href="dashboard.php" class="btn btn-light border py-2 px-4 text-muted fw-medium">Cancel</a>
                                </div>
                            </form>
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