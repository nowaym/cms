<?php
/**
 * Response wrapper
 *
 * Created as the result of any [Request] execution or utility method
 * (i.e. Redirect). Implements standard HTTP response format.
 *
 * @package    Gleez\Base
 * @author     Gleez Team
 * @version    1.0.2
 * @copyright  (c) 2011-2014 Gleez Technologies
 * @license    http://gleezcms.org/license  Gleez CMS License
 */
class Response implements HTTP_Response {

	/**
	 * HTTP status codes and messages
	 * @var array
	 */
	public static $messages = array(
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',

		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',

		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);

	/**
	 * The response http status
	 * @var integer
	 */
	protected $_status = 200;

	/**
	 * Headers returned in the response
	 * @var HTTP_Header
	 */
	protected $_header;

	/**
	 * The response body
	 * @var string
	 */
	protected $_body = '';

	/**
	 * Cookies to be returned in the response
	 * @var array
	 */
	protected $_cookies = array();

	/**
	 * The response protocol
	 * @var string
	 */
	protected $_protocol;

	/**
	 * Factory method to create a new [Response]
	 *
	 * Pass properties in using an associative array.
	 *
	 * Example:
	 * ~~~
	 * // Create a new response
	 * $response = Response::factory();
	 *
	 * // Create a new response with headers
	 * $response = Response::factory(array('status' => 200));
	 * ~~~
	 *
	 * @param   array  $config  Config for response object [Optional]
	 *
	 * @return  Response
	 */
	public static function factory(array $config = array())
	{
		return new Response($config);
	}

	/**
	 * Sets up the response object
	 *
	 * @param   array  $config  Config for response object [Optional]
	 *
	 * @return  Response
	 */
	public function __construct(array $config = array())
	{
		$this->_header = new HTTP_Header;

		foreach ($config as $key => $value)
		{
			if (property_exists($this, $key))
			{
				if ($key == '_header')
				{
					$this->headers($value);
				}
				else
				{
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Outputs the body when cast to string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_body;
	}

	/**
	 * Gets or sets the body of the response
	 *
	 * @param   string  $content  The body of the response [Optional]
	 * @return  string|Response
	 */
	public function body($content = NULL)
	{
		if ($content === NULL)
		{
			return $this->_body;
		}

		$this->_body = (string) $content;

		return $this;
	}

	/**
	 * Gets or sets the HTTP protocol
	 *
	 * The standard protocol to use is `HTTP/1.1`.
	 *
	 * @param   string  $protocol  Protocol to set to the request|response [Optional]
	 * @return  mixed
	 */
	public function protocol($protocol = NULL)
	{
		if ($protocol)
		{
			$this->_protocol = strtoupper($protocol);
			return $this;
		}

		if ($this->_protocol === NULL)
		{
			$this->_protocol = HTTP::$protocol;
		}

		return $this->_protocol;
	}

	/**
	 * Sets or gets the HTTP status from this response
	 *
	 * Example:
	 * ~~~
	 * // Set the HTTP status to 404 Not Found
	 * $response = Response::factory()
	 *                       ->status(404);
	 *
	 * // Get the current status
	 * $status = $response->status();
	 * ~~~
	 *
	 * @param   integer  $status  Status to set to this response [Optional]
	 *
	 * @return  mixed
	 *
	 * @throws  Gleez_Exception
	 */
	public function status($status = NULL)
	{
		if ($status === NULL)
		{
			return $this->_status;
		}
		elseif (array_key_exists($status, Response::$messages))
		{
			$this->_status = (int) $status;
			return $this;
		}
		else
		{
			throw new Gleez_Exception(__METHOD__.' unknown status value : :value', array(':value' => $status));
		}
	}

	/**
	 * Gets and sets headers to the [Response], allowing chaining
	 * of response methods
	 *
	 * If chaining isn't required, direct access to the property
	 * should be used instead.
	 *
	 * Example:
	 * ~~~
	 * // Get a header
	 * $accept = $response->headers('Content-Type');
	 *
	 * // Set a header
	 * $response->headers('Content-Type', 'text/html');
	 *
	 * // Get all headers
	 * $headers = $response->headers();
	 *
	 * // Set multiple headers
	 * $response->headers(array('Content-Type' => 'text/html', 'Cache-Control' => 'no-cache'));
	 * ~~~
	 *
	 * @param   mixed   $key    Header key [Optional]
	 * @param   string  $value  Header value [Optional]
	 *
	 * @return  mixed
	 *
	 * @uses    Arr::get
	 */
	public function headers($key = NULL, $value = NULL)
	{
		if ($key === NULL)
		{
			return $this->_header;
		}
		elseif (is_array($key))
		{
			$this->_header->exchangeArray($key);
			return $this;
		}
		elseif ($value === NULL)
		{
			return Arr::get($this->_header, $key);
		}
		else
		{
			$this->_header[$key] = $value;
			return $this;
		}
	}

	/**
	 * Returns the length of the body for use with content header
	 *
	 * @return  integer
	 */
	public function content_length()
	{
		return strlen($this->body());
	}

	/**
	 * Set and get cookies values for this response
	 *
	 * Example:
	 * ~~~
	 * // Get the cookies set to the response
	 * $cookies = $response->cookie();
	 *
	 * // Set a cookie to the response
	 * $response->cookie('session', array(
	 *     'value'      => $value,
	 *     'expiration' => 12352234
	 * ));
	 * ~~~
	 *
	 * @param   mixed   $key    Cookie name, or array of cookie values [Optional]
	 * @param   string  $value  Value to set to cookie [Optional]
	 *
	 * @return  string
	 * @return  void
	 * @return  [Response]
	 *
	 * @uses    Arr::get
	 * @uses    Cookie::$expiration
	 */
	public function cookie($key = NULL, $value = NULL)
	{
		// Handle the get cookie calls
		if ($key === NULL)
		{
			return $this->_cookies;
		}
		elseif ( ! is_array($key) AND ! $value)
		{
			return Arr::get($this->_cookies, $key);
		}

		// Handle the set cookie calls
		if (is_array($key))
		{
			reset($key);
			while (list($_key, $_value) = each($key))
			{
				$this->cookie($_key, $_value);
			}
		}
		else
		{
			if ( ! is_array($value))
			{
				$value = array(
					'value'      => $value,
					'expiration' => Cookie::$expiration
				);
			}
			elseif ( ! isset($value['expiration']))
			{
				$value['expiration'] = Cookie::$expiration;
			}

			$this->_cookies[$key] = $value;
		}

		return $this;
	}

	/**
	 * Deletes a cookie set to the response
	 *
	 * @param   string  $name  Cookie key
	 *
	 * @return  Response
	 */
	public function delete_cookie($name)
	{
		unset($this->_cookies[$name]);

		return $this;
	}

	/**
	 * Deletes all cookies from this response
	 *
	 * @return  Response
	 */
	public function delete_cookies()
	{
		$this->_cookies = array();

		return $this;
	}

	/**
	 * Sends the response status and all set headers
	 *
	 * @param   boolean   $replace   Replace existing headers [Optional]
	 * @param   callback  $callback  Function to handle header output [Optional]
	 *
	 * @return  mixed
	 */
	public function send_headers($replace = FALSE, $callback = NULL)
	{
		return $this->_header->send_headers($this, $replace, $callback);
	}

	/**
	 * Send file download as the response
	 *
	 * All execution will be halted when this method is called!
	 * Use TRUE for the filename to send the current response as the file content.
	 * The third parameter allows the following options to be set:
	 *
	 * Type      | Option    | Description                        | Default Value
	 * ----------|-----------|------------------------------------|--------------
	 * `boolean` | inline    | Display inline instead of download | `FALSE`
	 * `string`  | mime_type | Manual mime type                   | Automatic
	 * `boolean` | delete    | Delete the file after sending      | `FALSE`
	 *
	 * Example:
	 * ~~~
	 * // Download a file that already exists
	 * $request->send_file('media/packages/gleez.zip');
	 *
	 * // Download generated content as a file:
	 * $request->response($content);
	 * $request->send_file(TRUE, $filename);
	 * ~~~
	 *
	 * [!!] Note: No further processing can be done after this method is called!
	 *
	 * @param   string  $filename  Filename with path, or TRUE for the current response
	 * @param   string  $download  Downloaded file name [Optional]
	 * @param   array   $options   Additional options [Optional]
	 *
	 * @return  void
	 *
	 * @throws  Gleez_Exception
	 *
	 * @uses    File::mime_by_ext
	 * @uses    File::mime
	 * @uses    Request::send_headers
	 * @uses    Gleez_Exception::text
	 */
	public function send_file($filename, $download = NULL, array $options = NULL)
	{
		if ( ! empty($options['mime_type']))
		{
			// The mime-type has been manually set
			$mime = $options['mime_type'];
		}

		if ($filename === TRUE)
		{
			if (empty($download))
			{
				throw new Gleez_Exception('Download name must be provided for streaming files');
			}

			// Temporary files will automatically be deleted
			$options['delete'] = FALSE;

			if ( ! isset($mime))
			{
				// Guess the mime using the file extension
				$mime = File::mime_by_ext(strtolower(pathinfo($download, PATHINFO_EXTENSION)));
			}

			// Force the data to be rendered if
			$file_data = (string) $this->_body;

			// Get the content size
			$size = strlen($file_data);

			// Create a temporary file to hold the current response
			$file = tmpfile();

			// Write the current response into the file
			fwrite($file, $file_data);

			// File data is no longer needed
			unset($file_data);
		}
		else
		{
			// Get the complete file path
			$filename = realpath($filename);

			if (empty($download))
			{
				// Use the file name as the download file name
				$download = pathinfo($filename, PATHINFO_BASENAME);
			}

			// Get the file size
			$size = filesize($filename);

			if ( ! isset($mime))
			{
				// Get the mime type
				$mime = File::mime_by_ext(strtolower(pathinfo($download, PATHINFO_EXTENSION)));
			}

			// Open the file for reading
			$file = fopen($filename, 'rb');
		}

		if ( ! is_resource($file))
		{
			throw new Gleez_Exception('Could not read file to send: :file', array(
				':file' => $download,
			));
		}

		// Inline or download?
		$disposition = empty($options['inline']) ? 'attachment' : 'inline';

		// Calculate byte range to download.
		list($start, $end) = $this->_calculate_byte_range($size);

		if ( ! empty($options['resumable']))
		{
			if ($start > 0 OR $end < ($size - 1))
			{
				// Partial Content
				$this->_status = 206;
			}

			// Range of bytes being sent
			$this->_header['content-range'] = 'bytes '.$start.'-'.$end.'/'.$size;
			$this->_header['accept-ranges'] = 'bytes';
		}

		// Set the headers for a download
		$this->_header['content-disposition'] = $disposition.'; filename="'.$download.'"';
		$this->_header['content-type']        = $mime;
		$this->_header['content-length']      = (string) (($end - $start) + 1);

		if (Request::user_agent('browser') === 'Internet Explorer')
		{
			// Naturally, IE does not act like a real browser...
			if (Request::$initial->secure())
			{
				// http://support.microsoft.com/kb/316431
				$this->_header['pragma'] = $this->_header['cache-control'] = 'public';
			}

			if (version_compare(Request::user_agent('version'), '8.0', '>='))
			{
				// http://ajaxian.com/archives/ie-8-security
				$this->_header['x-content-type-options'] = 'nosniff';
			}
		}

		// Send all headers now
		$this->send_headers();

		while (ob_get_level())
		{
			// Flush all output buffers
			ob_end_flush();
		}

		// Manually stop execution
		ignore_user_abort(TRUE);

		// Send data in 16kb blocks
		$block = 1024 * 16;

		fseek($file, $start);

		while ( ! feof($file) AND ($pos = ftell($file)) <= $end)
		{
			if (connection_aborted())
				break;

			if ($pos + $block > $end)
			{
				// Don't read past the buffer.
				$block = $end - $pos + 1;
			}

			// Output a block of the file
			echo fread($file, $block);

			// Send the data now
			flush();
		}

		// Close the file
		fclose($file);

		if ( ! empty($options['delete']))
		{
			try
			{
				// Attempt to remove the file
				unlink($filename);
			}
			catch (Exception $e)
			{
				// Create a text version of the exception
				$error = Gleez_Exception::text($e);

				if (is_object(Kohana::$log))
				{
					// Add this exception to the log
					// And make sure the logs are written
					Log::error($error)->write();
				}

				// Do NOT display the exception, it will corrupt the output!
			}
		}

		// Stop execution
		exit;
	}

	/**
	 * Renders the HTTP_Interaction to a string, producing
	 *
	 *  - Protocol
	 *  - Headers
	 *  - Body
	 *
	 * @return  string
	 */
	public function render()
	{
		if ( ! $this->_header->offsetExists('content-type'))
		{
			// Add the default Content-Type header if required
			$this->_header['content-type'] = Kohana::$content_type.'; charset='.Kohana::$charset;
		}

		// Set the content length
		$this->headers('content-length', (string) $this->content_length());

		// If Kohana expose, set the user-agent
		if (Kohana::$expose)
		{
			$this->headers('user-agent', 'Gleez CMS '.Gleez::VERSION.' ('.Gleez::CODENAME.')');
		}

		// Prepare cookies
		if ($this->_cookies)
		{
			if (extension_loaded('http'))
			{
				$this->_header['set-cookie'] = http_build_cookie($this->_cookies);
			}
			else
			{
				$cookies = array();

				// Parse each
				foreach ($this->_cookies as $key => $value)
				{
					$string = $key.'='.$value['value'].'; expires='.date('l, d M Y H:i:s T', $value['expiration']);
					$cookies[] = $string;
				}

				// Create the cookie string
				$this->_header['set-cookie'] = $cookies;
			}
		}

		$output = $this->_protocol.' '.$this->_status.' '.Response::$messages[$this->_status]."\r\n";
		$output .= (string) $this->_header;
		$output .= $this->_body;

		return $output;
	}

	/**
	 * Generate ETag
	 *
	 * Generates an ETag from the response ready to be returned
	 *
	 * @throws  Request_Exception
	 *
	 * @return  string  Generated ETag
	 */
	public function generate_etag()
	{
	    if ($this->_body === '')
		{
			throw new Request_Exception('No response yet associated with request - cannot auto generate resource ETag');
		}

		// Generate a unique hash for the response
		return '"'.sha1($this->render()).'"';
	}

	/**
	 * Check Cache
	 *
	 * Checks the browser cache to see the response needs to be returned
	 *
	 * @param   string   $etag     Resource ETag [Optional]
	 * @param   Request  $request  The request to test against [Optional]
	 *
	 * @return  Response
	 *
	 * @throws  Request_Exception
	 */
	public function check_cache($etag = NULL, Request $request = NULL)
	{
		if ( ! $etag)
		{
			$etag = $this->generate_etag();
		}

		if ( ! $request)
		{
			throw new Request_Exception('A Request object must be supplied with an etag for evaluation');
		}

		// Set the ETag header
		$this->_header['etag'] = $etag;

		// Add the Cache-Control header if it is not already set
		// This allows etags to be used with max-age, etc
		if ($this->_header->offsetExists('cache-control'))
		{
			if (is_array($this->_header['cache-control']))
			{
				// @todo
				$this->_header['cache-control'][] = new HTTP_Header_Value('must-revalidate');
			}
			else
			{
				$this->_header['cache-control'] = $this->_header['cache-control'].', must-revalidate';
			}
		}
		else
		{
			$this->_header['cache-control'] = 'must-revalidate';
		}

		if ($request->headers('if-none-match') AND (string) $request->headers('if-none-match') === $etag)
		{
			// No need to send data again
			$this->_status = 304;
			$this->send_headers();

			// Stop execution
			exit;
		}

		return $this;
	}

	/**
	 * Parse the byte ranges from the HTTP_RANGE header used for
	 * resumable downloads.
	 *
	 * @link   http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35
	 *
	 * @return mixed
	 */
	protected function _parse_byte_range()
	{
		if ( ! isset($_SERVER['HTTP_RANGE']))
		{
			return FALSE;
		}

		// TODO, speed this up with the use of string functions.
		preg_match_all('/(-?[0-9]++(?:-(?![0-9]++))?)(?:-?([0-9]++))?/', $_SERVER['HTTP_RANGE'], $matches, PREG_SET_ORDER);

		return $matches[0];
	}

	/**
	 * Calculates the byte range to use with send_file
	 *
	 * If HTTP_RANGE doesn't exist then the complete byte range is returned
	 *
	 * @param  integer $size
	 *
	 * @return array
	 */
	protected function _calculate_byte_range($size)
	{
		// Defaults to start with when the HTTP_RANGE header doesn't exist.
		$start = 0;
		$end = $size - 1;

		if ($range = $this->_parse_byte_range())
		{
			// We have a byte range from HTTP_RANGE
			$start = $range[1];

			if ($start[0] === '-')
			{
				// A negative value means we start from the end, so -500 would be the
				// last 500 bytes.
				$start = $size - abs($start);
			}

			if (isset($range[2]))
			{
				// Set the end range
				$end = $range[2];
			}
		}

		// Normalize values.
		$start = abs(intval($start));

		// Keep the the end value in bounds and normalize it.
		$end = min(abs(intval($end)), $size - 1);

		// Keep the start in bounds.
		$start = ($end < $start) ? 0 : max($start, 0);

		return array($start, $end);
	}
}
