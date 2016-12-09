<?php
/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 5/17/2016
 * Time: 3:53 PM
 */
namespace Embassy;
use ErrorException, Exception, PDO, PDOException;

class PayrollInterface {
	/**
	 * Salaried employees are not even entered into the database.
	 * @param    $_fileInto    array    Stores information about the uploaded file.
	 */
	private $_output;

	protected $Ajax;
	protected $Dbc;
	protected $Debug;
	protected $Message;

	protected $fileInfo;
	protected $databaseTable;
	protected $empXRefArray;
	protected $jobXRefArray;
	protected $invalidDataArray;
	protected $overLapArray;
	protected $unrecognizedJobCodesArray;
	protected $duplicateArray; // An array of two arrays, i.e. array($this->_checkRow, $this->_currentRow);
	protected $outgoingDirectory;// The path to the outgoing file. This does not include the filename.
	protected $outgoingFilePath;// The path plus the filename.

	public function __construct($Ajax, $Dbc, $Debug, $Message) {
		$this->Ajax = $Ajax;
		$this->Dbc = $Dbc;
		$this->Debug = $Debug;
		$this->Message = $Message;

		if( MODE == 'serveFile' ){
			self::serveFile();
		}else{
			$this->databaseTable = '';
			$this->duplicateArray = array();
			$this->invalidDataArray = array();
			$this->overLapArray = array();
			$this->unrecognizedJobCodesArray = array();
			$this->nonbillOverlap = array();
			$this->fileInfo = array();
			$this->empXRefArray = array(); // currently not used. Individual database queries use NOT IN to ignore salaried employees.
			$this->jobXRefArray = array();
			$this->payrollHeaders = array('ID', 'EmpXRef', 'DeptXRef', 'JobXRef', 'PayCode', 'DateOfService', 'HrsWorked', 'BlankField', 'CentralReachId', 'EmployeeFirstName', 'EmployeeLastName', 'ProcedureCodeString', 'timeworkedfrom', 'timeworkedto');
			/*			$this->empXRefArray = array(2429, 1948, 2256, 1845, 1959, 1955, 4828, 2706, 2419, 2652, 2840, 2617, 100871, 101143, 100198, 101275);
						$this->jobXRefArray = array('BSMABILL1' => '//////0605', 'BSMANONBILL1' => '//////0661', 'BSMAOB1' => '//////0662', 'BSMABILL2' => '//////0608', 'BSMANONBILL2' => '//////0663', 'BSMAOB2' => '//////0664', 'BSMABILL' => '//////0607', 'BSMANONBILL' => '//////0663', 'BSMAOB' => '//////0664', 'BSPHDBILL1' => '//////0606', 'BSPHDNONBILL1' => '//////0672', 'BSPHDOB1' => '//////0673', 'BSPHDBILL' => '//////0608', 'BSPHDNONBILL' => '//////0674', 'BSPHDOB' => '//////0675', 'BTMABILL' => '//////0612', 'BTMANONBILL' => '//////0670', 'BTBABILL' => '//////0611', 'BTBANONBILL' => '//////0668'******, 'BTHSBILL' => '//////0610', 'BTHSNONBILL' => '//////0665', 'BSMATRAVEL' => '//////0999', 'BSPHDTRAVEL' => '//////0999', 'BTMATRAVEL' => '//////0999', 'BTBATRAVEL' => '//////0999', 'BTHSTRAVEL' => '//////0999', 'PDMGMT' => '//////0607', 'CDMGMT2' => '//////0618', 'DSACHILDPARA' => '//////0631', 'DSADULTPARA' => '//////0630', 'DSAMGMT' => '//////0631', 'DSCHILDPARA' => '//////0630', 'DSCHILDPRO' => '//////0630', 'DSMGMT' => '//////0630', 'DTADULTNONBILL' => '//////0632', 'DTADULTPARA' => '//////0632', 'DTCHILDPRO' => '//////0632', 'DTMGMT' => '//////0632', 'DTDirect Support Professional - DDA' => '//////0632', 'HIADULTNONBILL' => '//////0635', 'HIADULTPARA' => '//////0636', 'HICHILDNONBILL' => '//////0637', 'HICHILDPRO' => '//////0641', 'HSCHILDPRO' => '//////0650', 'HSCHILDNONBILL' => '//////0650', 'HSCHILDPARA' => '//////0650', 'HSSBI1' => '//////0650', 'OMMGMT' => '//////0979', 'OMCHILDPARA' => '//////0979', 'OMADULTPARA' => '//////0979', 'HICHILDPARA' => '//////0638', 'HSADULTPARA' => '//////0650', 'DTCHILDPARA' => '//////0632', 'DTCHILDNONBILL' => '//////0632', 'HSADULTNONBILL' => '//////0650', 'DSAADULTPARA' => '//////0631', 'HISBI1' => '//////0643', 'CSSBI1' => '//////0620', 'CSMGMT' => '//////0620', 'PMMGMT' => '//////0220', 'HSMGMT' => '//////0631 ');*/
		}
	}

	private function addJobXRef() {
		try{
			if( empty($_POST['JobXRef']) ){
				throw new CustomException('', '$_POST[\'JobXRef\'] is empty');
			}
			//Add the EmpXRef to the database.
			$addJobXRefStmt = $this->Dbc->prepare("INSERT IGNORE INTO
	jobxref
SET
	JobXRef = ?,
	JobCode = ?");
			$params = array($_POST['JobXRef']);
			$addJobXRefStmt->execute($params);
//			$this->Message = 'Great success';
			$this->Ajax->SetSuccess(true);
			$this->Ajax->SetReference('newJobXRef');
			$this->Ajax->AddValue(array('butter' => 'New JobXRef successfully added.'));
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

	protected function addToOutput($addThis) {
		$this->_output .= $addThis . '<br>';
	}

	protected function formatCurrencyAsNumber($number) {
		// Converts $1,120.34 to 1120.34.
		$region = 'en_US';
		$currency = 'USD';
		$formatter = new NumberFormatter($region, NumberFormatter::CURRENCY);
		return $formatter->parseCurrency($number, $currency);
	}

	protected function formatNumberWithCommas($number, $decimals = 2) {
		/**
		 * Converts 1120.34 to 1,120.34.
		 *
		 * @param $number
		 * @param int $decimals The number of digits after the decimal point.
		 * @return string
		 */
		return number_format($number, $decimals);
	}

	public function getDuplicateEntries() {
		/**
		 * Displays the duplicate information in a nicely formatted table.
		 * @param    $headerArray          array The column headers for the information that will be shown.
		 * @param    $ignoreFirstColumn    bool    If the first column of your data is the auto-increment id column you may wish it ignore it.
		 * @return  string  Returns html.
		 */
		if( !empty($this->duplicateArray) ){
			$count = count($this->payrollHeaders);
			$ignoreFirstColumn = $count == count($this->duplicateArray) ? false : true;
			$td = '';
			$headerCount = 0;
			foreach( $this->payrollHeaders as $value ){
				if( $headerCount == 0 && $ignoreFirstColumn ){
					$headerCount++;
					continue;
				}
				$td .= '<td>' . $value . '</td>';
			}
			$output = '<p class="interfaceResponse">There is some duplicate data that was ignored.<div class="toggleButton" style="display:inline">Click to View Duplicates</div>
<div class="toggleMeNoOverlap">
<table style="border-collapse:collapse"><tr>' . $td . '</tr>';
			foreach( $this->duplicateArray as $value ){
				$array1 = array_values($value[0]);// convert the associative array into an indexed array.
				$array2 = array_values($value[1]);
				$row1 = '';
				$row2 = '';
				for( $x = 0; $x < $count; $x++ ){
					if( $x == 0 && $ignoreFirstColumn ){
						continue;
					}
					if( $array1[$x] === true ){
						$temp1 = 'true';
					}else if( $array1[$x] === false ){
						$temp1 = 'false';
					}else{
						$temp1 = $array1[$x];
					}
					$row1 .= '<td>' . $temp1 . '</td>';

					if( $array2[$x] === true ){
						$temp2 = 'true';
					}else if( $array2[$x] === false ){
						$temp2 = 'false';
					}else{
						$temp2 = $array2[$x];
					}
					$row2 .= '<td style="border:0;margin:0">' . $temp2 . '</td>';

				}
				$output .= '<tr>' . $row1 . '</tr><tr style="background-color:#eee;">' . $row2 . '</tr>';
			}
			$output .= '</table></div></p>';
			return $output;
		}else{
			return '<p class="interfaceResponse">There is no duplicate data.</p>';
		}
	}

	protected function getEmpXRef() {
		/**
		 * Get the EmpXRef codes
		 *
		 * @return    bool    Returns true upon success, otherwise false.
		 */
		try{
			$getEmpXRefStmt = $this->Dbc->query("SELECT * FROM empxref");
			$getEmpXRefStmt->execute();
			while( $row = $getEmpXRefStmt->fetch(PDO::FETCH_ASSOC) ){
				$this->empXRefArray[] = $row['EmpXRef'];
			}
		}catch( CustomPDOException $e ){
			return false;
		}catch( PDOException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( CustomException $e ){
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	protected function getJobXRef() {
		/**
		 * Get the JobXRef codes
		 *
		 * @return    bool    Returns true upon success, otherwise false.
		 */
		try{
			$jobXRefStmt = $this->Dbc->query("SELECT * FROM
	jobxref");
			$jobXRefStmt->execute();
			while( $row = $jobXRefStmt->fetch(PDO::FETCH_ASSOC) ){
				$this->jobXRefArray[$row['JobXRef']] = $row['JobCode'];
			}
		}catch( CustomPDOException $e ){
			return false;
		}catch( PDOException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( CustomException $e ){
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	public function getFileInfo() {
		/**
		 * @return array    Returns the fileInfo array, which looks like [filename,type,size].
		 */
		return $this->fileInfo;
	}

	public function getInvalidData() {
		/**
		 * Displays the invalid data information in a nicely formatted table.
		 * @param    $headerArray array The column headers for the information that will be shown.
		 * @return  string  Returns an html table of the invalid rows.
		 *
		 * The incoming array looks something like this (
		 * 0 'EmpXRef' => '4828',
		 * 1 'DeptXRef' => '40014',
		 * 2 'JobXRef' => 'BSMATRAVEL',
		 * 3 'PayCode' => '1',
		 * 4 'DateOfService' => '2016-05-03',
		 * 5 'HrsWorked' => '0.5',
		 * 6 'BlankField' => '',
		 * 7 'CentralReachId' => '107212',
		 * 8 'EmployeeFirstName' => 'Briana',
		 * 9 'EmployeeLastName' => 'Jacobs',
		 * 10 'ProcedureCodeString' => 'BSMATRAVEL: Reimbursable travel time',
		 * 11 'timeworkedfrom' => '2016-05-03 17:30:00',
		 * 12 'timeworkedto' => '2016-05-03 18:00:00',
		 * )
		 */
		$count = count($this->payrollHeaders);
		$ignoreFirstColumn = $count == count($this->duplicateArray) ? false : true;
		$td = '';
		$headerCount = 0;
		foreach( $this->payrollHeaders as $value ){
			if( $headerCount == 0 && $ignoreFirstColumn ){
				$headerCount++;
				continue;
			}
			$td .= '<td>' . $value . '</td>';
		}
		if( !empty($this->invalidDataArray) ){
			$output = '<p class="interfaceResponse">Some invalid data was ignored.<div class="toggleButton" style="display:inline">Click to View Invalid Data</div>
<div class="toggleMeNoOverlap">
<table><tr>' . $td . '</tr>';
			foreach( $this->invalidDataArray as $row ){
				if( !empty($row['validationErrors']) ){
					$output .= '<tr><td colspan="13" class="red">';
					$error = '';
					foreach( $row['validationErrors'] as $value ){
						$error .= $error == '' ? $value : ' ' . $value;
					}
					$output .= $error . '</td></tr>';
				}
				$output .= '<tr>';
				for( $x = 0; $x < $count; $x++ ){
					if( $row[$x] === true ){
						$temp = 'true';
					}else if( $row[$x] === false ){
						$temp = 'false';
					}else{
						$temp = $row[$x];
					}
					$output .= '<td>' . $temp . '</td>';
				}
				$output .= '</tr>';
			}
			$output .= '</table></div></p>';
			return $output;
		}else{
			return '<p class="interfaceResponse">All of the data is valid.</p>';
		}
	}

	public function getUnrecognizedJobCodes() {
		if( !empty($this->unrecognizedJobCodesArray) ){
			$output = '<p>Some entries were ignored because they have unrecognized job codes.<div class="toggleButton" style="display:inline">Click to View Ignored Entries</div>
<div class="toggleMeNoOverlap">
<table><tr>
<td>EmpXRef</td><td>DeptXRef</td><td>JobXRef</td><td>PayCode</td><td>DateOfService</td><td>HrsWorked</td><td>CentralReachId</td><td>EmployeeFirstName</td><td>EmployeeLastName</td><td>ProcedureCodeString</td><td>timeworkedfrom</td><td>timeworkedto</td></tr>';
			foreach( $this->unrecognizedJobCodesArray as $row ){
				$output .= '<tr><td>' . $row['EmpXRef'] . '</td>
		<td>' . $row['DeptXRef'] . '</td>
		<td>' . $row['JobXRef'] . '</td>
		<td>' . $row['PayCode'] . '</td>
		<td>' . $row['DateOfService'] . '</td>
		<td>' . $row['HrsWorked'] . '</td>
		<td>' . $row['CentralReachId'] . '</td>
		<td>' . $row['EmployeeFirstName'] . '</td>
		<td>' . $row['EmployeeLastName'] . '</td>
		<td>' . $row['ProcedureCodeString'] . '</td>
		<td>' . $row['timeworkedfrom'] . '</td>
		<td>' . $row['timeworkedto'] . '</td></tr>';
			}
			$output .= '</table></div></p>';
			return $output;
		}else{
			return '<p class="interfaceResponse">All entries have known job codes.</p>';
		}
	}

	public function getOverlappingEntries() {
		/**
		 * Displays the duplicate information in a nicely formatted table.
		 * @return  string  Returns html.
		 */
		if( !empty($this->overLapArray) ){
			$output = '<p class="interfaceResponse">There were some overlapping times that were ignored. These need to be manually adjusted or entries from each employee for each listed day will be excluded.<div class="toggleButtonInline">Click to View Overlapping</div>
<div class="toggleMeNoOverlap">
<table><tr>
<td>EmpXRef</td><td>JobXRef</td><td>DateOfService</td><td>EmployeeFirstName</td><td>EmployeeLastName</td><td>ProcedureCodeString</td><td>timeworkedfrom</td><td>timeworkedto</td></tr>';
			foreach( $this->overLapArray as $set => $rows ){
				$output .= '<tr><td>' . $rows[0]['EmpXRef'] . '<br>
            ' . $rows[1]['EmpXRef'] . '</td><td>' . $rows[0]['JobXRef'] . '<br>
            ' . $rows[1]['JobXRef'] . '</td><td>' . $rows[0]['DateOfService'] . '<br>
            ' . $rows[1]['DateOfService'] . '</td><td>' . $rows[0]['EmployeeFirstName'] . '<br>
            ' . $rows[1]['EmployeeFirstName'] . '</td><td>' . $rows[0]['EmployeeLastName'] . '<br>
            ' . $rows[1]['EmployeeLastName'] . '</td><td>' . $rows[0]['ProcedureCodeString'] . '<br>
            ' . $rows[1]['ProcedureCodeString'] . '</td><td>' . $rows[0]['timeworkedfrom'] . '<br>
            ' . $rows[1]['timeworkedfrom'] . '</td><td>' . $rows[0]['timeworkedto'] . '<br>
            ' . $rows[1]['timeworkedto'] . '</td></tr>';
			}
			$output .= '</table></div></p>';
			return $output;
		}else{
			return '<p class="interfaceResponse">There is no overlapping data.</p>';
		}
	}

	private function numberWithCommasToFloat($string) {
		return floatval(str_replace(",", "", $string));
	}

	protected function numberWithCommasAndDecimalToInt($string) {
		$returnThis = '';
		if( empty($string) ){
			$returnThis = 0;
		}else{
			$returnThis = intval(str_replace(",", "", $string));
		}
		return $returnThis;
	}

	public function output() {
		return $this->_output;
	}

	protected function outputFile($filename, $dataToBeWritten) {
		/**
		 * Produces a file.
		 *
		 * @param   string $outputPath     The output directory relative to the calling page.
		 * @param   string $filename       The desired name for the file. This is a parameter rather than a class property because we sometimes produce more than one file per instance of the class.
		 * @param   array $dataToBeWritten String data to be written to the file.
		 *
		 * @return  bool  Returns true upon success, otherwise false.
		 */

		try{
			// Make the outgoing directory if it doesn't exist.
			if( !file_exists($this->outgoingDirectory) && !is_dir($this->outgoingDirectory) ){
				mkdir($this->outgoingDirectory);
			}
			$handle = fopen($this->outgoingDirectory . '/' . $filename, "w+");
			$fileResult = false;
			if( !$handle ){
				throw new CustomException("Could not open the file for writing.");
			}

			// Write the processed data string to the new file
			$fileResult = fwrite($handle, $dataToBeWritten);

			// Close the new file
			fclose($handle);

			// Show result message
			if( !$fileResult ){
				throw new CustomException("Could not produce the file.");
			}
			$this->outgoingFilePath = $this->outgoingDirectory . '/' . $filename;// Path to file.
		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	public function processPayroll($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) {
		/**
		 *
		 * @return  bool  Returns true on success, otherwise false.
		 *
		 */
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		date_default_timezone_set("America/Los_Angeles");
		try{
			$this->databaseTable = $databaseTableName;
			$this->outgoingDirectory = $outgoingDirectory;

			// Get the JobXRef codes.
			if( self::getJobXRef() === false ){
				throw new CustomException('Could not get the JobXRef codes.', '');
			}

			//Truncate database. We do this first because a thrown error may prevent the database from being truncated later.
			if( self::truncateDatabase($this->databaseTable) === false ){
				throw new CustomException();
			}

			// Save the uploaded file to the save directory.
			if( self::saveIncomingFile($formFileInputName, $saveDirectory) === false ){
				throw new CustomException('Could not save the incoming file.');
			}

			// Save the data to the database.
			if( self::saveToDatabasePayroll() === false ){
				throw new CustomException('Could not save the data to the database.');
			};
		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	public function processOtherTable($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) {
		/**
		 *
		 * @return  bool  Returns true on success, otherwise false.
		 *
		 */
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		date_default_timezone_set("America/Los_Angeles");
		try{
			$this->databaseTable = $databaseTableName;
			$this->outgoingDirectory = $outgoingDirectory;

			//Truncate database. We do this first because a thrown error may prevent the database from being truncated later.
			if( self::truncateDatabase($this->databaseTable) === false ){
				throw new CustomException();
			}

			// Save the uploaded file to the save directory.
			if( self::saveIncomingFile($formFileInputName, $saveDirectory) === false ){
				throw new CustomException('Could not save the incoming file.');
			}

			// Save the data to the database.
			if( self::saveToDatabaseOtherTable() === false ){
				throw new CustomException('Could not save the data to the database.');
			};
		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	protected function saveIncomingFile($formFileInputName, $saveDirectory) {
		/**
		 * Accept the incoming file.
		 *
		 * This will save the incoming file to a specified location while checking for errors.
		 * @param    string $formFileInputName The name of the form field file input.
		 * @param    string $saveDirectory     The location of the directory where the file is to be saved. This is relative to the directory of the form.
		 * @return    bool    Returns true upon a successful save, otherwise false.
		 */
		try{
			if( empty($_FILES[$formFileInputName]) ){
				throw new CustomException('Could not load the file.', '$_FILES[\'' . $formFileInputName . '\'] does not exist.');
			}
			if( $_FILES[$formFileInputName]["error"] > 0 ){
				throw new CustomException('There was an error with the file.', "Error: " . $_FILES[$formFileInputName]["error"]);
			}
			// Store info about the selected file.
			$this->fileInfo['filename'] = $_FILES[$formFileInputName]["name"];
			$this->fileInfo['type'] = $_FILES[$formFileInputName]["type"];
			$this->fileInfo['size'] = $_FILES[$formFileInputName]["size"] / 1024;
			$this->fileInfo['directory'] = $saveDirectory;

			// Make the save directory if it doesn't exist.
			if( !file_exists($saveDirectory) && !is_dir($saveDirectory) ){
				if( !mkdir($saveDirectory) ){
					throw new CustomException('', 'Could not make the save directory.');
				}
			}
			// Move the temp file into a folder on the server
			$move_result = move_uploaded_file($_FILES[$formFileInputName]["tmp_name"], $saveDirectory . '/' . $_FILES[$formFileInputName]["name"]);
			if( !$move_result ){
				throw new CustomException('Could not move the file.', "Move from temp file failed: " . $_FILES[$formFileInputName]["error"]);
			}
		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function saveToDatabaseOtherTable() {
		/**
		 * Save the other table data to the database.
		 *
		 * @return  bool    Returns true on success, otherwise false.
		 */
		try{
			// Open the File.
			$handle = fopen($this->fileInfo['directory'] . '/' . $this->fileInfo['filename'], "r");
			if( $handle !== FALSE ){
				// The mysql query to store the data.
				$row = fgetcsv($handle, 0, ",");// This is the header row. We will use it to get a parameter count.

				// Loop through the table describe to understand the columns and their data types.
				$describeTableStmt = $this->Dbc->query('DESCRIBE ' . $this->databaseTable);
				$describeTableStmt->execute();
				$tableRows = '';
				$describeCount = 0;
				$parameters = '';
				$dataTypeArray = array();
				$noticeArray = array();
				$notice = false;
				while( $tableInfo = $describeTableStmt->fetch(PDO::FETCH_ASSOC) ){
					if( $describeCount > 0 ){ // Skip the id column.
						$this->Debug->add($tableInfo['Type'], '$tableInfo[\'Type\']');
						$dataTypeArray[$describeCount] = $tableInfo['Type'];
						$parameters .= $parameters == '' ? '?' : ', ?';
						if( $tableInfo['Type'] == 'date' || $tableInfo['Type'] == 'datetime' || $tableInfo['Type'] == 'float' || strpos($tableInfo['Type'], 'decimal') !== false ){
							$noticeArray[$describeCount - 1] = $tableInfo['Type'];
							$notice = true;
						}
					}
					$describeCount++;
				}
				$this->Debug->printArray($noticeArray, '$noticeArray');
				//$this->Debug->printArray($dataTypeArray, '$dataTypeArray');
				$insertStmt = $this->Dbc->prepare("INSERT INTO $this->databaseTable
VALUES (NULL, " . $parameters . ')');
				$rowCount = 0;
				while( ($row = fgetcsv($handle, 0, ",")) !== FALSE ){
					// Insert the record to the database.
					if( $notice ){
						foreach( $noticeArray as $key => $value ){
							if( $value == 'date' ){
								$row[$key] = Time::mysqlDate($row[$key]);
							}elseif( $value == 'datetime' ){
								$row[$key] = Time::mysqlDatetime($row[$key]);
							}elseif( $value == 'float' || strpos($value, 'decimal') !== false ){
								$temp = $row[$key];
								if( strpos($row[$key], '$') !== false ){
									$temp = self::formatCurrencyAsNumber($row[$key]);
								}else{
									$temp = self::numberWithCommasToFloat($row[$key]);
								}
								$this->Debug->add($row[$key] . ' converted to ' . $temp);
								$row[$key] = $temp;
							}
						}
					}
//					$this->Debug->printArray($row,'$row');
					if( $insertStmt->execute($row) ){
						$rowCount++;
					}
				}
				$this->Debug->add($rowCount . ' records were inserted.');
			}else{
				throw new CustomException('Could not read from the file.');
			}
		}catch( CustomPDOException $e ){
			return false;
		}catch( PDOException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( CustomException $e ){
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function saveToDatabasePayroll() {
		/**
		 * Save the data to the database.
		 *
		 * @return  bool    Returns true on success, otherwise false.
		 */
		try{
			// Open the File.
			$handle = fopen($this->fileInfo['directory'] . '/' . $this->fileInfo['filename'], "r");
			if( $handle !== FALSE ){
				// The mysql query to store the data.
				$insertStmt = $this->Dbc->prepare("INSERT INTO $this->databaseTable
SET
  EmpXRef = ?,
  DeptXRef = ?,
  JobXRef = ?,
  PayCode = ?,
  DateOfService = ?,
  HrsWorked = ?,
  BlankField = ?,
  CentralReachId = ?,
  EmployeeFirstName = ?,
  EmployeeLastName = ?,
  ProcedureCodeString = ?,
  timeworkedfrom = ?,
  timeworkedto = ?");
				$rowCount = 0;
				while( ($row = fgetcsv($handle, 0, ",")) !== FALSE ){
					// Count the total columns in the row.
					$field_count = count($row);

					if( $field_count != 13 ){
						throw new CustomException('The number of fields in the CSV file has changed since this interface was last edited. Please contact the IT Department.', 'There are ' . $field_count . ' fields in the CSV file instead of 13.');
					}
					// Ignore the header row.
					if( $row[0] !== "EmpXRef" ){
						// Validate the data
						$validatedData = self::validateIncomingCSVData($row);
						if( $validatedData === false || array_key_exists('validationErrors', $validatedData) ){
							// Handle rows that do not validate.
							$this->invalidDataArray[] = $validatedData;
						}else{
							// Insert the record to the database.
							if( $insertStmt->execute($validatedData) ){
								$rowCount++;
							}
						}
					}
				}
				$this->Debug->add($rowCount . ' records were inserted.');
			}else{
				throw new CustomException('Could not read from the file.');
			}
		}catch( CustomPDOException $e ){
			return false;
		}catch( PDOException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( CustomException $e ){
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function serveFile() {
		/**
		 * Copyright 2012 Armand Niculescu - MediaDivision.com
		 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
		 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
		 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
		 * THIS SOFTWARE IS PROVIDED BY THE FREEBSD PROJECT "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
		 *
		 * This will serve a file to the browser. It is used by sending GET information including the filePath and fileName. You should create an IFRAME like the following:
		 *
		 *        <iframe class="hiddenFileDownload" id="' . $this->_outgoingRegularHoursFileName . '" src="./ServeFile.php?mode=serveFile&fileName=' . $this->_outgoingRegularHoursFileName . '&filePath=' . $this->_regularHoursOutgoingFilePath . '"></iframe>';
		 *
		 * There is associated JavaScript that reads the class and id and will remove the IFRAME.
		 *
		 * @return  string  header
		 */
		try{
			if( empty($_REQUEST['filePath']) ){
				throw new CustomException('', '$_POST[\'filePath\'] is empty.');
			}
			if( empty($_REQUEST['fileName']) ){
				throw new CustomException('', '$_POST[\'fileName\'] is empty.');
			}
			$this->Ajax->SetSuccess(true);
			$this->Message .= 'Sending file';
			$this->Debug->add('$_REQUEST[\'filePath\']: ' . $_REQUEST['filePath'] . '$_REQUEST[\'fileName\']: ' . $_REQUEST['fileName']);
// hide notices
			//@ini_set('error_reporting', E_ALL & ~E_NOTICE);

			/*if(!isset($_REQUEST['file']) || empty($_REQUEST['file']))
			{
				header("HTTP/1.0 400 Bad Request");
				exit;
			}*/

// sanitize the file request, keep just the name and extension
// also, replaces the file location with a preset one ('./myfiles/' in this example)
			$path_parts = pathinfo($_REQUEST['filePath']);
			$file_name = $path_parts['basename'];
			$file_ext = $path_parts['extension'];
			$this->Debug->printArray($path_parts, '$path_parts');
			//die($this->Debug->output(true));

// allow a file to be streamed instead of sent as an attachment
			$is_attachment = isset($_REQUEST['stream']) ? false : true;
// make sure the file exists
			if( is_file($_REQUEST['filePath']) ){
				$file_size = filesize($_REQUEST['filePath']);
				$this->Debug->add('$file_size: ' . $file_size);
				$file = @fopen($_REQUEST['filePath'], "rb");
				if( $file ){
					if( !empty($_REQUEST['fileName']) ){
						// Use cookies to report when the files have been served
						$cookies = session_get_cookie_params();
						setcookie($_REQUEST['fileName'], 'set', time() + 90, $cookies['path'], $cookies['domain']); // expires in 90 seconds.
					}
					// set the headers, prevent caching
					header("Pragma: public");
					header("Expires: -1");
					header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
					header("Content-Disposition: attachment; filename=\"$file_name\"");

					// set appropriate headers for attachment or streamed file
					if( $is_attachment ){
						header("Content-Disposition: attachment; filename=\"$file_name\"");
					}else{
						header('Content-Disposition: inline;');
						header('Content-Transfer-Encoding: binary');
					}

					// set the mime type based on extension, add yours if needed.
					$ctype_default = "text/csv";
					$content_types = array(
						"csv" => "text/csv",
						"exe" => "application/octet-stream",
						"zip" => "application/zip",
						"mp3" => "audio/mpeg",
						"mpg" => "video/mpeg",
						"avi" => "video/x-msvideo",
					);
					$ctype = isset($content_types[$file_ext]) ? $content_types[$file_ext] : $ctype_default;
					header("Content-Type: " . $ctype);

					//check if http_range is sent by browser (or download manager)
					if( isset($_SERVER['HTTP_RANGE']) ){
						list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
						if( $size_unit == 'bytes' ){
							//multiple ranges could be specified at the same time, but for simplicity only serve the first range
							//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
							list($range, $extra_ranges) = explode(',', $range_orig, 2);
						}else{
							$range = '';
							header('HTTP/1.1 416 Requested Range Not Satisfiable');
							exit;
						}
					}else{
						$range = '';
					}
					$this->Debug->add('$range: ' . $range);
					//figure out download piece from range (if set)
					if( $range != '' ){
						list($seek_start, $seek_end) = explode('-', $range, 2);
					}

					//set start and end based on range (if set), else set defaults
					//also check for invalid ranges.
					$seek_end = (empty($seek_end)) ? ($file_size - 1) : min(abs(intval($seek_end)), ($file_size - 1));
					$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0);

					//Only send partial content header if downloading a piece of the file (IE workaround)
					if( $seek_start > 0 || $seek_end < ($file_size - 1) ){
						header('HTTP/1.1 206 Partial Content');
						header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $file_size);
						header('Content-Length: ' . ($seek_end - $seek_start + 1));
					}else
						header("Content-Length: $file_size");

					header('Accept-Ranges: bytes');

					set_time_limit(0);
					fseek($file, $seek_start);

					while( !feof($file) ){
						print(@fread($file, 1024 * 8));
						ob_flush();
						flush();
						if( connection_status() != 0 ){
							@fclose($file);
							exit;
						}
					}

					// file save was a success
					@fclose($file);
					exit;
				}else{
					// file couldn't be opened
					header("HTTP/1.0 500 Internal Server Error");
					exit;
				}
			}else{
				// file does not exist
				header("HTTP/1.0 404 Not Found");
				exit;
			}

		}catch( CustomException $e ){
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
	}

	protected function truncateDatabase($tableName) {
		/**
		 * Truncate the table.
		 * @return    bool    Returns true upon success, false otherwise.
		 */
		try{
			$truncateQuery = $this->Dbc->query('TRUNCATE TABLE ' . $tableName . ';');
			$truncateQuery->execute();
		}catch( CustomPDOException $e ){
			return false;
		}catch( PDOException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( CustomException $e ){
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function validateIncomingCSVData($row) {
		/**
		 * Check for data problems.
		 *
		 * There are a number of validations and checks performed here before the data is inserted into the database.
		 *
		 * @author  Mark O'Russa    <mark@orussa.com>
		 * @param   array $row The current row being validated.
		 * @throws  CustomException    Execution is stopped when any validation fails.
		 *
		 * @return  array|bool    Returns an array or false. If there are validation errors there is an additional 'validationMessage' key added..
		 */
		// Validation *****************************************************************************
		try{
			/*
			 * array (
				  0 'EmpXRef' => '4828',
				  1 'DeptXRef' => '40014',
				  2 'JobXRef' => 'BSMATRAVEL',
				  3 'PayCode' => '1',
				  4 'DateOfService' => '2016-05-03',
				  5 'HrsWorked' => '0.5',
				  6 'BlankField' => '',
				  7 'CentralReachId' => '107212',
				  8 'EmployeeFirstName' => 'Briana',
				  9 'EmployeeLastName' => 'Jacobs',
				  10 'ProcedureCodeString' => 'BSMATRAVEL: Reimbursable travel time',
				  11 'timeworkedfrom' => '2016-05-03 17:30:00',
				  12 'timeworkedto' => '2016-05-03 18:00:00',
				)
			 */
			// Look for missing employee ID
			$errors = array();
			if( empty($row[0]) || preg_match("/^\s*$/", $row[0]) === 1 ){
				$errors[] = 'An EmpXRef code is missing';
			}
			// Is EmpXRef numeric?
			if( !is_numeric($row[0]) ){
				$errors[] = 'An EmpXRef code is not numeric';
			}

			// Look for missing date of service
			if( empty($row[5]) ){
				$errors[] = 'Date of service missing';
			}
			// Is date of service a real date?
			if( !Time::isRealDate($row[4]) ){
				$errors[] = 'DateOfService is not a valid date';
			}
			// Look for missing hours worked
			if( empty($row[5]) ){
				$errors[] = 'Hours worked missing';
			}
			// Is ProcedureCodeString inside 256 characters?
			if( strlen($row[10]) > 256 ){
				$errors[] = 'The ProcedureCodeString is longer than the maximum 256 characters allowed in the database field';
			}
			// Is timeworkedfrom a real date?
			if( !Time::isRealDate($row[11]) ){
				$errors[] = 'timeworkedfrom is not a valid date';
			}
			// Is timeworkedfrom a real date?
			if( !Time::isRealDate($row[12]) ){
				$errors[] = 'timeworkedto is not a valid date';
			}
			// Look for missing punches
			if( empty($row[11]) || empty($row[12]) ){
				$errors[] = 'Punch time missing';
			}
			// Format data for database storage. Dates should be date or datetime compatible.
			$dos = Time::convertToDateTime($row[4]);
			$row[4] = Time::mysqlDate($dos);

			$timeworkedfrom = Time::convertToDateTime($row[11]);
			$row[11] = Time::mysqlDatetime($timeworkedfrom);
			if( $row[11] == false ){
				$errors[] = 'timeworkedfrom is invalid';
			}
			$timeworkedto = Time::convertToDateTime($row[12]);
			$row[12] = Time::mysqlDatetime($timeworkedto);
			if( $row[12] == false ){
				$errors[] = 'timeworkedto is invalid';
			}
			if( !empty($errors) ){
				$row['validationErrors'] = $errors;
			}
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return $row;
	}
}