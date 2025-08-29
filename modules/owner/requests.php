<?php
require_once __DIR__.'/../../config/session.php';
require_login(); // Require login for any user
require_once __DIR__.'/../../config/db.php';

$houseId = (int)($_GET['house_id'] ?? 0);
$error = '';
$success = '';

if ($houseId <= 0) { 
    header('Location: browse.php'); 
    exit; 
}

// Get house details
$stmt = $mysqli->prepare('SELECT id, title, location, rent_price, status, owner_id FROM house_information WHERE id = ?');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $stmt = $mysqli->prepare('INSERT INTO rental_requests(house_id, user_id, status, created_at) VALUES(?, ?, "pending", NOW())');
    $stmt->bind_param('ii', $houseId, $uid);
    
    if ($stmt->execute()) {
        $success = 'Your rental request has been submitted successfully!';
    } else {
        $error = 'Failed to submit request. Please try again.';
    }
}
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <h2>Request to Rent</h2>
    
    <?php if ($error): ?>
        <div class="flash error" style="color:#ff8fa3; background:#3b0613; padding:12px; border-radius:8px; margin-bottom:16px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="flash success" style="color:#88ef9e; background:#08381a; padding:12px; border-radius:8px; margin-bottom:16px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <div class="card" style="margin-bottom:16px;">
        <div class="content">
            <h3><?php echo htmlspecialchars($house['title']); ?></h3>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($house['location']); ?></p>
            <p><strong>Rent:</strong> $<?php echo (int)$house['rent_price']; ?>/month</p>
            <span class="badge <?php echo $house['status']==='available'?'ok':'warn'; ?>">
                <?php echo htmlspecialchars($house['status']); ?>
            </span>
        </div>
    </div>
    
    <?php if (!$error && !$success): ?>
        <form method="post">
            <p>Are you sure you want to request to rent this house?</p>
            <button class="btn primary" type="submit">Submit Request</button>
            <a class="btn" href="browse.php">Cancel</a>
        </form>
    <?php else: ?>
        <a class="btn" href="browse.php">Back to Browse</a>
        <?php if ($success): ?>
            <a class="btn primary" href="my_requests.php">View My Requests</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>