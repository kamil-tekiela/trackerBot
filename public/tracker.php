<?php

use Dharman\ChatAPI;
use Dharman\StackAPI;

$searchString = $argv[2] ?? null;
define('DEBUG', isset($argv[1]) && $argv[1] == 1);
define('BASE_DIR', realpath(__DIR__.'/..'));

include BASE_DIR.'/vendor/autoload.php';

$dotEnv = new DotEnv();
$dotEnv->load(BASE_DIR.'/config.ini');

if (!file_exists(BASE_DIR.DIRECTORY_SEPARATOR.'logs')) {
	mkdir(BASE_DIR.DIRECTORY_SEPARATOR.'logs');
}

if (!file_exists(BASE_DIR.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'errors')) {
	mkdir(BASE_DIR.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'errors');
}

if (!file_exists(BASE_DIR.DIRECTORY_SEPARATOR.'data')) {
	mkdir(BASE_DIR.DIRECTORY_SEPARATOR.'data');
}

$client = new GuzzleHttp\Client();

$chatAPI = new ChatAPI($dotEnv->get('chatUserEmail'), $dotEnv->get('chatUserPassword'), BASE_DIR.'/data/chatAPI_cookies.json');

$stackAPI = new StackAPI($client);

$fetcher = new Tracker\Tracker($stackAPI, $chatAPI, $dotEnv);

if ($searchString) {
	// if executed from CLI then perform a search instead
	$fetcher->search($searchString, new Tracker\Services\NELQAnalyser($chatAPI, $dotEnv));
	exit;
}

// register services
$fetcher->registerService(new Tracker\Services\MysqliTracker($chatAPI, $dotEnv));
$fetcher->registerService(new Tracker\Services\NELQAnalyser($chatAPI, $dotEnv));
$fetcher->registerService(new Tracker\Services\DharmanTracker($chatAPI, $dotEnv));

// watch new questions constantly
$failedTries = 0;
while (1) {
	try {
		$fetcher->fetch();
	} catch (\Throwable $e) {
		$failedTries++;
		file_put_contents(BASE_DIR.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'errors'.DIRECTORY_SEPARATOR.date('Y_m_d_H_i_s').'.log', $e->getMessage().PHP_EOL.print_r($e->getTrace(), true));
		sleep($failedTries * 60);
		// keep trying up to n times
		if ($failedTries >= 10) {
			throw $e;
		}
		continue;
	}

	$failedTries = 0;

	sleep(75);
}
