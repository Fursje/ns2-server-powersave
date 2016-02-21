<?php

require __DIR__ . '/../steam-condenser-php/vendor/autoload.php';
use SteamCondenser\Servers\MasterServer;
use SteamCondenser\Servers\SourceServer;

class powersave {

	// Default action is that the server should be in powersave mode, onlt exceptions can cause it to change to performance.
	public $runMode = "daemon"; // or once or daemon
	public $GameServers = array();
	public $ns2_min_players_for_performance = 3;
	public $checkInterval = 60; // In Seconds
	
	
	private $PowerMode = 0; // 0: powersave 1: performance
	private $last_state = NULL;

	private $cpupower_performance = array("/usr/bin/cpupower idle-set --disable-by-latency 10");
	private $cpupower_powersave = array("/usr/bin/cpupower idle-set -E");


	public function __construct() {}

	public function run() {
	
		while (true) {
			$this->print_cli("info","Check Start: Current mode:[$this->last_state]");
			$this->PowerMode = 0;
			$this->_check_GameServers();


			if ($this->PowerMode !== $this->last_state) {
				switch ($this->PowerMode) {
					case '0':
						$this->print_cli("info"," Changing to Powersave");
						$this->_enable_powersave();
						break;
					case '1':
						$this->print_cli("info"," Changing to Performance");
						$this->_enable_performance();
						break;
				}
			} else {
				$this->print_cli("info","Check Stop: Nothing changed, already in the correct mode[".$this->last_state."]");
			}
			if ($this->runMode == "once") { break; }
			sleep($this->checkInterval);
		}

	}

	public function addServer($host,$port) {
		if (!empty($host) && is_numeric($port)) {
			$this->GameServers[] = array('host'=>$host,'port'=>$port);
		} else {
			print_cli("warning","adding of server $host:$port failed.");
		}
	}
	
	protected function print_cli($severity, $message) {
		$message = sprintf("%s %s: %s\n",date("r",time()),$severity,$message);
		print $message;
	}

	private function _enable_performance() {
		foreach ($this->cpupower_performance as $cli_cmd) {
			$this->print_cli("info","command: ".$cli_cmd);
			exec($cli_cmd,$out,$ret);
		}
		$this->last_state = 1;
	}

	private function _enable_powersave() {
		foreach ($this->cpupower_powersave as $cli_cmd) {
			$this->print_cli("info","command: ".$cli_cmd);
			exec($cli_cmd,$out,$ret);
		}
		$this->last_state = 0;
	}

	private function _check_GameServers() {
		foreach ($this->GameServers as $server) {
			$serverInfo = $this->_get_server_players($server['host'],$server['port']);
			if ($serverInfo['numberOfPlayers'] >= $this->ns2_min_players_for_performance) {
				$this->PowerMode = 1;
				#$this->print_cli("info","PowerSaveMode disabled because server ".$server['host'].":".$server['port']." has players playing");
			} 
		}
	}

	private function _get_server_players($host,$port) {
		$serverData = new SourceServer($host,$port);
		$serverInfo = array('numberOfPlayers'=>0);
		$retry_count = 0;
		$retry = 10;
		$players = 0;
		while ( $retry_count <= $retry) {
			try {
				$serverInfo = $serverData->getServerInfo();
				break;
			} catch (Exception $e) {
				$retry_count++;
				$this->print_cli('error', " GetDetails() Caught exception: ".  $e->getMessage());
				$this->print_cli('error', " Retry count: ". $retry_count);
				usleep(200000);
				if ($retry_count >= $retry) {
					$this->print_cli('error', " Givingup on ".$host.":".$port);
					return False;
				}
			}
		}
		return $serverInfo;
	}


}
