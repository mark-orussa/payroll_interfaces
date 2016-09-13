<?php

/**
 * Created by PhpStorm.
 * User: Mark O'Russa
 * Date: 5/9/2016
 * Time.php: 3:38 PM
 *
 * This interface is based off of code written by Tim McGee for other interfaces.
 * It is designed to accept CSV files from Imagine CentralReach and return modified CSV files for the ADP payroll system.
 * Two CSV files are produced at this time: regular hours and travel. They each have a specific format they must meet.
 * The Regular CSV has a header of : Employee ID,Date,Time,Job Code
 * The Travel CSV has a header of: Employee ID,Date,Hours Worked
 *
 * Sample CSV files can be found in a zip folder.
 * Better instructions can also be found in a file called "Imagine_CentralReach_To_ADP_Interface_Coding_Instructions.pdf"
 * This application uses a database to temporarily store and select data.
 */
Class ImagineCentralReachToAdp extends PayrollInterface {
	/*
	  * Original header:
	  0 EmpXRef
	  1 DeptXRef
	  2 JobXRef
	  3 PayCode
	  4 DateOfService
	  5 HrsWorked
	  6 BlankField
	  7 CentralReachId
	  8 EmployeeFirstName
	  9 EmployeeLastName
	  10ProcedureCodeString
	  11 timeworkedfrom
	  12 timeworkedto

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
	12 'timeworkedto' => '2016-05-03 18:00:00'
	*/

	// Properties
	private $_outgoingRegularHoursFileName;
	private $_outgoingTravelHoursFileName;
	private $_regularHoursOutgoingFilePath;
	private $_travelHoursOutgoingFilePath;

	private $_level1Emp;
	private $_level1EmpJobXRef;
	private $_level2Emp;
	private $_level2EmpJobXRef;
	private $_travelCodes;
	private $_specialCases;
	private $_currentRow;
	private $_checkRow;
	private $_regularHours;
	private $_travelHours;

	public function __construct($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) {
		/**
		 *
		 * @param    string $inputFileId   The id of the form input file element.
		 * @param    string $saveDirectory The location to store the file. Due to http protocol limitations this must be relative to the document receiving the file (i.e. './uploads').
		 * @param    string $this          ->_outgoingDirectory The location to store the output CSV file(s). Due to http protocol limitations this must be relative to the document receiving the file (i.e. './downloads').
		 * @param    string $tableName     The name of the temporary database table.
		 * @return    bool|string  Returns true, otherwise a string message. Use === true to verify success.
		 *
		 */

		try{
			parent::__construct();
			if( parent::processPayroll($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) === false ){
				throw new CustomException('', 'The parent init method returned false.');
			}
			// These employees need their JobXRef codes modified with a 1 or 2 at the end for the specific JobXRef codes below.
			$this->_level1Emp = array();
			if( self::getLevel1Emp() === false ){
				throw new CustomException('Could not get the level 1 Master Level EmpXRef codes.');
			}
			$this->_level1EmpJobXRef = array('BSMABILL', 'BSMANONBILL', 'BSMAOB', 'BSPHDBILL', 'BSPHDNONBILL', 'BSPHDOB');
			$this->_level2Emp = array();
			if( self::getLevel2Emp() === false ){
				throw new CustomException('Could not get the level 2 Master Level EmpXRef codes.');
			}

			$this->_level2EmpJobXRef = array('BSMABILL', 'BSMANONBILL');
			/*$jobXRefOld = array('BSMABILL1', 'BSMANONBILL1', 'BSMAOB1', 'BSMABILL2', 'BSMANONBILL2', 'BSMAOB2', 'BSMABILL', 'BSMANONBILL', 'BSMAOB', 'BSPHDBILL1', 'BSPHDNONBILL1', 'BSPHDOB1', 'BSPHDBILL', 'BSPHDNONBILL', 'BSPHDOB', 'BTMABILL', 'BTMANONBILL', 'BTBABILL', 'BTBANONBILL', 'BTHSBILL', 'BTHSNONBILL', 'BSMATRAVEL', 'BSPHDTRAVEL', 'BTMATRAVEL', 'BTBATRAVEL', 'BTHSTRAVEL', 'PDMGMT', 'CDMGMT2');*/

			/*
			 * Special cases:
			 * Remove all job codes for Jennifer Collado (EmpXRef 100717)
			 */
			$this->_specialCases = array(100717);

			// Travel codes to separate them from regular hours.
			$this->_travelCodes = array('BSMATRAVEL', 'BSPHDTRAVEL', 'BTMATRAVEL', 'BTBATRAVEL', 'BTHSTRAVEL');

			// The outgoing CSV filenames are generated based on the data.
			$this->_outgoingRegularHoursFileName = '';
			$this->_outgoingTravelHoursFileName = '';

			// Perform the data manipulations on regular hours and return a string.
			if( self::getRegularHours() === false ){
				throw new CustomException('Could not get the regular hours.');
			}

			if( self::outputFile($this->_outgoingRegularHoursFileName, $this->_regularHours) === false ){
				throw new CustomException('', 'outputFile() returned false for regular hours.');
			}

			$this->_regularHoursOutgoingFilePath = $this->_outgoingFilePath;

			// Perform the data manipulations on travel hours and return a string.
			if( self::getTravelHours() === false ){
				throw new CustomException('Could not get the travel hours.');
			}

			if( self::outputFile($this->_outgoingTravelHoursFileName, $this->_travelHours) === false ){
				throw new CustomException('', 'outputFile() returned false for travel hours.');
			}
			$this->_travelHoursOutgoingFilePath = $this->_outgoingFilePath;
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	public function getOutgoingRegularFileButton() {
		return '<div>The file <em>' . $this->_outgoingRegularHoursFileName . '</em> will automatically download. Check the download location.</div>
		<iframe class="hiddenFileDownload" id="' . $this->_outgoingRegularHoursFileName . '" src="./ServeFile.php?mode=serveFile&fileName=' . $this->_outgoingRegularHoursFileName . '&filePath=' . $this->_regularHoursOutgoingFilePath . '"></iframe>';
	}

	public function getOutgoingTravelFileButton() {
		return '<div>The file <em>' . $this->_outgoingTravelHoursFileName . '</em> will automatically download. Check the download location.</div>
		<iframe class="hiddenFileDownload" id="' . $this->_outgoingTravelHoursFileName . '" src="./ServeFile.php?mode=serveFile&fileName=' . $this->_outgoingTravelHoursFileName . '&filePath=' . $this->_travelHoursOutgoingFilePath . '"></iframe>';
	}

	private function getLevel1Emp() {
		// Get the level 1 employees who will have specific job codes modified.
		try{
			$selectLevel1EmpXRef = $this->_Dbc->query("SELECT EmpXRef FROM
	master_level_empxref
WHERE
	level = 1");
			$selectLevel1EmpXRef->execute();
			while( $row = $selectLevel1EmpXRef->fetch(PDO::FETCH_ASSOC) ){
				$this->_level1Emp[] = $row['EmpXRef'];
			}
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function getLevel2Emp() {
		// Get the level 2 employees who will have specific job codes modified.
		try{
			$selectLevel2EmpXRef = $this->_Dbc->query("SELECT EmpXRef FROM
	master_level_empxref
WHERE
	level = 2");
			$selectLevel2EmpXRef->execute();
			while( $row = $selectLevel2EmpXRef->fetch(PDO::FETCH_ASSOC) ){
				$this->_level2Emp[] = $row['EmpXRef'];
			}
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function getRegularHours() {
		/**
		 * Read the regular hours from the database and put them in an array.
		 *
		 * @return  string|bool   Returns a string in CSV format, otherwise false.
		 *
		 */
		try{
			// Build the travelcode parameters so we can ignore travel entries.
			$travelCodesCount = count($this->_travelCodes);
			$travelCodesString = '';
			for( $x = 0; $x < $travelCodesCount; $x++ ){
				$travelCodesString .= $travelCodesString == '' ? '?' : ',?';
			}

			// Select regular hours sorted by EmpXRef and timeworkedfrom
			$selectQuery = $this->_Dbc->prepare("SELECT * FROM
  $this->_databaseTable
  WHERE JobXRef NOT IN ($travelCodesString) AND
  EmpXRef NOT IN (SELECT EmpXRef from empxref)
ORDER BY EmpXRef ASC,timeworkedfrom ASC");
			$selectQuery->execute($this->_travelCodes);
			$returnArray = array();
			$this->_checkRow = array();
			$latestDate = '';
			$this->_regularHours = "Employee ID,Date,Time,Job Code\n";
			$this->_Debug->printArray($this->_jobXRefArray, '$this->_jobXRefArray');
			while( $row = $selectQuery->fetch(PDO::FETCH_ASSOC) ){
				$this->_currentRow = $row;
				if( self::modifyRegularHours() === true ){
					// Data modification is good. Do something.
					$latestDate = strtotime($latestDate) > strtotime($row['DateOfService']) ? $latestDate : $row['DateOfService'];
					$returnArray[] = $row;
					$this->_checkRow = $row;
				}
			}
			if( empty($returnArray) ){
				throw  new CustomException('No regular hours were found.');
			}
			$this->_Debug->add('Number of returned rows: ' . $selectQuery->rowCount() . ' on line ' . __LINE__ . '.');
			$latestDateDT = Time::convertToDateTime($latestDate);
			$week = $latestDateDT->format('W');
			$year = $latestDateDT->format('Y');

			// Get the last day of the week (Saturday)
			$latestDateDT->setISODate($year, $week, 6);

			// Build name for the output file. We will append the date of the last day of the week.
			$this->_outgoingRegularHoursFileName = 'Embassy_CentralReach_Imagine_' . $latestDateDT->format('Ymd') . '.csv';

		}catch( CustomPDOException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( PDOException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function getTravelHours() {
		/**
		 * Selects travel hours from the database.
		 * @return  array|bool  Returns an array of travel data, otherwise false.
		 */

		try{
			$travelCodesCount = count($this->_travelCodes);
			$travelCodesString = '';
			for( $x = 0; $x < $travelCodesCount; $x++ ){
				$travelCodesString .= $travelCodesString == '' ? '?' : ',?';
			}

			// Select regular hours from database sorted by EmpXRef and timeworkedfrom
			$query = "SELECT *,
SUM(HrsWorked) SumHours
FROM
  $this->_databaseTable
  WHERE JobXRef IN ($travelCodesString) AND
  EmpXRef NOT IN (SELECT EmpXRef from empxref)
  GROUP BY EmpXRef, DateOfService
ORDER BY EmpXRef ASC,timeworkedfrom ASC";
			$selectQuery = $this->_Dbc->prepare($query);
			$selectQuery->execute($this->_travelCodes);
			$this->_checkRow = '';
			$latestDate = '';
			$this->_travelHours = "Employee ID,Date,Hours Worked\n";
			$foundRows = false;
			// Add records to an output variable.
			while( $row = $selectQuery->fetch(PDO::FETCH_ASSOC) ){
				$this->_currentRow = $row;
				if( self::modifyTravelHours() === true ){
					// Data modification is good. Do something.
					$latestDate = strtotime($latestDate) > strtotime($row['DateOfService']) ? $latestDate : $row['DateOfService'];
					$this->_checkRow = $row;
					$foundRows = true;
				}
			}
			if( !$foundRows ){
				throw  new CustomException('No travel hours were found.');
			}
			$this->_Debug->add('Number of returned rows: ' . $selectQuery->rowCount() . ' on line ' . __LINE__ . '.');
			$latestDateDT = Time::convertToDateTime($latestDate);
			$week = $latestDateDT->format('W');
			$year = $latestDateDT->format('Y');
			// Get the last day of the week (Saturday)
			$latestDateDT->setISODate($year, $week, 6);

			// Build name for the output file.
			$this->_outgoingTravelHoursFileName = 'Embassy_CentralReach_Imagine_Travel_' . $latestDateDT->format('Ymd') . '.csv';
		}catch( CustomPDOException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( PDOException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function modifyRegularHours() {
		/**
		 * Modify the regular hours data.
		 *
		 * Certain fields need modification to meet the requirements of the ADP payroll system.
		 *
		 * @author  Mark O'Russa    <mark@orussa.com>
		 * @param   array $this ->_checkRow The last row modified.
		 * @param   array $this ->_currentRow  The current row being modified.
		 * @throws  CustomException    Execution is stopped when any modification fails.
		 *
		 * @return  array|bool    Returns a modified array if there are no problems, FALSE if there are.
		 */
		/*
		 * The incoming array looks like this (
			  0 'id' => '309',
			  1 'EmpXRef' => '4828',
			  2 'DeptXRef' => '40014',
			  3 'JobXRef' => 'BSMATRAVEL',
			  4 'PayCode' => '1',
			  5 'DateOfService' => '2016-05-03',
			  6 'HrsWorked' => '0.5',
			  7 'BlankField' => '',
			  8 'CentralReachId' => '107212',
			  9 'EmployeeFirstName' => 'Briana',
			  10 'EmployeeLastName' => 'Jacobs',
			  11 'ProcedureCodeString' => 'BSMATRAVEL: Reimbursable travel time',
			  12 'timeworkedfrom' => '2016-05-03 17:30:00',
			  13 'timeworkedto' => '2016-05-03 18:00:00',
			)

		The outgoing array looks like this (
			  0 'id' => '309',
			  1 'EmpXRef' => '4828',
			  2 'DeptXRef' => '40014',
			  3 'JobXRef' => 'BSMATRAVEL',
			  4 'PayCode' => '1',
			  5 'DateOfService' => '2016-05-03',
			  6 'HrsWorked' => '0.5',
			  7 'BlankField' => '',
			  8 'CentralReachId' => '107212',
			  9 'EmployeeFirstName' => 'Briana',
			  10 'EmployeeLastName' => 'Jacobs',
			  11 'ProcedureCodeString' => 'BSMATRAVEL: Reimbursable travel time',
			  12 'timeworkedfrom' => '2016-05-03 17:30:00',
			  13 'timeworkedto' => '2016-05-03 18:00:00',
			  14 'Employee ID' => 4828,
			  15 'Date' => '2016-05-03',
			  16 'inPunch' => '5:30 PM',
			  17 'outPunch' => '7:30 PM',
			  18 'Job Code' => '//////0611'
			)
		 */
		try{
			// Check for employee exceptions. These are employees compensated at different rates for the same JobXRef codes. Their JobXRef descriptions (i.e. BSMABILL1) are indicated by the addition of a 1 or 2 at the end of the description. The job code (i.e. //////0663) may be the same as the base description, but should be checked any way.
			$this->_Success = true;
			// Level 1 and 2 employees.
			if( in_array((Int)$this->_currentRow['EmpXRef'], $this->_level1Emp) && in_array($this->_currentRow['JobXRef'], $this->_level1EmpJobXRef) ){
				$this->_currentRow['JobXRef'] = $this->_currentRow['JobXRef'] . '1';
			}
			if( in_array($this->_currentRow['EmpXRef'], $this->_level2Emp) && in_array($this->_currentRow['JobXRef'], $this->_level2EmpJobXRef) ){
				$this->_currentRow['JobXRef'] = $this->_currentRow['JobXRef'] . '2';
			}

			// Look for special cases
			if( in_array($this->_currentRow['EmpXRef'], $this->_specialCases) ){
				$this->_currentRow['JobXRef'] = '';
				$this->_currentRow['Job Code'] = '';
			}elseif( empty($this->_jobXRefArray[$this->_currentRow['JobXRef']]) ){
				// This is an unrecognized job code.
				$this->_unrecognizedJobCodesArray[] = $this->_currentRow;
			}else{
				$this->_currentRow['Job Code'] = '//////' . $this->_jobXRefArray[$this->_currentRow['JobXRef']];
			}

			// Create the new values destined for output.
			$earlierDatetime = '';
			if( !empty($this->_checkRow) && array_key_exists('timeworkedto', $this->_checkRow) ){
				$earlierDatetime = is_array($this->_checkRow) ? Time::convertToDateTime($this->_checkRow['timeworkedto']) : '';
			}

			$laterDatetime = Time::convertToDateTime($this->_currentRow['timeworkedfrom']);

			// We need Date, In Punch, and Out Punch.
			$dateFormat = 'm/d/Y'; // 5/7/2016
			$timeFormat = 'g:i A'; // 1:30 PM

			$this->_currentRow['EmpXRef'] = str_pad($this->_currentRow['EmpXRef'], 6, '0', STR_PAD_LEFT);// Make all ExmXRef 6 digits
			$this->_currentRow['Employee ID'] = $this->_currentRow['EmpXRef'];

			// Format date.
			$this->_currentRow['Date'] = date($dateFormat, strtotime($this->_currentRow['DateOfService']));

			// Check for duplicate and overlapping punches ******************************************************************
			if( !empty($this->_checkRow) && $this->_checkRow['EmpXRef'] == $this->_currentRow['EmpXRef'] && $this->_checkRow['DateOfService'] == $this->_currentRow['DateOfService'] ){
				// Look for duplicate rows
				if( $this->_checkRow['ProcedureCodeString'] == $this->_currentRow['ProcedureCodeString'] && $this->_checkRow['timeworkedfrom'] == $this->_currentRow['timeworkedfrom'] && $this->_checkRow['timeworkedto'] == $this->_currentRow['timeworkedto'] ){
					// Don't write these rows to the output file and move on to the next row
					$this->_duplicateArray[] = array($this->_checkRow, $this->_currentRow);
					$this->_Debug->add('found duplicate data');
					$this->_Success = false;
				}

				/**
				 * Look for overlapping times
				 * We must check for a few things here.
				 * - the dates being compared must be the same day.
				 * - the EmpXRef must be the same.
				 * - the earlier date cannot end after the start of the later date.
				 */

				if( $earlierDatetime == $laterDatetime ){
					// Add a minute to avoid overlap.
					$laterDatetime->add(new DateInterval('PT1M'));
				}elseif( $earlierDatetime > $laterDatetime ){
//                    $this->_Debug->add('Difference is ' . $difference);
					// Do not throw an exception for overlaps. Add them to a separate record.
					$this->_overLapArray[] = array($this->_checkRow, $this->_currentRow);
				}
			}
			// Format punch in time
			$this->_currentRow['inPunch'] = $laterDatetime->format($timeFormat);

			// Format punch out time
			$this->_currentRow['outPunch'] = date($timeFormat, strtotime($this->_currentRow['timeworkedto']));
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		if( $this->_Success ){
			$this->_regularHours .= $this->_currentRow['Employee ID'] . ',' . $this->_currentRow['Date'] . ',' . $this->_currentRow['inPunch'] . ',' . $this->_currentRow['Job Code'] . "\n" . $this->_currentRow['Employee ID'] . ',' . $this->_currentRow['Date'] . ',' . $this->_currentRow['outPunch'] . "\n";
			return true;
		}else{
			return false;
		}
	}

	private function modifyTravelHours() {
		/**
		 * Modify the regular hours data.
		 *
		 * Certain fields need modification to meet the requirements of the ADP payroll system.
		 *
		 * @author  Mark O'Russa    <mark@orussa.com>
		 * @param   array $this ->_checkRow The last row modified.
		 * @param   array $this ->_currentRow  The current row being modified.
		 *
		 * @return  array|bool    Returns a modified array if there are no problems, FALSE if there are.
		 */

		/*
		 * The incoming array looks like this (
			  0 'id' => '309',
			  1 'EmpXRef' => '4828',
			  2 'DeptXRef' => '40014',
			  3 'JobXRef' => 'BSMATRAVEL',
			  4 'PayCode' => '1',
			  5 'DateOfService' => '2016-05-03',
			  6 'HrsWorked' => '0.5',
			  7 'BlankField' => '',
			  8 'CentralReachId' => '107212',
			  9 'EmployeeFirstName' => 'Briana',
			  10 'EmployeeLastName' => 'Jacobs',
			  11 'ProcedureCodeString' => 'BSMATRAVEL: Reimbursable travel time',
			  12 'timeworkedfrom' => '2016-05-03 17:30:00',
			  13 'timeworkedto' => '2016-05-03 18:00:00',
			)

		The outgoing array looks like this (
			  0 'id' => '309',
			  1 'EmpXRef' => '4828',
			  2 'DeptXRef' => '40014',
			  3 'JobXRef' => 'BSMATRAVEL',
			  4 'PayCode' => '1',
			  5 'DateOfService' => '2016-05-03',
			  6 'HrsWorked' => '0.5',
			  7 'BlankField' => '',
			  8 'CentralReachId' => '107212',
			  9 'EmployeeFirstName' => 'Briana',
			  10 'EmployeeLastName' => 'Jacobs',
			  11 'ProcedureCodeString' => 'BSMATRAVEL: Reimbursable travel time',
			  12 'timeworkedfrom' => '2016-05-03 17:30:00',
			  13 'timeworkedto' => '2016-05-03 18:00:00',
			  14 'Employee ID' => 4828,
			  15 'Date' => '2016-05-03',
			  16 'inPunch' => '5:30 PM',
			  17 'outPunch' => '7:30 PM',
			  18 'Job Code' => '//////0611'
			)
		 */
		try{
			$this->_Success = true;
			// Create the new values destined for output.
			$earlierDatetime = '';
			if( !empty($this->_checkRow) && array_key_exists('timeworkedto', $this->_checkRow) ){
				$earlierDatetime = is_array($this->_checkRow) ? Time::convertToDateTime($this->_checkRow['timeworkedto']) : '';
			}
			$laterDatetime = Time::convertToDateTime($this->_currentRow['timeworkedfrom']);

			// We need Date, In Punch, and Out Punch.
			$dateFormat = 'm/d/Y'; // 5/7/2016
			$timeFormat = 'g:i A'; // 1:30 PM

			$this->_currentRow['EmpXRef'] = str_pad($this->_currentRow['EmpXRef'], 6, '0', STR_PAD_LEFT);// Make all ExmXRef 6 digits
			$this->_currentRow['Employee ID'] = $this->_currentRow['EmpXRef'];

			// Format date.
			$this->_currentRow['Date'] = date($dateFormat, strtotime($this->_currentRow['DateOfService']));

			$this->_currentRow['Hours Worked'] = $this->_currentRow['SumHours'];

			// Check for duplicate and overlapping punches ******************************************************************
			if( !empty($this->_checkRow) && $this->_checkRow['EmpXRef'] == $this->_currentRow['EmpXRef'] && $this->_checkRow['DateOfService'] == $this->_currentRow['DateOfService'] ){
				// Look for duplicate rows
				if( $this->_checkRow['ProcedureCodeString'] == $this->_currentRow['ProcedureCodeString'] && $this->_checkRow['timeworkedfrom'] == $this->_currentRow['timeworkedfrom'] && $this->_checkRow['timeworkedto'] == $this->_currentRow['timeworkedto'] ){
					// Don't write these rows to the output file and move on to the next row
					$this->_duplicateArray[] = array($this->_checkRow, $this->_currentRow);
					$this->_Success = false;
				}

				// Look for overlapping times
				/*
				 * We must check for a few instances here.
				 * The dates being compared must be the same day.
				 * The EmpXRef must be the same.
				 * The earlier date cannot end after the start of the later date.
				 * The difference must be greater than one minute.
				 */

				if( $earlierDatetime == $laterDatetime ){
					// Add a minute to avoid overlap.
					$laterDatetime->add(new DateInterval('PT1M'));
				}elseif( $earlierDatetime > $laterDatetime ){
//                    $this->_Debug->add('Difference is ' . $difference);
					// Do not throw an exception for overlaps. Add them to a separate record.
					$this->_overLapArray[] = array($this->_checkRow, $this->_currentRow);
				}
			}
			// Format punch in time
			$this->_currentRow['inPunch'] = $laterDatetime->format($timeFormat);

			// Format punch out time
			$this->_currentRow['outPunch'] = date($timeFormat, strtotime($this->_currentRow['timeworkedto']));

		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		if( $this->_Success ){
			$this->_travelHours .= $this->_currentRow['Employee ID'] . ',' . $this->_currentRow['Date'] . ',' . $this->_currentRow['Hours Worked'] . "\n";
			return true;
		}else{
			return false;
		}

	}
}