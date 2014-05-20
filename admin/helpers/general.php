<?php

// No direct access
defined('_JEXEC') or die;

/**
 * EasyStaging component helper.
 *
 * @package  EasyStaging
 *
 * @since    1.0.0
 *
 */
class ES_General_Helper
{
	/**
	 * Creates a simple tag to denote major versions that can be used in file names to load version appropriate files.
	 * e.g. views etc
	 *
	 * @return string
	 */
	public static function getJoomlaVersionTag()
	{
		// Get our Joomla Tag
		$jv         = new JVersion;
		return 'j' . explode('.', $jv->RELEASE)[0];
	}
}
