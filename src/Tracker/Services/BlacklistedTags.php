<?php

declare(strict_types=1);

namespace Tracker\Services;

use Dharman\ChatAPI;
use Tracker\Question;

class BlacklistedTags implements ServicesInterface {
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

		$this->chatrooms = $dotEnv->get('chatrooms')['blockedTags'];

		// Say hello
		foreach ($this->chatrooms as $roomId) {
			$this->chatAPI->sendMessage($roomId, 'Blacklisted Tags Tracker started on '.gethostname());
		}
	}

	/**
	 * Execute question scan
	 *
	 * @param Question $post
	 * @return void
	 */
	public function execute(Question $post): void {
		$line = '';

		$blacklistedTags = file(BASE_DIR.'/blockedTags.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		if (array_diff($post->tags, $blacklistedTags) === []) {
			$line = implode(' ', array_map(fn ($text) => '[tag:'.$text.']', $post->tags));
		}

		if ($line) {
			foreach ($this->chatrooms as $roomId) {
				$this->chatAPI->sendMessage($roomId, $line." {$post->linkFormatted}".PHP_EOL);
			}
		}
	}
}
