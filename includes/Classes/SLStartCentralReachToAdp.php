<?php

/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 5/31/2016
 * Time: 5:11 PM
 *
 * This interface is designed to accept CSV files from SLStart Central Reach and return a modified CSV file for the ADP payroll system.
 *
 * Sample CSV files can be found in a zip folder.
 * Better instructions can be found in a file called "SLStart_CentralReach_To_ADP_Interface_Coding_Instructions.pdf"
 */
class SLStartCentralReachToAdp extends PayrollInterface {
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
	private $_output;

	private $_outgoingHoursFileName;
	private $_hours;
	private $_specialCases;
	private $_currentRow;
	private $_pendingRow;
	private $_latestDateDatetime;
	private $_timeFormat;
	private $_dateFormat;
	private $_hoursGroup;
	private $_regularHoursGroup;
	private $_nonbillHoursGroup;
	private $_count;

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
			$this->_latestDateDatetime = Time::convertToDateTime('2000-01-01');
			$this->_timeFormat = 'g:i A'; // 1:30 PM
			$this->_dateFormat = 'm/d/Y'; // 5/7/2016
			$this->_hoursGroup = array();
			$this->_regularHoursGroup = array();
			$this->_nonbillHoursGroup = array();
			$this->_count = 0;

			if( parent::processPayroll($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) === false ){
				throw new CustomException('', 'The parent init method returned false.');
			}
			// Special cases: adjust the billing codes for these employees.
			$this->_specialCases = array(4602, 101639);

			// The outgoing CSV filenames are generated based on the data.
			$this->_outgoingHoursFileName = '';

			// Perform the data manipulations on regular hours and return a string.
			if( self::getHours() === false ){
				throw new CustomException('Could not get the hours.');
			}

			if( self::outputFile($this->_outgoingHoursFileName, $this->_hours) === false ){
				throw new CustomException('', 'outputFile() returned false.');
			};
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', $e);
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
		}
	}

	private function addHours($rowArray) {
		/**
		 * Add to the hours property that will become the CSV file.
		 * @param array $rowArray The row array with hours information.
		 */

		try{
			// Perform some work on the data.
			$empXRef = str_pad($rowArray['EmpXRef'], 6, '0', STR_PAD_LEFT);// Make the ExpXRef 6 digits by padding it with zeroes.
			$jobCode = '//////' . $this->_jobXRefArray[$rowArray['JobXRef']];
			$date = Time::convertToDateTime($rowArray['DateOfService'])->format($this->_dateFormat);
			$inPunch = $rowArray['inDatetime']->format($this->_timeFormat);
			$outPunch = $rowArray['outDatetime']->format($this->_timeFormat);

			$this->_hours .= $empXRef . ',' . $date . ',' . $inPunch . ',' . $jobCode . "\n" . $empXRef . ',' . $date . ',' . $outPunch . "\n";
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->_Debug->error(__LINE__, '', '<pre>' . $e . '</pre>');
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function addToTypeArray($array) {
		// Add NONBILL status.
		if( strstr($array['JobXRef'], 'NONBILL') !== false || strstr($array['ProcedureCodeString'], 'Non-Bill') !== false ){
			// NONBILL hours.
			$this->_nonbillHoursGroup[] = $array;
			$this->_currentRow['NONBILL'] = true;
		}else{
			// Regular hours.
			if( in_array($array['EmpXRef'], $this->_specialCases) ){
				$array['JobXRef'] = 'HICHILDPRO';
				$this->_currentRow['JobXRef'] = 'HICHILDPRO';
			}
			$this->_regularHoursGroup[] = $array;
			$this->_currentRow['NONBILL'] = false;
		}
	}

	public function getOutgoingFile() {
		/*
		 * This method produces an iframe used to download a file.
		 */
		return '<div>The file <em>' . $this->_outgoingHoursFileName . '</em> will automatically download. Check the download location.</div>
		<iframe class="hiddenFileDownload" id="' . $this->_outgoingHoursFileName . '" src="./ServeFile.php?mode=serveFile&fileName=' . $this->_outgoingHoursFileName . '&filePath=' . $this->_outgoingFilePath . '"></iframe>';
	}

	private function getHours() {
		/**
		 * Read the hours from the database and produce a string formatted for CSV output. The string is stored in $_hours.
		 *
		 * @return  bool   Returns true upon success, otherwise false.
		 *
		 */
		try{
			// Select regular hours sorted by EmpXRef and timeworkedfrom
			$selectQuery = $this->_Dbc->query("SELECT * FROM
  $this->_databaseTable
  WHERE EmpXRef NOT IN (SELECT EmpXRef from empxref)
ORDER BY EmpXRef ASC,timeworkedfrom ASC");
			$selectQuery->execute();
			$this->_hours = "Employee ID,Date,Time,Job Code\n";
			$rowsFound = false;
			while( $row = $selectQuery->fetch(PDO::FETCH_ASSOC) ){
				$this->_currentRow = $row;
				if( self::modifyHours() === true ){
					$rowsFound = true;
				}
			}

			if( $rowsFound === false ){
				throw  new CustomException('No hours were found.');
			}
			$this->_Debug->add('Number of returned rows: ' . $selectQuery->rowCount() . ' on line ' . __LINE__ . '.');
			$week = $this->_latestDateDatetime->format('W');
			$year = $this->_latestDateDatetime->format('Y');

			// Get the last day of the week (Saturday)
			$this->_latestDateDatetime->setISODate($year, $week, 6);

			// Build name for the output file
			$this->_outgoingHoursFileName = 'Embassy_CentralReach_SLS_' . $this->_latestDateDatetime->format('Ymd') . '.csv';

		}catch( CustomPDOException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( PDOException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( CustomException $e ){
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

	private function modifyHours() {
		/**
		 * Modify the hours data.
		 *
		 * Certain fields need modification to meet the requirements of the ADP payroll system.
		 * We will divide the hours into groups of (EmpXRef and DateOfService, NONBILL and regular hours).
		 * Meaning for every EmpXRef, each day they worked will have two groups: NONBILL and regular hours.
		 *
		 * @author  Mark O'Russa    <mark@orussa.com>
		 * @throws  CustomException    Execution is stopped when some modifications fails.
		 * @throws  Exception    Execution is stopped upon other failures.
		 * @return  bool    Returns true if there are no problems, otherwise false.
		 */

		try{
			// Look for special cases
			if($this->_currentRow['EmpXRef'] == 4602 && in_array($this->_currentRow['EmpXRef'], $this->_specialCases) && $this->_currentRow['EmpXRef'] != 'CSMGMT' ){
				if( $this->_currentRow['JobXRef'] == 'CSCHILDPRO' ){
					$this->_currentRow['JobXRef'] = 'HICHILDPRO';
				}elseif( $this->_currentRow['JobXRef'] == 'CSADULTPARA' ){
					$this->_currentRow['JobXRef'] = 'CSMGMT';
				}
			}elseif( $this->_currentRow['EmpXRef'] == 101639 ){
				if( $this->_currentRow['JobXRef'] == 'HSCHILDPRO' ){
					$this->_currentRow['JobXRef'] = 'HICHILDPRO';
				}
			}
			// Look for unrecognized job codes.
			if( empty($this->_jobXRefArray[$this->_currentRow['JobXRef']]) ){
				$this->_unrecognizedJobCodesArray[] = $this->_currentRow;
			}else{
				$this->_currentRow['Job Code'] = $this->_jobXRefArray[$this->_currentRow['JobXRef']];
			}

			$this->_currentRow['inDatetime'] = Time::convertToDateTime($this->_currentRow['timeworkedfrom']);
			$this->_currentRow['outDatetime'] = Time::convertToDateTime($this->_currentRow['timeworkedto']);

			if( empty($this->_hoursGroup) ){
				// Add new information to hoursGroup.
				$this->_hoursGroup[] = $this->_currentRow;
				self::addToTypeArray($this->_currentRow);
			}elseif( $this->_hoursGroup[0]['DateOfService'] == $this->_currentRow['DateOfService'] && $this->_hoursGroup[0]['EmpXRef'] == $this->_currentRow['EmpXRef'] ){
				// Same EmpXRef and DateOfService. Add to the arrays.
				$this->_hoursGroup[] = $this->_currentRow;
				self::addToTypeArray($this->_currentRow);
			}else{
				// Process then empty array groups.
				if( !empty($this->_regularHoursGroup) || !empty($this->_nonbillHoursGroup) ){
					self::splitHours();
				}
				// Reset arrays.
				$this->_hoursGroup = array($this->_currentRow);
				$this->_nonbillHoursGroup = array();
				$this->_regularHoursGroup = array();
				self::addToTypeArray($this->_currentRow);
			}
		}catch( CustomException $e ){
			return false;
		}catch
		( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
		// Sanity check.
		/*if( $this->_count == 4 ){
			$this->_Debug->add('<pre>' . $this->_hours . '</pre>');
			die($this->_Debug->output(true));
		}
		$this->_count++;*/
		return true;
	}

	private function splitHours() {
		/*
		 * Check for overlap between the regular and nonbill hours for this employee for this DateOfService.
		 * If any overlap is found the execution of the loop stops and nothing is entered for this employee for this DateOfService.
		 */
		try{
			$count = count($this->_nonbillHoursGroup);
			$typeOverlap = false;
			if( $count > 0 ){
				// Check overlap between NONBILL and regular hours.
				for( $x = 0; $x < $count; $x++ ){
					foreach( $this->_regularHoursGroup as $entry ){
						// Check every NONBILL entry against every regular entry.
						if(
							($this->_nonbillHoursGroup[$x]['inDatetime'] > $entry['inDatetime'] && $this->_nonbillHoursGroup[$x]['inDatetime'] < $entry['outDatetime'])
							||
							($entry['inDatetime'] > $this->_nonbillHoursGroup[$x]['inDatetime'] && $entry['inDatetime'] < $this->_nonbillHoursGroup[$x]['outDatetime'])
						){
							// NONBILL hours overlap regular hours.
							$typeOverlap = true;
							$this->_overLapArray[] = array($this->_nonbillHoursGroup[$x], $entry);
							break;// Do not go any further. Report the overlap. This entire DateOfService for this employee will not be added.
						}
						$currentDatetime = Time::convertToDateTime($entry['timeworkedto']);
						$this->_latestDateDatetime = $this->_latestDateDatetime > $currentDatetime ? $this->_latestDateDatetime : $currentDatetime;
					}
					if( $typeOverlap === true ){
						break;
					}
				}
				if( $typeOverlap === false ){
					// There is no overlap between NONBILL and regular hours. Add the hours.
					self::processHours($this->_hoursGroup);
				}

			}else{
				// We have no NONBILL hours for this DateOfService. Process and add the regular hours.
				self::processHours($this->_regularHoursGroup);
			}
		}catch( CustomException $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->_Debug->error(__LINE__, '', $e);
			return false;
		}
	}

	private function processHours($array) {
		/**
		 * This is for adding a single array group of like hours (i.e. NONBILL or regular hours) from a single EmpXRef on a single DateOfService.
		 * If certain conditions are met the hours will be passed to the addHours method.
		 * It will combine sequential and overlapping hours into single blocks of time.
		 *
		 * @param array $string The array of hours to be processed.
		 */

		$this->_pendingRow = '';
		foreach( $array as $entry ){
			if( empty($this->_pendingRow) ){
				$this->_pendingRow = $entry;
			}else{
				if( $entry['inDatetime'] < $this->_pendingRow['outDatetime'] && $entry['outDatetime'] >= $this->_pendingRow['outDatetime'] ){
					// They overlap. Add the contiguous time periods together.
					$this->_pendingRow['outDatetime'] = $entry['outDatetime'];
				}elseif( $entry['inDatetime'] < $this->_pendingRow['outDatetime'] && $entry['outDatetime'] < $this->_pendingRow['outDatetime'] ){
					// This is the rare situation where a time period lies inside another. Ignore it.
				}elseif( $entry['timeworkedfrom'] == $this->_pendingRow['timeworkedfrom'] && $entry['timeworkedto'] == $this->_pendingRow['timeworkedto'] ){
					// The entries are the same time period. Log it, but ignore it. This is not a problem.
					$_duplicateArray[] = array($entry,$this->_pendingRow);
				}elseif( $entry['inDatetime'] == $this->_pendingRow['outDatetime'] ){
					// Add a minute to avoid overlap.
					$entry['inDatetime']->add(new DateInterval('PT1M'));
					self::addHours($this->_pendingRow, __LINE__);
					$this->_pendingRow = $entry;
				}else{
					// There is no overlap at all.
					self::addHours($this->_pendingRow, __LINE__);
					$this->_pendingRow = $entry;
				}
			}
		}
		// Add the last pending row.
		self::addHours($this->_pendingRow, __LINE__);
	}
}