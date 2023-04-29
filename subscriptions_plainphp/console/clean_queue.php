<?php
	require_once 'bootstrap.php';

	$db = db('default');	
	
	$portionSize = 3000;
	do {
		$queryHandler = mysqli_query($db, "DELETE FROM notifications_queue WHERE status = 'validated' AND added_at < now() - interval 1 day LIMIT 3000")
	} while (mysqli_affected_rows($queryHandler) === $portionSize);
	




























