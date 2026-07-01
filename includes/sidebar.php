<nav id="sidebar" class="sidebar">
    <div class="sidebar-header d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: rgba(43, 192, 130, 0.15);">
            <i class="bi bi-flask-fill fs-4" style="color: var(--ccs-accent);"></i>
        </div>
        <h5 class="m-0 fw-bolder text-white" style="letter-spacing: 0.5px;">LabBorrow</h5>
    </div>
    
    <div class="sidebar-body overflow-auto">
        <ul class="nav flex-column mb-auto">
            
            <li class="nav-item mb-1">
                <a href="dashboard.php" class="nav-link-sidebar">
                    <i class="bi bi-grid fs-5"></i>
                    <span class="fw-medium menu-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item mb-1">
                <a href="analytics.php" class="nav-link-sidebar">
                    <i class="bi bi-bar-chart-line fs-5"></i>
                    <span class="fw-medium menu-text">Analytics</span>
                </a>
            </li>
            
            <li class="nav-item mb-1 mt-4">
                <small class="text-white-50 fw-bold text-uppercase px-4" style="font-size: 0.7rem; letter-spacing: 1px;">Kiosk Operations</small>
            </li>
            <li class="nav-item mb-1">
                <a href="new_slip.php" class="nav-link-sidebar">
                    <i class="bi bi-cart-plus fs-5"></i>
                    <span class="fw-medium menu-text">New Borrowing</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="active_slips.php" class="nav-link-sidebar">
                    <i class="bi bi-arrow-return-left fs-5"></i>
                    <span class="fw-medium menu-text">Active Returns</span>
                </a>
            </li>

            <li class="nav-item mb-1 mt-4">
                <small class="text-white-50 fw-bold text-uppercase px-4" style="font-size: 0.7rem; letter-spacing: 1px;">Lab Inventory</small>
            </li>
            <li class="nav-item mb-1">
                <a href="inventory.php" class="nav-link-sidebar">
                    <i class="bi bi-tags fs-5"></i>
                    <span class="fw-medium menu-text">Equipment Categories</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="assets.php" class="nav-link-sidebar">
                    <i class="bi bi-upc-scan fs-5"></i>
                    <span class="fw-medium menu-text">Physical Assets (IDs)</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="slip_history.php" class="nav-link-sidebar">
                    <i class="bi bi-clock-history fs-5"></i>
                    <span class="fw-medium menu-text">Slip History</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="p-3 border-top" style="border-color: rgba(255,255,255,0.05) !important; background: rgba(0,0,0,0.1);">
        <a href="../logout.php" class="btn w-100 d-flex align-items-center justify-content-center gap-2 fw-bold border-0 rounded-3 text-white" style="background: rgba(220, 53, 69, 0.2); transition: all 0.2s;" onmouseover="this.style.background='rgba(220, 53, 69, 0.5)'" onmouseout="this.style.background='rgba(220, 53, 69, 0.2)'">
            <i class="bi bi-box-arrow-right"></i> <span class="menu-text">Log Out</span>
        </a>
    </div>
</nav>