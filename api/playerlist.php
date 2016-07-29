<?PHP
if($_GET['ip']) {
	include '../statistics/API.php';

	$ipAddress = $_GET['ip'];
	$res = preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d{1,5}$/', $ipAddress, $ip_matches);
	if (!$res) {
		exit;
	}
	
	$output = (object)array();
	$api = new API();
	$playerlist = $api->getPlayersOfServer($ipAddress);
		
	$output->{'client_count'} = $playerlist!==false?sizeof($playerlist):0;
	$output->{'clients'} = $playerlist!==false?$playerlist:[];
	$output->{'errors'} = $playerlist!==false?[]:["can't reach server"];
	
	echo(json_encode($output));
	
	$ipAddress = escapeshellarg($ipAddress);
	shell_exec('php ../statistics/update_single_server.php '.$ipAddress.' > /dev/null 2>/dev/null &');
}
?>