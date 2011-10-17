<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Client
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * A cURL client class.
 *
 * @package     Joomla.Platform
 * @subpackage  Client
 * @since       ?
 */
class JCurl extends JURI
{
	/**
	 * @var array curlOptions.
	 */
	protected $curlOptions = array();

	/**
	 * @var string Target path where to save the response
	 */
	protected $target = '';

	/**
	 * Get a cURL adapter.
	 *
	 * @param   string  $uri  The URI to work with.
	 *
	 * @throws Exception
	 *
	 * @return JCurl
	 */
	public static function getAdapter($uri = null)
	{
		if ( ! function_exists('curl_init'))
		{
			throw new Exception('cURL is not available');
		}

		return new JCurl($uri);
	}

	/**
	 * Constructor.
	 * You should pass a URI string to the constructor to initialise a specific URI.
	 *
	 * @param   string  $uri  The URI string
	 *
	 * @throws Exception
	 */
	public function __construct($uri = null)
	{
		if ( ! function_exists('curl_init'))
		{
			throw new Exception('cURL is not available');
		}

		parent::__construct($uri);
	}

	/**
	 * Set cURL options.
	 *
	 * @param   array  $options  The cURL options.
	 *
	 * @return JCurl
	 */
	public function setOptions($options)
	{
		$this->curlOptions = (array)$options;

		return $this;
	}

	/**
	 * Read URL contents.
	 *
	 * @throws Exception
	 *
	 * @return object The cURL response.
	 */
	public function fetch()
	{
		$ch = curl_init();

		curl_setopt_array($ch, $this->curlOptions);

		curl_setopt($ch, CURLOPT_URL, $this->_uri);

		if ( ! array_key_exists(CURLOPT_SSL_VERIFYHOST, $this->curlOptions))
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
		}

		if ( ! array_key_exists(CURLOPT_SSL_VERIFYPEER, $this->curlOptions))
		{
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		if ( ! array_key_exists(CURLOPT_FOLLOWLOCATION, $this->curlOptions))
		{
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		}

		if ( ! array_key_exists(CURLOPT_MAXREDIRS, $this->curlOptions))
		{
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		}

		if ( ! array_key_exists(CURLOPT_TIMEOUT, $this->curlOptions))
		{
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		}

		if ( ! array_key_exists(CURLOPT_RETURNTRANSFER, $this->curlOptions))
		{
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}

		if ($this->target)
		{
			// Write the response to a file
			$fp = fopen($this->target, 'w');

			if ( ! $fp)
			{
				throw new Exception('Can not open target file at: '.$this->target);
			}

			// Use CURLOPT_FILE to speed things up
			curl_setopt($ch, CURLOPT_FILE, $fp);
		}
		else
		{
			// Return the response
			if ( ! array_key_exists(CURLOPT_RETURNTRANSFER, $this->curlOptions))
			{
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			}
		}

		$response = curl_exec($ch);

		if (curl_errno($ch))
		{
			throw new Exception('Curl Error: '.curl_error($ch));
		}

		$info = curl_getinfo($ch);

		if (isset($info['http_code']) && $info['http_code'] != 200)
		{
			$response = false;
		}

		curl_close($ch);

		$return = JArrayHelper::toObject($info);
		$return->body = $response;

		return $return;
	}

	/**
	 * Save the response to a file.
	 *
	 * @param   string  $target  Target path
	 *
	 * @return boolean true on success
	 *
	 * @throws Exception
	 */
	public function saveToFile($target)
	{
		$this->target = $target;

		$response = $this->fetch();

		if (false === $response)
		{
			throw new Exception('File cannot be downloaded');
		}

		return true;
	}
}
