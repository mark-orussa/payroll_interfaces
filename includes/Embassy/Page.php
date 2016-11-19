<?php
namespace Embassy;

use Exception, ErrorException;

class Page {
	/**
	 * Build the page content.
	 *
	 * Build the body, javascript includes, meta, css includes, and other foundational html content.
	 *
	 * @author    Mark O'Russa    <mark@orussa.com>
	 * @param    array $javascriptIncludes The userId of the sender.
	 * @param    array $cssIncludes        The userId of the recipient.
	 *
	 */

	//Properties.
	protected $Dbc;
	protected $Debug;
	protected $Message;
	protected $ReturnThis;
	protected $Success;

	protected $javascriptIncludes;
	protected $cssIncludes;
	protected $body;
	protected $title;
	protected $filename;
	protected $requireAuth;

	public function __construct($Debug, $Dbc, $Message, $ReturnThis, $Success) {
		$this->Dbc = &$Dbc;
		$this->Debug = &$Debug;
		$this->Message = &$Message;
		$this->ReturnThis = &$ReturnThis;
		$this->Success = &$Success;

		$this->body = NULL;
		$this->javascriptIncludes = NULL;
		$this->cssIncludes = NULL;
		$this->title = '';
		$this->filename = '';
		$this->requireAuth = false;
		if( !$_SESSION['auth'] && stripos($_SERVER['PHP_SELF'], 'login') === false ){
			header('Location:' . LINKLOGIN);
		}
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
		$this->body .= $content;
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
					$this->javascriptIncludes .= '<script type="text/javascript" src="' . LINKJS . '/' . $key . '?' . date('H') . '"></script>
';
				}else{
					$this->javascriptIncludes .= '<script type="text/javascript" src="' . $key . '?' . date('H') . '"></script>
';
				}
			}
		}else{
			if( stripos($fileName, 'http://') === false && stripos($fileName, 'https://') === false ){
				$this->javascriptIncludes .= '<script type="text/javascript" src="' . LINKJS . '/' . $fileName . '?' . date('H') . '"></script>
';
			}else{
				$this->javascriptIncludes .= '<script type="text/javascript" src="' . $fileName . '?' . date('H') . '"></script>
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
					$this->cssIncludes .= '<link rel="stylesheet" href="' . LINKCSS . '/' . $key . '?' . date('H') . '" type="text/css" media="all">
';
				}else{
					$this->cssIncludes .= '<link rel="stylesheet" href="' . $key . '?' . date('H') . '" type="text/css" media="all">
';
				}
			}
		}else{
			if( stripos($fileName, 'http://') === false && stripos($fileName, 'https://') === false ){
				$this->cssIncludes .= '<link rel="stylesheet" href="' . LINKCSS . '/' . $fileName . '?' . date('H') . '" type="text/css" media="all">
';
			}else{
				$this->cssIncludes .= '<link rel="stylesheet" href="' . $fileName . '?' . date('H') . '" type="text/css" media="all">
';
			}
		}
	}

	public function buildLoginButton() {
		$output = '';
		if( isset($_SESSION['auth']) === true && $_SESSION['auth'] === true ){
			$output = '<span class="auth"><i class="fa fa-sign-out"></i>Logout</span>';
		}else{
			$output .= '<span class="auth"><i class="fa fa-sign-in"></i> Login</span>';
		}
		if( MODE == 'buildLoginButton' ){
			$this->ReturnThis['buildLoginButton'] = $output;
			returnData('buildLoginButton');
		}else{
			return $output;
		}
	}

	public function getTitle() {
		return $this->title;
	}

	public function toString($defaultIncludes = true) {
		$output = '';
		$head = '<!DOCTYPE HTML>
<html lang="en" xml:lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
';
		if( $this->title != '' ){
			$head .= '<title>' . THENAMEOFTHESITE . ' - ' . $this->title . '</title>
';
		}else{
			$head .= '<title>' . THENAMEOFTHESITE . '</title>
';
			$this->Debug->add('The $title array for this page was not found.<br>');
		}

		// CSS files.
		$head .= empty($this->cssIncludes) ? '' : $this->cssIncludes;
		$head .= '<link rel="stylesheet" href="' . LINKCSS . '/main.css?' . date('j') . '" media="all" type="text/css">
		<link rel="stylesheet" href="' . LINKCSS . '/font-awesome-4.6.3/css/font-awesome.min.css" type="text/css">';

		// Javascript files
		$head .= '<script type="text/javascript" src="' . LINKJS . '/jquery/jquery-1.12.3.min.js"></script>
		<script src=\'https://www.google.com/recaptcha/api.js\'></script>
        ';//<script src="https://use.fonticons.com/f71366fc.js"></script>
		if( $defaultIncludes ){
			$head .= '<script type="text/javascript" src="' . LINKJS . '/functions.js?' . date('j') . '"></script>
';
		}
		$head .= empty($this->javascriptIncludes) ? '' : $this->javascriptIncludes . '</head>';
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
		$output .= empty($this->Message) ? '' : $this->Message;
		$output .= '</div>';

		// Auth section and content
		if( $this->requireAuth === true ){
			if( isset($_SESSION['auth']) && $_SESSION['auth'] === true ){
				$output .= $this->body . '<div class="toggleButtonInline">Show Debug Information</div>
	<div class="toggleMe">
		' . $this->Debug->output() . '
	</div>';
			}else{
				$output .= self::buildLogin() . '<div class="toggleButtonInline">Show Debug Information</div>
	<div class="toggleMe">
				' . $this->Debug->output() . '
	</div>';
			}
		}else{
			$output .= $this->body;
		}
		$output .= '
					</body >
</html > ';
		return $output;
	}

	public function setTitleAndFilename($title, $filename) {
		/**
		 * @param string $title    The title of the page as it will appear in the html title tag.
		 * @param string $filename The name of the file for debugging purposes.
		 */
		$this->title = $title;
		$this->filename = $filename;
		$this->Debug->newFile($filename);
	}

	public function setTitle($title) {
		/**
		 * @param string $title The title of the page as it will appear in the html title tag.
		 */
		$this->title = $title;
	}

	public function setFilename($filename) {
		/**
		 * @param string $filename The name of the file for debugging purposes.
		 */
		$this->filename = $filename;
	}
}