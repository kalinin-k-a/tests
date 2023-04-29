<?php
	$config = require_once 'config/config.php';
	require_once 'helpers/db.php';
	
	
	function db(string $connectionName) use ($config)  
	{
		global $consoleFlags_mypid;
		static $dbs = [];
		if (!isset($dbs[$consoleFlags_mypid])) { // ну типа чтоб не произошло чудес, когда мы этой функцией родили коннект в родительском процессе и потом вызвали ее в чайлде
			$dbs[$consoleFlags_mypid] = mysqli_connect($config['host'], $config['useraname'], $config['password'], $config['db']);
		}
		return $dbs[$consoleFlags_mypid];
	}
