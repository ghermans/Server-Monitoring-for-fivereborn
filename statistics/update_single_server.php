<?
include 'API.php';

if(sizeof($argv) == 2) {
	$ipAddress = $argv[1];
	$res = preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d{1,5}$/', $ipAddress, $ip_matches);
	if ($res) {

		$api = new API();
		
		Log::warning("checkUpdateSingleServer File called!");
		
		$api->checkUpdateSingleServer($ipAddress);
	}
}
?>