<?php
require_once '../classes/database.php';
require_once '../classes/analytics.php';

$database = new Database();
$db = $database->getConnection();
$analytics = new Analytics($db);

// 1. Capture Filters from URL
$currentFilters = [
    'category_id' => $_GET['category_id'] ?? '',
    'asset_id' => $_GET['asset_id'] ?? '',
    'spec_id' => $_GET['spec_id'] ?? ''
];

// 2. Fetch Data (Pass filters into getFilterOptions for dependency)
$filterOptions = $analytics->getFilterOptions($currentFilters);
$metrics = $analytics->getQuickMetrics($currentFilters);
$popularCategories = $analytics->getPopularCategories($currentFilters);
$recentVolume = $analytics->getRecentBorrowingVolume($currentFilters);

if (isset($_GET['export']) && $_GET['export'] === '1') {
    $selectedCategory = null;
    foreach ($filterOptions['categories'] as $cat) {
        if ((string)$cat['id'] === (string)$currentFilters['category_id']) {
            $selectedCategory = $cat['category_name'];
            break;
        }
    }

    $selectedSpec = null;
    foreach ($filterOptions['specs'] as $spec) {
        if ((string)$spec['id'] === (string)$currentFilters['spec_id']) {
            $selectedSpec = $spec['specification_name'];
            break;
        }
    }

    $selectedAsset = null;
    foreach ($filterOptions['assets'] as $asset) {
        if ((string)$asset['id'] === (string)$currentFilters['asset_id']) {
            $selectedAsset = $asset['unique_asset_code'];
            break;
        }
    }

    $filename = 'analytics_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Analytics Report']);
    fputcsv($output, ['Category Filter', $selectedCategory ?? 'All Categories']);
    fputcsv($output, ['Specification Filter', $selectedSpec ?? 'All Specifications']);
    fputcsv($output, ['Asset Filter', $selectedAsset ?? 'All Assets']);
    fputcsv($output, []);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Assets', $metrics['total_assets']]);
    fputcsv($output, ['Total Slips', $metrics['total_slips']]);
    fputcsv($output, []);
    fputcsv($output, ['Most Borrowed Categories']);
    fputcsv($output, ['Category', 'Borrow Count']);
    foreach ($popularCategories as $row) {
        fputcsv($output, [$row['category_name'], $row['borrow_count']]);
    }
    fputcsv($output, []);
    fputcsv($output, ['Borrowing Volume (Last 7 Days)']);
    fputcsv($output, ['Date', 'Daily Count']);
    foreach ($recentVolume as $row) {
        fputcsv($output, [$row['borrow_date'], $row['daily_count']]);
    }
    fclose($output);
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    .printable-analytics, .printable-analytics * {
        visibility: visible;
    }

    .printable-analytics {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }

    .sidebar, .navbar, .content-wrapper > .container-fluid > .d-flex.justify-content-between.align-items-center.mb-4, .btn, .card-header, .card-body canvas {
        display: none !important;
    }

    .content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }

    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
        break-inside: avoid;
    }
}
</style>

<div class="content-wrapper printable-analytics" style="margin-left: 260px; padding: 2rem;">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0 fw-bolder" style="color: var(--ccs-darkest);">System Analytics</h3>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary fw-medium" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
                <?php
                $exportQuery = $_GET;
                $exportQuery['export'] = '1';
                $exportUrl = 'analytics.php?' . http_build_query($exportQuery);
                ?>
                <a href="<?php echo htmlspecialchars($exportUrl); ?>" class="btn btn-outline-success fw-medium">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4 d-print-none">
            <div class="card-body p-4">
                <form id="filterForm" method="GET" action="analytics.php" class="row g-3 align-items-end">
                    
                    <!-- Category Dropdown -->
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Category</label>
                        <select name="category_id" id="category_select" class="form-select border-0 bg-light">
                            <option value="">All Categories</option>
                            <?php foreach ($filterOptions['categories'] as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($currentFilters['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Specification Dropdown (Filtered by Category) -->
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Specification</label>
                        <select name="spec_id" id="spec_select" class="form-select border-0 bg-light">
                            <option value="">All Specifications</option>
                            <?php foreach ($filterOptions['specs'] as $spec): ?>
                                <option value="<?php echo $spec['id']; ?>" <?php echo ($currentFilters['spec_id'] == $spec['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['specification_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Asset Dropdown (Filtered by Spec/Category) -->
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-bold" style="font-size: 0.85rem;">Specific Asset</label>
                        <select name="asset_id" id="asset_select" class="form-select border-0 bg-light">
                            <option value="">All Assets</option>
                            <?php foreach ($filterOptions['assets'] as $asset): ?>
                                <option value="<?php echo $asset['id']; ?>" <?php echo ($currentFilters['asset_id'] == $asset['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($asset['unique_asset_code']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-medium" style="background-color: var(--ccs-primary); border-color: var(--ccs-primary);">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                        <?php if(array_filter($currentFilters)): ?>
                            <a href="analytics.php" class="btn btn-light fw-medium text-danger border">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10" style="width: 60px; height: 60px;">
                            <i class="bi bi-upc-scan fs-3 text-primary"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 fw-bold text-uppercase" style="font-size: 0.8rem;">Total Assets (Filtered)</p>
                            <h2 class="mb-0 fw-bolder"><?php echo $metrics['total_assets']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10" style="width: 60px; height: 60px;">
                            <i class="bi bi-receipt fs-3 text-success"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 fw-bold text-uppercase" style="font-size: 0.8rem;">Total Slips (Filtered)</p>
                            <h2 class="mb-0 fw-bolder"><?php echo $metrics['total_slips']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-muted">Most Borrowed Categories</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-muted">Borrowing Volume (Last 7 Days)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="volumeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// --- Dependent Filter Auto-submit Logic ---
document.getElementById('category_select').addEventListener('change', function() {
    // When category changes, clear spec and asset and refresh to get filtered options
    document.getElementById('spec_select').value = '';
    document.getElementById('asset_select').value = '';
    document.getElementById('filterForm').submit();
});

document.getElementById('spec_select').addEventListener('change', function() {
    // When spec changes, clear asset and refresh
    document.getElementById('asset_select').value = '';
    document.getElementById('filterForm').submit();
});

// --- Chart Logic (Remains unchanged to prevent breakage) ---
const categoryData = <?php echo json_encode($popularCategories); ?>;
const volumeData = <?php echo json_encode($recentVolume); ?>;

if(categoryData.length > 0) {
    const catLabels = categoryData.map(item => item.category_name);
    const catCounts = categoryData.map(item => item.borrow_count);

    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Times Borrowed',
                data: catCounts,
                backgroundColor: 'rgba(31, 125, 83, 0.7)',
                borderColor: 'rgba(31, 125, 83, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
}

if(volumeData.length > 0) {
    const volLabels = volumeData.map(item => item.borrow_date);
    const volCounts = volumeData.map(item => item.daily_count);

    new Chart(document.getElementById('volumeChart'), {
        type: 'line',
        data: {
            labels: volLabels,
            datasets: [{
                label: 'Slips Created',
                data: volCounts,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
}
</script>

<?php include '../includes/footer.php'; ?>