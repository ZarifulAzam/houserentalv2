<?php
require_once __DIR__.'/../../config/session.php';
require_login(['tenant', 'owner', 'admin']);
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/security.php';

$houseId = (int)($_GET['house_id'] ?? 0);
$error = '';
$success = '';

if ($houseId <= 0) { 
    header('Location: browse.php'); 
    exit; 
}

// Get house details
$stmt = $mysqli->prepare('SELECT h.id, h.title, h.location, h.rent_price, h.facilities, h.status, h.owner_id, u.name as owner_name, u.email as owner_email FROM house_information h LEFT JOIN users u ON u.id = h.owner_id WHERE h.id = ?');
$stmt->bind_param('i', $houseId);
$stmt->execute();
$house = $stmt->get_result()->fetch_assoc();

if (!$house) {
    header('Location: browse.php');
    exit;
}

if ($house['status'] !== 'available') {
    $error = 'This house is no longer available for rent.';
}

$uid = (int)($_SESSION['user']['id'] ?? 0);

// Check if user is the owner
if ($house['owner_id'] == $uid) {
    $error = 'You cannot request to rent your own house.';
}

// Check if user already has a pending or approved request for this house
if (!$error) {
    $stmt = $mysqli->prepare('SELECT id, status FROM rental_requests WHERE house_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('ii', $houseId, $uid);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        if ($existing['status'] === 'pending') {
            $error = 'You already have a pending request for this house.';
        } elseif ($existing['status'] === 'approved') {
            $error = 'You already have an approved request for this house.';
        }
    }
}

// Generate CSRF token
SecurityUtils::generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!SecurityUtils::validateCSRFToken($csrf_token)) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        // Double-check house is still available
        $stmt = $mysqli->prepare('SELECT status FROM house_information WHERE id = ?');
        $stmt->bind_param('i', $houseId);
        $stmt->execute();
        $current_status = $stmt->get_result()->fetch_assoc();
        
        if (!$current_status || $current_status['status'] !== 'available') {
            $error = 'This house is no longer available for rent.';
        } else {
            // Check for existing request one more time
            $stmt = $mysqli->prepare('SELECT id FROM rental_requests WHERE house_id = ? AND user_id = ? AND status IN ("pending", "approved")');
            $stmt->bind_param('ii', $houseId, $uid);
            $stmt->execute();
            $duplicate_check = $stmt->get_result()->fetch_assoc();
            
            if ($duplicate_check) {
                $error = 'You already have an active request for this house.';
            } else {
                // Create the rental request
                $stmt = $mysqli->prepare('INSERT INTO rental_requests(house_id, user_id, status, created_at) VALUES(?, ?, "pending", NOW())');
                $stmt->bind_param('ii', $houseId, $uid);
                
                if ($stmt->execute()) {
                    $success = 'Your rental request has been submitted successfully!';
                    // Regenerate CSRF token for security
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = 'Failed to submit request. Please try again.';
                }
            }
        }
    }
}
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <div style="margin-bottom: 20px;">
        <a href="browse.php" class="btn">← Back to Browse</a>
    </div>
    
    <h2>Request to Rent</h2>
    
    <?php if ($error): ?>
        <div class="flash error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="flash success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <!-- House Details Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="content">
            <h3><?php echo htmlspecialchars($house['title']); ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 16px 0;">
                <div>
                    <strong>Location:</strong><br>
                    <span style="color: var(--muted);"><?php echo htmlspecialchars($house['location']); ?></span>
                </div>
                <div>
                    <strong>Monthly Rent:</strong><br>
                    <span style="color: var(--primary); font-size: 1.25rem; font-weight: 600;">$<?php echo (int)$house['rent_price']; ?></span>
                </div>
                <div>
                    <strong>Status:</strong><br>
                    <span class="badge <?php echo $house['status']==='available'?'ok':'warn'; ?>">
                        <?php echo htmlspecialchars($house['status']); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($house['facilities']): ?>
                <div style="margin-top: 16px;">
                    <strong>Facilities:</strong><br>
                    <p style="color: var(--muted); margin: 8px 0;"><?php echo htmlspecialchars($house['facilities']); ?></p>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #283249;">
                <strong>Property Owner:</strong> <?php echo htmlspecialchars($house['owner_name'] ?? 'Unknown'); ?>
            </div>
        </div>
    </div>
    
    <?php if (!$error && !$success): ?>
        <div class="card">
            <div class="content">
                <h3>Submit Rental Request</h3>
                <p style="color: var(--muted); margin-bottom: 20px;">
                    By submitting this request, you're expressing interest in renting this property. 
                    The owner will review your request and respond accordingly.
                </p>
                
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div style="background: #0f1424; padding: 16px; border-radius: 8px; border: 1px solid #283249; margin-bottom: 20px;">
                        <p style="margin: 0; font-weight: 500;">Request Details:</p>
                        <ul style="margin: 8px 0 0 20px; color: var(--muted);">
                            <li>Your request will be sent to the property owner</li>
                            <li>You'll be notified when the owner responds</li>
                            <li>You can track your request status in "My Requests"</li>
                        </ul>
                    </div>
                    
                    <div class="form-actions">
                        <button class="btn primary" type="submit">Submit Rental Request</button>
                        <a class="btn" href="browse.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($success): ?>
        <div style="text-align: center; margin-top: 20px;">
            <a class="btn primary" href="my_requests.php">View My Requests</a>
            <a class="btn" href="browse.php">Browse More Houses</a>
        </div>
    <?php else: ?>
        <div style="text-align: center; margin-top: 20px;">
            <a class="btn" href="browse.php">Back to Browse</a>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>