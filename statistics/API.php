<?php
class Log {
	const LOG_LEVEL = 10;
	
	private static $logFilename;
	private static $fileHandler;
	private static $loggingId;
	
	/* Konstruktor */
	public static function start() {
		self::$logFilename = Date("Y-m-d").".log";
		self::$fileHandler = fopen(self::$logFilename, 'a');
		self::$loggingId = self::randomString(5);
		self::write('Start log session', "SESSION");
	}
	
	/* Destruktor */
	public static function stop() {
		self::write('End log session\r\n', "SESSION", 2);
		fclose(self::$fileHandler);
	}
	
	private static function randomString($length) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
	private static function write($text, $label, $linebreaks = 1) {
		$microtime = microtime(true);
		$mSecs = $microtime - floor($microtime);
		$mSecs = substr($mSecs, 1);
		$time = Date("H:i:s").$mSecs;
		
		fwrite(self::$fileHandler, '['.self::$loggingId.' | '.$time.' | '.$label.'] '. $text);
		for($i = 0; $i < $linebreaks; $i++)
			fwrite(self::$fileHandler, "\r\n");
	}
	
	public static function info($text, $level = 0) {
		if ($level >= self::LOG_LEVEL)
			self::write($text, 'INFO');
	}
	
	public static function warning($text) {
		self::write($text, 'WARNING');
	}
	
	public static function error($text, $line) {
		self::write($text, 'ERROR (L '.$line.')');
	}
}

class MySQL {
	const MYSQL_HOST = "localhost";
	const MYSQL_USER = "USER";
	const MYSQL_PASSWORD = "PASSWORD";
	const MYSQL_DATABASE = "DATABASE";
	
	private static $mysqli;
	
	/* Konstruktor */
	public static function init() {
        self::$mysqli = new mysqli(self::MYSQL_HOST, self::MYSQL_USER, self::MYSQL_PASSWORD, self::MYSQL_DATABASE);
		if (self::$mysqli->connect_error) {
			Log::error("MySQL connection failed (".self::$mysqli->connect_error.")", __LINE__);
		}
	}
	
	/* Destruktor */
	public static function destroy() {
		self::$mysqli->close();
	}
	
	public static function query($query, $line) {
		$result = self::$mysqli->query($query);
		if (self::$mysqli->error) {
			Log::error(self::$mysqli->error, $line);
			Log::error("Query: ".$query, $line);
			return false;
		}
		return $result;
	}
	
	public static function getInsertId() {
		return self::$mysqli->insert_id;
	}
	
	public static function real_escape_string($string) {
		return self::$mysqli->real_escape_string($string);
	}
}

class Variables {
	private static $variables;
	
	public static function init() {
		self::$variables = array();
		self::loadVariables();
	}
	
	private static function loadVariables() {
		if (!($query_result = MySQL::query(
			"SELECT * FROM `statistics_variables` ".
			"UNION ALL(SELECT `A`.`name`,@`type`:='exceeded' AS `type`,@`is_exceeded` := (TIMEDIFF(DATE_SUB(NOW(), INTERVAL `A`.`value` SECOND),`B`.`value`) > 0) AS `value` ".
			"FROM `statistics_variables` AS `A`,`statistics_variables` AS `B` ".
			"WHERE `A`.`name`=`B`.`name` AND `A`.`type`!=`B`.`type` AND `A`.`type`='interval' AND `B`.`type`='last_update')"
		, __LINE__))) return false;
		
		if ($query_result->num_rows > 0) {
			while ($row = $query_result->fetch_assoc()) {
				if (!isset(self::$variables[$row['type']])) {
					self::$variables[$row['type']] = array();
				}
				self::$variables[$row['type']][$row['name']] = $row['value'];
			}
		}
		return true;
	}
	
	public static function getVariable($type, $name) {
		if (isset(self::$variables[$type]) && isset(self::$variables[$type][$name])) {
			return self::$variables[$type][$name];
		}
		return null;
	}
	
	public static function updateVariable($name) {
		$type = "last_update";
		if(!($query_result = MySQL::query("UPDATE `statistics_variables` SET `value`=NOW() WHERE `name`='".$name."' AND `type`='".$type."'", __LINE__))) return false;
		return true;
	}
}

class MapHandler {
	private static $mapArray;
	
	public static function init() {
		self::$mapArray = array();
		self::loadMaps();
	}
	
	private static function loadMaps() {
		if(!($query_result = MySQL::query("SELECT `id`, `name` FROM `statistics_map`", __LINE__))) return false;

		if ($query_result->num_rows > 0) {
			while ($row = $query_result->fetch_assoc()) {
				self::$mapArray[$row[id]] = $row['name'];
			}
		}
		Log::info("Loaded ".sizeof(self::$mapArray)." maps from database", 1);
	}
	
	private static function addMap($name) {
		Log::info("Add new map to database (".$name.")", 2);
		$name = MySQL::real_escape_string(trim($name));
		if(!($query_result = MySQL::query("INSERT INTO `statistics_map` (name) VALUES ('".$name."')", __LINE__))) return false;
		$id = MySQL::getInsertId();
		self::$mapArray[$id] = $name;
		return $id;
	}
	
	public static function getMapName($id) {
		if ($id >= 0 && $id < sizeof(self::$mapArray))
			return self::$mapArray[$id];
		return false;
	}
	
	public static function getMapId($name) {
		if (!($id = array_search(trim($name), self::$mapArray))) {
			return self::addMap($name);
		}
		return $id;
	}
}

class GametypeHandler {
	private static $gameTypeArray;
	
	public static function init() {
		self::$gameTypeArray = array();
		self::loadGameTypes();
	}
		
	private static function loadGameTypes() {
		if(!($query_result = MySQL::query("SELECT `id`, `name` FROM `statistics_gametype`", __LINE__))) return false;
		if ($query_result->num_rows > 0) {
			while ($row = $query_result->fetch_assoc()) {
				self::$gameTypeArray[$row[id]] = $row['name'];
			}
		}
		Log::info("Loaded ".sizeof(self::$gameTypeArray)." gametypes from database", 1);
	}
	
	private static function addGameType($name) {
		$name = MySQL::real_escape_string(trim($name));
		if(!($query_result = MySQL::query("INSERT INTO `statistics_gametype` (name) VALUES ('".$name."')", __LINE__))) return false;
		$id = MySQL::getInsertId();
		self::$gameTypeArray[$id] = $name;
		return $id;
	}
	
	public static function getGameTypeName($id) {
		if ($id >= 0 && $id < sizeof(self::$gameTypeArray))
			return self::$gameTypeArray[$id];
		return false;
	}
	
	public static function getGameTypeId($name) {
		if (!($id = array_search(trim($name), self::$gameTypeArray))) {
			return self::addGameType($name);
		}
		return $id;
	}
}

class Server {
	const INFO_RESPONSE_OFFLINE = 0;
	const INFO_RESPONSE_ONLINE 	= 1;
	const INFO_RESPONSE_TIMEOUT = 2;
	
	private $ip;
	private $port;
	private $inDatabase;
	private $isValid;
	
	private $databaseValues;
	private $responseValues;
	private $serverEntries;
	
	/* Konstruktor */
	function __construct($arg1, $arg2 = null) {
		Log::info("Init Server (".$arg1.", ".($arg2!=null?$arg2:"null").")" ,0);
		$this->databaseValues = array();
		$this->responseValues = array();
		$this->serverEntries = array();
		$this->isValid = true;
		$this->ip = null;
		$this->port = null;
		
		if ($arg2 == null) {
			$this->inDatabase = $this->loadById($arg1);
		} else {
			$this->inDatabase = $this->loadByAddress($arg1, $arg2);
		}
	}
	
	/* Destruktor */
	function __destruct() {
		if ($this->inDatabase) {
			Log::info("Destroy Server (".$this->ip.", ".$this->port.")" ,0);
		} else {
			Log::info("Destroy Server (Unknown)" ,0);
		}
	}
	
	private function load($query_result) {
		if ($query_result->num_rows != 1) return false;
		$this->databaseValues = $query_result->fetch_assoc();
		
		if ($this->databaseValues['gametype'] !== null)
			$this->databaseValues['gametype_name'] = GameTypeHandler::getGameTypeName($this->databaseValues['gametype']);

		if ($this->databaseValues['map'] !== null)
			$this->databaseValues['map_name'] = MapHandler::getMapName($this->databaseValues['map']);
		
		if ($this->databaseValues['exceeded'] === null)
			$this->databaseValues['exceeded'] = 1;
		
		$this->ip = $this->databaseValues['ip'];
		$this->port = $this->databaseValues['port'];
		
		if (!($count_result = MySQL::query("SELECT COUNT(*) FROM `statistics_server_player` WHERE `serverid`='".$this->databaseValues['id']."'", __LINE__))) return false;
		$this->databaseValues['clients'] = $count_result->fetch_row()[0];
		return true;
	}
	
	private function loadByAddress($ip, $port) {
		Log::info("Load server from database by address (".$ip.", ".$port.")" ,1);
		$update_interval = Variables::getVariable("interval", "update_server");
		if ($update_interval == null) 
			$update_interval = 0;
		
		if (!($query_result = MySQL::query("SELECT *,@`is_exceeded`:=(TIMEDIFF(DATE_SUB(NOW(), INTERVAL ".$update_interval." SECOND), `last_update`)>0) AS `exceeded` FROM `statistics_server` WHERE `ip`='".$ip."' AND `port`='".$port."'", __LINE__))) return false;
		
		$this->isValid = true;
		$this->ip = $ip;
		$this->port = $port;
		return $this->load($query_result);
	}
	
	private function loadById($serverId) {
		Log::info("Load server from database by id (".$serverId.")" ,1);
		$update_interval = Variables::getVariable("interval", "update_server");
		if ($update_interval == null) 
			$update_interval = 0;
		
		if (!($query_result = MySQL::query("SELECT *,@`is_exceeded`:=(TIMEDIFF(DATE_SUB(NOW(), INTERVAL ".$update_interval." SECOND), `last_update`)>0) AS `exceeded` FROM `statistics_server` WHERE `id`='".$serverId."'", __LINE__))) return false;
		if ($query_result->num_rows != 1) $this->isValid = false;
		else $this->isValid = true;
		return $this->load($query_result);
	}
	
	public function loadServerEntries() {
		if (!$this->inDatabase)
			return false;
		if (!($query_result = MySQL::query("SELECT UNIX_TIMESTAMP(`timestamp`) AS `timestamp`, `online`, `ping`, `clients` FROM `statistics_server_entries` WHERE `serverid`='".$this->databaseValues['id']."' ORDER BY `timestamp` DESC", __LINE__))) return false;
		if ($query_result->num_rows > 0) {
			while ($row = $query_result->fetch_assoc()) {
				$this->serverEntries[] = $row;
			}
		}
		return true;
	}
	
	public function getServerEntries() {
		return $this->serverEntries;
	}
	
	private function isUpdating() {
		return $this->hasServerInfo() && $this->databaseValues['is_updating'];
	}
	
	public function isOnline() {
		if ($this->hasServerInfo())
			return $this->responseValues['online'];
		return ($this->requestServerInfo() == self::INFO_RESPONSE_ONLINE);
	}
	
	public function isValid() {
		return $this->isValid && $this->ip != null && $this->port != null;
	}
	
	private function hasServerInfo() {
		return sizeof($this->responseValues) > 0;
	}
	
	public function getDatabaseValue($value) {
		if ($this->inDatabase) {
			return $this->databaseValues[$value];
		} 
		return null;
	}
	
	public function requestServerInfo() {
		Log::info("Request serverinfo for server (".$this->ip.", ".$this->port.")" ,1);
		$udp_connection_timeout = Variables::getVariable("timeout", "udp_connection");
		if ($udp_connection_timeout == null) $udp_connection_timeout = 2;
		
		$udp_response_timeout = Variables::getVariable("timeout", "udp_response");
		if ($udp_response_timeout == null) $udp_response_timeout = 4;
		
		if (!$this->isValid()) {
			$this->responseValues['online'] = false;
			$this->responseValues['ping'] = -1;
			return self::INFO_RESPONSE_OFFLINE;
		}
		
		$fp = fsockopen("udp://".$this->ip, $this->port, $errno, $errstr, $udp_connection_timeout);
		if (!$fp) {
			$this->responseValues['online'] = false;
			$this->responseValues['ping'] = -1;
			fclose($fp);
			return self::INFO_RESPONSE_OFFLINE;
		}
		stream_set_timeout($fp, $udp_response_timeout);
		fwrite($fp, str_repeat(chr(255),4)."getstatus 1\r\n");
		$starttime = microtime(true);
		$response = fread($fp, 1024);
		$stoptime = microtime(true);
		$info = stream_get_meta_data($fp);
		fclose($fp);
		
		if ($info['timed_out']) {
			$this->responseValues['online'] = false;
			$this->responseValues['ping'] = -1;
			return self::INFO_RESPONSE_TIMEOUT;
		}
		$this->responseValues['ping'] = floor(($stoptime - $starttime) * 1000);
		
		$responseInfo = explode(chr(10), $response);
		if (sizeof($responseInfo) < 2 || strcmp($responseInfo[0], str_repeat(chr(255),4)."statusResponse") !== 0) {
			$this->responseValues['ping'] = -1;
			$this->responseValues['online'] = false;
			return self::INFO_RESPONSE_OFFLINE;
		}
		
		$this->responseValues['online'] = true;
		$split = split('\\\\', substr($responseInfo[1], 1), 16);
		for ($i = 0; $i < 16; $i+=2) {
			$this->responseValues[$split[$i]] = $split[$i+1];
		}
		
		$this->responseValues['client_list'] = array();
		if (sizeof($responseInfo) > 2) {
			for ($i = 2; $i < sizeof($responseInfo); $i++) {
				$f = strpos($responseInfo[$i], '"');
				$l = strrpos($responseInfo[$i], '"');
				$playerName = substr($responseInfo[$i], $f+1, $l-$f-1);
				if(strlen($playerName) > 0) {
					$this->responseValues['client_list'][] = $playerName;
				}
			}
		}
		return self::INFO_RESPONSE_ONLINE;
	}
	
	public function addToDatabase() {
		Log::info("Add to database (".$this->ip.", ".$this->port.", ".$this->isValid().")" ,2);
		if (!$this->isValid())
			return false;
		if (!$this->isOnline())
			return false;
		
		if(!($query_result = MySQL::query("INSERT INTO `statistics_server` (`name`, `ip`, `port`, `maxclients`) VALUES ('".MySQL::real_escape_string($this->responseValues['hostname'])."','".$this->ip."','".$this->port."','".MySQL::real_escape_string($this->responseValues['sv_maxclients'])."')", __LINE__))) return false;
		$serverId = MySQL::getInsertId();
		
		if(!(MySQL::query("INSERT INTO `statistics_server_lifetime` (`serverid`) VALUES ('".$serverId."')", __LINE__))) return false;
		if(!(MySQL::query("INSERT INTO `statistics_server_records` (`serverid`) VALUES ('".$serverId."')", __LINE__))) return false;
		return $serverId;
	}
	
	public function update() {
		Log::info("Update server (".$this->ip.", ".$this->port.", ".$this->isValid().")" ,1);
		if (!$this->isValid())
			return false;
		
		if (!$this->inDatabase) {
			/* is already in $this->addToDatabase()
			if (!$this->isOnline())
				return false;
			*/
			$serverId = $this->addToDatabase();
			if (!$serverId) 
				return false;
			
			$this->inDatabase = $this->loadById($serverId);
		}
		if ($this->isUpdating()) {
			return true;
		}
		if (!$this->databaseValues['exceeded']) {
			return true;
		}
		
		/* Get time since last update */
		if (!($query_result = MySQL::query("SELECT `id`, @`since_last_update`:=UNIX_TIMESTAMP(now())-UNIX_TIMESTAMP(`A`.`last_update`) as `since_last_update` FROM `statistics_server` as `A` WHERE `id`='".$this->databaseValues['id']."'", __LINE__))) return false;

		if ($query_result->num_rows != 1) return false;
		$seconds_since_last_update = $query_result->fetch_assoc()['since_last_update'];
		if ($seconds_since_last_update == null) $seconds_since_last_update = 0;
		
		$this->databaseValues['is_updating'] = true;
		if (!($query_result = MySQL::query("UPDATE `statistics_server` SET `is_updating`='1' WHERE `id`='".$this->databaseValues['id']."'", __LINE__))) return false;

		MySQL::query("DELETE FROM `statistics_server_player` WHERE `serverid` = '".$this->databaseValues['id']."'", __LINE__);
		
		$serverUpdateQuery = 'UPDATE `statistics_server` SET `last_update`=now()';
		if ($this->isOnline()) {
			MySQL::query("UPDATE `statistics_server_lifetime` SET `up_seconds`= `up_seconds` + ".$seconds_since_last_update." WHERE `serverid`='".$this->databaseValues['id']."'", __LINE__);

			if (!isset($this->databaseValues['name']) || $this->databaseValues['name'] != $this->responseValues['hostname']) {
				$serverUpdateQuery .= ",`name`='".MySQL::real_escape_string($this->responseValues['hostname'])."'";
			}
			if (!isset($this->databaseValues['gametype_name']) || $this->databaseValues['gametype_name'] != $this->responseValues['gametype']) {
				$gameTypeId = GametypeHandler::getGameTypeId($this->responseValues['gametype']);
				$serverUpdateQuery .= ",`gametype`='".$gameTypeId."'";
			}
			if (!isset($this->databaseValues['map_name']) || $this->databaseValues['map_name'] != $this->responseValues['mapname']) {
				$mapId = MapHandler::getMapId($this->responseValues['mapname']);
				$serverUpdateQuery .= ",`map`='".$mapId."'";
			}
			if (!isset($this->databaseValues['maxclients']) || $this->databaseValues['maxclients'] != $this->responseValues['sv_maxclients']) {
				$serverUpdateQuery .= ",`maxclients`='".MySQL::real_escape_string($this->responseValues['sv_maxclients'])."'";
			}
			
			/* Update Player */
			if (sizeof($this->responseValues['client_list']) > 0) {
				$insertPlayerQuery = "INSERT INTO `statistics_server_player` (`serverid`, `playerid`, `name`) VALUES ";
				$insertPlayerValues = "";
				
				for($i = 0; $i < sizeof($this->responseValues['client_list']); $i++) {
					if ($i > 0)
						$insertPlayerValues .= ",";
					$insertPlayerValues .= "('".$this->databaseValues['id']."','".$i."','".MySQL::real_escape_string($this->responseValues['client_list'][$i])."')";
				}
				MySQL::query($insertPlayerQuery.$insertPlayerValues, __LINE__);
			}
		} else {
			MySQL::query("UPDATE `statistics_server_lifetime` SET `down_seconds`= `down_seconds` + ".$seconds_since_last_update." WHERE `serverid`='".$this->databaseValues['id']."'", __LINE__);
		}
		
		MySQL::query("INSERT INTO `statistics_server_entries` (`serverid`, `online`, `ping`, `clients`) VALUES ('".$this->databaseValues['id']."','".($this->isOnline()?"1":"0")."','".$this->responseValues['ping']."','".sizeof($this->responseValues['client_list'])."')", __LINE__);
		
		/* Update Server */
		$serverUpdateQuery .= ",`is_online`='".($this->isOnline()?"1":"0")."',`is_updating`='0' WHERE `id`='".$this->databaseValues['id']."'";
		
		if(!(MySQL::query($serverUpdateQuery, __LINE__))) return false;
		return true;
	}
	
	/* STATIC */
	public static function getPlayerList($ip, $port) {
		if (!($count_result = MySQL::query("SELECT COUNT(*) FROM `statistics_server` WHERE `ip`='".$ip."' AND `port`='".$port."'", __LINE__))) continue;
		if ($count_result->fetch_row()[0] != 1) {
			$server = new Server($ip, $port);
			$server->update();
			unset($server);
		}
		
		MySQL::query("UPDATE `statistics_server` SET `request_playerlist` = `request_playerlist` +1 WHERE `ip`='".$ip."' AND `port`='".$port."'", __LINE__);
		if (!($query_result = MySQL::query("SELECT PLAYER.`name` FROM `statistics_server` AS SERVER, `statistics_server_player` AS PLAYER WHERE SERVER.`id`=PLAYER.`serverid` AND SERVER.`ip`='".$ip."' AND SERVER.`port`='".$port."'", __LINE__))) return false;
		$onlinePlayer = array();
		if ($query_result->num_rows > 0) {
			while ($row = $query_result->fetch_assoc()) {
				$onlinePlayer[] = $row['name'];
			}
		}
		return $onlinePlayer;
	}
	
	public function addRequestDrawing() {
		if ($this->inDatabase) {
			MySQL::query("UPDATE `statistics_server` SET `request_drawing` = `request_drawing` +1 WHERE `id`='".$this->databaseValues['id']."'", __LINE__);
		}
	}
}

class API {	
	/* Konstruktor */
	function __construct() {
		Log::start();
		MySQL::init();
		GametypeHandler::init();
		MapHandler::init();
		Variables::init();
    }

	/* Destruktor */
	function __destruct() {
		MySQL::destroy();
		Log::stop();
	}
	
	private function validateIp($ipString) {
		$res = preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d{1,5}$/', $ipString, $ip_matches);
		if (!$res) {
			return false;
		}
		$expl = explode(':', $ipString);
		if (sizeof($expl) != 2) {
			return false;
		}
		return ["ip" => $expl[0], "port" => $expl[1]];
	}
	
	public function updateServerList() {
		$http_timeout = Variables::getVariable("timeout", "http_request");
		if ($http_timeout == null) 
			$http_timeout = 2;
		
		$ctx = stream_context_create(array('http' => array('timeout' => $http_timeout)));
		if (!$jsonResponse = file_get_contents("https://servers.fivereborn.com/json", 0, $ctx)) {
			$error = error_get_last();
			Log::error("HTTP request failed. Error was: " . $error['message'], __LINE__);
		}
		$officialServerList = json_decode($jsonResponse);
		
		if (!($query_result = MySQL::query("SELECT `ip`, `port` FROM `statistics_server`", __LINE__))) return false;
		if ($query_result->num_rows > 0) {
			while ($row = $query_result->fetch_assoc()) {
				$ipAddress = $row['ip'].":".$row['port'];
				if (isset($officialServerList->{$ipAddress})) {
					unset($officialServerList->{$ipAddress});
				}
			}
		}
		if (sizeof((array)$officialServerList) > 0) {
			foreach ($officialServerList as $key => $value) {
				if(!($info = $this->validateIp($key)))
					continue;

				$server = new Server($info['ip'], $info['port']);
				$server->update();
				unset($server);
			}
		}
	}
	
	public function updateSingleServers() {
		$update_interval = Variables::getVariable("interval", "update_server");
		if ($update_interval == null) 
			$update_interval = 0;
		
		$update_limit = Variables::getVariable("limit", "update_server_amount");
		if ($update_limit == null) 
			$update_limit = 5;
		
		Log::info("Update ".$update_limit." server older than ".$update_interval." second", 1);
		
		if (!($query_result = MySQL::query("SELECT `id` FROM `statistics_server` WHERE `is_updating`='0' AND (`last_update` IS NULL OR `last_update` <= now() - INTERVAL ".$update_interval." SECOND) ORDER BY `last_update`,`id` ASC LIMIT ".$update_limit, __LINE__))) return false;
		if ($query_result->num_rows > 0) {
			while ($row = $query_result->fetch_assoc()) {
				$server = new Server($row['id']);
				$server->update();
				unset($server);
			}
		} else {
			Log::warning("There are no servers to update");
		}
		return true;
	}
	
	/* Public Functions */
	public function checkUpdates() {
		if (Variables::getVariable('exceeded', 'update_serverlist')) {
			Variables::updateVariable('update_serverlist');
			$this->updateServerList();
		}
		if (Variables::getVariable('exceeded', 'update_server')) {
			Variables::updateVariable('update_server');
			$this->updateSingleServers();
		}
		if (Variables::getVariable('exceeded', 'delete_entries')) {
			Variables::updateVariable('delete_entries');
			$update_interval = Variables::getVariable('time', 'delete_entries');
			MySQL::query("DELETE FROM `statistics_server_entries` WHERE `timestamp` <= now() - INTERVAL ".$update_interval." SECOND", __LINE__);
		}
	}
	
	public function checkUpdateSingleServer($ipAddress) {		
		if(!($info = $this->validateIp($ipAddress)))
			return false;
		
		$server = new Server($info['ip'], $info['port']);
		$server->update();
		unset($server);
		return true;
	}
	
	public function getPlayersOfServer($ipAddress) {
		if(!($info = $this->validateIp($ipAddress)))
			return false;
		return Server::getPlayerList($info['ip'], $info['port']);
	}
	
	public function getServerByIp($ipAddress) {
		if(!($info = $this->validateIp($ipAddress)))
			return false;
		return new Server($info['ip'], $info['port']);
	}
}
?>