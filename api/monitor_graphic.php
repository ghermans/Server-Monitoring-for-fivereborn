<?PHP
if($_GET['ip']) {
	include '../statistics/API.php';
	
	$ipAddress = $_GET['ip'];
	$res = preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d{1,5}$/', $ipAddress, $ip_matches);
	if (!$res) {
		exit;
	}

	$api = new API();
	$server = $api->getServerByIp($ipAddress);
	$server->loadServerEntries();
	$serverEntries = $server->getServerEntries();
	
	if ($server->getDatabaseValue('is_online')) {
		$imgPng = imageCreateFromPng("../statistics/img/online.png");
	} else {
		$imgPng = imageCreateFromPng("../statistics/img/offline.png");
	}
	imageAlphaBlending($imgPng, true);
	imageSaveAlpha($imgPng, true);

	$black = imagecolorallocate($imgPng, 0, 0, 0);
	$blue= imagecolorallocate($imgPng, 65, 131, 196);
	$red= imagecolorallocate($imgPng, 255, 0, 0);
	$blanc= imagecolorallocate($imgPng, 255, 255, 255);
	$gray = imagecolorallocate($imgPng, 102, 102, 102);
	
	/*Transparent colors */
	$tred = imagecolorallocatealpha($imgPng, 255, 0, 0, 110);
	$tgreen = imagecolorallocatealpha($imgPng, 0, 255, 0, 110);
	
	//Light
	$font1 = '../statistics/fonts/Light.ttf';
	$font2 = '../statistics/fonts/Semibold.ttf';
	$font3 = '../statistics/fonts/Bold.ttf';
	$font4 = '../statistics/fonts/logotype.ttf';
	
	
	$highestPing = 0;
	for ($i = 0; $i < sizeof($serverEntries); $i++)
		if ($serverEntries[$i]['ping'] > $highestPing)
			$highestPing = $serverEntries[$i]['ping'];
	$highestPing += 40;

	imagettftext($imgPng, 12, 0, 23, 18, $gray, $font4, $server->getDatabaseValue('name'));
	imagettftext($imgPng, 12, 0, 45, 148, $gray, $font4, $server->getDatabaseValue('clients')."/".$server->getDatabaseValue('maxclients'));
	imagettftext($imgPng, 12, 0, 220, 148, $gray, $font4, $server->getDatabaseValue('map_name'));
	
	imagettftext($imgPng, 10, 0, 378, 37, $gray, $font4, "-".$server->getDatabaseValue('maxclients'));
	imagettftext($imgPng, 10, 0, 378, 124, $gray, $font4, "- 0");
	
	
	imagettftext($imgPng, 10, 0, 22, 37, $gray, $font4, $highestPing."-");
	imagettftext($imgPng, 10, 0, 26, 124, $gray, $font4, "0 -");
	
	if (sizeof($serverEntries) > 1) {
		/*
		top-left: 43|31
		bottom-right: 377|120
		*/
		$interval = 86400; // 24 hours in seconds
		$width = 334;
		$height = 88;
		$zeroX = 377;
		$zeroY = 119;
		
		$heightPerPlayer = $height / $server->getDatabaseValue('maxclients');
		$widthPerSecond = $width / $interval;
		
		$heightPerPing = $height / $highestPing;
		
		$lastestTimestamp = $serverEntries[0]['timestamp'];
		
		$lastSecondsAgo = 0;
		$i = 1;
		
		while ($i < sizeof($serverEntries) && ($secondsAgo = $lastestTimestamp-$serverEntries[$i]['timestamp']) < 86400) {
			if($serverEntries[$i-1]['online'] && $serverEntries[$i]['online']) {
				imagefilledrectangle($imgPng, $zeroX-($lastSecondsAgo*$widthPerSecond), $zeroY, $zeroX-($secondsAgo*$widthPerSecond), $zeroY-$height, $tgreen);
				imageline($imgPng, $zeroX-($lastSecondsAgo*$widthPerSecond), $zeroY-($serverEntries[$i-1]['ping']*$heightPerPing), $zeroX-($secondsAgo*$widthPerSecond), $zeroY-($serverEntries[$i]['ping']*$heightPerPing), $blue);
				imageline($imgPng, $zeroX-($lastSecondsAgo*$widthPerSecond), $zeroY-($serverEntries[$i-1]['clients']*$heightPerPlayer), $zeroX-($secondsAgo*$widthPerSecond), $zeroY-($serverEntries[$i]['clients']*$heightPerPlayer), $red);
			} else {
				imagefilledrectangle($imgPng, $zeroX-($lastSecondsAgo*$widthPerSecond), $zeroY, $zeroX-($secondsAgo*$widthPerSecond), $zeroY-$height, $tred);
			}
			$lastSecondsAgo = $secondsAgo;
			$i++;
		}
		if($serverEntries[0]['online']) {
			imagettftext($imgPng, 10, 0, 22, $zeroY-($serverEntries[0]['ping']*$heightPerPing)+5, $blue, $font4, $serverEntries[0]['ping']." -");
			imagettftext($imgPng, 10, 0, 378, $zeroY-($serverEntries[0]['clients']*$heightPerPlayer)+5, $red, $font4, "- ".$serverEntries[0]['clients']);
		}
	}
	//imageline($imgPng, 43, 100, 377, 100, $red);
	
	header("Content-type: image/png");
	imagePng($imgPng);
	
	
	$ipAddress = escapeshellarg($ipAddress);
	shell_exec('php ../statistics/update_single_server.php '.$ipAddress.' > /dev/null 2>/dev/null &');
}
?>