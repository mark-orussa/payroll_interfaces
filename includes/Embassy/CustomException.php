<?php
namespace Embassy;

use Exception;

class CustomException extends Exception {

	private $Debug;
	private $Message;

	public function __construct($publicMessage = '', $debugMessage = '', $code = 0, Exception $previous = null) {
		/**
		 *CustomException constructor.
		 * This will add messages to the global $Message variable and $Debug object.
		 *
		 * @author    O'Russa    <mark@orussa.com>
		 * @param string $publicMessage    The message visible to the end user.
		 * @param string $debugMessage     The message visible in debug information.
		 * @param int $code                This is passed to the parent.
		 * @param Exception|null $previous Optionally throw a specific type of exception.
		 */

		global $Debug, $Message;
		$this->Debug = &$Debug;
		$this->Message = &$Message;
		$Debug->newFile('includes/Embassy/CustomException.php');

		self::setMessages($publicMessage,$debugMessage,$this->Message,$this->Debug);

		//Add the messages.
		$trace = parent::getTrace();
		$temp = '<div style="border: 2px dashed darkolivegreen;">
	<div style="style="font-weight: bold"></span> Custom Exception</div>
';
		$traceArgs = '';
		if( is_array($trace) ){
			foreach( $trace as $traceKey => $traceValue ){
				$traceValue['class'] = empty($traceValue['class']) ? '' : $traceValue['class'];
				$temp .= '	<span style="font-weight: bold">File:</span>' . $traceValue['file'] . ' line ' . $traceValue['line'] . '
	<div><span style="font-weight: bold">Function Called:</span>' . $traceValue['class'] . '->' . $traceValue['function'] . '(';
				if( isset($traceValue['args']) ){
					foreach( $traceValue['args'] as $key => $value ){
						if( empty($traceArgs) ){
							if( !is_object($value) ){
								if( is_array($value) ){
									$Debug->printArrayOutput($value);
								}else{
									$traceArgs .= $value;
								}
							}
						}else{
							if(is_array($value)){
								$traceArgs .= $Debug->printArrayOutput($value);
							}else{
								$traceArgs .= ', ' . $value;
							}
						}
					}
				}
				$temp .= ')</div>';
			}
		}
		$temp .= $traceArgs . '<br>
	<span style="font-weight: bold">Class File:</span>' . parent::getFile() . ' on line ' . parent::getLine() . '<br>
	<span style="font-weight: bold">Public Message:</span>' . $Message->toString() . '
	<span style="font-weight: bold">Private Message:</span>' . $debugMessage . '</span>
</div>';
		$Debug->add($temp);
		$code = (int)$code;
		parent::__construct($publicMessage, $code, $previous);
		return ($publicMessage);
	}

	public static function setMessages($publicMessage,$debugMessage,$Message,$Debug) {
		if( empty($publicMessage) && strstr($Message->__toString(), 'encountered a technical problem') === false ){
			$Message->add('We\'ve encountered a technical problem that is preventing information from being shown. Please try again in a few moments.<br>
If the problem persists please contact support.<br>');
		}else{
			$Message->add($publicMessage);
		}
		$Debug->add($debugMessage);
	}
}
