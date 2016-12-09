<?php
/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 11/18/2016
 * Time: 5:02 PM
 */
namespace Embassy;

use ErrorException, Exception;

class Auth {
	//Properties.
	protected $Ajax;
	protected $Dbc;
	protected $Debug;
	protected $Message;
	private $siteKey;
	private $secret;

	public function __construct($Ajax, $Debug, $Dbc, $Message, $Secret, $Config) {
		$this->Ajax = &$Ajax;
		$this->Dbc = &$Dbc;
		$this->Debug = &$Debug;
		$this->Message = &$Message;

		$this->siteKey = '6LcCPgwUAAAAAIFRz9cJwRYtk7clMYiMODfCdGF2';
//		$this->secret = $Secret->decrypt($Config->getGoogleCaptchaSecret());/ No longer using Defuse.
		$this->secret = $Config->getGoogleCaptchaSecret();

		if( MODE == 'login' ){
			self::login();
		}elseif( MODE == 'logout' ){
			self::logout();
		}
	}

	public function buildLogin() {
		// Add the g-recaptcha tag to the form you want to include the reCAPTCHA element
		$form = new Form($this->Debug, 'login', false);
		$output = $form->open() . '
	<fieldset id="login">';
		$output .= $form->input('hidden', 'mode', array('value' => 'login'));
		$output .= $form->input('password', 'password', array('label'));
		$output .= '<div id = "loginError" class="red" ></div >
		<p > Check the box below to prove you\'re human.</p>
		<div class="g-recaptcha" data-sitekey="' . $this->siteKey . '"></div>
		<p><input type="submit" value="Submit" /></p>		
	</fieldset>
';
		//<p><input type="submit" value="Submit" /></p>
		//</form>
		// <span class="makeButton" id="loginSubmit">Submit</span>
		return $output;
	}

	public function buildLogout() {
		if( $this->isAuth() ){
			return '<div id="logoutContainer"><span id="logout" class="makeButtonInline"><i class="fa fa-sign-out"></i>Logout</span></div>';
		}
	}

	public function isAuth() {
		if( isset($_SESSION['auth']) && $_SESSION['auth'] === true ){
			return true;
		}elseif( stripos($_SERVER['SCRIPT_FILENAME'], 'login') === false ){
			// This redirects to the login page when a user is not authenticated.
//			die('Redirecting in file ' . __FILE__ . ' line ' . __LINE__);
			header('Location:' . LINKLOGIN);
		}
	}

	public function login() {
		$this->Debug->add('Inside Auth::login().');
		try{
			$output = '';
			// Verify the CSRF code.
			if( Form::verifyCsrf($_POST['login']['CSRF']) === false ){
				throw new CustomException('', 'Could not verify the CSRF code.');
			}
			if( $this->siteKey === '' || $this->secret === '' ){
				throw new CustomException('', 'Could not find the Google captcha items.');
			}
			if( isset($_POST['g-recaptcha-response']) ){
				$this->Debug->add('Inside the login method. $_POST[\'g-recaptcha-response\'] is set.');
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
					$this->Debug->add('Google Captcha response was good.');
					if( !isset($_POST['login']['password']) ){
						$this->Debug->add('$_POST[\'login\'][\'password\'] is not set.');
						throw new CustomException('', '$_POST[\'login\'][\'password\'] is not set.');
					}
					if( $_POST['login']['password'] == '1234' ){
						$_SESSION['auth'] = true;
						// Redirect to root.
						$this->Debug->add('We successfully logged in as regular user. Trying to redirect to home.');
//						die('Redirecting in file ' . __FILE__ . ' line ' . __LINE__);
						header('Location: ' . AUTOLINK);
					}elseif( $_POST['login']['password'] == '1394' ){
						$_SESSION['auth'] = true;
						$_SESSION['admin'] = true;
						// Redirect to root.
						$this->Debug->add('We successfully logged in as admin. Trying to redirect to home.');
//						die('Redirecting in file ' . __FILE__ . ' line ' . __LINE__);
						header('Location: ' . AUTOLINK);
					}else{
						// The password is invalid, so show the login form again.
						$this->Debug->add('Invalid password.');
						$this->Message->add('Invalid password.');
						$output .= self::buildLogin();
					}
				}else{
					$this->Debug->add('Google Captcha response was not good.');
					//$_POST['g-recaptcha-response'] = '';
					// If it's not successful, then one or more error codes will be returned.
					foreach( $resp->getErrorCodes() as $code ){
						$this->Debug->add($code);
					}
					$this->Debug->add('<p>Check the error code reference at <tt><a href="https://developers.google.com/recaptcha/docs/verify#error-code-reference">https://developers.google.com/recaptcha/docs/verify#error-code-reference</a></tt>.
	<p><strong>Note:</strong> Error code <tt>missing-input-response</tt> may mean the user just didn\'t complete the reCAPTCHA.</p>
	<p><a href="/">Try again</a></p>');
					$this->Message->add('Please check the "I\'m not a robot" checkbox in the reCAPTCHA box.');
					$output .= self::buildLogin();
				}
			}else{
				$this->Debug->add('Inside the buildLogin method. $_POST[\'g-recaptcha-response\'] is not set.');
				$this->Debug->add('Google Captcha response is not set.');
				$this->Message->add('Google Captcha was bad.');
			}

		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
//			$this->Ajax->ReturnData();
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
//			$this->Ajax->ReturnData();
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
//			$this->Ajax->ReturnData();
		}finally{
		}
//		$this->Ajax->ReturnData();
		return $output;
	}

	public function logout() {
		try{
			destroySession();
			$this->Ajax->SetSuccess(true);
//			$this->Ajax->AddValue(array('buildLoginButton'] = self::buildLoginButton();
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();
		}
		$this->Ajax->ReturnData();
	}
}