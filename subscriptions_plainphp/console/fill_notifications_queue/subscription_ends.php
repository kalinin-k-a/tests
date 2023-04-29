<?php 
	require_once 'bootstrap.php';
	require_once 'console/fill_notifications_queue.php'
	
	$daysUntilSubscriptionEnds = $_SERVER['argv'][1] ?? null;
	
	if (!$daysUntilSubscriptionEnds || !is_numeric($daysUntilSubscriptionEnds)) {
		echo "argument (days) must be a positive number";
		return 1;
	}

	
	// можно создать каталог /repositories, в него положить репозитории юзеров и нотификаций и реализовать там функицонал фильров, джойнов, 
	// сделать там функцию для получения юзеров под конкретную нотификацию.
	// Я пока это просто текстом напишу, может для тестового и так сойдет, но если скажете, реализую :)
	
	// Очередь сделал на мускуле, чтоб если наполнялка падает, можно было удобно приджойнить очередь и отфильтровать юзеров, которые уже в очереди
	// можно сделать очередь в чем-нибудь еще, в том же редисе (вернее несколько очередей - на валидацию и на рассылку), а контроль дубликатов сделать другим способом (писать ключики в кеш, например)
	$queryHandler = mysqli_query($db, "
			SELECT * 
			FROM users u
			LEFT JOIN notifications_queue q ON u.id = q.user_id AND notification_id = {$notificationId}
			WHERE validts BETWEEN current_date - interval {$daysUntilSubscriptionEnds} days AND current_date - interval {$daysUntilSubscriptionEnds} - 1 days
				AND q.user_id is null
				AND checked
		"); 
	
	putNotificationsBatchesIntoQueue($queryHandler);
	
