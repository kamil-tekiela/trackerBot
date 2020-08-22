<?php

declare(strict_types=1);

namespace Tracker\Services;

use Tracker\Question;

class MysqliTracker implements ServicesInterface {
	/**
	 * Chat API to talk in the chat
	 *
	 * @var ChatAPI
	 */
	private $chatAPI = null;

	/**
	 * Chat rooms to report to
	 *
	 * @var array
	 */
	private $chatrooms = [];

	public function __construct(\ChatAPI $chatAPI, \DotEnv $dotEnv) {
		$this->chatAPI = $chatAPI;

		$this->chatrooms = $dotEnv->get('chatrooms')['mysqli'];

		// Say hello
		foreach ($this->chatrooms as $roomId) {
			$this->chatAPI->sendMessage($roomId, 'TrackerBot started on '.gethostname());
		}
	}

	/**
	 * Execute question scan
	 *
	 * @param Question $post
	 * @return void
	 */
	public function execute(Question $post): void {
		// Our rules
		$line = '';
		if (stripos($post->bodyWithTitle, 'mysqli') !== false) {
			$line = "[tag:mysqli]";
		} elseif (preg_match('#mysql_(?:query|connect|select_db|error|fetch|num_rows|escape_string|close|result)#i', $post->bodyWithTitle)) {
			$line = "[tag:mysql_]";
		} elseif (preg_match('#fetch_(?:assoc|array|row|object|num|both|all|field)#i', $post->bodyWithTitle)) {
			$line = "[tag:mysqli]";
		} elseif (stripos($post->bodyWithTitle, '->query') !== false) {
			$line = "[tag:mysqli]";
		} elseif (stripos($post->bodyWithTitle, 'bind_param') !== false) {
			$line = "[tag:mysqli]";
		} elseif (stripos($post->bodyWithTitle, '->error') !== false) {
			$line = "[tag:mysqli]";
		} elseif (in_array('mysqli', $post->tags, true)) {
			$line = "[tag:mysqli]";
		}

		if ($line) {
			$tags = array_reduce($post->tags, function ($carry, $e) {
				return $carry."[tag:{$e}] ";
			});
			foreach ($this->chatrooms as $roomId) {
				$this->chatAPI->sendMessage($roomId, $tags.$line." {$post->linkFormatted}".PHP_EOL);
			}
		}
	}
}
