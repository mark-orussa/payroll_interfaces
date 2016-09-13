<?php

class CustomException extends Exception {
	/**
	 * Create custom exceptions.
	 *
	 * This will add messages to the global $Message variable and $Debug object.
	 *
	 * @author    includes O'Russa    <mark@orussa.com>
	 */

	// Allow for public and private messages.
	public function __construct($publicMessage = '', $privateMessage = '', $code = 0, Exception $previous = null) {
		/**
		 * CustomException constructor.
		 * @param string $publicMessage	The message visible to the end user.
		 * @param string $privateMessage	The message visible in debug information.
		 * @param int $code	This is passed to the parent.
		 * @param Exception|null $previous	Optionally throw a specific type of exception.
		 */
		global $Debug, $Message, $Success;
		$Success = false;
		if( empty($publicMessage) && strstr($Message, 'encountered a technical problem') === false ){
			$Message .= 'We\'ve encountered a technical problem that is preventing information from being shown. Please try again in a few moments.<br>
If the problem persists please contact support.<br>';
		}else{
			$Message .= $publicMessage . '<br>';
		}
		//Add the messages.
		$trace = parent::getTrace();
		$temp = '<div class="border red">
	Custom Exception<br>
';
		$traceArgs = '';
		if( is_array($trace) ){
			foreach( $trace as $traceKey => $traceValue ){
				$traceValue['class'] = empty($traceValue['class']) ? '' : $traceValue['class'];
				$temp .= '	<span class="bold">File:</span>' . $traceValue['file'] . ' line ' . $traceValue['line'] . '<br>
	<span class="bold">Called:</span>' . $traceValue['class'] . '->' . $traceValue['function'] . '(';
				$traceArgs = '';
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
							$traceArgs .= ', ' . $value;
						}
					}
				}
			}
		}
		$temp .= $traceArgs . ')<br>
	<span class="bold">Class File:</span>' . parent::getFile() . ' line ' . parent::getLine() . '<br>
	<span class="bold">Message:</span>' . $Message . '<br>
	<span class="bold">Private Message:</span>' . $privateMessage . '</span>
</div>';
		$Debug->add($temp);
		// make sure everything is assigned properly
		$code = (int)$code;
		parent::__construct($publicMessage, $code, $previous);
		return ($publicMessage);
//        return($publicMessage . $Debug->output(true));
	}
}
