<?php
namespace Embassy;
use Exception, ErrorException, PDOException, PDO;

class Dbc extends PDO {
	private $Debug;
	private $status;

	public function __construct($Debug, $hostname, $databaseName, $port, $user, $password) {
		$this->Debug = &$Debug;
		$this->status = false;
		$this->Debug->newFile('includes/Embassy/Dbc.php');

		try{
			if( !defined('PDO::ATTR_DRIVER_NAME') ){
				throw new CustomException('','PDO::ATTR_DRIVER_NAME is not defined. Verify that the module called pdo_mysql is installed by running phpinfo().');
			}
			$options = array(
				PDO::ATTR_PERSISTENT => true,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);
			parent::__construct("mysql:host=$hostname;dbname=$databaseName;port=$port", $user, $password, $options);
		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', '<pre>' . $e . '</pre>');
		}catch( Exception $e ){
			$Debug->error(__LINE__, '', $e);
		}
		$this->status = true;
	}

	public function getStatus() {
		return $this->status;
	}

	public function execute($values = array()) {
		$this->Debug->printArray($values, '$values');
		$t = false;
		try{
			$t = parent::execute($values);
			// maybe do some logging here?
		}catch( PDOException $e ){
			// maybe do some logging here?
			die('funkytown2');
			//throw $e . $Debug;
		}
		return $t;
	}

	public static function interpolateQuery($query, $params) {
		$keys = array();

		# build a regular expression for each parameter
		if( is_array($params) ){
			foreach( $params as $key => $value ){
				if( is_string($key) ){
					$keys[] = '/:' . $key . '/';
				}else{
					$keys[] = '/[?]/';
				}
			}
		}
		$query = preg_replace($keys, $params, $query, 1, $count);
		#trigger_error('replaced '.$count.' keys');
		return $query;
	}
}