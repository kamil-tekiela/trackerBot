<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$restart = isset($_GET['restart']);
$stop = isset($_GET['stop']);

exec('ps -eaf | grep \'[p]hp74 tracker.php\' | awk \'{print $2}\'', $out);
foreach($out as $pid){
	if($restart || $stop) {
		exec("kill -9 $pid");
	}
}
if($stop) {
	echo 'It\'s probably stopped.';
	die;
}
if($out && !$restart){
	echo 'Already running';
	die;
}

// chdir('../../trackerBot/public');
passthru('php74 tracker.php > trackerout.txt 2>trackerout.txt & ', $ret);

var_dump($ret);

echo 'Started!';
