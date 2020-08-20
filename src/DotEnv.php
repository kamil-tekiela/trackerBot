<?php

/**
 * Config class - stores all environment variables loaded from config file
 */
class DotEnv
{
	private static $config = [];

	/**
	 * Load the config file
	 *
	 * @param string $path
	 * @return void
	 */
	public static function load(string $path)
	{
		// open and parse the config file
		self::$config = \parse_ini_file($path, true, INI_SCANNER_TYPED);
		if (!self::$config) {
			throw new \Exception('No config file!');
		}
	}

	/**
	 * Getter for config. Returns the configuration variable
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get(string $key)
	{
		return self::$config[$key] ?? null;
	}
}
