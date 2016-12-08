<?php
/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 9/26/2016
 * Time: 3:46 PM
 */

namespace Embassy;

class Ajax {

	private $Debug;
	private $Message;
	private $reference;
	private $returnThis;
	private $success;

	public function __construct($Debug, $Message) {
		$this->Debug = &$Debug;
		$this->Message = &$Message;

		$this->reference = '';
		$this->returnThis = array();
		$this->success = false;
		$Debug->newFile('includes/Embassy/Ajax.php');
	}

	public function AddValue($array) {
		/**
		 * @param $array An array of key => value pairs that will be returned as JSON key => value pairs.
		 */
		foreach( $array as $key => $value ){
			$this->returnThis[$key] = $value;
		}
	}

	public function SetReference($reference) {
		$this->reference = $reference;
	}

	public function GetReference() {
		return $this->reference;
	}

	public function ReturnData() {
		/**
		 * Create JSON syntax information to send back to the browser. This is the final step in the ajax process.
		 * Any information we want to send back to the client goes through this method. The values are passed in JSON format.
		 *
		 */
		$jsonArray = array('message' => $this->Message->__toString(), 'success' => $this->success, 'reference' => $this->reference);
		if( is_array($this->returnThis) ){
			foreach( $this->returnThis as $key => $value ){
				$jsonArray[$key] = $value;
			}
		}
		//$Debug->add(json_decode($test));
		$this->Debug->writeToLog();// Since we die in the next line we need to write our debug info now or forever hold our peace.
		die(json_encode($jsonArray, JSON_HEX_APOS | JSON_HEX_QUOT));
	}

	public function SetSuccess($state) {
		if( is_bool($state) ){
			$this->success = $state;
		}
	}
}