<?php
require_once __DIR__.'/../../config/session.php';
require_login(['admin']);
require_once __DIR__.'/../../config/db.php';

// Get dashboard statistics
$stats = [];
try {
    $stats['users'] = $mysqli->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'] ?? 0;
    $stats['houses'] = $mysqli->query('SELECT COUNT(*) c FROM house_information')->fetch_assoc()['c'] ?? 0;
    $stats['requests'] = $mysqli->query('SELECT COUNT(*) c FROM rental_requests')->fetch_assoc()['c'] ?? 0;
    $stats['available'] = $mysqli->query('SELECT COUNT(*) c FROM house_information WHERE status="available"')->fetch_assoc()['c'] ?? 0;
    $stats['rented'] = $mysqli->query('SELECT COUNT(*) c FROM house_information WHERE status="rented"')->fetch_assoc()['c'] ?? 0;
    $stats['pending_requests'] = $mysqli->query('SELECT COUNT(*) c FROM rental_requests WHERE status="pending"')->fetch_assoc()['c'] ?? 0;
} catch (Exception $e) {
    error_log('Admin dashboard stats error: ' . $e->getMessage());
    $stats = ['users' => 0, 'houses' => 0, 'requests' => 0, 'available' => 0, 'rented' => 0, 'pending_requests' => 0];
}

// Get recent activity
$recent_users = [];
$recent_houses = [];
$recent_requests = [];

try {
    $recent_users = $mysqli->query('SELECT name, email, role FROM users ORDER BY id DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
    $recent_houses = $mysqli->query('SELECT h.title, h.location, h.status, u.name as owner FROM house_information h LEFT JOIN users u ON u.id = h.owner_id ORDER BY h.id DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
    $recent_requests = $mysqli->query('SELECT r.status, h.title, u.name as tenant FROM rental_requests r JOIN house_information h ON h.id = r.house_id JOIN users u ON u.id = r.user_id ORDER BY r.id DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log('Admin dashboard recent activity error: ' . $e->getMessage());
}
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <h2>Admin Dashboard</h2>
    <p style="color: var(--muted); margin-bottom: 24px;">Welcome to the administration panel. Monitor system activity and manage users.</p>
    
    <!-- Statistics Cards -->
    <div class="row" style="margin-bottom: 32px;">
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--primary); margin: 0; font-size: 2rem;"><?php echo (int)$stats['users']; ?></h3>
                <p style="margin: 8px 0 0 0; color: var(--muted); font-weight: 500;">Total Users</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--accent); margin: 0; font-size: 2rem;"><?php echo (int)$stats['houses']; ?></h3>
                <p style="margin: 8px 0 0 0; color: var(--muted); font-weight: 500;">Total Houses</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--success); margin: 0; font-size: 2rem;"><?php echo (int)$stats['available']; ?></h3>
                <p style="margin: 8px 0 0 0; color: var(--muted); font-weight: 500;">Available</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--danger); margin: 0; font-size: 2rem;"><?php echo (int)$stats['pending_requests']; ?></h3>
                <p style="margin: 8px 0 0 0; color: var(--muted); font-weight: 500;">Pending Requests</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="content">
            <h3>Quick Actions</h3>
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px;">
                <a class="btn primary" href="users.php">Manage Users</a>
                <a class="btn" href="houses.php">View All Houses</a>
                <a class="btn" href="roles.php">Roles & Permissions</a>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row">
        <div class="col card">
            <div class="content">
                <h3>Recent Users</h3>
                <?php if (empty($recent_users)): ?>
                    <p style="color: var(--muted);">No users registered yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_users as $user): ?>
                        <div style="padding: 8px 0; border-bottom: 1px solid #1f2437;">
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div style="font-size: 0.875rem; color: var(--muted);">
                                <?php echo htmlspecialchars($user['email']); ?> • 
                                <span class="badge <?php echo $user['role'] === 'admin' ? 'warn' : 'ok'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col card">
            <div class="content">
                <h3>Recent Houses</h3>
                <?php if (empty($recent_houses)): ?>
                    <p style="color: var(--muted);">No houses listed yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_houses as $house): ?>
                        <div style="padding: 8px 0; border-bottom: 1px solid #1f2437;">
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($house['title']); ?></div>
                            <div style="font-size: 0.875rem; color: var(--muted);">
                                <?php echo htmlspecialchars($house['location']); ?> • 
                                Owner: <?php echo htmlspecialchars($house['owner'] ?? 'Unknown'); ?> •
                                <span class="badge <?php echo $house['status'] === 'available' ? 'ok' : 'warn'; ?>">
                                    <?php echo htmlspecialchars($house['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (!empty($recent_requests)): ?>
    <div class="card" style="margin-top: 20px;">
        <div class="content">
            <h3>Recent Rental Requests</h3>
            <?php foreach ($recent_requests as $request): ?>
                <div style="padding: 8px 0; border-bottom: 1px solid #1f2437;">
                    <div style="font-weight: 500;"><?php echo htmlspecialchars($request['title']); ?></div>
                    <div style="font-size: 0.875rem; color: var(--muted);">
                        Requested by: <?php echo htmlspecialchars($request['tenant']); ?> • 
                        <span class="badge <?php 
                            echo $request['status'] === 'pending' ? '' : 
                                ($request['status'] === 'approved' ? 'ok' : 'warn'); 
                        ?>">
                            <?php echo htmlspecialchars($request['status']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.card:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease;
}

.quick-actions .btn {
    margin-right: 12px;
    margin-bottom: 8px;
}

@media (max-width: 768px) {
    .row {
        flex-direction: column;
    }
    
    .col {
        width: 100%;
    }
}
</style>

<?php require_once __DIR__.'/../../includes/footer.php'; ?>