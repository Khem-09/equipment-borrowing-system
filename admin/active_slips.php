<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
} // Added missing closing brace for the login protection check

require_once '../classes/database.php';
$db = new Database();
$conn = $db->getConnection();
$message = '';

// --- PROCESS RETURNS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_return') {
    $slip_id = (int)$_POST['slip_id'];
    $item_statuses = $_POST['item_status']; // Array of slip_item_id => condition
    $asset_ids = $_POST['asset_id']; // Array of slip_item_id => asset_id
    $penalty_types = $_POST['penalty_type'] ?? []; // Array of slip_item_id => penalty
    $penalty_deadlines = $_POST['penalty_deadline'] ?? []; // Array of slip_item_id => deadline

    try {
        $conn->beginTransaction();
        $stmt_update_item = $conn->prepare("UPDATE slip_items SET return_status = ?, return_date = NOW(), penalty_type = ?, penalty_status = ?, penalty_deadline = ? WHERE id = ?");
        $stmt_update_asset = $conn->prepare("UPDATE equipment_assets SET status = ? WHERE id = ?");
        $all_intact = true;

        // Loop through everything the admin inspected on the tray
        foreach ($item_statuses as $slip_item_id => $condition) {
            $a_id = $asset_ids[$slip_item_id];

            // 1. Update the record on the slip
            $p_type = null;
            $p_status = 'None';
            $p_deadline = null;
            if (($condition === 'Returned_Broken' || $condition === 'Lost') && !empty($penalty_types[$slip_item_id])) {
                $p_type = $penalty_types[$slip_item_id];
                $p_status = 'Pending';
                $p_deadline = !empty($penalty_deadlines[$slip_item_id]) ? $penalty_deadlines[$slip_item_id] : date('Y-m-d', strtotime('+7 days'));
            }
            $stmt_update_item->execute([$condition, $p_type, $p_status, $p_deadline, $slip_item_id]);

            // 2. Update the actual physical asset's status
            if ($condition === 'Returned_Intact') {
                $stmt_update_asset->execute(['Available', $a_id]);
            } elseif ($condition === 'Returned_Broken') {
                $stmt_update_asset->execute(['Broken', $a_id]);
                $all_intact = false;
            } elseif ($condition === 'Lost') {
                $stmt_update_asset->execute(['Lost', $a_id]);
                $all_intact = false;
            }
        }

        // 3. Close the Slip
        $final_slip_status = $all_intact ? 'Returned' : 'Incomplete'; // Mark Incomplete if something was broken/lost
        $stmt_close_slip = $conn->prepare("UPDATE slips SET status = ? WHERE id = ?");
        $stmt_close_slip->execute([$final_slip_status, $slip_id]);

        $conn->commit();
        $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4'><i class='bi bi-check-circle me-2'></i>Slip closed successfully. Asset statuses updated.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4'>Error processing return: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// --- FETCH ACTIVE SLIPS ---
$stmt = $conn->query("SELECT * FROM slips WHERE status = 'Active' ORDER BY issue_date ASC");
$active_slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Extract unique courses from active slips for filters
$courses = array_filter(array_unique(array_column($active_slips, 'course_section')));
sort($courses);

// Fetch items for these slips
$slip_items = [];
if (count($active_slips) > 0) {
    // Get all slip IDs to query their items at once
    $slip_ids = array_column($active_slips, 'id');
    $placeholders = str_repeat('?,', count($slip_ids) - 1) . '?';

    $query_items = "
        SELECT si.*, a.unique_asset_code, c.category_name
        FROM slip_items si
        JOIN equipment_assets a ON si.asset_id = a.id
        JOIN equipment_categories c ON a.category_id = c.id
        WHERE si.slip_id IN ($placeholders)
    ";
    $stmt_items = $conn->prepare($query_items);
    $stmt_items->execute($slip_ids);
    $all_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Group items by slip_id for easy display
    foreach ($all_items as $item) {
        $slip_items[$item['slip_id']][] = $item;
    }
} // <--- FIXED: Closed the missing inner logic check block here!
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Returns | LabBorrow</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <!-- QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        #qr-reader {
            width: 100% !important;
            height: 100% !important;
            border: none !important;
            background-color: #000 !important;
        }
        #qr-reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }
        /* Viewfinder Overlay styling */
        .qr-scanner-wrapper {
            position: relative;
            width: 100%;
            max-width: 320px;
            aspect-ratio: 1/1;
            overflow: hidden;
            background: #000;
        }
        .qr-viewfinder-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        .qr-target-box {
            width: 200px;
            height: 200px;
            position: relative;
            border: 2px dashed rgba(255, 255, 255, 0.45);
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.55); /* Dim areas outside focus */
            border-radius: 16px;
        }
        .qr-target-box .corner {
            position: absolute;
            width: 24px;
            height: 24px;
            border: 4px solid var(--ccs-primary);
        }
        .qr-target-box .top-left {
            top: -2px;
            left: -2px;
            border-bottom: none;
            border-right: none;
            border-top-left-radius: 8px;
        }
        .qr-target-box .top-right {
            top: -2px;
            right: -2px;
            border-bottom: none;
            border-left: none;
            border-top-right-radius: 8px;
        }
        .qr-target-box .bottom-left {
            bottom: -2px;
            left: -2px;
            border-top: none;
            border-right: none;
            border-bottom-left-radius: 8px;
        }
        .qr-target-box .bottom-right {
            bottom: -2px;
            right: -2px;
            border-top: none;
            border-left: none;
            border-bottom-right-radius: 8px;
        }
        /* Animated scan line */
        .scanner-laser-line {
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--ccs-primary), transparent);
            position: absolute;
            top: 0;
            animation: scanLaser 2.2s linear infinite;
        }
        @keyframes scanLaser {
            0% { top: 5%; }
            50% { top: 95%; }
            100% { top: 5%; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="me-4"><i class="bi bi-list"></i></button>
                <h5 class="m-0 fw-bold">Active Borrows (Return Desk)</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 d-none d-sm-block">
                    <div class="fw-bold" style="font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">System Administrator</div>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['full_name']) ?>&background=1F7D53&color=fff&bold=true" class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>

        <div class="content-area p-4 p-md-5">
            <?= $message ?>

            <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                <div class="row gx-3 gy-3 align-items-center">
                    <div class="col-lg-6">
                        <label class="form-label text-muted small fw-bold mb-1">SEARCH ACTIVE SLIPS</label>
                        <div class="input-group shadow-sm rounded-pill overflow-hidden" style="max-width: 100%;">
                            <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="slipSearchInput" class="form-control border-0 ps-0 text-dark" placeholder="Search by slip no, name, ID, subject, professor...">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label text-muted small fw-bold mb-1">COURSE & SECTION</label>
                        <select id="courseFilter" class="form-select bg-light rounded-pill border-0 shadow-sm" style="font-size: 0.9rem;">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 d-flex flex-column">
                        <label class="form-label text-muted small fw-bold mb-1">&nbsp;</label>
                        <button type="button" class="btn btn-custom w-100 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#scanQrModal">
                            <i class="bi bi-qr-code-scan me-2"></i> Scan Slip QR
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-card shadow-sm border-0 bg-white rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-transparent text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                <tr>
                    <th class="border-bottom-0 pb-3 ps-4 pt-4">Slip No.</th>
                    <th class="border-bottom-0 pb-3 pt-4">Borrower Info</th>
                    <th class="border-bottom-0 pb-3 pt-4">Class Details</th>
                    <th class="border-bottom-0 pb-3 pt-4">Items Borrowed</th>
                    <th class="border-bottom-0 pb-3 pe-4 pt-4 text-end">Action</th>
                </tr>
            </thead>
            <tbody id="activeSlipsBody">
                <?php if (count($active_slips) > 0): ?>
                    <?php foreach ($active_slips as $slip): ?>
                        <tr data-slip-id="<?= $slip['id'] ?>">
                            <td class="slip-number-col ps-4 fw-bold text-primary font-monospace small">
                                <?= $slip['slip_number'] ?>
                                <?php if (date('Y-m-d', strtotime($slip['issue_date'])) < date('Y-m-d')): ?>
                                    <br><span class="badge bg-danger bg-opacity-10 text-danger border border-danger mt-1" style="font-size: 0.65rem;"><i class="bi bi-clock-history me-1"></i>OVERDUE</span>
                                <?php else: ?>
                                    <br><span class="badge bg-success bg-opacity-10 text-success border border-success mt-1" style="font-size: 0.65rem;"><i class="bi bi-clock me-1"></i>TODAY</span>
                                <?php endif; ?>
                            </td>
                            <td class="student-info-col">
                                <div class="fw-bold" style="color: var(--ccs-darkest);"><?= htmlspecialchars($slip['student_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($slip['student_id']) ?> &bull; <span class="course-section-text"><?= htmlspecialchars($slip['course_section']) ?></span></div>
                            </td>
                            <td class="class-info-col">
                                <div class="fw-medium text-dark"><?= htmlspecialchars($slip['subject_code']) ?></div>
                                <div class="text-muted small">Prof. <?= htmlspecialchars($slip['instructor_name']) ?> &bull; <?= htmlspecialchars($slip['class_time']) ?></div>
                            </td>
                            <td class="items-info-col">
                                <ul class="list-unstyled small mb-0 font-monospace text-muted">
                                    <?php
                                    $items_for_this_slip = $slip_items[$slip['id']] ?? [];
                                    foreach ($items_for_this_slip as $item) {
                                        echo "<li><i class='bi bi-dot'></i> <span class='text-dark fw-semibold'>{$item['unique_asset_code']}</span> <span class='text-secondary' style='font-size: 0.75rem;'>{$item['category_name']}</span></li>";
                                    }
                                    ?>
                                </ul>
                            </td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-sm btn-custom rounded-pill fw-bold px-3 shadow-sm" onclick="openReturnModal(<?= $slip['id'] ?>)">
                                    <i class="bi bi-box-arrow-in-down me-1"></i> Process
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="emptyTableStateRow">
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-check2-circle display-4 text-success opacity-50 mb-3 d-block"></i>
                            <h5 class="text-muted fw-bold">All clear!</h5>
                            <p class="text-muted mb-0">There are currently no active borrowing slips.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr id="noResultsRow" class="d-none">
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-slash-circle display-4 text-danger opacity-50 mb-3 d-block"></i>
                        <h5 class="text-muted fw-bold">No matching records found</h5>
                        <p class="text-muted mb-0">Adjust your search keyword or selected course filter.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($active_slips as $slip): ?>
    <div class="modal fade" id="returnModal_<?= $slip['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="" class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Process Return: <?= $slip['slip_number'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="process_return">
                    <input type="hidden" name="slip_id" value="<?= $slip['id'] ?>">

                    <div class="alert alert-info py-2 small mb-4">
                        <i class="bi bi-info-circle me-1"></i> Inspect the tray and log the condition of each item.
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th>Asset Code</th>
                                    <th>Item Type</th>
                                    <th width="200">Return Condition</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $items = $slip_items[$slip['id']] ?? [];
                                foreach ($items as $item):
                                ?>
                                    <tr>
                                        <td class="font-monospace fw-bold text-primary"><?= $item['unique_asset_code'] ?></td>
                                        <td class="small fw-medium"><?= htmlspecialchars($item['category_name']) ?></td>
                                        <td>
                                            <input type="hidden" name="asset_id[<?= $item['id'] ?>]" value="<?= $item['asset_id'] ?>">

                                            <select name="item_status[<?= $item['id'] ?>]" class="form-select form-select-sm border-secondary" onchange="togglePenalty(this, <?= $item['id'] ?>)" required>
                                                <option value="Returned_Intact" selected>✅ Intact / Good</option>
                                                <option value="Returned_Broken">❌ Broken / Damaged</option>
                                                <option value="Lost">❓ Missing / Lost</option>
                                            </select>
                                            <div id="penalty_group_<?= $item['id'] ?>" class="d-none mt-2">
                                                <select name="penalty_type[<?= $item['id'] ?>]" id="penalty_<?= $item['id'] ?>" class="form-select form-select-sm border-danger mb-1">
                                                    <option value="">-- Select Penalty --</option>
                                                    <option value="Replace Item">Replace Item</option>
                                                    <option value="Community Service">Community Service</option>
                                                    <option value="Pay Fine">Pay Fine</option>
                                                </select>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text border-danger text-danger bg-transparent" style="font-size: 0.7rem;">Due by</span>
                                                    <input type="date" name="penalty_deadline[<?= $item['id'] ?>]" id="deadline_<?= $item['id'] ?>" class="form-control border-danger" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Confirm & Close Slip</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<!-- SCAN SLIP QR MODAL -->
<div class="modal fade" id="scanQrModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--ccs-darkest);"><i class="bi bi-qr-code-scan me-2" style="color: var(--ccs-primary);"></i>Scan Slip QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnExitScanner"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted small mb-3">Align the borrowing slip QR code inside the camera view finder frame below to process returns automatically.</p>

                <!-- SCANNER GRAPHICAL AREA -->
                <div class="mx-auto mb-3 qr-scanner-wrapper rounded-4 shadow">
                    <div id="qr-reader" style="width: 100%; height: 100%;"></div>
                    <!-- Viewfinder Square Overlay Target -->
                    <div class="qr-viewfinder-overlay">
                        <div class="qr-target-box">
                            <span class="corner top-left"></span>
                            <span class="corner top-right"></span>
                            <span class="corner bottom-left"></span>
                            <span class="corner bottom-right"></span>
                            <div class="scanner-laser-line"></div>
                        </div>
                    </div>
                </div>

                <div id="scannerFeedback" class="badge bg-warning text-dark border p-2 mb-3">
                    Initializing camera stream...
                </div>

                <!-- FILE UPLOAD FALLBACK -->
                <div class="bg-light rounded-3 p-3 border text-start mb-3">
                    <label class="form-label text-muted small fw-bold mb-1"><i class="bi bi-file-earmark-image me-1"></i>OR UPLOAD SLIP QR IMAGE</label>
                    <input type="file" id="qrFileSelector" class="form-control form-control-sm" accept="image/*">
                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">Useful if the camera is already in-use by another application.</div>
                </div>

                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close Scanner</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });

    // --- 1. OPEN THE RETURN MODAL ---
    function openReturnModal(slipId) {
        var modalId = 'returnModal_' + slipId;
        var modalEl = document.getElementById(modalId);
        if (modalEl) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            alert("Return modal markup not found for slip ID: " + slipId);
        }
    }

    // --- 2. SEARCH & COURSE FILTERING LOGIC ---
    const searchInput = document.getElementById('slipSearchInput');
    const courseFilter = document.getElementById('courseFilter');
    const activeSlipsBody = document.getElementById('activeSlipsBody');
    const emptyState = document.getElementById('emptyTableStateRow');
    const noResultsRow = document.getElementById('noResultsRow');

    function filterActiveSlips() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const course = courseFilter ? courseFilter.value.toLowerCase().trim() : '';

        const tableRows = Array.from(activeSlipsBody.querySelectorAll('tr')).filter(row => {
            return row.id !== 'emptyTableStateRow' && row.id !== 'noResultsRow';
        });

        if (tableRows.length === 0) return;

        let visibleCount = 0;

        tableRows.forEach(row => {
            const slipNumber = row.querySelector('.slip-number-col')?.textContent.toLowerCase() || '';
            const studentInfo = row.querySelector('.student-info-col')?.textContent.toLowerCase() || '';
            const classInfo = row.querySelector('.class-info-col')?.textContent.toLowerCase() || '';
            const itemsInfo = row.querySelector('.items-info-col')?.textContent.toLowerCase() || '';

            const matchesQuery = query === '' ||
                                 slipNumber.includes(query) ||
                                 studentInfo.includes(query) ||
                                 classInfo.includes(query) ||
                                 itemsInfo.includes(query);

            const courseText = row.querySelector('.course-section-text')?.textContent.toLowerCase() || '';
            const matchesCourse = course === '' || courseText === course; // exact course match

            if (matchesQuery && matchesCourse) {
                row.classList.remove('d-none');
                visibleCount++;
            } else {
                row.classList.add('d-none');
            }
        });

        // Toggle search fallback message
        if (visibleCount === 0) {
            noResultsRow.classList.remove('d-none');
        } else {
            noResultsRow.classList.add('d-none');
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterActiveSlips);
    if (courseFilter) courseFilter.addEventListener('change', filterActiveSlips);

    // --- 3. QR CODE SCANNER CONTROLLER ---
    let html5QrcodeScanner = null;
    const scanQrModalElement = document.getElementById('scanQrModal');

    if (scanQrModalElement) {
        scanQrModalElement.addEventListener('shown.bs.modal', function () {
            const feedback = document.getElementById('scannerFeedback');
            feedback.className = "badge bg-info text-white border p-2 mb-3";
            feedback.textContent = "Waiting for layout stability...";

            // Delay initialization slightly to let Bootstrap modal's CSS slide/fade transitions settle
            setTimeout(function() {
                feedback.textContent = "Requesting system camera permissions...";

                // Instantiate Html5Qrcode scanner targeting the div frame
                html5QrcodeScanner = new Html5Qrcode("qr-reader");

                // Enumerate cameras to avoid hard constraints errors on single-lens webcams
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length > 0) {
                        // Try to identify a back/environment lens, else fallback to first available
                        let cameraId = devices[0].id;
                        for (let device of devices) {
                            const label = device.label.toLowerCase();
                            if (label.includes("back") || label.includes("rear") || label.includes("environment")) {
                                cameraId = device.id;
                                break;
                            }
                        }

                        // Start camera using specific ID and ideal constraints (scanning full window frame)
                        html5QrcodeScanner.start(
                            cameraId,
                            {
                                fps: 15,
                                videoConstraints: {
                                    width: { min: 640, ideal: 1280 },
                                    height: { min: 480, ideal: 720 }
                                }
                            },
                            onScanSuccess,
                            onScanFailure
                        ).then(() => {
                            feedback.className = "badge bg-success text-white border p-2 mb-3";
                            feedback.textContent = "Camera active. Fit QR code inside region.";
                        }).catch(err => {
                            console.error("Camera start failure:", err);
                            feedback.className = "badge bg-danger text-white border p-2 mb-3";
                            feedback.textContent = "Camera error: " + err;
                        });
                    } else {
                        feedback.className = "badge bg-danger text-white border p-2 mb-3";
                        feedback.textContent = "No camera devices detected.";
                    }
                }).catch(err => {
                    console.error("Camera enumeration failure:", err);
                    feedback.className = "badge bg-danger text-white border p-2 mb-3";
                    feedback.textContent = "Camera error: " + err;
                });
            }, 350);
        });

        scanQrModalElement.addEventListener('hidden.bs.modal', function () {
            stopScanner();
        });
    }

    function stopScanner() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner = null;
                document.getElementById('qr-reader').innerHTML = "";
            }).catch(err => {
                console.warn("Error stopping scanner stream:", err);
                html5QrcodeScanner = null;
            });
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        console.log("QR Scan Matched Data:", decodedText);

        // Kill scanner ASAP
        stopScanner();

        // Close scanner modal
        const bootstrapModal = bootstrap.Modal.getInstance(scanQrModalElement);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }

        // Search active slip rows for the scanned number
        const tableRows = Array.from(activeSlipsBody.querySelectorAll('tr')).filter(row => {
            return row.id !== 'emptyTableStateRow' && row.id !== 'noResultsRow';
        });

        let targetRow = null;
        for (let row of tableRows) {
            const slipCol = row.querySelector('.slip-number-col');
            if (slipCol && slipCol.textContent.trim().toLowerCase() === decodedText.trim().toLowerCase()) {
                targetRow = row;
                break;
            }
        }

        if (targetRow) {
            // Match found! Highlight row momentarily and open modal
            targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

            const originalBg = targetRow.style.backgroundColor;
            targetRow.style.backgroundColor = "rgba(31, 125, 83, 0.2)";
            setTimeout(() => {
                targetRow.style.backgroundColor = originalBg;
            }, 1500);

            const slipId = targetRow.getAttribute('data-slip-id');
            setTimeout(() => {
                openReturnModal(slipId);
            }, 400);
        } else {
            alert("Active Borrowing Slip Number '" + decodedText + "' was not found active in the desk records.");
        }
    }

    function onScanFailure(error) {
        // Continuous decoding failure loop is silent to prevent log spam
    }

    // --- 4. QR CODE FILE UPLOAD SCANNER ---
    const qrFileSelector = document.getElementById('qrFileSelector');
    if (qrFileSelector) {
        qrFileSelector.addEventListener('change', function (e) {
            if (!e.target.files || e.target.files.length === 0) return;

            const file = e.target.files[0];
            const feedback = document.getElementById('scannerFeedback');
            feedback.className = "badge bg-info text-white border p-2 mb-3";
            feedback.textContent = "Processing selected image for QR code...";

            // Stop the camera-based scanner is it's active
            stopScanner();

            // Instantiate a scanner instance specifically for file parsing
            const fileScanner = new Html5Qrcode("qr-reader");
            fileScanner.scanFile(file, true)
                .then(decodedText => {
                    feedback.className = "badge bg-success text-white border p-2 mb-3";
                    feedback.textContent = "QR Code parsed successfully from file upload!";

                    // Clear the file picker
                    qrFileSelector.value = '';

                    // Call the scan success logic
                    onScanSuccess(decodedText, null);
                })
                .catch(err => {
                    console.error("Local file scanner error:", err);
                    feedback.className = "badge bg-danger text-white border p-2 mb-3";
                    feedback.textContent = "Failed to parse QR code from selected image.";
                    qrFileSelector.value = '';
                });
        });
    }

    // Function to toggle penalty dropdown and deadline
    function togglePenalty(selectElement, itemId) {
        var penaltyGroup = document.getElementById('penalty_group_' + itemId);
        var penaltySelect = document.getElementById('penalty_' + itemId);
        var deadlineInput = document.getElementById('deadline_' + itemId);

        if (selectElement.value === 'Returned_Broken' || selectElement.value === 'Lost') {
            penaltyGroup.classList.remove('d-none');
            penaltySelect.required = true;
            deadlineInput.required = true;
        } else {
            penaltyGroup.classList.add('d-none');
            penaltySelect.required = false;
            deadlineInput.required = false;
            penaltySelect.value = "";
        }
    }
</script>
</body>
</html>