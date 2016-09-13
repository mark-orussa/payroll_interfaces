<?php
/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 5/24/2016
 * Time: 2:30 PM
 */

function destroySession() {
	global $Debug;
	$_SESSION = array();
	@session_unset(); // Remove all session variables.
	@session_destroy();
	setcookie(session_name(), '', -1, COOKIEPATH, COOKIEDOMAIN);// time()-42000
	$Debug->add('The session has been destroyed.');
	$_SESSION['auth'] = false;
}

function intThis($value) {
	//Attempts to return an integer. The coersion method used here is faster than (int) or intval(), and produces more desireable outcomes when given non-numeric values.
	$temp = 0 + $value;
	$temp = (int)$temp;
	return $temp;
}

function returnData($mode) {
	//Create JSON syntax information to send back to the browser.
	if( MODE == $mode ){
		global $Debug, $Message, $Success, $ReturnThis;
		$Success = empty($Success) ? false : $Success;
		$Message = empty($Message) ? '' : $Message;//<span style="display:none">No message.</span>
		if( is_array($ReturnThis) ){
			$jsonArray = array('debug' => $Debug->output(), 'message' => $Message, 'success' => $Success);
			foreach( $ReturnThis as $key => $value ){
				$jsonArray[$key] = $value;
			}
		}else{
			$jsonArray = array('debug' => $Debug->output(), 'message' => $Message, 'success' => $Success);
		}

		/*$output = "{debug:'" . charConvert($Debug->output()) . "', message:'" . charConvert($Message) . "', success: '" . $Success . "'";
		if(!empty($ReturnThis)){
			foreach($ReturnThis as $key => $value){
				 $output .= "," . charConvert($key) . ":'" . charConvert($value) . "'";
				$Debug->add('$key: ' . $key . ', $value: ' . $value);
			}
		}
		$output .= '}';
		//echo "{ ReturnThis:'hi' }";
		die($output);
		*/
		$output = json_encode($jsonArray, JSON_HEX_APOS | JSON_HEX_QUOT);
		//$Debug->add(json_decode($test));
		die($output);
	}
}
