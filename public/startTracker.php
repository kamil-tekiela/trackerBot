<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

exec('ps -eaf | grep \'[p]hp73 tracker.php\' | awk \'{print $2}\'', $out);
foreach($out as $pid){
	// exec("kill -9 $pid");
}
if($out){
	echo 'Already running';
	die;
}
passthru('php73 tracker.php > trackerout.txt 2>trackerout.txt & ', $ret);

var_dump($ret);

echo 'Started!';