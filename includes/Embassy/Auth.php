<?php
/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 11/18/2016
 * Time: 5:02 PM
 */

namespace Embassy;

class Auth {
	//Properties.
	protected $Dbc;
	protected $Debug;
	protected $Message;
	private $siteKey;
	private $secret;

	public function __construct($Debug, $Dbc, $Message) {
		$this->Dbc = &$Dbc;
		$this->Debug = &$Debug;
		$this->Message = &$Message;

		$this->siteKey = '6LcCPgwUAAAAAIFRz9cJwRYtk7clMYiMODfCdGF2';
		$this->secret = '6LcCPgwUAAAAAEz8o4NPGp8efFMXIyvM5eMPHINh';
		if( $this->siteKey === '' || $this->secret === '' ){
			// bad
		}elseif( isset($_POST['g-recaptcha-response']) ){
			self::login();
		}else{
			self::buildLogin();
		}
	}

	public function buildLogin() {
		// Add the g-recaptcha tag to the form you want to include the reCAPTCHA element
		$output = '<form action="/" method="post">
	<fieldset id="login">
		<label for="password">Password: </label> <input name="password" type="password">
		<div id="loginError" class="red"></div>
		<p>Check the box below to prove you\'re human.</p>
		<div class="g-recaptcha" data-sitekey="' . $this->siteKey . '"></div>
		<p><input type="submit" value="Submit" /></p>
	</fieldset>
</form>';
		// <span class="makeButton" id="loginSubmit">Submit</span>
		return $output;
	}

	public function login() {
		try{
			$output = '';
			// If the form submission includes the "g-captcha-response" field
			// Create an instance of the service using your secret
			$recaptcha = new \ReCaptcha\ReCaptcha($this->secret);
			// If file_get_contents() is locked down on your PHP installation to disallow
			// its use with URLs, then you can use the alternative request method instead.
			// This makes use of fsockopen() instead.
			// $recaptcha = new \ReCaptcha\ReCaptcha($secret, new \ReCaptcha\RequestMethod\SocketPost());
			// Make the call to verify the response and also pass the user's IP address
			$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
			if( $resp->isSuccess() ){
				// If the response is a success, that's it!
				header('Location: ' . $_SERVER['PHP_SELF']);
			}else{
				// If it's not successful, then one or more error codes will be returned.
				foreach( $resp->getErrorCodes() as $code ){
					$output .= $code;
				}
				$output .= '<p>Check the error code reference at <tt><a href="https://developers.google.com/recaptcha/docs/verify#error-code-reference">https://developers.google.com/recaptcha/docs/verify#error-code-reference</a></tt>.
	<p><strong>Note:</strong> Error code <tt>missing-input-response</tt> may mean the user just didn\'t complete the reCAPTCHA.</p>
	<p><a href="/">Try again</a></p>';
			}

			if( !isset($_POST['password']) ){
				throw new CustomException('', '$_POST[\'password\'] is not set.');
			}
			if( $_POST['password'] == '1234' ){
				$_SESSION['auth'] = true;
				// Redirect to root.
				header('Location: ' . AUTOLINK);
			}elseif( $_POST['password'] == '1394' ){
				$_SESSION['auth'] = true;
				$_SESSION['admin'] = true;
				// Redirect to root.
				header('Location: ' . AUTOLINK);
			}else{
				$this->Message = 'Invalid password.';
			}
		}catch( CustomException $e ){
			returnData('login');
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			returnData('login');
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			returnData('login');
		}
		returnData('login');
	}

	public function logout() {
		try{
			destroySession();
			$this->Success = true;
//			$this->ReturnThis['buildLoginButton'] = self::buildLoginButton();
		}catch( CustomException $e ){
			returnData('logout');
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			returnData('logout');
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			returnData('logout');
		}
		returnData('logout');
	}
}