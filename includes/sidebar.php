<nav id="sidebar" class="sidebar bg-white shadow-sm border-end h-100 position-fixed" style="width: 260px; z-index: 1000; top: 0; left: 0; transition: 0.3s;">
    <div class="sidebar-header p-4 border-bottom d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: rgba(31, 125, 83, 0.1);">
            <i class="bi bi-flask-fill fs-4" style="color: var(--ccs-primary);"></i>
        </div>
        <h5 class="m-0 fw-bolder" style="color: var(--ccs-darkest); letter-spacing: 0.5px;">LabBorrow</h5>
    </div>
    
    <div class="sidebar-body py-3 overflow-auto" style="height: calc(100% - 160px);">
        <ul class="nav flex-column mb-auto">
            
            <li class="nav-item mb-1 px-3">
                <a href="dashboard.php" class="nav-link text-dark rounded-3 px-3 py-2 d-flex align-items-center gap-3 transition-all hover-bg-light">
                    <i class="bi bi-grid fs-5 text-muted"></i>
                    <span class="fw-medium">Dashboard</span>
                </a>
            </li>

            <li class="nav-item mb-1 px-3">
                <a href="analytics.php" class="nav-link text-dark rounded-3 px-3 py-2 d-flex align-items-center gap-3 transition-all hover-bg-light">
                    <i class="bi bi-bar-chart-line fs-5 text-muted"></i>
                    <span class="fw-medium">Analytics</span>
                </a>
            </li>
            
            <li class="nav-item mb-1 px-3 mt-4">
                <small class="text-muted fw-bold text-uppercase px-3" style="font-size: 0.7rem; letter-spacing: 1px;">Kiosk Operations</small>
            </li>
            <li class="nav-item mb-1 px-3">
                <a href="new_slip.php" class="nav-link text-dark rounded-3 px-3 py-2 d-flex align-items-center gap-3 hover-bg-light">
                    <i class="bi bi-cart-plus fs-5 text-muted"></i>
                    <span class="fw-medium">New Borrowing</span>
                </a>
            </li>
            <li class="nav-item mb-1 px-3">
                <a href="active_slips.php" class="nav-link text-dark rounded-3 px-3 py-2 d-flex align-items-center gap-3 hover-bg-light">
                    <i class="bi bi-arrow-return-left fs-5 text-muted"></i>
                    <span class="fw-medium">Active Returns</span>
                </a>
            </li>

            <li class="nav-item mb-1 px-3 mt-4">
                <small class="text-muted fw-bold text-uppercase px-3" style="font-size: 0.7rem; letter-spacing: 1px;">Lab Inventory</small>
            </li>
            <li class="nav-item mb-1 px-3">
                <a href="inventory.php" class="nav-link text-dark rounded-3 px-3 py-2 d-flex align-items-center gap-3 hover-bg-light">
                    <i class="bi bi-tags fs-5 text-muted"></i>
                    <span class="fw-medium">Equipment Categories</span>
                </a>
            </li>
            <li class="nav-item mb-1 px-3">
                <a href="assets.php" class="nav-link text-dark rounded-3 px-3 py-2 d-flex align-items-center gap-3 hover-bg-light">
                    <i class="bi bi-upc-scan fs-5 text-muted"></i>
                    <span class="fw-medium">Physical Assets (IDs)</span>
                </a>
            </li>
            <li class="nav-item mb-1 px-3">
                <a href="slip_history.php" class="nav-link text-dark rounded-3 px-3 py-2 d-flex align-items-center gap-3 hover-bg-light">
                    <i class="bi bi-clock-history fs-5 text-muted"></i>
                    <span class="fw-medium">Slip History</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-footer p-3 border-top position-absolute bottom-0 w-100 bg-white">
        <a href="../logout.php" class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2 text-danger fw-bold border-0 shadow-sm rounded-pill" style="background-color: #fff0f0;">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
</nav>

<style>
    .hover-bg-light:hover { background-color: rgba(31, 125, 83, 0.05); color: var(--ccs-primary) !important; }
    .hover-bg-light:hover i { color: var(--ccs-primary) !important; }
</style>