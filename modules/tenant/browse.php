<?php
require_once __DIR__.'/../../config/session.php';
require_once __DIR__.'/../../config/db.php';

$location = trim($_GET['location'] ?? '');
$maxRent = trim($_GET['max_rent'] ?? '');
$status = $_GET['status'] ?? '';

$sql = 'SELECT h.id, h.title, h.location, h.rent_price, h.facilities, h.status, u.name as owner_name 
        FROM house_information h 
        LEFT JOIN users u ON u.id = h.owner_id 
        WHERE 1=1';
$params = [];
$types = '';

if ($location !== '') { 
    $sql .= ' AND h.location LIKE ?'; 
    $params[] = '%'.$location.'%'; 
    $types .= 's'; 
}
if ($maxRent !== '') { 
    $sql .= ' AND h.rent_price <= ?'; 
    $params[] = (int)$maxRent; 
    $types .= 'i'; 
}
if ($status !== '') { 
    $sql .= ' AND h.status = ?'; 
    $params[] = $status; 
    $types .= 's'; 
}

$sql .= ' ORDER BY h.id DESC';
$stmt = $mysqli->prepare($sql);

if ($params) { 
    $stmt->bind_param($types, ...$params); 
}
$stmt->execute();
$houses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <h2>Browse Houses</h2>
    <form method="get" class="row" style="margin-bottom:16px">
        <div class="col">
            <input class="input" name="location" placeholder="Search by location" value="<?php echo htmlspecialchars($location); ?>">
        </div>
        <div class="col">
            <input class="input" name="max_rent" type="number" min="0" placeholder="Max rent" value="<?php echo htmlspecialchars($maxRent); ?>">
        </div>
        <div class="col">
            <select name="status" class="input">
                <option value="">Any status</option>
                <option value="available" <?php if($status==='available') echo 'selected';?>>Available</option>
                <option value="rented" <?php if($status==='rented') echo 'selected';?>>Rented</option>
            </select>
        </div>
        <div class="col" style="flex:0 0 auto">
            <button class="btn primary" type="submit">Filter</button>
            <a href="browse.php" class="btn">Clear</a>
        </div>
    </form>
    
    <?php if (empty($houses)): ?>
        <div class="container" style="text-align:center; padding:40px;">
            <h3>No houses found</h3>
            <p>Try adjusting your search criteria.</p>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($houses as $h): ?>
                <div class="card">
                    <div class="content">
                        <h3><?php echo htmlspecialchars($h['title']); ?></h3>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($h['location']); ?></p>
                        <p><strong>Rent:</strong> $<?php echo (int)$h['rent_price']; ?>/month</p>
                        <?php if ($h['facilities']): ?>
                            <p><strong>Facilities:</strong> <?php echo htmlspecialchars($h['facilities']); ?></p>
                        <?php endif; ?>
                        <p><strong>Owner:</strong> <?php echo htmlspecialchars($h['owner_name'] ?? 'Unknown'); ?></p>
                        <span class="badge <?php echo $h['status']==='available'?'ok':'warn'; ?>">
                            <?php echo htmlspecialchars($h['status']); ?>
                        </span>
                        <?php if ($h['status'] === 'available' && isset($_SESSION['user'])): ?>
                            <div style="margin-top:12px">
                                <a class="btn primary" href="request.php?house_id=<?php echo (int)$h['id']; ?>">Request to Rent</a>
                            </div>
                        <?php elseif (!isset($_SESSION['user'])): ?>
                            <div style="margin-top:12px">
                                <a class="btn" href="../../login.php">Login to Request</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>