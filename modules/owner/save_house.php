<?php
	require_once __DIR__.'/../../config/session.php';
	require_login(['owner','admin']);
	require_once __DIR__.'/../../config/db.php';
	$uid = (int)($_SESSION['user']['id'] ?? 0);
	$id = (int)($_GET['id'] ?? 0);
	$title=$location=$fac=''; $rent=0; $status='available';
	if ($id) {
		$stmt=$mysqli->prepare('SELECT id,title,location,facilities,rent_price,status FROM house_information WHERE id=? AND (owner_id=? OR ?="admin")');
		$role=$_SESSION['user']['role'];
		$stmt->bind_param('iis',$id,$uid,$role);
		$stmt->execute();
		if($row=$stmt->get_result()->fetch_assoc()){ $title=$row['title'];$location=$row['location'];$fac=$row['facilities'];$rent=(int)$row['rent_price'];$status=$row['status']; }
	}
	if($_SERVER['REQUEST_METHOD']==='POST'){
		$title=trim($_POST['title']??'');
		$location=trim($_POST['location']??'');
		$fac=trim($_POST['facilities']??'');
		$rent=(int)($_POST['rent_price']??0);
		$status=$_POST['status']??'available';
		if($id){
			$stmt=$mysqli->prepare('UPDATE house_information SET title=?,location=?,facilities=?,rent_price=?,status=? WHERE id=? AND owner_id=?');
			$stmt->bind_param('sssisii',$title,$location,$fac,$rent,$status,$id,$uid);
			$stmt->execute();
		}else{
			$stmt=$mysqli->prepare('INSERT INTO house_information(owner_id,title,location,facilities,rent_price,status) VALUES(?,?,?,?,?,?)');
			$stmt->bind_param('isssis',$uid,$title,$location,$fac,$rent,$status);
			$stmt->execute();
		}
		header('Location: /house_rental/modules/owner/manage.php');
		exit;
	}
?>
<?php require_once __DIR__.'/../../includes/header.php'; ?>
<div class="container">
	<h2><?php echo $id? 'Edit House':'Add House'; ?></h2>
	<form method="post">
		<label>Title<input class="input" name="title" value="<?php echo htmlspecialchars($title); ?>" required></label>
		<label>Location<input class="input" name="location" value="<?php echo htmlspecialchars($location); ?>" required></label>
		<label>Facilities<textarea class="input" name="facilities"><?php echo htmlspecialchars($fac); ?></textarea></label>
		<label>Rent Price<input class="input" type="number" min="0" name="rent_price" value="<?php echo (int)$rent; ?>" required></label>
		<label>Status
			<select class="input" name="status">
				<option value="available" <?php if($status==='available') echo 'selected';?>>Available</option>
				<option value="rented" <?php if($status==='rented') echo 'selected';?>>Rented</option>
			</select>
		</label>
		<button class="btn primary" type="submit">Save</button>
	</form>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>


