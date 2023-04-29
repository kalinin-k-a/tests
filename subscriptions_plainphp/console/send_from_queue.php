<?php 
	require_once 'bootstrap.php';
	require_once 'helpers/console.php'
	
	$db = db('default');	
	
	$queryHandler = mysqli_query($db, "
		SELECT notification_id, email
		FROM notifications_queue q
		WHERE status = 'validated'
	");
	
	while ($row = mysqli_fetch_assoc($queryHandler)) {
		$batch[] = $row;
		if (count($batch) === 2000) { 
			processInFork(['sendEmails', [$batch]]);
		}
	}
	
	
	function sendEmails(array $notificationsBatch) {
		$db = db('default');
		
		$sentNotificationsBuffer = [];
		foreach ($notificationsBatch as $notification) {
			$text = drawNotification($notification['notification_id'], $notification['user_id']);
			send_email(NOTIFICATION_SENDER_EMAIL, $notification['email'], $text);
			$sentNotificationsBuffer[] = "({$notification['notification_id']}, {$notification['user_id']}, 'sent')";
			if (count($sentNotificationsBuffer) === 100) {
				markNotificationsAsSent($sentNotificationsBuffer);
			}
		}
		
		markNotificationsAsSent($db, $sentNotificationsBuffer);
	}
	
	function markNotificationsAsSent($db, array $buffer): void
	{  // сделал батчем, т.к. все-таки почты шлются интенсивно. Но есть риск, если рассылка упала по середине батча и очередь не проапдейтилась, то после перезапуска часть батча повторно разошлется
		mysql_query($db, "
			INSERT INTO notifications_queue 
			VALUES " . implode(',', $buffer) . "
			ON DUPLICATE KEY update status = values(status)
		");
	}
	









