<?php
	// Verbindungs-Objekt und Zugangsdaten festlegen
	$db = new mysqli("localhost", "root", "gta1234", "gta");
	
	final class auth {
		public static $playerid;
		public static $token;
		public static $valid = false;
		public static $name;
		public static $level;
		public static $geld;
		public static $uid;
		
		public static function init($playerid, $token) {
			if(is_null($token) || strlen($token) == 0) {
				API::send(array("error" => "Missing Session Parameter!", "code" => 403));
			}
			
			self::$playerid = $playerid;
			self::$token = $token;
			self::$valid = self::loginWithToken($token);
		}
		
		public static function loginWithToken($token) {
			global $db;
			
			$query = "SELECT id, name, level, geld FROM user WHERE session='".mysql_real_escape_string($token)."'";
			if ($result = $db->query($query)) {
				$row = $result->fetch_assoc();
				if(!is_null($row["name"])) {
					self::$name = $row["name"];
					self::$geld = $row["geld"];
					self::$level = $row["level"];
					self::$uid = $row["id"];
					return true;
				}
				$result->free();
			}
			
			return false;
		}
		
		public static function getAuth() {
			return array("dbid" => (int)self::$uid, "playerid" => (int)self::$playerid, "sess" => (int)self::$token, "name" => self::$name, "level" => (int)self::$level, "geld" => (int)self::$geld);
		}
		
		public static function requireValidAccount() {
			if(!self::$valid) {
				API::send(array("error" => "Authentication failed!", "code" => 403));
			}
		}
	}
	
	final class methods {
		public static function chat() {
			$params = func_get_arg(0);
			auth::init($params["playerid"], $params["sess"]);
			auth::requireValidAccount();
			global $db;

			/* insert */
			$stmt = $db->prepare("INSERT INTO message (text, uid, time) VALUES (?, ?, ?)");
			$uid = 1;
			$stmt->bind_param('sii', $params["message"], $uid, time());
			$stmt->execute();
			
			$arr = array();
			$arr["type"] = API::$REPLY_CHAT;
			$arr["auth"] = auth::getAuth();
			$arr["data"]["response"] = 1;
			
			return $arr;
		}
		
		// TODO Auth etc..
		public static function remote() {
			$params = func_get_arg(0);
			
			/*
			require('C:\xampp\htdocs\php\socket\client\Socks5Socket.class.php');
			$s = new \Socks5Socket\Client();
			$s->connect('25.108.238.139', 9999);
			$s->send($params["data"]);
			//$response = $s->readAll();
			$s->disconnect();
			*/
			$reqId = uniqid();
			$fp = fopen("C:\\xampp\\htdocs\\api\\ipc\\ipc_request.queue", "a+");
			$success = false;
			do {
				if(flock($fp, LOCK_EX)) {  // acquire an exclusive lock
					$contents = stream_get_contents($fp);
					$obj = json_decode($contents);
					$params_filtered = array();
					foreach($params AS $i=>$p) {
						if(filter_var($p, FILTER_VALIDATE_INT))
							$params_filtered[$i] = (int)$p;
						else
							$params_filtered[$i] = $p;
					}
					$params_filtered["requestId"] = $reqId;
					$obj[] = $params_filtered;
					ftruncate($fp, 0);      // truncate file
					fwrite($fp, json_encode($obj));
					fflush($fp);            // flush output before releasing the lock
					flock($fp, LOCK_UN);    // release the lock
					$success = true;
				}
			} while(!$success);
			fclose($fp);
			
			if($params["ret"] == 1) {
				while(true) {
					$filename = "C:\\xampp\\htdocs\\api\\ipc\\ipc_reply_".$reqId.".queue";
					$fp = fopen($filename, "r+");
					if($fp) {
						$contents = stream_get_contents($fp);
						ftruncate($fp, 0);
						fclose($fp);
						unlink($filename);
						$obj = json_decode($contents, true);
						foreach($obj AS $command) {
							if($command["requestId"] == $reqId) {
								return $command;
							}
						}
					}
					usleep(1000);
				}
			}
			
			return array("response" => 1);
		}
		
		public static function login() {
			$params = func_get_arg(0);
			$arr = array();
			$arr["type"] = API::$REPLY_LOGIN;
			$arr["data"]["playerid"] = $params["playerid"];
			global $db;
			$login = false;
	
			/* check access */
			$query = "SELECT passwort, geld, level FROM user WHERE name='".mysql_real_escape_string ($params["name"])."'";	
			if ($result = $db->query($query)) {
				/* fetch associative array */
				$row = $result->fetch_assoc();
				$geld = $row["geld"];
				$level = $row["level"];
				if($row["passwort"] != MD5($params["password"])) {
					$arr["data"]["response"] = 2;
					return $arr;
				}
				else if($row["passwort"] == MD5($params["password"])) {
					$login = true;
				}
			
				/* free result set */
				$result->free();
			}

			/* update session id */
			$sess = md5(uniqid(time()));
			$stmt = $db->prepare("UPDATE user SET session=?, time=? WHERE name='".$params["name"]."'");
			$stmt->bind_param('si', $sess, time());
			$stmt->execute();
			
			$arr["data"]["sess"] = $sess;
			$arr["data"]["level"] = (int)$level;
			$arr["data"]["money"] = (int)$geld;
			
			if($login)
				$arr["data"]["response"] = 1;
			else
				$arr["data"]["response"] = 2;
				
			return $arr;
		}
	}
	
	final class API {
		// Constants
		public static $REPLY_LOGIN = 1;
		public static $REQUEST_LOGIN = 2;
		public static $REQUEST_CHAT = 3;
		public static $REPLY_CHAT = 4;
			
		public static function callById($id, $data) {
			switch($id) {
				case self::$REQUEST_LOGIN:
					return API::exec("login", $data);
				break;
				case self::$REQUEST_CHAT:
					return API::exec("chat", $data);
				break;
				default:
					echo "ERROR::TYPE NOT FOUND!!";
				break;
			}
		}
		
		public static function send($msg) {
			if(!is_null($msg))
				echo json_encode($msg, JSON_PRETTY_PRINT);
			exit;
		}
		
		public static function exec($name, $params) {
			return call_user_func_array("methods::".$name, array($params));
		}
	}
?>