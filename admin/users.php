<?php
session_start();
require_once '../classes/database.php';

// Ensure only logged-in Admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// --- 1. HANDLE ADMIN ACTIONS (Approve / Suspend / Reactivate / Reject) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['student_id'])) {
    $action = $_POST['action'];
    $student_id = $_POST['student_id'];

    if ($action === 'approve' || $action === 'reactivate') {
        $update_stmt = $conn->prepare("UPDATE users SET account_status = 'Active' WHERE id = :id AND role = 'Student'");
        $update_stmt->execute(['id' => $student_id]);
    } elseif ($action === 'suspend') {
        $update_stmt = $conn->prepare("UPDATE users SET account_status = 'Suspended' WHERE id = :id AND role = 'Student'");
        $update_stmt->execute(['id' => $student_id]);
    } elseif ($action === 'reject') {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role = 'Student' AND account_status = 'Pending'");
        $delete_stmt->execute(['id' => $student_id]);
    }

    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';
    $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
    header("Location: users.php?tab=" . urlencode($current_tab) . "&page=" . urlencode($current_page));
    exit;
}

// --- 2. HANDLE TAB FILTERING ---
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';
$status_filter = 'Pending';
if ($tab === 'active') $status_filter = 'Active';
elseif ($tab === 'suspended') $status_filter = 'Suspended';

// --- 3. PAGINATION LOGIC ---
$limit = 20; // Exactly 20 records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count total records for the current tab
$total_query = "SELECT COUNT(*) FROM users WHERE role = 'Student' AND account_status = :status";
$tot_stmt = $conn->prepare($total_query);
$tot_stmt->execute(['status' => $status_filter]);
$total_records = $tot_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch paginated students
$query = "SELECT id, school_id, first_name, last_name, course, year_level, section, account_status
          FROM users
          WHERE role = 'Student' AND account_status = :status
          ORDER BY created_at DESC
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
$stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Badges counts
$count_stmt = $conn->query("SELECT account_status, COUNT(*) as count FROM users WHERE role = 'Student' GROUP BY account_status");
$counts = ['Pending' => 0, 'Active' => 0, 'Suspended' => 0];
while ($row = $count_stmt->fetch(PDO::FETCH_ASSOC)) {
    $counts[$row['account_status']] = $row['count'];
}

// Pagination calculation display
$start_record = $total_records > 0 ? $offset + 1 : 0;
$end_record = min($offset + $limit, $total_records);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | Laboratory Equipment Borrowing</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        /* Enterprise UI Variables & Structural Fixes */
        :root {
            --bg-main: #f4f6f8;
            --bg-card: #ffffff;
            --border-color: #e5e7eb;
            --text-main: #1f2937;
            --text-muted: #9ca3af;
            --primary-dark: #111827;
            --accent-blue: #3b82f6; /* Bright blue for the active tab */
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg-main);
            margin: 0;
            padding: 0;
            color: var(--text-main);
        }

        .wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-main);
            overflow-y: auto;
        }

        /* Topbar Styling */
        .topbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .content-area {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* -------------------------------------------
           NEW TAB DESIGN (Based on Reference Image)
           ------------------------------------------- */
        .tabs-header-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #e5e7eb; /* The continuous light bottom line */
            margin-bottom: 1.5rem;
        }

        .custom-tabs {
            display: flex;
            gap: 2.5rem; /* Spacious gap between text */
            margin-bottom: -2px; /* Overlaps the bottom border perfectly */
            padding-left: 0.5rem;
        }

        .custom-tabs .nav-link {
            color: var(--text-muted); /* Muted gray text */
            font-weight: 500;
            font-size: 0.95rem;
            padding: 1rem 0.5rem;
            border: none;
            border-bottom: 2px solid transparent; /* Hidden by default */
            background: transparent !important;
            border-radius: 0;
            transition: all 0.2s ease;
        }

        .custom-tabs .nav-link:hover {
            color: #6b7280;
        }

        .custom-tabs .nav-link.active {
            color: var(--accent-blue); /* Bright blue text */
            border-bottom: 2px solid var(--accent-blue); /* Bright blue underline */
            font-weight: 600;
        }

        /* Tab Badges Styling */
        .custom-tabs .badge {
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 0.4rem;
            vertical-align: text-top;
        }
        .custom-tabs .nav-link:not(.active) .badge {
            background-color: #f3f4f6 !important;
            color: #9ca3af !important;
        }
        .custom-tabs .nav-link.active .badge-pending {
            background-color: #ef4444 !important; /* Keep Red for Pending Attention */
            color: white !important;
        }
        .custom-tabs .nav-link.active .badge-regular {
            background-color: #eff6ff !important;
            color: var(--accent-blue) !important;
        }

        /* Search Input */
        .search-container {
            position: relative;
            max-width: 320px;
            margin-bottom: 0.75rem; /* Align perfectly above the border */
        }
        .search-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        .search-container input {
            padding-left: 2.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            font-size: 0.9rem;
        }
        .search-container input:focus {
            border-color: #adb5bd;
            box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.05);
        }

        /* Table Card & Data Grid */
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .table { margin-bottom: 0; }

        .table thead th {
            background-color: #f9fafb;
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color) !important;
        }

        .table tbody td {
            padding: 1.25rem 1.5rem !important;
            vertical-align: middle;
            color: var(--text-main);
            font-size: 0.9rem;
            font-weight: 400 !important;
            border-bottom: 1px solid #f1f3f5 !important;
        }

        .table tbody tr:hover { background-color: #f9fafb; }

        /* Muted Flat Badges */
        .badge-status { font-weight: 500; padding: 0.35em 0.8em; border-radius: 6px; font-size: 0.8rem; }
        .badge-status-pending { background-color: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
        .badge-status-active { background-color: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
        .badge-status-suspended { background-color: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

        /* Transparent Subtle Action Icons */
        .action-icon {
            background: transparent;
            border: none;
            color: #9ca3af;
            font-size: 1.1rem;
            padding: 0.35rem 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .action-icon:hover { background: #f3f4f6; }
        .action-icon.edit:hover { color: var(--accent-blue); background: #eff6ff; }
        .action-icon.approve:hover, .action-icon.reactivate:hover { color: #059669; background: #ecfdf5; }
        .action-icon.reject:hover, .action-icon.suspend:hover { color: #dc2626; background: #fef2f2; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar Include -->
    <?php include '../includes/admin_sidebar.php'; ?>

    <div class="main-content" id="mainContent">

        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-3 border-0 bg-transparent text-muted fs-5"><i class="bi bi-list"></i></button>
                <h5 class="m-0 fw-bold" style="color: var(--primary-dark);">Student Directory</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem; color: var(--primary-dark);"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name'] ?? 'Admin') ?>&background=111827&color=fff&bold=true" class="rounded-circle shadow-sm" width="38" height="38">
            </div>
        </div>

        <div class="content-area">

            <!-- NEW TAB CONTAINER IMPLEMENTATION -->
            <div class="tabs-header-container">
                <ul class="nav custom-tabs" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="users.php?tab=pending" class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>">
                            Pending Approval
                            <?php if($counts['Pending'] > 0): ?>
                                <span class="badge rounded-pill <?= $tab === 'pending' ? 'badge-pending' : '' ?>"><?= $counts['Pending'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="users.php?tab=active" class="nav-link <?= $tab === 'active' ? 'active' : '' ?>">
                            Active Students <span class="badge rounded-pill <?= $tab === 'active' ? 'badge-regular' : '' ?>"><?= $counts['Active'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="users.php?tab=suspended" class="nav-link <?= $tab === 'suspended' ? 'active' : '' ?>">
                            Suspended <span class="badge rounded-pill <?= $tab === 'suspended' ? 'badge-regular' : '' ?>"><?= $counts['Suspended'] ?></span>
                        </a>
                    </li>
                </ul>

                <div class="search-container w-100">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" class="form-control shadow-none" placeholder="Search by name or ID...">
                </div>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table align-middle" id="studentTable">
                        <thead>
                            <tr>
                                <th class="ps-4">School ID</th>
                                <th>Full Name</th>
                                <th>Course/Yr/Sec</th>
                                <th class="text-center">Status</th>
                                <th class="pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr class="data-row">
                                        <td class="ps-4 text-muted"><?= htmlspecialchars($student['school_id']) ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                        <td><?= htmlspecialchars($student['course'] . '-' . $student['year_level'] . $student['section']) ?></td>
                                        <td class="text-center">
                                            <?php if ($student['account_status'] === 'Pending'): ?>
                                                <span class="badge-status badge-status-pending">Pending</span>
                                            <?php elseif ($student['account_status'] === 'Active'): ?>
                                                <span class="badge-status badge-status-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-status-suspended">Suspended</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <?php if ($student['account_status'] === 'Pending'): ?>
                                                <button type="button" class="action-icon approve" title="Approve" onclick="triggerModal('approve', <?= $student['id'] ?>, '<?= htmlspecialchars(addslashes($student['first_name'] . ' ' . $student['last_name'])) ?>')">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button type="button" class="action-icon reject" title="Reject" onclick="triggerModal('reject', <?= $student['id'] ?>, '<?= htmlspecialchars(addslashes($student['first_name'] . ' ' . $student['last_name'])) ?>')">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>

                                            <a href="edit_student.php?id=<?= $student['id'] ?>" class="action-icon edit d-inline-block text-decoration-none" title="Edit Record">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <?php if ($student['account_status'] === 'Suspended'): ?>
                                                <button type="button" class="action-icon reactivate" title="Reactivate" onclick="triggerModal('reactivate', <?= $student['id'] ?>, '<?= htmlspecialchars(addslashes($student['first_name'] . ' ' . $student['last_name'])) ?>')">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            <?php elseif ($student['account_status'] === 'Active'): ?>
                                                <button type="button" class="action-icon suspend" title="Suspend" onclick="triggerModal('suspend', <?= $student['id'] ?>, '<?= htmlspecialchars(addslashes($student['first_name'] . ' ' . $student['last_name'])) ?>')">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="noResultsRow">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inboxes fs-1 d-block mb-3 opacity-50"></i>
                                        No <?= htmlspecialchars($tab) ?> records found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <?php if ($total_records > 20): ?>
                <div class="d-flex align-items-center justify-content-between p-3 px-4 border-top bg-white">
                    <div class="text-muted small">
                        Showing <?= $start_record ?> to <?= $end_record ?> of <?= $total_records ?> records
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link text-dark shadow-none border-0" href="?tab=<?= $tab ?>&page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link shadow-none border-0 rounded-2 mx-1 <?= ($i == $page) ? 'bg-dark text-white' : 'text-dark' ?>" href="?tab=<?= $tab ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link text-dark shadow-none border-0" href="?tab=<?= $tab ?>&page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- Dynamic Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted py-4" id="modalBodyText">
                Are you sure you want to perform this action?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="">
                    <input type="hidden" name="action" id="modalActionInput">
                    <input type="hidden" name="student_id" id="modalStudentIdInput">
                    <button type="submit" class="btn px-4 rounded-pill" id="modalConfirmBtn">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() { document.getElementById('sidebar').classList.toggle('collapsed'); });

    // Live Omni-Search Implementation
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.data-row');

        rows.forEach(row => {
            let idText = row.cells[0].textContent.toLowerCase();
            let nameText = row.cells[1].textContent.toLowerCase();

            if (idText.includes(filter) || nameText.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Dynamic Modal Trigger Function
    function triggerModal(action, studentId, studentName) {
        const modalElement = new bootstrap.Modal(document.getElementById('confirmationModal'));
        const title = document.getElementById('modalTitle');
        const bodyText = document.getElementById('modalBodyText');
        const confirmBtn = document.getElementById('modalConfirmBtn');
        const actionInput = document.getElementById('modalActionInput');
        const idInput = document.getElementById('modalStudentIdInput');

        actionInput.value = action;
        idInput.value = studentId;

        if (action === 'approve') {
            title.textContent = 'Approve Student';
            bodyText.innerHTML = `Are you sure you want to approve the application for <strong class="text-dark">${studentName}</strong>?`;
            confirmBtn.className = 'btn btn-success px-4 rounded-pill';
            confirmBtn.textContent = 'Approve Student';
        } else if (action === 'reject') {
            title.textContent = 'Reject Application';
            bodyText.innerHTML = `Are you sure you want to reject and permanently delete the application for <strong class="text-dark">${studentName}</strong>? This cannot be undone.`;
            confirmBtn.className = 'btn btn-danger px-4 rounded-pill';
            confirmBtn.textContent = 'Reject & Delete';
        } else if (action === 'suspend') {
            title.textContent = 'Suspend Student';
            bodyText.innerHTML = `Are you sure you want to suspend the account of <strong class="text-dark">${studentName}</strong>? They will no longer be able to borrow equipment.`;
            confirmBtn.className = 'btn btn-danger px-4 rounded-pill';
            confirmBtn.textContent = 'Suspend User';
        } else if (action === 'reactivate') {
            title.textContent = 'Reactivate Student';
            bodyText.innerHTML = `Are you sure you want to reactivate the account of <strong class="text-dark">${studentName}</strong>?`;
            confirmBtn.className = 'btn btn-success px-4 rounded-pill';
            confirmBtn.textContent = 'Reactivate User';
        }

        modalElement.show();
    }
</script>
</body>
</html>