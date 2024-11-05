<?php

namespace VnBiz;

use Exception, Throwable;

class VnBizError extends Exception
{
	private $status = null;
	private $error_fields = null;
	private $http_status;

	// Redefine the exception so message isn't optional
	public function __construct($message, $status = 'unknown', $error_fields = null, Throwable $previous = null, $http_status = null)
	{
		// make sure everything is assigned properly
		parent::__construct($message, 1, $previous);

		$this->status = $status;
		$this->error_fields = $error_fields;
		$this->http_status = $http_status;
		if ($http_status === null) {
			$this->http_status = 400;
			if ($status === 'permission') {
				$this->http_status = 403;
			} 
		} 
	}

	// custom string representation of object
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

	public function get_status()
	{
		return $this->status;
	}
	public function get_error_fields()
	{
		return $this->error_fields;
	}
	public function http_status()
	{
		return $this->http_status;
	}
}