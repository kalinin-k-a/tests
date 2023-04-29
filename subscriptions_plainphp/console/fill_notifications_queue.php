<?php 	
	function putNotificationsBatchesIntoQueue($queryHandler) {
		$batch = [];
		while ($row = mysqli_fetch_assoc($queryHandler)) {
			$batch[] = "('{$row['email']}', {$notificationId}, '" . ($row['checked'] ? 'validated' : 'new') . "')";
			if (count($batch) === 10000) {
				flushInsertsBatch($db, "INSERT INTO notifications_queue (email, notification_id, status)", $batch);
			}
		}
		
		flushInsertsBatch($db, $batch);
	}
