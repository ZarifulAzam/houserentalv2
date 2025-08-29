<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['owner','admin']);
	require_once __DIR__.'/../../config/db.php';
	$uid = (int)($_SESSION['user']['id'] ?? 0);
	$stmt = $mysqli->prepare('SELECT id, title, location, rent_price, status FROM house_information WHERE owner_id=? ORDER BY id DESC');
	$stmt->bind_param('i', $uid);
	$stmt->execute();
	$houses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
	<h2>Manage Houses</h2>
	<a class="btn primary" href="/house_rental/modules/owner/save_house.php">Add House</a>
	<table style="margin-top:12px">
		<tr><th>Title</th><th>Location</th><th>Rent</th><th>Status</th><th>Actions</th></tr>
		<?php foreach ($houses as $h): ?>
			<tr>
				<td><?php echo htmlspecialchars($h['title']); ?></td>
				<td><?php echo htmlspecialchars($h['location']); ?></td>
				<td>$<?php echo (int)$h['rent_price']; ?></td>
				<td><?php echo htmlspecialchars($h['status']); ?></td>
				<td>
					<a class="btn" href="/house_rental/modules/owner/save_house.php?id=<?php echo (int)$h['id']; ?>">Edit</a>
					<a class="btn" href="/house_rental/modules/owner/toggle_status.php?id=<?php echo (int)$h['id']; ?>">Toggle Status</a>
					<a class="btn danger" href="/house_rental/modules/owner/delete_house.php?id=<?php echo (int)$h['id']; ?>" onclick="return confirm('Delete this house?')">Delete</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<p style="margin-top:16px"><a class="btn" href="/house_rental/modules/owner/requests.php">View Tenant Requests</a></p>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>


