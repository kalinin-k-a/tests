<?php

	function flushInsertsBatch($db, string $insertQueryHead, array $batch) 
	{
		mysqli_query($db, "{$insertQueryHead} VALUES " . implode(',', $batch));
	}
