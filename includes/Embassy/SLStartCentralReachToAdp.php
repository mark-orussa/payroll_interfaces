<?php
namespace Embassy;

use DateInterval, Exception, ErrorException, PDO, PDOException;

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
	/**
	 * @hoursGroup           array This holds all of the dates of service for an employee. When the employee has no more dates of service it is reset.
	 * @regularHoursGroup    array    This holds all of the regular hours
	 */

	private $_output;

	private $outgoingHoursFileName;
	private $hours;
	private $specialCases;
	private $latestDateDatetime;
	private $timeFormat;
	private $dateFormat;
	private $hoursGroup;
	private $regularHoursGroup;
	private $nonbillHoursGroup;
	private $count;

	public function __construct($Ajax, $Dbc, $Debug, $Message, $formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) {
		/**
		 *
		 * @param    string $inputFileId   The id of the form input file element.
		 * @param    string $saveDirectory The location to store the file. Due to http protocol limitations this must be relative to the document receiving the file (i.e. './uploads').
		 * @param    string $this          ->outgoingDirectory The location to store the output CSV file(s). Due to http protocol limitations this must be relative to the document receiving the file (i.e. './downloads').
		 * @param    string $tableName     The name of the temporary database table.
		 * @return    bool|string  Returns true, otherwise a string message. Use === true to verify success.
		 *
		 */
		try{
			$Debug->newFile('includes/Embassy/SLStartCentralReachToAdp.php');
			parent::__construct($Ajax, $Dbc, $Debug, $Message);
			$this->latestDateDatetime = Time::convertToDateTime('2000-01-01');
			$this->timeFormat = 'g:i A'; // 1:30 PM
			$this->dateFormat = 'm/d/Y'; // 5/7/2016
			$this->hoursGroup = array();
			$this->regularHoursGroup = array();
			$this->nonbillHoursGroup = array();
			$this->count = 0;

			if( parent::processPayroll($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) === false ){
				throw new CustomException('', 'The parent init method returned false.');
			}
			// Special cases: adjust the billing codes for these employees.
			$this->specialCases = array(4602, 101639);

			// Perform the data manipulations on regular hours and return a string.
			if( self::getHours() === false ){
				throw new CustomException('Could not get the hours.');
			}
			$this->outgoingHoursFileName = self::buildFilename('Embassy_CentralReach_SLS_', $this->latestDateDatetime);
			if( self::outputFile($this->outgoingHoursFileName, $this->hours) === false ){
				throw new CustomException('', 'outputFile() returned false.');
			};
		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
		}
	}

	private function addHours($rowArray) {
		/**
		 * Add to the hours property that will become the CSV file. This is among the last steps in the process of modifying and adjusting hours.
		 * @param array $rowArray The row array with hours information.
		 */

		try{
			// Perform some work on the data.
			$empXRef = str_pad($rowArray['EmpXRef'], 6, '0', STR_PAD_LEFT);// Make the ExpXRef 6 digits by padding it with zeroes.
			$jobCode = '//////' . $this->jobXRefArray[$rowArray['JobXRef']];
			$date = Time::convertToDateTime($rowArray['DateOfService'])->format($this->dateFormat);
			$inPunch = $rowArray['inDatetime']->format($this->timeFormat);
			$outPunch = $rowArray['outDatetime']->format($this->timeFormat);

			$this->hours .= $empXRef . ',' . $date . ',' . $inPunch . ',' . $jobCode . "\n" . $empXRef . ',' . $date . ',' . $outPunch . "\n";
		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', '<pre>' . $e . '</pre>');
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return true;
	}

	private function buildFilename($prefix, $latestDateDatetime) {
		/**
		 * The outgoing CSV filenames are generated based on the data.
		 *
		 * @param string $prefix             The beginning of the filename.
		 * @param object $latestDateDatetime A datetime object that will be converted into a string and become part of the file name.
		 * @return string The full file name.
		 */

		$week = $latestDateDatetime->format('W');
		$year = $latestDateDatetime->format('Y');

		// Get the last day of the week (Saturday)
		$latestDateDatetime->setISODate($year, $week, 6);

		// Build name for the output file
		return $prefix . $latestDateDatetime->format('Ymd') . '.csv';
	}

	public function buildIframe() {
		/*
		 * This method produces an iframe used to produce a file.
		 */
		return '<div>The file <em>' . $this->outgoingHoursFileName . '</em> will automatically download. Check the download location.</div>
		<iframe class="hiddenFileDownload" id="' . $this->outgoingHoursFileName . '" src="./ServeFile.php?mode=serveFile&fileName=' . $this->outgoingHoursFileName . '&filePath=' . $this->outgoingFilePath . '"></iframe>';
	}

	private function getHours() {
		/**
		 * Read the hours from the database and produce a string formatted for CSV output. The string is stored in $hours.
		 *
		 * @return  bool   Returns true upon success, otherwise false.
		 *
		 */
		try{
			// Select regular hours sorted by EmpXRef and timeworkedfrom
			$selectQuery = $this->Dbc->query("SELECT * FROM
  $this->databaseTable
  WHERE EmpXRef NOT IN (SELECT EmpXRef from empxref)
ORDER BY EmpXRef ASC,timeworkedfrom ASC");
			$selectQuery->execute();
			$this->hours = "Employee ID,Date,Time,Job Code\n";
			$rowsFound = false;
			while( $row = $selectQuery->fetch(PDO::FETCH_ASSOC) ){
				if( self::modifyHours($row) === true ){
					$rowsFound = true;
				}
			}
			if( $rowsFound === false ){
				throw  new CustomException('No hours were found.');
			}
			self::modifyHours($row);// We need to run this one last time to complete the last record.
			$this->Debug->add($selectQuery->rowCount() . ' rows returned in on line ' . __LINE__ . ' in file ' . __FILE__ . '.');
		}catch( CustomPDOException $e ){
			$this->Debug->error(__LINE__, '', $e);
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

	private function modifyHours($row) {
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
			if( $row['EmpXRef'] == 4602 && in_array($row['EmpXRef'], $this->specialCases) && $row['EmpXRef'] != 'CSMGMT' ){
				if( $row['JobXRef'] == 'CSCHILDPRO' ){
					$row['JobXRef'] = 'HICHILDPRO';
				}elseif( $row['JobXRef'] == 'CSADULTPARA' ){
					$row['JobXRef'] = 'CSMGMT';
				}
			}elseif( $row['EmpXRef'] == 101639 ){
				if( $row['JobXRef'] == 'HSCHILDPRO' ){
					$row['JobXRef'] = 'HICHILDPRO';
				}
			}
			// Look for unrecognized job codes.
			if( empty($this->jobXRefArray[$row['JobXRef']]) ){
				$this->unrecognizedJobCodesArray[] = $row;
			}else{
				$row['Job Code'] = $this->jobXRefArray[$row['JobXRef']];
			}

			$row['inDatetime'] = Time::convertToDateTime($row['timeworkedfrom']);
			$row['outDatetime'] = Time::convertToDateTime($row['timeworkedto']);

			if( empty($this->hoursGroup) ){
				// Add new information to hoursGroup.
				$this->hoursGroup[] = $row;
			}elseif( $this->hoursGroup[0]['DateOfService'] == $row['DateOfService'] && $this->hoursGroup[0]['EmpXRef'] == $row['EmpXRef'] ){
				// Same EmpXRef and DateOfService. Add to the arrays.
				$this->hoursGroup[] = $row;
			}else{
				// Process and then empty array groups.
				if( !empty($this->regularHoursGroup) || !empty($this->nonbillHoursGroup) ){
					self::splitHours();
				}
				// Reset arrays.
				$this->hoursGroup = array($row);// This row is either a new employee or a new day.
				$this->Debug->printArray($row,'$row in modifyHours on line ' . __LINE__);

				$this->nonbillHoursGroup = array();
				$this->regularHoursGroup = array();
			}

			// Set the nonbill status.
			if( strstr($row['JobXRef'], 'NONBILL') !== false || strstr($row['ProcedureCodeString'], 'Non-Bill') !== false ){
				// NONBILL exists in the JobXRef or ProcedureCodeString. Add them to our NONBILL hours group array.
				$this->nonbillHoursGroup[] = $row;
				$row['NONBILL'] = true;
			}else{
				// These are regular hours. Add them to our regular hours group array.
				if( in_array($row['EmpXRef'], $this->specialCases) ){
					$row['JobXRef'] = 'HICHILDPRO';
				}
				$this->regularHoursGroup[] = $row;
				$row['NONBILL'] = false;
			}
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		// Sanity check.
		/*if( $this->count == 4 ){
			$this->Debug->add('<pre>' . $this->hours . '</pre>');
		}
		$this->count++;*/
		return true;
	}

	private function splitHours() {
		/*
		 * Check for overlap between the regular and nonbill hours for this employee for this DateOfService.
		 * If any overlap is found the execution of the loop stops and nothing is entered for this employee for this DateOfService.
		 */
		try{
			$count = count($this->nonbillHoursGroup);
			$typeOverlap = false;
			if( $count > 0 ){
				// Check overlap between NONBILL and regular hours.
				for( $x = 0; $x < $count; $x++ ){
					foreach( $this->regularHoursGroup as $entry ){
						// Check every NONBILL entry against every regular entry. This is the most complicated piece of code in the whole application.
						if(
							($this->nonbillHoursGroup[$x]['inDatetime'] > $entry['inDatetime'] && $this->nonbillHoursGroup[$x]['inDatetime'] < $entry['outDatetime'])
							||
							($entry['inDatetime'] > $this->nonbillHoursGroup[$x]['inDatetime'] && $entry['inDatetime'] < $this->nonbillHoursGroup[$x]['outDatetime'])
						){
							// NONBILL hours overlap regular hours.
							$typeOverlap = true;
							$this->overLapArray[] = array($this->nonbillHoursGroup[$x], $entry);
							break;// Do not go any further with this employee for this day. Report the overlap. This entire DateOfService for this employee will not be added.
						}
						$currentDatetime = Time::convertToDateTime($entry['timeworkedto']);
						$this->latestDateDatetime = $this->latestDateDatetime > $currentDatetime ? $this->latestDateDatetime : $currentDatetime;// Update the latest date.
					}
					if( $typeOverlap === true ){
						break;
					}
				}
				if( $typeOverlap === false ){
					// There is no overlap between NONBILL and regular hours. Add the hours.
					self::processHours($this->hoursGroup);
				}

			}else{
				// We have no NONBILL hours for this DateOfService. Process and add the regular hours.
				self::processHours($this->regularHoursGroup);
			}
		}catch( CustomException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
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

		$pendingRow = '';
		foreach( $array as $entry ){
			if( empty($pendingRow) ){
				$pendingRow = $entry;
			}else{
				if( $entry['inDatetime'] < $pendingRow['outDatetime'] && $entry['outDatetime'] >= $pendingRow['outDatetime'] ){
					// They overlap. Add the contiguous time periods together.
					$pendingRow['outDatetime'] = $entry['outDatetime'];
				}elseif( $entry['inDatetime'] < $pendingRow['outDatetime'] && $entry['outDatetime'] < $pendingRow['outDatetime'] ){
					// This is the rare situation where a time period lies inside another. Ignore it.
				}elseif( $entry['timeworkedfrom'] == $pendingRow['timeworkedfrom'] && $entry['timeworkedto'] == $pendingRow['timeworkedto'] ){
					// The entries are the same time period. Log it, but ignore it. This is not a problem.
					$duplicateArray[] = array($entry, $pendingRow);
				}elseif( $entry['inDatetime'] == $pendingRow['outDatetime'] ){
					// Add a minute to avoid overlap.
					$entry['inDatetime']->add(new DateInterval('PT1M'));
					self::addHours($pendingRow, __LINE__);
					$pendingRow = $entry;
				}else{
					// There is no overlap at all.
					self::addHours($pendingRow, __LINE__);
					$pendingRow = $entry;
				}
			}
		}
		// Add the last pending row.
		self::addHours($pendingRow, __LINE__);
	}
}