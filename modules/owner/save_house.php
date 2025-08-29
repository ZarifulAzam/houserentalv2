<?php
require_once __DIR__.'/../../config/session.php';
require_login(['owner','admin']);
require_once __DIR__.'/../../config/db.php';

$uid = (int)($_SESSION['user']['id'] ?? 0);
$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['user']['role'] ?? 'tenant';

$title = $location = $fac = ''; 
$rent = 0; 
$status = 'available';
$error = '';
$success = '';

// If editing existing house, load data
if ($id > 0) {
    $stmt = $mysqli->prepare('SELECT id, title, location, facilities, rent_price, status FROM house_information WHERE id = ? AND (owner_id = ? OR ? = "admin")');
    $stmt->bind_param('iis', $id, $uid, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) { 
        $title = $row['title'];
        $location = $row['location'];
        $fac = $row['facilities'];
        $rent = (int)$row['rent_price'];
        $status = $row['status']; 
    } else {
        $error = 'House not found or you do not have permission to edit it.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $fac = trim($_POST['facilities'] ?? '');
    $rent = (int)($_POST['rent_price'] ?? 0);
    $status = $_POST['status'] ?? 'available';
    
    // Basic validation
    if (empty($title)) {
        $error = 'House title is required.';
    } elseif (empty($location)) {
        $error = 'Location is required.';
    } elseif ($rent <= 0) {
        $error = 'Rent price must be greater than 0.';
    } elseif (!in_array($status, ['available', 'rented'])) {
        $error = 'Invalid status selected.';
    } else {
        try {
            if ($id > 0) {
                // Update existing house
                $stmt = $mysqli->prepare('UPDATE house_information SET title = ?, location = ?, facilities = ?, rent_price = ?, status = ? WHERE id = ? AND owner_id = ?');
                $stmt->bind_param('ssisiii', $title, $location, $fac, $rent, $status, $id, $uid);
                
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $success = 'House updated successfully!';
                    header('refresh:2;url=manage.php');
                } else {
                    $error = 'Failed to update house or no changes were made.';
                }
            } else {
                // Insert new house
                $stmt = $mysqli->prepare('INSERT INTO house_information (owner_id, title, location, facilities, rent_price, status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('issiis', $uid, $title, $location, $fac, $rent, $status);
                
                if ($stmt->execute()) {
                    $success = 'House added successfully!';
                    header('refresh:2;url=manage.php');
                } else {
                    $error = 'Failed to add house. Please try again.';
                }
            }
        } catch (Exception $e) {
            error_log('Save house error: ' . $e->getMessage());
            $error = 'Database error occurred. Please try again.';
        }
    }
}
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
    <div style="margin-bottom: 20px;">
        <a href="manage.php" class="btn">← Back to Manage Houses</a>
    </div>
    
    <h2><?php echo $id ? 'Edit House' : 'Add New House'; ?></h2>
    
    <?php if ($error): ?>
        <div class="flash error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="flash success">
            <?php echo htmlspecialchars($success); ?>
            <p style="margin-top: 8px; font-size: 0.9em;">Redirecting to manage houses...</p>
        </div>
    <?php endif; ?>
    
    <?php if (!$success): ?>
    <form method="post" class="card">
        <div class="content">
            <div class="row">
                <div class="col">
                    <label>House Title *
                        <input class="input" 
                               name="title" 
                               value="<?php echo htmlspecialchars($title); ?>" 
                               placeholder="e.g., Beautiful 3BR House in Downtown"
                               maxlength="160" 
                               required>
                    </label>
                </div>
                <div class="col">
                    <label>Location *
                        <input class="input" 
                               name="location" 
                               value="<?php echo htmlspecialchars($location); ?>" 
                               placeholder="e.g., Dhaka, Gulshan"
                               maxlength="160" 
                               required>
                    </label>
                </div>
            </div>
            
            <label>Facilities & Amenities
                <textarea class="input" 
                          name="facilities" 
                          rows="4" 
                          placeholder="e.g., 3 bedrooms, 2 bathrooms, kitchen, parking, garden..."
                          maxlength="1000"><?php echo htmlspecialchars($fac); ?></textarea>
            </label>
            
            <div class="row">
                <div class="col">
                    <label>Monthly Rent (BDT) *
                        <input class="input" 
                               type="number" 
                               min="1" 
                               step="1"
                               name="rent_price" 
                               value="<?php echo $rent > 0 ? $rent : ''; ?>" 
                               placeholder="e.g., 25000"
                               required>
                    </label>
                </div>
                <div class="col">
                    <label>Availability Status
                        <select class="input" name="status">
                            <option value="available" <?php if($status === 'available') echo 'selected'; ?>>Available for Rent</option>
                            <option value="rented" <?php if($status === 'rented') echo 'selected'; ?>>Currently Rented</option>
                        </select>
                    </label>
                </div>
            </div>
            
            <div style="margin-top: 24px; text-align: center;">
                <button class="btn primary" type="submit" style="min-width: 140px;">
                    <?php echo $id ? 'Update House' : 'Add House'; ?>
                </button>
                <a class="btn" href="manage.php" style="margin-left: 12px;">Cancel</a>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = form?.querySelector('button[type="submit"]');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.textContent = '<?php echo $id ? "Updating..." : "Adding..."; ?>';
            
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '<?php echo $id ? "Update House" : "Add House"; ?>';
            }, 10000);
        });
    }
});
</script>

<?php require_once __DIR__.'/../../includes/footer.php'; ?>