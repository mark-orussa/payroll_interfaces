<?php
/**
 * Important stuff is defined here. This file must be placed at the beginning of every php file for proper operation.
 * The operation of this page relies on the .htaccess file which holds important information.
 * The production server must allow .htaccess files.
 */
//ini_set("session.save_handler", "files");
$fileInfo = array('fileName' => 'config.php');
if( isset($_REQUEST['message']) ){
	$Message = $_REQUEST['message'];// == 'Please Login' ? 'Please Login' : $_REQUEST['message'];
}else{
	$Message = '';
}
/*
* When trying to make a site work on a production server and local server without having to edit code, there
* are many things to consider. The production server will surely have a different directory structure than the remote.
* For example, for ServInt the publicly accessible directory would be: /home/name of the account/public_html
* A web browser sees things differently. It expects http://domain name/folder or filename.
*/
define('DOMAIN', 'pi.embassyllc.com', true);//The base domain of the website, without the http://. No trailing slash.
define('LOCALDOMAIN', 'pi.embassyllc.dev');//The base domain of the local website, without the http://. No trailing slash. If your site is accessed by using http://localhost, then enter localhost here. The remainder of the domain will be entered below in LOCALHOMEDIRECTORY.

//The home directory is the base home directory following the domain where the public facing site is found. Begin with a /
define('PRODUCTIONHOMEDIRECTORY', '', true);// Starts with a forward slash /, otherwise empty.
define('LOCALHOMEDIRECTORY', '', true);// Starts with a forward slash /

//Begin with a / and follow with the entire path from the root of the server. For ServInt this would be /home/name_of_the_account/includes
define('PRODUCTIONINCLUDEDIRECTORY', '/var/www/html/includes', true);// Use environment variables
define('LOCALINCLUDEDIRECTORY', '/xampp/htdocs/pi.embassyllc.dev/includes', true);// Use environment variables

define('LOCALIP', '192.168.11.27', true);//When using virtual machine software enter the ip address of the local machine.
define('THENAMEOFTHESITE', 'pi.embassyllc.com', true);//This is shown at the top of each page. If your address is www.mysite.com, name it My Site.
define('READABLEDOMAIN', 'pi.embassyllc.com', true);
define('REMEMBERME', 'piRememberMe', true);//The cookie used to autofill the user's email address at the login page.
define('UNIQUECOOKIE', 'piUniqueId');//The cookie used to autofill the user's email address at the login page.
$rdbHost = '127.0.0.1';//The hostname or IP address for the remote database. Get this information from your web hosting company. Example: mysql5.yourhostingcompany.com for shared hosting, 'localhost' for dedicated hosting.
$remoteDbPort = $_SERVER['PRODUCTION_PAYROLL_INTERFACES_PORT'];//The remote database port number.
$remoteDbName = $_SERVER['PRODUCTION_PAYROLL_INTERFACES_DATABASE'];//The name of the remote database. Do not enter the credentials here. Use environment variables.
$remoteDbUser = $_SERVER['PRODUCTION_PAYROLL_INTERFACES_DATABASE_USER'];//The username for the remote database. Do not enter the credentials here. Use environment variables.
$remoteDbPassword = $_SERVER['PRODUCTION_PAYROLL_INTERFACES_DATABASE_PASSWORD'];//The password for the remote database. Do not enter the credentials here. Use environment variables.
$ldbHost = 'localhost';//The hostname or IP address for the local database hostname, usually localhost.
$ldbPort = $_SERVER['DEVELOPMENT_PAYROLL_INTERFACES_PORT'];//The local database port number.
$ldbName = $_SERVER['DEVELOPMENT_PAYROLL_INTERFACES_DATABASE'];//The name of the DEVELOPMENT database. Do not enter the credentials here. Use environment variables.
$ldbUser = $_SERVER['DEVELOPMENT_PAYROLL_INTERFACES_DATABASE_USER'];//The username for the development database. Often this is root. Do not enter the credentials here. Use environment variables.
$ldbPass = $_SERVER['DEVELOPMENT_PAYROLL_INTERFACES_DATABASE_PASSWORD'];//The password for the local database. Often this is root. Do not enter the credentials here. Use environment variables.
$errorDbHost = 'localhost';//The error reporting database.
$errorDbName = 'database_name';
$errorDbPort = '3306';
$errorDbUser = 'username';
$errorDbPass = 'password';
$errorDbHostLocal = 'localhost';//The error reporting database for local connections.
$errorDbNameLocal = 'database';
$errorDbPortLocal = 3306;
$errorDbUserLocal = 'root';
$errorDbPassLocal = 'root';
define('EMAILDONOTREPLY', 'donotreply@' . DOMAIN);
define('EMAILSUPPORT', 'support@' . DOMAIN);

/*define('GOOGLEANALYTICS',$_SERVER['GOOGLEANALYTICS'],1);//The unique Google Analytics Web Property Id. Format: UA-XXXXX-X.
define('RECAPTCHAPRIVATEKEY', $_SERVER['RECAPTCHAPRIVATEKEY']);*/

class EmbassyAutoloader {
	public static function autoload($className) {
		global $Debug;
		$includePath = 'Classes/' . str_replace('_', '/', $className) . '.php';
		if( is_readable(__DIR__ . '/' . $includePath) ){
			require $includePath;
		}else{
			die('Could not include: ' . get_include_path() . $includePath);
		}
	}
}

spl_autoload_register(null, false);
spl_autoload_register('EmbassyAutoloader::autoload');

if( empty($Debug) ){
	$Debug = new Debug();
}
$Debug->newFile($fileInfo['fileName']);

/*
The settings below here generally do not need to be changed.

Define HTTPS and redirect to http or https.
To force an https connection add define('FORCEHTTPS',true); at the top of the page before including this file. Conversely, add define('FORCEHTTPS',false); to force an http connection.
*/
if( !defined('FORCEHTTPS') ){
	define('FORCEHTTPS', false, true);
}
if( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ){
	//Using https:. This does not mean the connection is actually secure, just that the protocol is HTTPS.
	define('HTTPS', true, true);
}else{
	//Not using https://
	define('HTTPS', false, true);
}
$virtualMachine = stripos($_SERVER['SERVER_NAME'], LOCALIP) === false ? false : true;
//Define the includes folder, starting directory, and other constants depending on local or production.
if( !defined('LOCAL') ){
	if( stripos($_SERVER['SERVER_NAME'], 'dev') === false && !$virtualMachine ){
		//Production server.
		define('LOCAL', false, true);
		if( PRODUCTIONHOMEDIRECTORY == '' ){
			$currentPage = $_SERVER['PHP_SELF'];
		}else{
			$currentPageParts = explode(PRODUCTIONHOMEDIRECTORY, $_SERVER['REQUEST_URI']);
			$currentPage = isset($currentPageParts[1]) ? $currentPageParts[1] : '';//The path and filename, minus the domain, for the currently loaded page.
		}
		//Redirect for http or https.
		if( HTTPS ){
			if( FORCEHTTPS === false ){
				//Redirect to http (non-secure).
				header('Location: http://' . DOMAIN . PRODUCTIONHOMEDIRECTORY . $currentPage);
			}
			define('AUTOLINK', 'https://' . DOMAIN . PRODUCTIONHOMEDIRECTORY, true);
			define('CURRENTPAGE', 'https://' . DOMAIN . PRODUCTIONHOMEDIRECTORY . $currentPage, true);
		}else{
			$Debug->add('PRODUCTIONHOMEDIRECTORY: ' . PRODUCTIONHOMEDIRECTORY);
			if( FORCEHTTPS !== false ){
				//Redirect to https (secure).
				header('Location: https://' . DOMAIN . PRODUCTIONHOMEDIRECTORY . $currentPage);
			}
			define('AUTOLINK', 'http://' . DOMAIN . PRODUCTIONHOMEDIRECTORY, true);
			define('CURRENTPAGE', 'http://' . DOMAIN . PRODUCTIONHOMEDIRECTORY . $currentPage, true);
		}
		set_include_path(PRODUCTIONINCLUDEDIRECTORY . '/');
		define('COOKIEDOMAIN', '.' . DOMAIN, true);
		define('COOKIEPATH', PRODUCTIONHOMEDIRECTORY, true);// The / is the default so the session cookie functions on all folders of the site.
		$dbHost = $rdbHost;
		$dbName = $remoteDbName;
		$dbPort = $remoteDbPort;
		$dbUser = $remoteDbUser;
		$dbPass = $remoteDbPassword;
	}else{
		//Local server.
		define('LOCAL', true, true);
		if( LOCALHOMEDIRECTORY == '' ){
			$currentPage = $_SERVER['PHP_SELF'];
		}else{
			$currentPageParts = explode(LOCALHOMEDIRECTORY, $_SERVER['PHP_SELF']);
			$currentPage = isset($currentPageParts[1]) ? $currentPageParts[1] : '';//The path and filename, minus the domain, for the currently loaded page.
		}
		if( HTTPS ){
			if( FORCEHTTPS === false ){
				//Redirect to http (non-secure).
				if( $virtualMachine ){
					header('Location: http://' . LOCALIP . '/' . LOCALDOMAIN . LOCALHOMEDIRECTORY . $currentPage);
				}else{
					header('Location: http://' . LOCALDOMAIN . LOCALHOMEDIRECTORY . $currentPage);
				}
			}
			define('AUTOLINK', 'https://' . LOCALDOMAIN . LOCALHOMEDIRECTORY, true);
			define('CURRENTPAGE', 'https://' . LOCALDOMAIN . LOCALHOMEDIRECTORY . $currentPage, true);
		}else{
			if( FORCEHTTPS !== false ){
				//Redirect to https (secure).
				if( $virtualMachine ){
					header('Location: https://' . LOCALIP . '/' . LOCALDOMAIN . LOCALHOMEDIRECTORY . $currentPage);
				}else{
					header('Location: https://' . LOCALDOMAIN . LOCALHOMEDIRECTORY . $currentPage);
				}
			}
			define('AUTOLINK', 'http://' . LOCALDOMAIN . LOCALHOMEDIRECTORY, true);
			define('CURRENTPAGE', 'http://' . LOCALDOMAIN . LOCALHOMEDIRECTORY . $currentPage, true);
		}
		set_include_path(LOCALINCLUDEDIRECTORY . '/');
		define('COOKIEDOMAIN', '.' . LOCALDOMAIN, true);
		define('COOKIEPATH', LOCALHOMEDIRECTORY, true);//This limits the session cookie to the current sub folder.
		$dbHost = $ldbHost;
		$dbName = $ldbName;
		$dbPort = $ldbPort;
		$dbUser = $ldbUser;
		$dbPass = $ldbPass;
		$errorDbHost = $errorDbHostLocal;
		$errorDbName = $errorDbNameLocal;
		$errorDbPort = $errorDbPortLocal;
		$errorDbUser = $errorDbUserLocal;
		$errorDbPass = $errorDbPassLocal;
	}
}

//Define the current local time.
date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US');
setlocale(LC_CTYPE, 'C');//Downgrades the character type locale to the POSIX (C) locale.
list($micro, $sec) = explode(" ", microtime());
define('TIMESTAMP', $sec);//Unix timestamp of the default timezone in config.php (UTC), so all time functions refer to that timezone.
define('MICROTIME', (int)str_replace('0.', '', $micro));//Microseconds displayed as an eight digit integer (47158200).
define('DATETIME', date('Y-m-d H:i:s', TIMESTAMP));//This time is used for entry into a MYSQL database as a datetime format: YYYY-MM-DD HH:MM:SS
$PHPErrorHandler = new ErrorHandler(NULL, true);
$useStrictDebugging = false;
if( $useStrictDebugging ){
	function my_exception_handler(Exception $e) {
		global $Debug;
		//error_log($Debug->printArrayOutput($e),3,__DIR__ . '/../customError.log');
//		$myFile = __DIR__ . '/../customError.log';
		$myFile = '../customError.log';
		if( is_writable($myFile) ){
			$filesize = filesize($myFile);
			$mode = $filesize > 100000 ? 'w' : 'a';
			$fh = fopen($myFile, $mode);
			fwrite($fh, DATETIME . '
' . $Debug->printArray($e) . $Debug->output());
		}else{
			echo $myFile . ' is not readable.
	';
		}
		$path = LOCAL ? LOCALDOMAIN : DOMAIN;
		echo '<html lang="en" xml:lang="en">
<body style="text-align:center;font-family:Helvetica,Arial,Verdana,sans-serif;	font-size:0.75em;font-size-adjust:none;font-stretch:normal;font-style:normal;font-variant:normal;font-weight:normal;line-height:1.2em;">
<div style="text-align:left">
	<img src="' . LINKIMAGES . '/embassy_logo.png" style="height:68px;width:245px">
</div>
<div>
	We apologize, but we encountered an error we couldn\'t recover from.<br>
<br>
Please <a href="' . $_SERVER['PHP_SELF'] . '">refresh this page</a> and try again.
</div>
', $Debug->output(), '
</body>
</html>';
	}

	function customError($errno, $errstr) {
		echo "<b>Error:</b> [$errno] $errstr<br>";
		echo "Ending Script";
		die();
	}

	function exception_error_handler($errorNumber, $errorMessage, $filename, $lineNumber) {
		global $Debug;
		$Debug->printArray($errorNumber, '$errorNumber');
//		throw new CustomException($errorMessage);
		throw new ErrorException($errorMessage, $errorNumber, 0, $filename, $lineNumber);
	}

	function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
		// timestamp for the error entry
		$dt = date("Y-m-d H:i:s (T)");

		// define an assoc array of error string
		// in reality the only entries we should
		// consider are E_WARNING, E_NOTICE, E_USER_ERROR,
		// E_USER_WARNING and E_USER_NOTICE
		$errortype = array(
			E_ERROR => 'Error',
			E_WARNING => 'Warning',
			E_PARSE => 'Parsing Error',
			E_NOTICE => 'Notice',
			E_CORE_ERROR => 'Core Error',
			E_CORE_WARNING => 'Core Warning',
			E_COMPILE_ERROR => 'Compile Error',
			E_COMPILE_WARNING => 'Compile Warning',
			E_USER_ERROR => 'User Error',
			E_USER_WARNING => 'User Warning',
			E_USER_NOTICE => 'User Notice',
			E_STRICT => 'Runtime Notice',
			E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
		);
		// set of errors for which a var trace will be saved
		$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

		$err = "<errorentry>\n";
		$err .= "\t<datetime>" . $dt . "</datetime>\n";
		$err .= "\t<errornum>" . $errno . "</errornum>\n";
		$err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
		$err .= "\t<errormsg>" . $errmsg . "</errormsg>\n";
		$err .= "\t<scriptname>" . $filename . "</scriptname>\n";
		$err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";

		if( in_array($errno, $user_errors) ){
			$err .= "\t<vartrace>" . wddx_serialize_value($vars, "Variables") . "</vartrace>\n";
		}
		$err .= "</errorentry>\n\n";

		// for testing
		echo $err;

		// save to the error log, and e-mail me if there is a critical user error
		error_log($err, 3, "../customError.log");
		if( $errno == E_USER_ERROR ){
			mail("mark@markproaudio.com", "Critical User Error", $err);
		}
	}

	function myErrorHandler($errno, $errstr, $errfile, $errline) {
		if( !(error_reporting() & $errno) ){
			// This error code is not included in error_reporting
			return;
		}

		switch( $errno ) {
			case E_USER_ERROR:
				echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
				echo "  Fatal error on line $errline in file $errfile";
				echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
				echo "Aborting...<br />\n";
				exit(1);
				break;

			case E_USER_WARNING:
				echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
				break;

			case E_USER_NOTICE:
				echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
				break;

			default:
				echo "Unknown error type: [$errno] $errstr<br />\n";
				break;
		}

		/* Don't execute PHP internal error handler */
		return true;
	}

	//set_exception_handler('my_exception_handler');
	set_error_handler("myErrorHandler");//Do not use on a production server. This is only for debugging.
}

//Define MAGICQUOTES
if( get_magic_quotes_gpc() ){
	if( !ini_set('magic_quotes_gpc', 'Off') ){
		define('MAGICQUOTES', true, true);
		$Debug->add('Just set magic_quotes_gpc: ' . MAGICQUOTES . '<br>');
	}
}else{
	define('MAGICQUOTES', false, true);
}

define('DSN', "mysql:host=$dbHost;dbname=$dbName;port=$dbPort{user}$dbUser{pass}$dbPass", true);
define('ERRORDBC', "mysql:host=$errorDbHost;dbname=$errorDbName;port=$errorDbPort{user}$errorDbUser{pass}$errorDbPass");

//define('ERRORDBC', "mysql:host=$errorDbHost;dbname=$errorDbName;port=$errorDbPort{user}$errorDbUser{pass}$errorDbPass");
// Define session parameters.
/**
 * PHP Sessions store a file on the server that relates to a cookie on the remote user's computer. You can use these two values to authenticate a user.
 * To use sessions you must call session_start(). This automatically creates $_SESSION, stores a file on the local server, and places a cookie on the remote user's computer.
 * This in itself does not indicate a state of authentication. It only establishes the ability to use sessions.
 * You must validate the user by such common means as username/password combination and then store a $_SESSION variable such as 'auth'.
 * In subsequent page calls you can then verify just the $_SESSION['auth'] variable to establish authentication.
 * */
define('PAYROLL_INTERFACE_SESSION_ID', 'payroll_interface_session_id');
session_name(PAYROLL_INTERFACE_SESSION_ID);//use a unique session name
session_set_cookie_params(7776000, COOKIEPATH, COOKIEDOMAIN, HTTPS, HTTPS);
session_cache_limiter('nocache');
session_start();
//session_regenerate_id();

//define('LINKADMIN', AUTOLINK . '/admin', 1);
define('LINKCSS', AUTOLINK . '/css', 1);
define('LINKIMAGES', AUTOLINK . '/images', 1);
define('LINKJS', AUTOLINK . '/js', 1);
define('LINKDOCUMENTS', AUTOLINK . '/documents', 1);
define('COLORBLACK', '000000', 1);
define('COLORBLUE', '00BCDC', 1);
define('COLORGRAY', 'F5F5F5', 1);
define('COLORLIGHTRED', 'FF7070', 1);
define('COLORTEXT', '333333', 1);
define('FONT', 'Helvetica Neue,​Helvetica,​Verdana,​Arial,​sans-serif', 1);
define('SIZE1', '1', 1);
define('SIZE2', '2', 1);
define('SIZE3', '3', 1);
define('SIZE4', '4', 1);
define('SIZE5', '5', 1);
$Success = false;
$ReturnThis = '';

try{
	// Check that pdo_mysql driver is installed.
	if( defined('PDO::ATTR_DRIVER_NAME') ){
		$Dbc = new Dbc(DSN);
	}else{
		throw new CustomException('', 'Verify that the module called pdo_mysql is installed by running phpinfo().');
	}
	/*if( $Dbc->getStatus() === false ){
		throw new CustomException('There is no database connection.', 'Could not connect to the database.', '');
	}*/
}catch( CustomException $e ){
	$Debug->error(__LINE__, '', $e);
	die($Debug->output());
//	$PHPErrorHandler->addErrorMessage($e->getMessage());
}
if( empty($_REQUEST['mode']) ){
	define('MODE', '', true);
}else{
	define('MODE', $_REQUEST['mode'], true);
}
$Debug->add('MODE: ' . MODE);
require('utilities.php');