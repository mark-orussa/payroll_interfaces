<?php
/**
 * Important stuff is defined here. This file must be included at the beginning of every php file for proper operation.
 *
 * Software Requirements:
 * - PHP 5.5 or higher
 * - YAML extension
 * - PDO MYSQL extension
 * - Defuse PHP encryption library (https://github.com/defuse/php-encryption)
 * - jQuery version 1.12 or newer (https://jquery.com/)
 *
 * Instructions for configuring this application.
 * - You must create and declare the absolute path to a config file. This is done in the first line of code below.
 *   The config file is a YAML-formatted file that contains global required declarations that this application depends on. It can have any title and any or no extension.
 * - You must create and declare the absolute path to a key file. It is declared in the second line of code below. This is a plain text document with a single line of code. It can have any title and any or no extension.
 * - The Debug class needs to write to a log file.
 */
$configPath = 'C:\xampp\htdocs\pi.embassyllc.devSecrets\config.yml'; // The location of the YML formatted config file. It should not be in the public directory.
$keyPath = 'C:\xampp\htdocs\pi.embassyllc.devSecrets\key.txt';// The location of the key file. It should not be in the public directory. Ideally it is in a different directory than the config file.


// Nothing below this point should be modified.
class EmbassyAutoloader {
	/**
	 * Currently not in use. We are using the Composer PSR-4 compliant autoloader.
	 * Autoloaders make requiring classes a thing of the past. By following a directory-file hierarchy you don't need to include class files.
	 *
	 * All Embassy classes should be located in the Classes/Embassy directory, which sits at the root of the site.
	 * Don't confuse the root of the site with the public facing directory. They are not always the same.
	 * The class directory should not be in the public directory for security reasons.
	 *
	 * Given this directory structure:
	 *        Classes/Config/Page.php
	 *
	 * You would call the Page class with:
	 *        new Config_Page();
	 *
	 * The name of the class would be:
	 *        Config_Page
	 *
	 * With this structure the Autoloader will always find the class and avoid ambiguity.
	 */
	public static function autoload($className) {
		if( strpos($className, 'Embassy') !== false ){
			$includePath = 'includes/Classes/' . str_replace('_', '/', $className) . '.php';
			if( is_readable(__DIR__ . '/' . $includePath) ){
				require $includePath;
			}else{
				die('Could not include: ' . get_include_path() . $includePath);
			}
		}
	}
}

/*spl_autoload_register(null, false);
spl_autoload_register('EmbassyAutoloader::autoload');*/

function my_error_handler($errorLevel, $errorMessageString, $errorFile, $errorLine, array $errorContext) {
	global $Debug, $Message;
	// error was suppressed with the @-operator
	if( 0 === error_reporting() ){
		return false;
	}
	$output = '';
	if( !empty($errorLevel) ){
		$output .= 'There is a level ' . $errorLevel . ' error';
	}else{
		$output .= 'There is an error';
		$Message->accumulate('We encountered an error.');
	}
	if( !empty($errorFile) ){
		$output .= ' in file <span style="font-weight: bold">' . $errorFile . '</span>';
	}
	if( !empty($errorLine) ){
		$output .= ' on line <span style="font-weight: bold">' . $errorLine . '</span>';
	}
	if( !empty($errorMessageString) ){
		$output .= ' with the message: <pre style="margin-left:2em">' . $errorMessageString . '</pre>';
	}
	/*if( !empty($errorContext) ){
		$output .= '<span style="font-weight: bold">Error Context:</span> ' . $Debug->printArrayOutput($errorContext);
	}*/
	$Debug->add($output);
	//throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

try{
	// Use the Composer autoloader. We have given it the location of our custom classes.
	require 'vendor/autoload.php';

	//$loader = require __DIR__ . '/vendor/autoload.php';
	//$loader->add('Embassy\\', __DIR__ . '/vendor/embassy');

// Instantiate our classes.
	$Message = new Embassy\Message();
	$Debug = new Embassy\Debug();
	set_error_handler("my_error_handler");
	$Config = new Embassy\Config($configPath);
	$Secret = new Embassy\Secret($keyPath);
	$Ajax = new Embassy\Ajax();

	// This is for initial encryption work.
	//	print $Secret::generateKey();
	//	die();
	//	print $Secret->encrypt('localhost');
	//	print $Secret->decrypt($Config->getDatabaseHostname());


	if( isset($_SESSION['auth']) && !$_SESSION['auth'] && stripos($_SERVER['PHP_SELF'], 'login') === false ){
		header('Location:' . LINKLOGIN);
	}

// Get the database credentials and establish the default base connection.
	$databaseCredentials = $Config->getDatabaseCredentials();
	foreach( $databaseCredentials as $key => &$value ){
		if( !empty($value) ){
			$value = $Secret->decrypt($value);
		}
	}
	$Dbc = new Embassy\Dbc($databaseCredentials['DATABASE_HOST'], $databaseCredentials['DATABASE_NAME'], $databaseCredentials['DATABASE_PORT'], $databaseCredentials['DATABASE_USER'], $databaseCredentials['DATABASE_PASSWORD']);


// Catch our AJAX requests.
//	require 'Embassy/AjaxListener.php';

// Instantiate the Page class.
	$Page = new Embassy\Page($Debug, $Dbc, $Message, $ReturnThis, $Success);
	$Page->addBody('<div id="environment" style="display: none;">' . ENVIRONMENT . '</div>');
	$Page->addJs(array('jquery-3.1.0.js', 'functions.js'));

	// Pass our application off to the routing.
//	require '../routes.php';
	require('utilities.php');

}catch( Exception $exception ){
	echo $exception;
	$Debug->writeToLog();
}
// TODO: slowly converting site to new php logic configuration.

