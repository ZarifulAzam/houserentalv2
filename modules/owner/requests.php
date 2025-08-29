<?php
require_once __DIR__.'/../../config/session.php';
require_login(['owner', 'admin']);
require_once __DIR__.'/../../config/db.php';

$uid = (int)($_SESSION['user']['id'] ?? 0);
$success = '';
$error = '';

// Simple CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle request status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if ($csrf_token !== $_SESSION['csrf_token']) {
        $error = 'Security token mismatch. Please refresh the page.';
    } elseif ($request_id && in_array($action, ['approve', 'reject'], true)) {
        
        $stmt = $mysqli->prepare('SELECT r.id, r.house_id, r.user_id, r.status, h.title, h.status as house_status, u.name as tenant_name 
                                  FROM rental_requests r 
                                  JOIN house_information h ON h.id = r.house_id 
                                  JOIN users u ON u.id = r.user_id 
                                  WHERE r.id = ? AND h.owner_id = ? AND r.status = "pending"');
        $stmt->bind_param('ii', $request_id, $uid);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if (!$request) {
            $error = 'Request not found or you do not have permission to modify it.';
        } elseif ($request['house_status'] !== 'available' && $action === 'approve') {
            $error = 'Cannot approve request - house is no longer available.';
        } else {
            $new_status = $action === 'approve' ? 'approved' : 'rejected';
            
            $mysqli->autocommit(false);
            
            try {
                $stmt = $mysqli->prepare('UPDATE rental_requests SET status = ? WHERE id = ?');
                $stmt->bind_param('si', $new_status, $request_id);
                $stmt->execute();
                
                if ($action === 'approve') {
                    $stmt = $mysqli->prepare('UPDATE house_information SET status = "rented" WHERE id = ?');
                    $stmt->bind_param('i', $request['house_id']);
                    $stmt->execute();
                    
                    $stmt = $mysqli->prepare('UPDATE rental_requests SET status = "rejected" WHERE house_id = ? AND status = "pending" AND id != ?');
                    $stmt->bind_param('ii', $request['house_id'], $request_id);
                    $stmt->execute();
                    
                    $success = "Request approved! {$request['tenant_name']}'s request has been accepted.";
                } else {
                    $success = "Request rejected. {$request['tenant_name']}'s request has been declined.";
                }
                
                $mysqli->commit();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
            } catch (Exception $e) {
                $mysqli->rollback();
                $error = 'Failed to update request status. Please try again.';
                error_log("Request status update error: " . $e->getMessage());
            }
            
            $mysqli->autocommit(true);
        }
    }
}

// Get all rental requests
$stmt = $mysqli->prepare('SELECT r.id, r.status, r.created_at, r.house_id, h.title, h.location, h.rent_price, u.name as tenant_name, u.email as tenant_email, u.phone as tenant_phone 
                          FROM rental_requests r 
                          JOIN house_information h ON h.id = r.house_id 
                          JOIN users u ON u.id = r.user_id 
                          WHERE h.owner_id = ? 
                          ORDER BY 
                              CASE r.status 
                                  WHEN "pending" THEN 1 
                                  WHEN "approved" THEN 2 
                                  WHEN "rejected" THEN 3 
                              END, 
                              r.created_at DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pending_count = $approved_count = $rejected_count = 0;
foreach ($requests as $req) {
    switch ($req['status']) {
        case 'pending': $pending_count++; break;
        case 'approved': $approved_count++; break;
        case 'rejected': $rejected_count++; break;
    }
}
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <div style="margin-bottom: 20px;">
        <a href="manage.php" class="btn">← Back to Manage Houses</a>
    </div>
    
    <h2>Rental Requests</h2>
    
    <?php if ($error): ?>
        <div class="flash error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="flash success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="row" style="margin-bottom: 24px;">
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--primary);"><?php echo $pending_count; ?></h3>
                <p style="margin: 0; color: var(--muted);">Pending</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--success);"><?php echo $approved_count; ?></h3>
                <p style="margin: 0; color: var(--muted);">Approved</p>
            </div>
        </div>
        <div class="col card">
            <div class="content" style="text-align: center;">
                <h3 style="color: var(--danger);"><?php echo $rejected_count; ?></h3>
                <p style="margin: 0; color: var(--muted);">Rejected</p>
            </div>
        </div>
    </div>
    
    <?php if (empty($requests)): ?>
        <div class="card">
            <div class="content" style="text-align: center; padding: 40px;">
                <h3>No Rental Requests Yet</h3>
                <p style="color: var(--muted);">When tenants request to rent your houses, they'll appear here.</p>
                <a href="manage.php" class="btn primary">Manage My Houses</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
            <div class="card" style="margin-bottom: 16px;">
                <div class="content">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 20px; flex-wrap: wrap;">
                        <div style="flex: 1;">
                            <h3><?php echo htmlspecialchars($req['title']); ?></h3>
                            <p style="color: var(--muted);"><?php echo htmlspecialchars($req['location']); ?></p>
                            <p style="color: var(--primary); font-weight: 600;">৳<?php echo number_format((int)$req['rent_price']); ?>/month</p>
                            <p style="color: var(--muted); font-size: 0.875rem;">
                                <?php echo htmlspecialchars($req['tenant_name']); ?> • <?php echo htmlspecialchars($req['tenant_email']); ?>
                            </p>
                            <p style="color: var(--muted); font-size: 0.875rem;">
                                Requested: <?php echo date('M j, Y', strtotime($req['created_at'])); ?>
                            </p>
                        </div>
                        
                        <div style="text-align: right;">
                            <span class="badge <?php 
                                echo $req['status'] === 'pending' ? '' : 
                                    ($req['status'] === 'approved' ? 'ok' : 'warn'); 
                            ?>">
                                <?php echo strtoupper($req['status']); ?>
                            </span>
                            
                            <?php if ($req['status'] === 'pending'): ?>
                                <div style="margin-top: 12px; display: flex; gap: 8px;">
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn primary small" type="submit" onclick="return confirm('Approve this request?')">Approve</button>
                                    </form>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="btn danger small" type="submit" onclick="return confirm('Reject this request?')">Reject</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.btn.small {
    padding: 6px 12px;
    font-size: 0.875rem;
}

.requests-list .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

@media (max-width: 768px) {
    .card .content > div {
        flex-direction: column;
    }
    
    .btn.small {
        width: 100%;
        margin-bottom: 4px;
    }
}
</style>

<?php require_once __DIR__.'/../../includes/footer.php'; ?>