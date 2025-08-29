<?php
require_once __DIR__.'/../../config/session.php';
require_login(['admin']);
require_once __DIR__.'/../../config/db.php';

// Get role statistics
$role_stats = [];
try {
    $result = $mysqli->query('SELECT role, COUNT(*) as count FROM users GROUP BY role');
    while ($row = $result->fetch_assoc()) {
        $role_stats[$row['role']] = (int)$row['count'];
    }
} catch (Exception $e) {
    error_log('Role stats error: ' . $e->getMessage());
}

// Ensure all roles are represented
$roles = ['tenant' => 0, 'owner' => 0, 'admin' => 0];
$role_stats = array_merge($roles, $role_stats);
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
        <h2 style="margin: 0;">Roles & Permissions</h2>
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
    
    <!-- Role Statistics -->
    <div class="row" style="margin-bottom: 24px;">
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--primary); margin: 0;"><?php echo $role_stats['tenant']; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Tenants</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--accent); margin: 0;"><?php echo $role_stats['owner']; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Owners</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--danger); margin: 0;"><?php echo $role_stats['admin']; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Admins</p>
            </div>
        </div>
    </div>
    
    <!-- Role Descriptions -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="content">
            <h3>Role Overview</h3>
            <p style="color: var(--muted); margin-bottom: 20px;">
                This system uses a simple role-based access control with three main user types. 
                Each role has specific permissions and access levels within the application.
            </p>
        </div>
    </div>
    
    <!-- Role Details -->
    <div class="row">
        <div class="col card">
            <div class="content">
                <div style="display: flex; align-items: center; margin-bottom: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                        <span style="color: white; font-weight: 600;">T</span>
                    </div>
                    <h3 style="margin: 0; color: var(--primary);">Tenant</h3>
                </div>
                
                <p style="color: var(--muted); margin-bottom: 16px;">
                    Regular users looking for rental properties.
                </p>
                
                <h4 style="margin: 16px 0 8px 0; color: var(--text);">Permissions:</h4>
                <ul style="color: var(--muted); margin: 0; padding-left: 20px;">
                    <li>Browse available houses</li>
                    <li>Submit rental requests</li>
                    <li>View their own request history</li>
                    <li>Update profile information</li>
                </ul>
            </div>
        </div>
        
        <div class="col card">
            <div class="content">
                <div style="display: flex; align-items: center; margin-bottom: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                        <span style="color: white; font-weight: 600;">O</span>
                    </div>
                    <h3 style="margin: 0; color: var(--accent);">Owner</h3>
                </div>
                
                <p style="color: var(--muted); margin-bottom: 16px;">
                    Property owners who list houses for rent.
                </p>
                
                <h4 style="margin: 16px 0 8px 0; color: var(--text);">Permissions:</h4>
                <ul style="color: var(--muted); margin: 0; padding-left: 20px;">
                    <li>Add, edit, and delete their houses</li>
                    <li>View and manage rental requests</li>
                    <li>Approve or reject tenant applications</li>
                    <li>Update house status (available/rented)</li>
                    <li>All tenant permissions</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <div class="content">
            <div style="display: flex; align-items: center; margin-bottom: 16px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--danger); display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                    <span style="color: white; font-weight: 600;">A</span>
                </div>
                <h3 style="margin: 0; color: var(--danger);">Administrator</h3>
            </div>
            
            <p style="color: var(--muted); margin-bottom: 16px;">
                System administrators with full access to all features and data.
            </p>
            
            <h4 style="margin: 16px 0 8px 0; color: var(--text);">Permissions:</h4>
            <ul style="color: var(--muted); margin: 0; padding-left: 20px;">
                <li>Full access to admin dashboard and statistics</li>
                <li>Manage all users and change user roles</li>
                <li>View and manage all houses in the system</li>
                <li>Access system logs and analytics</li>
                <li>All owner and tenant permissions</li>
            </ul>
        </div>
    </div>
    
    <!-- Management Actions -->
    <div class="card" style="margin-top: 24px;">
        <div class="content">
            <h3>Role Management</h3>
            <p style="color: var(--muted); margin-bottom: 16px;">
                To change user roles, use the user management interface. Role changes take effect immediately.
            </p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="users.php" class="btn primary">Manage Users</a>
                <a href="houses.php" class="btn">View All Houses</a>
            </div>
        </div>
    </div>
</div>

<style>
.role-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
}

.role-description {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}

ul {
    list-style-type: disc;
}

ul li {
    margin-bottom: 4px;
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