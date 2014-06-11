<?php
require_once("C:\\xampp\\htdocs\\api\\api.php");
error_reporting(E_ERROR | E_PARSE);
set_time_limit(0);

class TcpServer {
	const ECONNABORTED = 103;   /* Software caused connection abort */
	const ECONNRESET   = 104;   /* Connection reset by peer */

	private static $disconnect_errors = array(self::ECONNRESET, self::ECONNABORTED);

	private $server_port = 6666;
	private $server_ip = '25.108.238.139';
	private $server_sock;

	private $client_timeout = 300;
	private $clients = array();
	private $clients_data = array();

	private function error($msg) {
		throw new ETcpServer($msg);
	}

	private function log($msg) {
		echo $msg, "\n";
	}

	function init() {
		$this->server_sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!$this->server_sock) die('Error creating HTTP socket.');
		socket_set_nonblock($this->server_sock);

		if (!socket_bind($this->server_sock, $this->server_ip, $this->server_port))
			return $this->error("Cannot bind to address: $this->server_ip:$this->server_port");
		if (!socket_listen($this->server_sock))
			return $this->error("Cannot listen at address: $this->server_ip:$this->server_port");
		
		$this->onInitCompleted();

		return true;
	}

	function close_client($client_id) {
		$client = $this->clients[$client_id];
		if (is_resource($client))
			socket_close($client);
		unset($this->clients[$client_id]);
		unset($this->clients_data[$client_id]);
	}

	function send($client, $data) {
		$bytes_sent = 0;
		$length = strlen($data);
		while ($length > 0) {
			if ($bytes_sent) $data = substr($data, $bytes_sent);
			$bytes_sent = socket_write($client, $data, $length);
			if (!$bytes_sent) break;
			$length -= $bytes_sent;
		}
	}
	
	function sendToAll($data) {
		foreach($this->clients AS $client) {
			$bytes_sent = 0;
			$length = strlen($data);
			while ($length > 0) {
				if ($bytes_sent) $data = substr($data, $bytes_sent);
				$bytes_sent = socket_write($client, $data, $length);
				if (!$bytes_sent) break;
				$length -= $bytes_sent;
			}
		}
	}
	
	function step() {
		/********************************
			IPC
		**********************************/
		$fp = fopen("C:\\xampp\\htdocs\\api\\ipc\\ipc_request.queue", "a+");
		$success = false;
		do {
			if(flock($fp, LOCK_EX)) {  // acquire an exclusive lock
				$contents = stream_get_contents($fp);
				ftruncate($fp, 0);
				$success = true;
			}
		} while(!$success);

		fclose($fp);
		$obj = json_decode($contents, true);
		foreach($obj AS $command) {
			$this->onIPCCommand($command);
		}
		
		while ($client = @socket_accept($this->server_sock)) {
			$this->clients[] = $client;
			$had_events = true;
			$this->onClientConnected($client);
		}

		if (empty($this->clients))
			return false;

		$read = $this->clients;
		$write = $except = null;

		$changed = socket_select($read, $write, $except, 0);
		if ($changed === false)
			return $this->error('Select error: ' . socket_strerror(socket_last_error()));
		if (!$changed)
			return $had_events;


		foreach ($read as $client) {
			$client_id = array_search($client, $this->clients);
			if ($client_id === false) continue;

			/* socket_read() should return FALSE when client disconnects, but it never does */
			$data = @socket_read($client, 4096);
			if ($data === false) {
				$error = socket_last_error($client);
				if (in_array($error, self::$disconnect_errors)) {
					$this->close_client($client_id);
					$this->onClientDisconnected($client);
				}
				continue;
			}

			$length = strlen($data);

			/* bug workaround - disconnect client after a certain time of inactivity ... */
			$client_data = &$this->clients_data[$client_id];
			if (!is_array($client_data)) $client_data = array();

			if ($length > 0)
				$client_data['time'] = time();
			elseif (
				isset($client_data['time']) &&
				(time() - $client_data['time'] > $this->client_timeout)
			) {
				$this->close_client($client_id);
				$this->onClientDisconnected($client);
				continue;
			}
			/* ... end workaround */

			if ($length > 0) {
				$had_events = true;
				//$this->send($client, $data, $length);
				$this->onReceive($client, $data, $length);
			}
		}
		
		return $had_events;
	}

	function run() {
		if (!$this->init())
			return false;

		while (true) {
			if (!$this->step())
				usleep(1000);
			/* do some other stuff */
		}
	}

	/**
		Custom Callback Implementation
	**/
	
	function onClientConnected($client) {
		$this->log('New client connected.');
		//$this->sendToAll("Hello");
	}
	
	function onClientDisconnected($client) {
		$this->log('Client disconnected.');
		$client_id = array_search($client, $this->clients);
		unset($this->clients[$client_id]);
	}
	
	function onReceive($client, $data, $length) {
		$this->log('Client sent:'.$data);
		/*
		$arr["message"] = array();
		$arr["type"] = 1;
		$arr["message"]["lol"] = "peace out";
		$arr["message"]["rofl"] = "wixxer!!!";
		$this->sendToAll(json_encode($arr));
		*/
		
		// API Call
		$obj = json_decode($data, true);
		if(strlen($obj["type"]) <= 0) {
			$this->log('Client sent malformed call (type missing):'.$data);
		}
		else {
			// indicates a call initiated by the webserver
			if(!is_null($obj["requestId"])) {
				$reqId = mysql_real_escape_string($obj["requestId"]);	// enough escape here?? SECURITY!!!
				if(!is_null($obj["ret"])) {
					do {
						$fp = fopen("C:\\xampp\\htdocs\\api\\ipc\\ipc_reply_".$reqId.".queue", "a+");
					} while(!$fp);
					
					$contents = stream_get_contents($fp);
					$objOdd = json_decode($contents);
					$objOdd[] = $obj;
					ftruncate($fp, 0);      // truncate file
					fwrite($fp, json_encode($objOdd));
					fflush($fp);            // flush output before releasing the lock
					$success = true;
					fclose($fp);
				}
			}
			else {
				$ret = json_encode(API::callById($obj["type"], $obj["data"]));
				
				if(!is_null($ret)) {
					$this->log("Sending back: ".$ret);
					$this->sendToAll($ret);
				}
			}
		}
	}
	
	function onIPCCommand($command) {
		$cmd_str = json_encode($command);
		$this->log('IPC Command to '.$command["destination"].':'.$cmd_str);
		if($command["destination"] == "webserver") {
			API::callById($command["type"], $command["data"]);
		}
		else if($command["destination"] == "game") {
			$this->sendToAll($cmd_str);
		}
	}
	
	function onInitCompleted() {
		$this->log("Sock at $this->server_ip:$this->server_port");
	}
}

$server = new TcpServer();
$server->run();
?>