<?php

class Page {
	/**
	 * Build the page content.
	 *
	 * Build the body, javascript includes, meta, css includes, and other foundational html content.
	 *
	 * @author    Mark O'Russa    <mark@orussa.com>
	 * @param    array $_javascriptIncludes The userId of the sender.
	 * @param    array $_cssIncludes        The userId of the recipient.
	 *
	 */

	//Properties.
	protected $_Dbc;
	protected $_Debug;
	protected $_Message;
	protected $_ReturnThis;
	protected $_Success;

	protected $_javascriptIncludes;
	protected $_cssIncludes;
	protected $_body;
	protected $_title;
	protected $_filename;
	protected $_requireAuth;

	public function __construct($title = '', $filename = '') {
		global $Dbc, $Debug, $Message, $ReturnThis, $Success;
		$this->_Dbc = &$Dbc;
		$this->_Debug = &$Debug;
		$this->_Message = &$Message;
		$this->_ReturnThis = &$ReturnThis;
		$this->_Success = &$Success;

		$this->_body = NULL;
		$this->_javascriptIncludes = NULL;
		$this->_cssIncludes = NULL;
		$this->_title = $title;
		$this->_filename = $filename;
		$this->_requireAuth = false;
		$this->_Debug->newFile($filename);

		if( MODE == 'buildLogin' ){
			self::buildLogin();
		}elseif( MODE == 'buildLoginButton' ){
			self::buildLoginButton();
		}elseif( MODE == 'login' ){
			self::login();
		}elseif( MODE == 'logout' ){
			self::logout();
		}
	}

	public function addBody($content) {
		$this->_body .= $content;
	}

	public function addIncludes($fileName, $throwError = '') {
		/**
		 * Include php files.
		 *
		 * Accepts either a single file or an array of file names in filename.extension format.
		 *
		 * @author      Mark O'Russa    <mark@orussa.com>
		 * @fileName    array|string    the file name(s) with the file extension(s). This script assumes the file is in the includes folder.
		 *
		 */
		if( !empty($throwError) ){
			throw new CustomException('You are trying to include more than one file, but you haven\'t put it in an array.');
		}
		if( is_array($fileName) ){
			foreach( $fileName as $key ){
				require_once($key);
			}
		}else{
			require_once($fileName);
		}
	}

	public function addJs($fileName) {
		/**
		 * Include javascript files.
		 *
		 * Accepts either a single file or an array of file names in filename.extension format.
		 *
		 * @author      Mark O'Russa    <mark@orussa.com>
		 * @fileName    array|string    the file name(s) with the file extension(s). If the file is relative or lacking a FQDN an autolink will be created.
		 *
		 * @return    string    Valid html javascript include(s).
		 */
		if( is_array($fileName) ){
			foreach( $fileName as $key ){
				if( stripos($key, 'http://') === false && stripos($key, 'https://') === false ){
					$this->_javascriptIncludes .= '<script type="text/javascript" src="' . LINKJS . '/' . $key . '?' . date('H') . '"></script>
';
				}else{
					$this->_javascriptIncludes .= '<script type="text/javascript" src="' . $key . '?' . date('H') . '"></script>
';
				}
			}
		}else{
			if( stripos($fileName, 'http://') === false && stripos($fileName, 'https://') === false ){
				$this->_javascriptIncludes .= '<script type="text/javascript" src="' . LINKJS . '/' . $fileName . '?' . date('H') . '"></script>
';
			}else{
				$this->_javascriptIncludes .= '<script type="text/javascript" src="' . $fileName . '?' . date('H') . '"></script>
';
			}
		}
	}

	public function addCss($fileName) {
		/**
		 * Include css files.
		 *
		 * Accepts either a single file or an array of file names in filename.extension format.
		 *
		 * @author      Mark O'Russa    <mark@orussa.com>
		 * @fileName    array|string    the file name(s) with the file extension(s). If the file is relative or lacking a FQDN an autolink will be created.
		 *
		 * @return    string    Valid html javascript include(s).
		 */
		if( is_array($fileName) ){
			foreach( $fileName as $key ){
				if( stripos($key, 'http://') === false && stripos($key, 'https://') === false ){
					$this->_cssIncludes .= '<link rel="stylesheet" href="' . LINKCSS . '/' . $key . '?' . date('H') . '" type="text/css" media="all">
';
				}else{
					$this->_cssIncludes .= '<link rel="stylesheet" href="' . $key . '?' . date('H') . '" type="text/css" media="all">
';
				}
			}
		}else{
			if( stripos($fileName, 'http://') === false && stripos($fileName, 'https://') === false ){
				$this->_cssIncludes .= '<link rel="stylesheet" href="' . LINKCSS . '/' . $fileName . '?' . date('H') . '" type="text/css" media="all">
';
			}else{
				$this->_cssIncludes .= '<link rel="stylesheet" href="' . $fileName . '?' . date('H') . '" type="text/css" media="all">
';
			}
		}
	}

	public function buildLoginButton() {
		$output = '';
		if(isset($_SESSION['auth']) === true && $_SESSION['auth'] === true){
			$output = '<span class="auth"><i class="fa fa-sign-out"></i>Logout</span>';
		}else{
			$output .= '<span class="auth"><i class="fa fa-sign-in"></i> Login</span>';
		}
		if( MODE == 'buildLoginButton' ){
			$this->_ReturnThis['buildLoginButton'] = $output;
			returnData('buildLoginButton');
		}else{
			return $output;
		}
	}

	public function buildLogin() {
		$output = '<label for="password">Password: </label> <input name="password" type="password">
<div>
	<span class="makeButton" id="loginSubmit">Submit</span>
	<div id="loginError" class="red"></div>
</div>';
		$this->_ReturnThis['buildLogin'] = $output;
		$this->_Success = true;
		returnData('buildLogin');
	}

	public function getTitle() {
		return $this->_title;
	}

	public function login() {
		try{
			if( !isset($_POST['password']) ){
				throw new CustomException('', '$_POST[\'password\'] is not set.');
			}
			if( $_POST['password'] == '1234' ){
				$_SESSION['auth'] = true;
				$this->_Success = true;
				$this->_ReturnThis['buildLoginButton'] = self::buildLoginButton();
			}elseif($_POST['password'] == '1394'){
				$_SESSION['auth'] = true;
				$_SESSION['admin'] = true;
				$this->_Success = true;
				$this->_ReturnThis['buildLoginButton'] = self::buildLoginButton();
			}else{
				$this->_ReturnThis['message'] = 'Invalid password.';
			}
		}catch( CustomException $e ){
			returnData('login');
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			returnData('login');
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			returnData('login');
		}
		returnData('login');
	}

	public function logout() {
		try{
			destroySession();
			$this->_Success = true;
//			$this->_ReturnThis['buildLoginButton'] = self::buildLoginButton();
		}catch( CustomException $e ){
			returnData('logout');
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			returnData('logout');
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			returnData('logout');
		}
		returnData('logout');
	}

	public function output($defaultIncludes = true) {
		$output = '';
		$head = '<!DOCTYPE HTML>
<html lang="en" xml:lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
';
		if( $this->_title != '' ){
			$head .= '<title>' . THENAMEOFTHESITE . ' - ' . $this->_title . '</title>
';
		}else{
			$head .= '<title>' . THENAMEOFTHESITE . '</title>
';
			$this->_Debug->add('The $title array for this page was not found.<br>');
		}

		// CSS files.
		$head .= empty($this->_cssIncludes) ? '' : $this->_cssIncludes;
		$head .= '<link rel="stylesheet" href="' . LINKCSS . '/main.css?' . date('j') . '" media="all" type="text/css">
		<link rel="stylesheet" href="' . LINKCSS . '/font-awesome-4.6.3/css/font-awesome.min.css" type="text/css">';

		// Javascript files
		$head .= '<script type="text/javascript" src="' . LINKJS . '/jquery/jquery-1.12.3.min.js"></script>
        ';//<script src="https://use.fonticons.com/f71366fc.js"></script>
		if( $defaultIncludes ){
			$head .= '<script type="text/javascript" src="' . LINKJS . '/functions.js?' . date('j') . '"></script>
';
		}
		$head .= empty($this->_javascriptIncludes) ? '' : $this->_javascriptIncludes . '</head>';
		//Build the output. Spinners and floaters are for AJAX operations.
		$output .= $head . '<body>
	<div id="cover"></div>
	<div id="spinner">
	<a data-ajax="false" href="' . AUTOLINK . '/' . $_SERVER['PHP_SELF'] . '"><img alt="" class="absolute" src="' . LINKIMAGES . '/spinner.png" style=""><p>Refresh</p></a>
	</div>
	<div class="red textCenter">
		<noscript>(javascript required)</noscript>
	</div>
	<div id="floater" class="floater"></div>
	<div id="message">';
		$output .= empty($this->_Message) ? '' : $this->_Message;
		$output .= '</div>';

		// Auth section and content
		if( $this->_requireAuth === true ){
			$output .= self::buildLoginButton();
			if( isset($_SESSION['auth']) && $_SESSION['auth'] === true ){
				$output .= $this->_body . '<div class="toggleButtonInline">Show Debug Information</div>
	<div class="toggleMe">
		' . $this->_Debug->output() . '
	</div>';
			}else{
				$output .= '<div style="text-align: center">Please login.</div>';
			}
		}else{
			$output .= $this->_body;
		}
		$output .= '
					</body >
</html > ';
		return $output;
	}

	public function setRequireAuth($state) {
		$this->_requireAuth = $state === true ? true : false;
	}
}