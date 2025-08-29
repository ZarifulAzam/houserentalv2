<?php
require_once __DIR__.'/../../config/session.php';
require_login(['admin']);
require_once __DIR__.'/../../config/db.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$location_filter = trim($_GET['location'] ?? '');

// Build query with filters
$sql = 'SELECT h.id, h.title, h.location, h.rent_price, h.status, u.name as owner, u.email as owner_email 
        FROM house_information h 
        LEFT JOIN users u ON u.id = h.owner_id 
        WHERE 1=1';
$params = [];
$types = '';

if ($status_filter && in_array($status_filter, ['available', 'rented'])) {
    $sql .= ' AND h.status = ?';
    $params[] = $status_filter;
    $types .= 's';
}

if ($location_filter) {
    $sql .= ' AND h.location LIKE ?';
    $params[] = '%' . $location_filter . '%';
    $types .= 's';
}

$sql .= ' ORDER BY h.id DESC';

$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$houses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$total_houses = count($houses);
$available_count = 0;
$rented_count = 0;

foreach ($houses as $house) {
    if ($house['status'] === 'available') {
        $available_count++;
    } else {
        $rented_count++;
    }
}
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
        <h2 style="margin: 0;">All Houses</h2>
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
    
    <!-- Filter Form -->
    <form method="get" class="card" style="margin-bottom: 20px;">
        <div class="content">
            <h3>Filter Houses</h3>
            <div class="row">
                <div class="col">
                    <label>Location
                        <input class="input" name="location" placeholder="Search by location" 
                               value="<?php echo htmlspecialchars($location_filter); ?>">
                    </label>
                </div>
                <div class="col">
                    <label>Status
                        <select class="input" name="status">
                            <option value="">All Status</option>
                            <option value="available" <?php if($status_filter === 'available') echo 'selected'; ?>>Available</option>
                            <option value="rented" <?php if($status_filter === 'rented') echo 'selected'; ?>>Rented</option>
                        </select>
                    </label>
                </div>
                <div class="col" style="flex: 0 0 auto; display: flex; align-items: end; gap: 8px;">
                    <button class="btn primary" type="submit">Filter</button>
                    <a href="houses.php" class="btn">Clear</a>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Summary Statistics -->
    <div class="row" style="margin-bottom: 24px;">
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--primary); margin: 0;"><?php echo $total_houses; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Total Houses</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--success); margin: 0;"><?php echo $available_count; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Available</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--warning); margin: 0;"><?php echo $rented_count; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Rented</p>
            </div>
        </div>
    </div>
    
    <!-- Houses Table -->
    <?php if (empty($houses)): ?>
        <div class="card">
            <div class="content" style="text-align: center; padding: 40px;">
                <h3>No Houses Found</h3>
                <p style="color: var(--muted);">No houses match your current filter criteria.</p>
                <a href="houses.php" class="btn primary">Show All Houses</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="content" style="overflow-x: auto;">
                <table style="width: 100%; margin: 0;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Title</th>
                            <th style="text-align: left;">Owner</th>
                            <th style="text-align: left;">Location</th>
                            <th style="text-align: right;">Rent</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($houses as $h): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($h['title']); ?></strong>
                            </td>
                            <td>
                                <?php if ($h['owner']): ?>
                                    <div><?php echo htmlspecialchars($h['owner']); ?></div>
                                    <div style="font-size: 0.875rem; color: var(--muted);">
                                        <?php echo htmlspecialchars($h['owner_email']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--muted);">No Owner</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($h['location']); ?></td>
                            <td style="text-align: right; font-weight: 600; color: var(--primary);">
                                ৳<?php echo number_format((int)$h['rent_price']); ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge <?php echo $h['status'] === 'available' ? 'ok' : 'warn'; ?>">
                                    <?php echo htmlspecialchars($h['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
table th {
    padding: 12px 8px;
    border-bottom: 2px solid #283249;
    font-weight: 600;
}

table td {
    padding: 12px 8px;
    border-bottom: 1px solid #1f2437;
}

table tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

@media (max-width: 768px) {
    table th,
    table td {
        padding: 8px 4px;
        font-size: 0.875rem;
    }
    
    .row {
        flex-direction: column;
    }
    
    .col {
        width: 100%;
    }
}
</style>

<?php require_once __DIR__.'/../../includes/footer.php'; ?>