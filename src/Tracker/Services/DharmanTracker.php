<?php

declare(strict_types=1);

namespace Tracker\Services;

use Dharman\ChatAPI;
use Tracker\Question;

class DharmanTracker implements ServicesInterface {
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

	public function __construct(ChatAPI $chatAPI, \DotEnv $dotEnv) {
		$this->chatAPI = $chatAPI;

		$this->chatrooms = $dotEnv->get('chatrooms')['dharman'];

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
		if (stripos($post->bodyWithTitle, 'dharman') !== false) {
			$line = "@Dharman";
		}

		if ($line) {
			foreach ($this->chatrooms as $roomId) {
				$this->chatAPI->sendMessage($roomId, $line." {$post->linkFormatted}".PHP_EOL);
			}
		}
	}
}
