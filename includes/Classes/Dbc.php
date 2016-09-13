<?php

class Dbc extends PDO {
	private $_Debug;
	private $_status;

	public function __construct($dsn) {
		global $Debug;
		$this->_Debug = &$Debug;
		$this->_status = false;
		try{
			$options = array(
				PDO::ATTR_PERSISTENT => true,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			);

			$dbcParts = preg_split('/{\w+}/', $dsn);
			parent::__construct($dbcParts[0], $dbcParts[1], $dbcParts[2], $options);
		}catch( CustomException $e ){
			$Debug->error(__LINE__, '', $e);
		}catch( ErrorException $e ){
			$Debug->error(__LINE__, '', '<pre>' . $e . '</pre>');
		}catch( Exception $e ){
			$Debug->error(__LINE__, '', $e);
			die($Debug->output());
		}
		$this->_status = true;
	}

	public function getStatus() {
		return $this->_status;
	}

	public function execute($values = array()) {
		$this->_Debug->printArray($values, '$values');
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