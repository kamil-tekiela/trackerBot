<?php

declare(strict_types=1);

namespace Tracker;

use Tracker\Services\ServicesInterface;

class Tracker {
	/**
	 * Timestamp
	 *
	 * @var int
	 */
	private $lastRequestTime;

	/**
	 * Chat API to talk in the chat
	 *
	 * @var ChatAPI
	 */
	private $chatAPI = null;

	/**
	 * Stack API class for using the official Stack Exchange API
	 *
	 * @var StackAPI
	 */
	private $stackAPI = null;

	private $logRoomId = null;

	/**
	 * Runnable services which will be executed for each question
	 *
	 * @var ServicesInterface[]
	 */
	private $services = [];

	public function __construct(\StackAPI $stackAPI, \ChatAPI $chatAPI, \DotEnv $dotEnv) {
		$this->chatAPI = $chatAPI;
		$this->stackAPI = $stackAPI;

		if (!$this->lastRequestTime) {
			$this->lastRequestTime = strtotime('15 minutes ago');
		}

		$this->logRoomId = (int) $dotEnv->get('trackRoomId');
	}

	/**
	 * Entry point. Fetches a bunch of questions and then parses them.
	 *
	 * @return void
	 */
	public function fetch() {
		$apiEndpoint = 'questions';
		$url = "https://api.stackexchange.com/2.2/" . $apiEndpoint;

		$args = [
			'todate' => strtotime('2 minutes ago'),
			'site' => 'stackoverflow',
			'order' => 'asc',
			'sort' => 'creation',
			'pagesize' => '100',
			'page' => '1',
			'filter' => '7yrx3gca'
		];
		if (!DEBUG) {
			$args['fromdate'] = $this->lastRequestTime + 1;
		} else {
			$args['fromdate'] = 0;
		}

		do {
			echo(date_create_from_format('U', (string) $this->lastRequestTime)->format('Y-m-d H:i:s')). ' to '.(date_create_from_format('U', (string) $args['todate'])->format('Y-m-d H:i:s')).PHP_EOL;

			// Request questions
			$contents = $this->stackAPI->request('GET', $url, $args);

			if (!$contents) {
				continue;
			}

			foreach ($contents->items as $postJSON) {
				$post = new Question($postJSON);

				// Execute services
				foreach ($this->services as $service) {
					$service->execute($post);
				}

				// set last request
				$this->lastRequestTime = $post->creation_date->format('U');
			}
			$args['page']++;
		} while ($contents->has_more);

		// end processing
		echo 'Processing finished at: '.date_create()->format('Y-m-d H:i:s').PHP_EOL;
	}

	public function search(string $searchString, ServicesInterface $service) {
		$apiEndpoint = 'search/advanced';
		$url = "https://api.stackexchange.com/2.2/" . $apiEndpoint;

		$args = [
			'todate' => strtotime('2 minutes ago'),
			'site' => 'stackoverflow',
			'order' => 'asc',
			'sort' => 'creation',
			'pagesize' => '100',
			'page' => '1',
			'filter' => '7yrx3gca',
			'fromdate' => 0,
			'q' => $searchString,
			'closed' => 'False',
		];

		$this->chatAPI->sendMessage($this->logRoomId, 'Started search for: '.$searchString);

		do {
			echo(date_create_from_format('U', (string) $this->lastRequestTime)->format('Y-m-d H:i:s')). ' to '.(date_create_from_format('U', (string) $args['todate'])->format('Y-m-d H:i:s')).PHP_EOL;

			// Request questions
			$contents = $this->stackAPI->request('GET', $url, $args);

			if (!$contents) {
				continue;
			}

			foreach ($contents->items as $postJSON) {
				$post = new Question($postJSON);

				// Execute services
				$service->execute($post);

				// set last request
				$this->lastRequestTime = $post->creation_date->format('U');
			}
			$args['page']++;
		} while ($contents->has_more);

		$this->chatAPI->sendMessage($this->logRoomId, 'Search is over. Quota remaining: '.$contents->quota_remaining);

		// end processing
		echo 'Processing finished at: '.date_create()->format('Y-m-d H:i:s').PHP_EOL;
	}

	public function registerService(ServicesInterface $service) {
		$this->services[] = $service;
	}
}
