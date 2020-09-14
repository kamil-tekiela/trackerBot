<?php

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client as Guzzle;

class StackAPI {
	/**
	 * Guzzle
	 *
	 * @var Guzzle
	 */
	private $client;

	/**
	 * My app key. Not secret
	 */
	private const APP_KEY = 'KU)9*jHmdv28q21fadYYVg((';

	/**
	 * Name of the persistant storage file for the timestamp $nextRqPossibleAt
	 */
	private const TIMEFILE = 'nextRqPossibleAt';

	/**
	 * Time the next request can be made at.
	 *
	 * @var float
	 */
	private $nextRqPossibleAt = 0.0;

	private $lastQuota = null;

	public function __construct(Guzzle $client) {
		$this->client = $client;
		if (file_exists(self::TIMEFILE)) {
			$this->nextRqPossibleAt = (float) file_get_contents(self::TIMEFILE);
		}
	}

	public function request(string $method, string $url, array $args): stdClass {
		// handle backoff properly
		$timeNow = microtime(true);
		if ($timeNow < $this->nextRqPossibleAt) {
			$backoffTime = ceil($this->nextRqPossibleAt - $timeNow);
			echo 'Backing off for '.$backoffTime.' seconds'.PHP_EOL;
			sleep($backoffTime);
		}

		// enhance with API key for more quota
		$args += [
			'key' => self::APP_KEY
		];

		// log
		$logLine = date_create()->format('Y-m-d H:i:s')."\t{$this->lastQuota}\t$url".PHP_EOL;
		file_put_contents(BASE_DIR.'/logs/apiCall.log', $logLine, FILE_APPEND);

		// make the call
		try {
			if ($method == 'GET') {
				$rq = $this->client->request($method, $url, ['query' => $args]);
			} else {
				$rq = $this->client->request($method, $url, ['form_params' => $args]);
			}
		} catch (RequestException $e) {
			$response = $e->getResponse();
			if (isset($response)) {
				if (($json = json_decode($response->getBody()->getContents())) && isset($json->error_id) && $json->error_id == 502) {
					sleep(10 * 60);
					return $this->request($method, $url, $args);
				} else {
					throw new Exception(Psr7\str($e->getResponse()));
				}
			} else {
				throw $e;
			}
		}
		
		if (isset($rq)) {
			$body = $rq->getBody()->getContents();
		} else {
			throw new Exception("Response is empty");
		}

		$contents = json_decode($body);
		
		$this->nextRqPossibleAt = microtime(true);
		if (isset($contents->backoff)) {
			echo 'I was told to back off for '.$contents->backoff.' seconds'.PHP_EOL;
			$this->nextRqPossibleAt + $contents->backoff;
		}
		file_put_contents(self::TIMEFILE, $this->nextRqPossibleAt);

		$this->lastQuota = $contents->quota_remaining;

		return $contents;
	}
}
