<?php

namespace Tracker;

class Question {
	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $body;

	/**
	 * @var string
	 */
	public $bodyWithoutCode;

	/**
	 * @var string
	 */
	public $bodyWithTitle;

	/**
	 * @var string
	 */
	public $bodyStrippedWithTitle;

	/**
	 * @var int
	 */
	public $score;

	/**
	 * @var \DateTime
	 */
	public $creation_date;

	/**
	 * @var string
	 */
	public $closed_date;

	/**
	 * @var string
	 */
	public $link;

	/**
	 * @var string
	 */
	public $linkFormatted;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * Question owner
	 *
	 * @var \stdClass
	 */
	public $owner;

	/**
	 * Tags
	 *
	 * @var array
	 */
	public $tags = [];

	public function __construct(\stdClass $json) {
		$this->id = $json->question_id;
		$this->score = $json->score;
		$this->closed_date = $json->closed_date ?? '';
		$this->creation_date = date_create_from_format('U', $json->creation_date);
		$this->link = $json->link;
		$this->title = $json->title;
		$this->body = $json->body;
		$this->owner = $json->owner;
		$this->tags = $json->tags;
		$this->bodyWithTitle = $json->title.PHP_EOL.$json->body;
		$this->linkFormatted = '['.$this->quotemeta($this->title).']('.$this->link.')';

		$this->bodyWithoutCode = preg_replace('#\s*(?:<pre>)?<code>.*?<\/code>(?:<\/pre>)?\s*#s', '', $this->body);
		$this->bodyStrippedWithTitle = strip_tags(preg_replace('#\s*<a.*?>.*?<\/a>\s*|\s*<blockquote>.*?<\/blockquote>\s*|\s*<pre>.*?<\/pre>\s*#s', '', $this->bodyWithoutCode));
	}

	private function quotemeta($string) {
		return str_replace(['\\', '[', ']', '(', ')'], ['\\\\', '\[', '\]', '\(', '\)'], $string);
	}
}
