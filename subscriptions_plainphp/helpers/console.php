<?php

	$consoleFlags_mypid = getmypid();

	pcntl_signal(SIGTERM, function () { 
		global $consoleFlags_gotSigterm;
		$consoleFlags_gotSigterm = true;
	});
	
	function processInFork(callable $callback, int $workersLimit = 20) { 
		global $consoleFlags_gotSigterm;
		global $consoleFlags_mypid;
		static $workers = [];
		
		pcntl_signal_dispatch();
		
		if($consoleFlagsGotSigterm) {
			return;
		}
		while (count($workers) > $workersLimit) {
			handleWorkerSignals($workers);
			sleep(2);
		}
		
		$pid = pcntl_fork();
		if ($pid === -1) {
			echo "Can't fork process from " . getmypid();

			return 1;
		} elseif ($pid > 0) {
			$workers[$pid] = $pid;
		} else {
			$consoleFlags_mypid = getmypid();
			call_user_func_array($callback)
		}
	}
	
	function handleWorkerSignals(array &$workers): void
    {
        foreach ($this->workers as $pid => $nothing) {
            $res = pcntl_waitpid($pid, $status, WNOHANG);
            
            if ($res > 0) {
                unset($workers[$pid]);
            }
        }
    }
