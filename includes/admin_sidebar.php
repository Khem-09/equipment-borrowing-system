<?php 
$currentPage = basename($_SERVER['PHP_SELF']); 
?>

<div class="sidebar shadow-sm" id="sidebar">
    <div class="sidebar-header">
        <h5 class="m-0 fw-bold menu-text text-white">Admin</h5>
        <i class="bi bi-shield-lock d-none collapsed-icon text-white"></i>
    </div>
    
    <div class="px-4 mt-4 mb-2 text-uppercase menu-label" style="font-size: 0.7rem; letter-spacing: 1.2px; color: rgba(255,255,255,0.4);">
        Management Menu
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill me-3"></i> <span class="menu-text">Dashboard Overview</span>
            </a>
        </li>
        <li>
            <a href="inventory.php" class="<?= ($currentPage == 'inventory.php') ? 'active' : '' ?>">
                <i class="bi bi-box-seam me-3"></i> <span class="menu-text">Inventory Status</span>
            </a>
        </li>
        <li>
            <a href="requests.php" class="<?= ($currentPage == 'requests.php') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-check me-3"></i> <span class="menu-text">Pending Requests</span>
            </a>
        </li>
        <li>
            <a href="users.php" class="<?= ($currentPage == 'users.php') ? 'active' : '' ?>">
                <i class="bi bi-people me-3"></i> <span class="menu-text">Manage Students</span>
            </a>
        </li>
        <li class="mt-4">
            <a href="../logout.php" style="color: #ff6b6b;">
                <i class="bi bi-box-arrow-left me-3"></i> <span class="menu-text">Logout</span>
            </a>
        </li>
    </ul>
</div>