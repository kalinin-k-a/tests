<?php

	require_once 'bootstrap.php';
	require_once 'helpers/console.php'
	
	$batchSize = $_SERVER['argv'][1] ?? 20;
	$workersLimit = $_SERVER['argv'][2] ?? 100;

	$db = db('default');	
	
	// Сделал логику, в которой родительский процесс получает данные и раздает чайлдам, но можно и чтоб чайлды сами получали данные.
	// Тогда надо будет решить проблему, чтоб два чайлда не высосали одну задачу.
	// Если у нас редис там или ребиит, то такой проблемы не будет, а в случае с мускулем можно добавить столбечик locked_by_pid, 
	// и тогда чайлд будет сначала update notifications_queue set  locked_by_pid = getmypid() where locked_by_pid is null limit 5000 и потом select ... where locked_by_pid = getmypid().
	// или select for update - тоже вариант
	$queryHandler = mysqli_query($db, "
		SELECT DISTINCT user_id, email
		FROM notifications_queue q
		WHERE status = 'new'
	");
	
	while ($row = mysqli_fetch_assoc($queryHandler)) {
		$batch[] = $row;
		// количество мыл в батче зависит от того, насколько часто check_email работает реально долго. Если большая часть срабатываний укладывается, скажем, в 5с, то можно побольше в один батч мыл сунуть,
		// если минута - частое явление, то можно уменьшить размер батча вплоть до 1. Надо статистику собирать. 
		// TODO унести бачсайз и воркерскаунт в аргументы командной строки
		if (count($batch) === $batchSize) { 
			processInFork(['checkEmails', [$batch]], $workersLimit);
		}
	}
	

	function checkEmails($batch) {
		$db = db('default');
		foreach ($batch as $emailData) {
			// check_email не бесплатный и тяжелый, поэтому попробуем хоть что-то отбросить легковесными способами на своей стороне
			// вообще такую проверку конечно стоит делать сразу при регистрации или смене емейла, но поскольку об этом ничего не сказано и мы пишем только сервис рассылки, 
			//	а таблица уже есть изначально, пусть будет тут такая проверка

			if (!preg_match('/big-email-validaion-regexp/', $emailData['email'])) { 
				$result = false; 
			} else {
				$result = check_email($emailData['email']);
			}
			if ($result) {
				mysql_query($db, "UPDATE notifications_queue SET status = 'validated' WHERE user_id = {$emailData['user_id']} AND email = '{$emailData['email']}'"); // не исключаю, что в очереди может быть несколько отправок на разные емейлы одного юзера
				// юзеров апдейтим, чтоб в следующих рассылках не валидировать
				mysql_query($db, "UPDATE users SET checked = 1 WHERE user_id = {$emailData['user_id']} AND email = '{$emailData['email']}'");
				// можно батчем апдейтить типа update users set email_check_result = 1 where user_id in (йдишники батча)
				// но учитывая, что check_email не бесплатен, не хочется потерять результат его работы, если скрипт упадет в процессе накопления батча, поэтому сразу апдейт.
				// плюс check_email работает долго, апдейты будут с паузой, т.е. тут не та ситуация, чтоб апдейты батчами оптимизировать
			} else {
				mysql_query($db, "DELETE FROM notifications_queue WHERE user_id = {$emailData['user_id']} AND email = '{$emailData['email']}' ");
			}
		}
	}














