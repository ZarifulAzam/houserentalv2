<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['tenant','owner','admin']); // any logged-in user can request; typically tenant
	require_once __DIR__.'/../../config/db.php';
	$houseId = (int)($_GET['house_id'] ?? 0);
	if ($houseId <= 0) { header('Location: browse.php'); exit; }

	$stmt = $mysqli->prepare('INSERT INTO rental_requests(house_id, user_id, status, created_at) VALUES(?,?,"pending",NOW())');
	$uid = (int)($_SESSION['user']['id'] ?? 0);
	$stmt->bind_param('ii', $houseId, $uid);
	$stmt->execute();
	header('Location: /house_rental/modules/tenant/my_requests.php');
	exit;
?>


