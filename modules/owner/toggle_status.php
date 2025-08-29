<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['owner','admin']);
	require_once __DIR__.'/../../config/db.php';
	$uid=(int)($_SESSION['user']['id']??0);
	$id=(int)($_GET['id']??0);
	if($id){
		// flip between available and rented
		$mysqli->query('UPDATE house_information SET status = IF(status="available","rented","available") WHERE id='.(int)$id.' AND owner_id='.(int)$uid);
	}
	header('Location: /house_rental/modules/owner/manage.php');
	exit;
?>


