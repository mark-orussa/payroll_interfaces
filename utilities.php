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
	$cookies = session_get_cookie_params();
	setcookie(session_name(), '', -1, $cookies['path'], $cookies['domain']);// time()-42000
	$Debug->add('The session has been destroyed.');
	$_SESSION['auth'] = false;
}

function intThis($value) {
	//Attempts to return an integer. The coersion method used here is faster than (int) or intval(), and produces more desireable outcomes when given non-numeric values.
	$temp = 0 + $value;
	$temp = (int)$temp;
	return $temp;
}