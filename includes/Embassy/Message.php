<?php

/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 9/21/2016
 * Time: 12:33 PM
 */
namespace Embassy;

class Message {
	/**
	 * This is for displaying feedback to the user through a special html section that can hover, cover, and hide.
	 * Errors, warnings, and other feedback should be output through this message class.
	 */

	private $message;
	private $accumulated;

	public function __construct() {
		$this->message = '';
		$this->accumulated = '';
	}

	public function add($message) {
		$this->message .= '<div>' . $message . '</div>';
	}

	public function __toString() {
		return $this->message;
	}

	public function accumulate($message) {
		// This prevents duplicate messages from building up.
		if( strpos($this->accumulated,$message ) === false ){
			$this->accumulated .= $message;
			$this->message .= $message;
		}
	}
}