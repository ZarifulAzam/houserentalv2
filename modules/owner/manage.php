<?php
require_once __DIR__.'/../../config/session.php';
require_login(['owner','admin']);
require_once __DIR__.'/../../config/db.php';

$uid = (int)($_SESSION['user']['id'] ?? 0);
$stmt = $mysqli->prepare('SELECT id, title, location, rent_price, status FROM house_information WHERE owner_id=? ORDER BY id DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$houses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get summary counts
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
        <h2 style="margin: 0;">Manage Houses</h2>
        <a class="btn primary" href="save_house.php">Add House</a>
    </div>
    
    <!-- Summary Cards -->
    <?php if (!empty($houses)): ?>
    <div class="row" style="margin-bottom: 24px;">
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--primary); margin: 0;"><?php echo count($houses); ?></h3>
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
    <?php endif; ?>
    
    <?php if (empty($houses)): ?>
        <div class="card">
            <div class="content" style="text-align: center; padding: 40px;">
                <h3>No Houses Listed Yet</h3>
                <p style="color: var(--muted); margin-bottom: 24px;">Start by adding your first house to rent out to tenants.</p>
                <a href="save_house.php" class="btn primary">Add Your First House</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Houses Table -->
        <div class="card">
            <div class="content" style="overflow-x: auto;">
                <table style="width: 100%; margin: 0;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Title</th>
                            <th style="text-align: left;">Location</th>
                            <th style="text-align: right;">Rent</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($houses as $h): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($h['title']); ?></strong>
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
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                                        <a class="btn small" href="save_house.php?id=<?php echo (int)$h['id']; ?>">Edit</a>
                                        <a class="btn small" href="toggle_status.php?id=<?php echo (int)$h['id']; ?>" 
                                           onclick="return confirm('Toggle status for this house?')">
                                            <?php echo $h['status'] === 'available' ? 'Mark Rented' : 'Mark Available'; ?>
                                        </a>
                                        <a class="btn small danger" href="delete_house.php?id=<?php echo (int)$h['id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this house? This action cannot be undone.')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 24px; text-align: center;">
        <a class="btn" href="requests.php">View Tenant Requests</a>
    </div>
</div>

<style>
.btn.small {
    padding: 4px 8px;
    font-size: 0.875rem;
}

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
    .btn.small {
        font-size: 0.75rem;
        padding: 2px 6px;
    }
    
    table th,
    table td {
        padding: 8px 4px;
        font-size: 0.875rem;
    }
    
    .card .content {
        overflow-x: auto;
    }
}
</style>

<?php require_once __DIR__.'/../../includes/footer.php'; ?>