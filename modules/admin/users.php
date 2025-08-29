<?php
require_once __DIR__.'/../../config/session.php';
require_login(['admin']);
require_once __DIR__.'/../../config/db.php';

$success = '';
$error = '';

// Handle role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['role'])) {
    $id = (int)$_POST['id'];
    $new_role = $_POST['role'];
    
    // Validate role
    if (in_array($new_role, ['tenant', 'owner', 'admin'], true)) {
        $stmt = $mysqli->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $new_role, $id);
        
        if ($stmt->execute()) {
            $success = 'User role updated successfully!';
        } else {
            $error = 'Failed to update user role.';
        }
    } else {
        $error = 'Invalid role selected.';
    }
}

// Get all users with their house count (for owners)
$users = [];
try {
    $sql = 'SELECT u.id, u.name, u.email, u.phone, u.role, 
                   COUNT(h.id) as house_count,
                   COUNT(r.id) as request_count
            FROM users u 
            LEFT JOIN house_information h ON h.owner_id = u.id
            LEFT JOIN rental_requests r ON r.user_id = u.id
            GROUP BY u.id 
            ORDER BY u.id DESC';
    $users = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log('Users query error: ' . $e->getMessage());
    $error = 'Failed to load users data.';
}

// Get user statistics
$user_stats = [];
foreach (['tenant', 'owner', 'admin'] as $role) {
    $user_stats[$role] = 0;
}
foreach ($users as $user) {
    $user_stats[$user['role']]++;
}
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
        <h2 style="margin: 0;">Manage Users</h2>
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
    
    <?php if ($success): ?>
        <div class="flash success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="flash error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- User Statistics -->
    <div class="row" style="margin-bottom: 24px;">
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--primary); margin: 0;"><?php echo $user_stats['tenant']; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Tenants</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--accent); margin: 0;"><?php echo $user_stats['owner']; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Owners</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--danger); margin: 0;"><?php echo $user_stats['admin']; ?></h3>
                <p style="margin: 4px 0 0 0; color: var(--muted);">Admins</p>
            </div>
        </div>
    </div>
    
    <!-- Users Table -->
    <?php if (empty($users)): ?>
        <div class="card">
            <div class="content" style="text-align: center; padding: 40px;">
                <h3>No Users Found</h3>
                <p style="color: var(--muted);">No users are registered in the system yet.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="content" style="overflow-x: auto;">
                <table style="width: 100%; margin: 0;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">User</th>
                            <th style="text-align: center;">Current Role</th>
                            <th style="text-align: center;">Activity</th>
                            <th style="text-align: center;">Change Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div style="font-size: 0.875rem; color: var(--muted); margin-top: 2px;">
                                    <?php echo htmlspecialchars($u['email']); ?>
                                </div>
                                <?php if ($u['phone']): ?>
                                    <div style="font-size: 0.875rem; color: var(--muted);">
                                        <?php echo htmlspecialchars($u['phone']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge <?php 
                                    echo $u['role'] === 'admin' ? 'warn' : 
                                        ($u['role'] === 'owner' ? '' : 'ok'); 
                                ?>">
                                    <?php echo htmlspecialchars($u['role']); ?>
                                </span>
                            </td>
                            <td style="text-align: center; font-size: 0.875rem; color: var(--muted);">
                                <?php if ($u['role'] === 'owner'): ?>
                                    <?php echo (int)$u['house_count']; ?> houses
                                <?php else: ?>
                                    <?php echo (int)$u['request_count']; ?> requests
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                    <form method="post" style="display: flex; gap: 8px; align-items: center; justify-content: center;">
                                        <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                        <select class="input" name="role" style="width: 120px; padding: 6px; font-size: 0.875rem;">
                                            <?php foreach(['tenant', 'owner', 'admin'] as $r): ?>
                                                <option value="<?php echo $r; ?>" <?php if($u['role'] === $r) echo 'selected'; ?>>
                                                    <?php echo ucfirst($r); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn small" type="submit" 
                                                onclick="return confirm('Are you sure you want to change this user\'s role?')">
                                            Update
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--muted); font-size: 0.875rem;">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Role Change Warning -->
    <div class="card" style="margin-top: 20px;">
        <div class="content">
            <h3>Role Change Guidelines</h3>
            <ul style="color: var(--muted); margin: 0; padding-left: 20px;">
                <li><strong>Tenant to Owner:</strong> User will gain access to house management features</li>
                <li><strong>Owner to Tenant:</strong> User will lose access to their houses and requests</li>
                <li><strong>Admin Role:</strong> Grants full system access - use with caution</li>
                <li><strong>Note:</strong> Role changes take effect immediately</li>
            </ul>
        </div>
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
    vertical-align: top;
}

table tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

form select.input {
    margin: 0;
}

@media (max-width: 768px) {
    table th,
    table td {
        padding: 8px 4px;
        font-size: 0.875rem;
    }
    
    form {
        flex-direction: column;
        gap: 4px;
    }
    
    form select.input {
        width: 100% !important;
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