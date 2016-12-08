<?php
namespace Embassy;

use PDO, ErrorException, Exception;

/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 6/20/2016
 * Time: 12:34 PM
 */
class PayrollManagement extends PayrollInterface {

	public function __construct($Ajax, $Dbc, $Debug, $Message) {
		parent::__construct($Ajax, $Dbc, $Debug, $Message);
		if( MODE == 'newEmpXRef' ){
			self::newEmpXRef();
		}elseif( MODE == 'newJobXRef' ){
			self::newJobXRef();
		}elseif( MODE == 'newMasterLevelEmpXRef' ){
			self::newMasterLevelEmpXRef();
		}elseif( MODE == 'listEmpXRef' ){
			self::listEmpXRef();
		}elseif( MODE == 'listJobXRef' ){
			self::listJobXRef();
		}elseif( MODE == 'listMasterLevelEmpXRef' ){
			self::listMasterLevelEmpXRef();
		}elseif( MODE == 'deleteEmpXRef' ){
			self::deleteEmpXRef();
		}elseif( MODE == 'deleteJobXRef' ){
			self::deleteJobXRef();
		}elseif( MODE == 'deleteMasterLevelEmpXRef' ){
			self::deleteMasterLevelEmpXRef();
		}
	}

	public function deleteEmpXRef() {
		try{
			if( !isset($_POST['EmpXRef']) ){
				throw new CustomException('', '$_POST[\'EmpXRef\'] is not set.');
			}
			$deleteEmpXRefStmt = $this->Dbc->prepare("DELETE FROM
	empxref
WHERE
	EmpXRef = ?
LIMIT 1");
			$params = array($_POST['EmpXRef']);
			$deleteEmpXRefStmt->execute($params);
			$this->Ajax->SetSuccess(true);
			$this->Ajax->setReference('deleteEmpXRef');
			$this->Ajax->AddValue(array('list' => self::listEmpXRef()));
			$this->Message->add('Deleted the EmpXRef.');
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

	public function deleteJobXRef() {
		try{
			if( empty($_POST['JobXRef']) ){
				throw new CustomException('', '$_POST[\'JobXRef\'] is empty.');
			}
			$deleteJobXRefStmt = $this->Dbc->prepare("DELETE FROM
	jobxref
WHERE
	JobXRef = ?
LIMIT 1");
			$params = array($_POST['JobXRef']);
			$deleteJobXRefStmt->execute($params);
			$this->Ajax->SetSuccess(true);
			$this->Ajax->AddValue(array('list' => self::listJobXRef()));
			$this->Message->add( 'Deleted the JobXRef.');
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}
		$this->Ajax->ReturnData();;
	}

	public function deleteMasterLevelEmpXRef() {
		try{
			if( empty($_POST['EmpXRef']) ){
				throw new CustomException('', '$_POST[\'EmpXRef\'] is empty.');
			}
			$deleteEmpXRefStmt = $this->Dbc->prepare("DELETE FROM
	master_level_empxref
WHERE
	EmpXRef = ?
LIMIT 1;");
			$params = array($_POST['EmpXRef']);
			$deleteEmpXRefStmt->execute($params);
			$this->Ajax->SetSuccess(true);
			$this->Ajax->AddValue(array('list' => self::listMasterLevelEmpXRef()));
			$this->Message->add('Deleted the Master Level EmpXRef.');
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}
		$this->Ajax->ReturnData();;
	}

	public function listEmpXRef() {
		$output = '';
		try{
			$listEmpXRefStmt = $this->Dbc->prepare("SELECT * FROM
	empxref
ORDER BY EmpXRef");
			$listEmpXRefStmt->execute();
			$output .= '<ul>';
			$foundRows = false;
			while( $row = $listEmpXRefStmt->fetch(PDO::FETCH_ASSOC) ){
				$output .= '<li><i class="fa fa-close deleteEmpXRef red" data-empxref="' . $row['EmpXRef'] . '"></i> ' . $row['EmpXRef'] . '</li>';
				$foundRows = true;
			}
			if( !$foundRows ){
				$output .= '<li>No EmpXRef codes were found.</li>';
			}
			$output .= '</ul>';
			$this->Ajax->SetSuccess(true);
			$this->Ajax->AddValue(array('list' => $output));
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Error $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}
		if( MODE == 'listEmpXRef' ){
			$this->Ajax->ReturnData();;
		}else{
			return $output;
		}
	}

	public function listJobXRef() {
		$output = '';
		try{
			$listJobXRefStmt = $this->Dbc->prepare("SELECT * FROM
	jobxref ORDER BY JobXRef");
			$listJobXRefStmt->execute();
			$output .= '<table>';
			$foundRows = false;
			$x = 1;
			$output .= '<tr><td></td><td style="text-align:left;padding-left:3em;font-weight:bold">JobXRef</td><td style="text-align:left;font-weight:bold">Job Code</td></tr>';
			while( $row = $listJobXRefStmt->fetch(PDO::FETCH_ASSOC) ){
				if( $x % 2 == 0 ){
					$bg = 'white';
				}else{
					$bg = '#E0E0E0';
				}
				$output .= '<tr style="background-color: ' . $bg . '"><td style="text-align:right">' . $x . '<td><i class="fa fa-close deleteJobXRef red" data-jobxref="' . $row['JobXRef'] . '"></i>' . $row['JobXRef'] . '</td><td>' . $row['JobCode'] . '</td></tr>';
				$foundRows = true;
				$x++;
			}
			if( !$foundRows ){
				$output .= '<tr><td>No JobXRef codes were found.</td></tr>';
			}
			$output .= '</table>';
			$this->Ajax->SetSuccess(true);
			$this->Ajax->AddValue(array('list' => $output));
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}
		if( MODE == 'listJobXRef' ){
			$this->Ajax->ReturnData();;
		}else{
			return $output;
		}
	}

	public function listMasterLevelEmpXRef() {
		$output = '';
		try{
			$listMasterLevelEmpXRefStmt = $this->Dbc->prepare("SELECT * FROM
	master_level_empxref
ORDER BY level, EmpXRef;");
			$listMasterLevelEmpXRefStmt->execute();
			$output .= '<table>';
			$foundRows = false;
			$x = 1;
			$output .= '<tr><td></td><td style="text-align:left;padding-left:3em;font-weight:bold">EmpXRef</td><td style="text-align:left;font-weight:bold">Level</td></tr>';
			while( $row = $listMasterLevelEmpXRefStmt->fetch(PDO::FETCH_ASSOC) ){
				if( $x % 2 == 0 ){
					$bg = 'white';
				}else{
					$bg = '#E0E0E0';
				}
				$output .= '<tr style="background-color: ' . $bg . '"><td style="text-align:right">' . $x . '<td><i class="fa fa-close deleteMasterLevelEmpXRef red" data-empxref="' . $row['EmpXRef'] . '"></i>' . $row['EmpXRef'] . '</td><td>' . $row['level'] . '</td></tr>';
				$foundRows = true;
				$x++;
			}
			if( !$foundRows ){
				$output .= '<tr><td>No Master Level EmpXRef codes were found.</td></tr>';
			}
			$output .= '</table>';
			$this->Ajax->SetSuccess(true);
			$this->Ajax->AddValue(array('list' => $output));
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}
		if( MODE == 'listMasterLevelEmpXRef' ){
			$this->Ajax->ReturnData();;
		}else{
			return $output;
		}
	}

	protected function newEmpXRef() {
		try{
			if( empty($_POST['EmpXRef']) ){
				throw new CustomException('', '$_POST[\'EmpXRef\'] is empty');
			}
			if( !is_numeric($_POST['EmpXRef']) ){
				throw new CustomException('The EmpXRef code must be numeric.');
			}
			$EmpXRef = intThis(trim($_POST['EmpXRef']));
			if( !is_int($EmpXRef) ){
				throw new CustomException('The interface had trouble recognizing the number you entered. Make sure to enter it without letters or special characters.');
			}
			//Add the EmpXRef to the database.
			$addEmployeeStmt = $this->Dbc->prepare("INSERT IGNORE INTO
	empxref
SET
	EmpXRef = ?");
			$params = array($EmpXRef);
			$addEmployeeStmt->execute($params);
			$resultCount = $addEmployeeStmt->rowCount();
			$this->Ajax->SetSuccess(true);
			$this->Message->add( $resultCount > 0 ? 'New EmpXRef successfully added.' : '');
			$this->Ajax->AddValue(array('list' => self::listEmpXRef()));
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}
		$this->Ajax->ReturnData();;
	}

	protected function newJobXRef() {
		$output = '';
		try{
			if( empty($_POST['JobXRef']) ){
				throw new CustomException('', '$_POST[\'JobXRef\'] is empty');
			}
			if( strlen($_POST['JobXRef']) > 255 ){
				throw new CustomException('The JobXRef code must be less than 255 characters.');
			}
			if( !is_numeric($_POST['JobCode']) ){
				throw new CustomException('The JobCode must be numeric.');
			}
			if( strlen($_POST['JobCode']) != 4 ){
				throw new CustomException('The JobCode must be 4 digits long.');
			}

			// Check that the JobXRef code does not already exist.
			$checkStmt = $this->Dbc->query("SELECT * FROM jobxref
WHERE
	JobXRef LIKE('%" . $_POST['JobXRef'] . "%')");
			$output .= '<ul>';
			$foundRows = false;
			while( $row = $checkStmt->fetch(PDO::FETCH_ASSOC) ){
				$output .= '<li>' . $row['JobXRef'] . ': ' . $row['JobCode'] . '</li>';
				$foundRows = true;
			}
			$output .= '</ul>';
			if( $foundRows ){
				$this->Message->add('There is already an entry with this JobXRef code.');
			}else{
				//Add the EmpXRef to the database.
				$addEmployeeStmt = $this->Dbc->prepare("INSERT IGNORE INTO
	jobxref
SET
	JobXRef = ?,
	JobCode = ?");
				$params = array($_POST['JobXRef'], $_POST['JobCode']);
				$addEmployeeStmt->execute($params);
				$returnCount = $addEmployeeStmt->rowCount();
				$this->Ajax->SetSuccess(true);
				$this->Message->add( $returnCount > 0 ? 'New JobXRef successfully added.' : '');
				$this->Ajax->AddValue(array('list' => self::listJobXRef()));
			}
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}
		$this->Ajax->ReturnData();;
	}

	protected function newMasterLevelEmpXRef() {
		try{
			if( empty($_POST['masterLevelEmpXRef']) ){
				throw new CustomException('', '$_POST[\'masterLevelEmpXRef\'] is empty');
			}
			if( !is_numeric($_POST['masterLevelEmpXRef']) ){
				throw new CustomException('The master level EmpXRef code must be numeric.');
			}
			if( !is_numeric($_POST['masterLevel']) ){
				throw new CustomException('The master level must be numeric.');
			}
			if( strlen($_POST['masterLevel']) != 1 ){
				throw new CustomException('The master level must be 1 digit long.');
			}
			$EmpXRef = intThis(trim($_POST['masterLevelEmpXRef']));
			if( !is_int($EmpXRef) ){
				throw new CustomException('The interface had trouble recognizing the EmpXRef number you entered. Make sure to enter it without letters or special characters.');
			}
			$masterLevel = intThis(trim($_POST['masterLevel']));
			if( !is_int($masterLevel) ){
				throw new CustomException('The interface had trouble recognizing the Level you entered. Make sure to enter it without letters or special characters.');
			}
			//Add the master level EmpXRef to the database.
			$addMasterLevelEmployeeStmt = $this->Dbc->prepare("INSERT IGNORE INTO
	master_level_empxref
SET
	EmpXRef = ?,
	level = ?;");
			$params = array($EmpXRef, $masterLevel);
			$addMasterLevelEmployeeStmt->execute($params);
			$resultCount = $addMasterLevelEmployeeStmt->rowCount();
			$this->Ajax->SetSuccess(true);
			$this->Message->add($resultCount > 0 ? 'New master level EmpXRef code successfully added.' : ' ');
			$this->Ajax->AddValue(array('list' => self::listMasterLevelEmpXRef()));
		}catch( CustomException $e ){
			$this->Ajax->ReturnData();;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			$this->Ajax->ReturnData();;
		}
		$this->Ajax->ReturnData();;
	}
}