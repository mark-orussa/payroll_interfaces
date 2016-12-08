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
		$output = '';
		if(!empty($this->message)){
			$output .= '<div id="message"><div class="generalCancel"><i class="fa fa-close"></i> Close</div>' . $this->message . '</div>';
		}else{
			$output .= '<div id="message" style="display:none;"></div>';
		}
		return $output;
	}

	public function accumulate($message) {
		// This prevents duplicate messages from building up.
		if( strpos($this->accumulated,$message ) === false ){
			$this->accumulated .= $message;
			$this->message .= $message;
		}
	}
}