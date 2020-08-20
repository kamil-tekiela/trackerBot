<?php

declare(strict_types=1);

namespace Tracker\Services;

use Tracker\Question;

interface ServicesInterface {
	public function __construct(\ChatAPI $chatAPI, \DotEnv $dotEnv);

	public function execute(Question $post): void;
}
