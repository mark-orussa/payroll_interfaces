<?php
namespace Embassy;
use Exception;
/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 5/25/2016
 * Time: 3:31 PM
 */
class SFTP {

	// SSH Server Fingerprint
	private $_serverFingerprint;
	// SSH Public Key File
	private $_id_rsaPub;// .ssh/id_rsa.pub';
	// SSH Private Key File
	private $_id_rsa;// .ssh/id_rsa';
	// SSH Private Key Passphrase (null == no passphrase)
	private $_passphrase;
	// SSH Connection
	private $_connection;


	public function __construct() {}

	public function getFile($remoteFilePath,$localFilePath){
		ssh2_scp_recv($this->_connection,$remoteFilePath,$localFilePath);
	}
	public function connectRSA() {
		if( !($this->_connection = ssh2_connect($this->_host, $this->_port)) ){
			throw new Exception('Cannot connect to server');
		}
		$fingerprint = ssh2_fingerprint($this->_connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
		if( strcmp($this->$this->_serverFingerprint, $fingerprint) !== 0 ){
			throw new Exception('Unable to verify server identity!');
		}
		if( !ssh2_auth_pubkey_file($this->_connection, $this->_username, $this->_id_rsaPub, $this->_id_rsa, $this->_passphrase) ){
			throw new Exception('Autentication rejected by server');
		}
	}

	public function connect($host, $port, $username, $password) {
		/**
		 * Make a simple username and password ssh connection to a server.
		 * @param string $host The SFTP host name, usually a domain or ip address.
		 * @param string $port The port the SFTP server is on.
		 * @param string $username
		 * @param string $password
		 * @return	bool	Returns true if connection succeeds, otherwise false.
		 */
		global $Debug;
		try{
			$connection = ssh2_connect($host, $port);
			if( !$connection ){
				throw new CustomException('Could not make a connection');
			}
			if( !ssh2_auth_password($connection, $username, $password) ){
				throw new CustomException('Could not authenticate username/password.');
			}
			$this->_connection = $connection;
		}catch( CustomException $e ){
			$Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	public function exec($cmd) {
		if( !($stream = ssh2_exec($this->_connection, $cmd)) ){
			throw new Exception('SSH command failed');
		}
		stream_set_blocking($stream, true);
		$data = "";
		while( $buf = fread($stream, 4096) ){
			$data .= $buf;
		}
		fclose($stream);
		return $data;
	}

	public function disconnect() {
		$this->exec('echo "EXITING" && exit;');
		$this->_connection = null;
	}

	public function __destruct() {
		$this->disconnect();
	}
}