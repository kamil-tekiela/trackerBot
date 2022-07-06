<?php

declare(strict_types=1);

namespace Tracker\Services;

use Dharman\ChatAPI;
use Tracker\Question;

class TrovamicoTracker implements ServicesInterface {
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

		$this->chatrooms = $dotEnv->get('chatrooms')['mysqli'];

		// Say hello
		foreach ($this->chatrooms as $roomId) {
			$this->chatAPI->sendMessage($roomId, 'TrovamicoTracker started on '.gethostname());
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
		if (preg_match('#(?:Trovamico|epdragon|FirebaseDatabase\.getInstance\(\)\.getReference\("Users"\))#i', $post->bodyWithTitle)) {
			$line = "@Dharman [Bubino](https://stackoverflow.com/users/12700297/bubino) is back. ";
		}

		if ($line) {
			foreach ($this->chatrooms as $roomId) {
				$this->chatAPI->sendMessage($roomId, $line." {$post->linkFormatted}".PHP_EOL);
			}
		}
	}
}
