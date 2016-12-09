<?php

/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 9/16/2016
 * Time: 1:04 PM
 */
namespace Embassy;

use Exception, Symfony\Component\Yaml\Exception\ParseException;

class Config {
	/**
	 * The structure of this application starts with a config.yml file which stores basic information as well as 'secret' data.
	 * The following keys are necessary for this application to function:
	 *      ENVIRONMENT - Either 'development' or 'production'.
	 *      DOMAIN - The base domain of the website as registered with the registrar. No http:// or trailing slash.
	 *      PUBLIC_PATH - The full path (from root) to the publicly available files.
	 *      INCLUDE_PATH - The full path (from root) to the files that are included when calling include() and require().
	 *      NAME_OF_THE_SITE - This becomes part of the page title. If your address is www.mysite.com, you would use 'My Site'.
	 *      REMEMBER_ME_COOKIE - The cookie used to retain login over browser sessions.
	 *
	 * We need to differentiate between the development server and the production server. Declaring the environment is an easy way to do this.
	 * The production server will surely have a different directory structure than the development server, so we need to define the paths.
	 * A local development installation using something like WAMP or XAMPP often locate the files in a user directory or root:
	 *      c:\Users\your_user_name\Sites\your_application
	 *      c:\xampp\htdocs\your_application
	 *
	 * On a production Linux server they are often located in:
	 *      /var/www/your_application
	 *
	 * We set the include path so that php only looks in one location. This assures that your files are found.
	 * Again, these are unique to the production and development servers. This can also be hard-coded in the php.ini file, but that is a less-obvious place
	 * and may be overwritten in some installations.
	 *
	 */

	public $_environment;

	private $databaseCredentials;
	private $emailCredentials;
	private $googleCaptchaSecret;

	private $Debug; // This is a reference to the Debug instance.

	public function __construct($Debug, $configPath) {
		/**
		 * Config_Base constructor.
		 * This establishes a number of constants that are available throughout the application.
		 *
		 * When trying to make a site work on a production server and local server without having to edit code, there
		 * are many things to consider. The production server will surely have a different directory structure than the remote.
		 * A local development installation using something like WAMP or XAMPP is often located in a user directory or root:
		 *      c:\Users\your_user_name\Sites\your_application
		 *      c:\xampp\htdocs\your_application
		 * On a production Linux server it is often located in:
		 *      /var/www/your_application
		 *
		 * We set the include path so that php only looks in one location. This assures that your files are found.
		 * This can also be hard-coded in the php.ini file
		 *
		 * @param $configPath    string    The path to the yml formatted config file relative to this file. Include the name and yml extension of the config file.
		 */
		$this->Debug = &$Debug;
		$this->Debug->newFile('includes/Embassy/Config.php');
		$this->databaseCredentials = array();
		$this->emailCredentials = array();
		$this->googleCaptchaSecret = '';

		//Define the current local time.
		date_default_timezone_set('UTC');
		setlocale(LC_ALL, 'en_US');
		setlocale(LC_CTYPE, 'C');//Downgrades the character type locale to the POSIX (C) locale.
		list($micro, $sec) = explode(" ", microtime());
		define('TIMESTAMP', $sec);//Unix timestamp of the default timezone in config.php (UTC), so all time functions refer to that timezone.
		define('MICROTIME', (int)str_replace('0.', '', $micro));//Microseconds displayed as an eight digit integer (47158200).
		define('DATETIME', date('Y-m-d H:i:s', TIMESTAMP));//This time is used for entry into a MYSQL database as a datetime format: YYYY-MM-DD HH:MM:SS
		try{
			// Read the config file.
			if( !file_exists($configPath) ){
				die('File does not exist: ' . $configPath);
			}
			if( !is_readable($configPath) ){
				die('File is not readable: ' . $configPath);
			}
			function testFile($Debug, $configPath) {
				$myfile = fopen($configPath, "r") or die("Unable to open file!");
				$Debug->add(fread($myfile, filesize($configPath)));
				die('');
				$Debug->writeToLog();
			}

			// Test that the config file is available.
//			testFile($this->Debug,$configPath);

			// Read the config file.
			if( function_exists('yaml_parse_file') ){
				$config = yaml_parse_file($configPath, 0);
			}else{
				// The PECL YAML extension is not loaded. Try the Symphony version.
				$config = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($configPath));
			}

			// Get the non-encrypted config values and place them in constants.
			if( !isset($config['ENVIRONMENT']) ){
				throw new CustomException('', 'ENVIRONMENT value not found in config file . ');
			}else{
				define('ENVIRONMENT', $config['ENVIRONMENT']);
			}
			if( !isset($config['DOMAIN']) ){
				throw new CustomException('', 'DOMAIN value not found in config file . ');
			}else{
				define('DOMAIN', $config['DOMAIN']);
			}

			// Force an SSL connection.
			if( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ){
				//Using https:. This does not mean the connection is actually secure, just that the protocol is HTTPS.
				define('PROTOCOL', 'https://', true);
				define('HTTPS', true, true);
			}else{
				//Not using https. Redirect to secure page.
				die('Redirecting in file ' . __FILE__ . ' line ' . __LINE__);
				header('Location: https://' . DOMAIN . $_SERVER['PHP_SELF']);
				define('PROTOCOL', 'http://', true);
				define('HTTPS', false, true);
			}

			if( !isset($config['PUBLIC_PATH']) ){
				throw new CustomException('', 'PUBLIC_PATH value not found in config file.');
			}else{
				define('PUBLIC_PATH', $config['PUBLIC_PATH']);
			}
			if( !isset($config['INCLUDE_PATH']) ){
				throw new CustomException('', 'INCLUDE_PATH value not found in config file.');
			}else{
				set_include_path($config['INCLUDE_PATH'] . '/');
			}
			if( !isset($config['CUSTOM_NAMESPACE']) ){
				throw new CustomException('', 'CUSTOM_NAMESPACE value not found in config file.');
			}else{
				define('CUSTOM_NAMESPACE', $config['CUSTOM_NAMESPACE']);
			}
			if( !isset($config['NAME_OF_THE_SITE']) ){
				throw new CustomException('', 'NAME_OF_THE_SITE value not found in config file.');
			}else{
				define('NAME_OF_THE_SITE', $config['NAME_OF_THE_SITE']);
			}
			if( !isset($config['LOG_PATH']) ){
				throw new CustomException('', 'LOG_PATH value not found in config file.');
			}else{
				define('LOG_PATH', $config['LOG_PATH']);
			}
			if( !isset($config['GOOGLE_CAPTCHA_SECRET']) ){
				throw new CustomException('', 'GOOGLE_CAPTCHA_SECRET value not found in config file.');
			}else{
				$this->googleCaptchaSecret = $config['GOOGLE_CAPTCHA_SECRET'];
			}
			if( !isset($config['SESSION_NAME']) ){
				throw new CustomException('', 'SESSION_NAME value not found in config file.');
			}else{
				// Define session parameters.
				/**
				 * PHP Sessions store a file on the server that relates to a cookie on the remote user's computer. You can use these two values to authenticate a user.
				 * To use sessions you must call session_start(). This automatically creates $_SESSION, stores a file on the local server, and places a cookie on the
				 * remote user's computer.
				 * This in itself does not indicate a state of authentication. It only establishes the ability to use sessions.
				 * You must validate the user by such common means as username/password combination and then store a $_SESSION variable such as 'auth'.
				 * In subsequent page calls you can then verify just the $_SESSION['auth'] variable to establish authentication.
				 * */
				session_name($config['SESSION_NAME']);
				session_set_cookie_params(7776000, PUBLIC_PATH, '.' . DOMAIN, false, false);
				session_cache_limiter('nocache');
				session_start();
				//session_regenerate_id();
			}
			// Database credentials.
			if( !isset($config['DATABASE_HOST']) ){
				throw new CustomException('', 'DATABASE_HOST value not found in config file.');
			}else{
				$this->databaseCredentials['DATABASE_HOST'] = $config['DATABASE_HOST'];
			}
			if( !isset($config['DATABASE_NAME']) ){
				throw new CustomException('', 'DATABASE_NAME value not found in config file.');
			}else{
				$this->databaseCredentials['DATABASE_NAME'] = $config['DATABASE_NAME'];
			}
			if( !isset($config['DATABASE_PORT']) ){
				throw new CustomException('', 'DATABASE_PORT value not found in config file.');
			}else{
				$this->databaseCredentials['DATABASE_PORT'] = $config['DATABASE_PORT'];
			}
			if( !isset($config['DATABASE_USER']) ){
				throw new CustomException('', 'DATABASE_USER value not found in config file.');
			}else{
				$this->databaseCredentials['DATABASE_USER'] = $config['DATABASE_USER'];
			}
			if( !isset($config['DATABASE_PASSWORD']) ){
				throw new CustomException('', 'DATABASE_PASSWORD value not found in config file.');
			}else{
				$this->databaseCredentials['DATABASE_PASSWORD'] = $config['DATABASE_PASSWORD'];
			}
			// Email credentials.
			if( !isset($config['EMAIL_HOST']) ){
				throw new CustomException('', 'EMAIL_HOST value not found in config file.');
			}else{
				$this->emailCredentials['EMAIL_HOST'] = $config['EMAIL_HOST'];
			}
			if( !isset($config['EMAIL_USER']) ){
				throw new CustomException('', 'EMAIL_USER value not found in config file.');
			}else{
				$this->emailCredentials['EMAIL_USER'] = $config['EMAIL_USER'];
			}
			if( !isset($config['EMAIL_PASSWORD']) ){
				throw new CustomException('', 'EMAIL_PASSWORD value not found in config file.');
			}else{
				$this->emailCredentials['EMAIL_PASSWORD'] = $config['EMAIL_PASSWORD'];
			}
			if( !isset($config['EMAIL_PORT']) ){
				throw new CustomException('', 'EMAIL_PORT value not found in config file.');
			}else{
				$this->emailCredentials['EMAIL_PORT'] = $config['EMAIL_PORT'];
			}

			define('AUTOLINK', PROTOCOL . DOMAIN, true);
			define('LINKCSS', AUTOLINK . '/css', 1);
			define('LINKIMAGES', AUTOLINK . '/images', 1);
			define('LINKJS', AUTOLINK . '/js', 1);
			define('LINKDOCUMENTS', AUTOLINK . '/documents', 1);
			define('LINKLOGIN', AUTOLINK . '/login', 1);
		}catch( ParseException $exception ){
			printf("Unable to parse the YAML string: %s", $exception->getMessage() . ' on line ' . __LINE__ . ' in file ' . __FILE__);
			die($exception);
		}catch( \Error $exception ){
			printf("Unable to parse the YAML string: %s", $exception->getMessage() . ' on line ' . __LINE__ . ' in file ' . __FILE__);
			die($exception);
		}catch( CustomException $exception ){
			$this->Debug->error(__LINE__, 'butter ball', '<pre>' . $exception . '</pre>');
			die('<pre>' . $exception . '</pre>');
			$this->Debug->writeToLog();
		}catch( Exception $exception ){
			$this->Debug->add('<pre>' . $exception . '</pre>');
			die('<pre>' . $exception . '</pre>');
			$Debug->writeToLog();
		}finally{
		}
	}

	public function getDatabaseCredentials() {
		/**
		 * @return string
		 */
		return $this->databaseCredentials;
	}

	public function getEmailCredentials() {
		/**
		 * @return string
		 */
		return $this->emailCredentials;
	}

	public function getGoogleCaptchaSecret() {
		/**
		 * @return string
		 */
		return $this->googleCaptchaSecret;
	}
}