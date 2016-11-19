<?php
namespace Embassy;
use PDOException;

class CustomPDOException extends PDOException{
	/**
	 * Create custom PDO exceptions.
	 *
	 * This will add messages to the public $Message variable and the private $Debug.
	 *
	 * @author	Mark O'Russa	<mark@markproaudio.com>
	*/
	
	//Properties.
	
	// Allow for public and private messages.
	public function __construct($publicMessage = '', $privateMessage = '', $code = 0, Exception $previous = null) {
		global $Debug, $Message, $returnThis;
        //Add the messages.
		$trace = parent::getTrace();
		$temp = '<div class="border red red">
	Custom Exception<br>
';
		if(is_array($trace)){
			foreach($trace as $traceKey => $traceValue){
				$temp .= '	<span class="bold">File:</span>' . $traceValue['file'] . ' line ' . $traceValue['line'] . '<br>
	<span class="bold">Called:</span>' . $traceValue['class'] . '->' . $traceValue['function'] . '(';
				$traceArgs = '';
				foreach($traceValue['args'] as $key => $value){
					if(empty($traceArgs)){
						$traceArgs .= $value;
					}else{
						$traceArgs .= ', ' . $value;
					}
				}
			}
		}
		$temp .= $traceArgs . ')<br>
	<span class="bold">Class File:</span>' . parent::getFile() . ' line ' . parent::getLine() . '<br>
	<span class="bold">Message:</span>' . $privateMessage . '</span>
</div>
yellowbelly';
		$Debug->add($temp);

        // make sure everything is assigned properly
        parent::__construct();
		return($publicMessage);
    }


    // custom string representation of object
    /*public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }*/
}
