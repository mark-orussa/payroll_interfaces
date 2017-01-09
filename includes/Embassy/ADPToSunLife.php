<?php
namespace Embassy;

use PDO, ErrorException, Exception, PDOException;

/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 8/11/2016
 * Time: 9:19 AM
 *
 * This inherits from and uses some methods of PayrollInterface, but defines some of it's own methods for saving to the database and some other things.
 */
class ADPToSunLife extends PayrollInterface {
	// Properties
	private $_employeeArray; // This is to verify there are no duplicate employees.
	private $_dateOfReport;
	private $_outputCSV;
	private $_outputTable;
	private $_outgoingFilename;

	public function __construct($Ajax, $Dbc, $Debug, $Message) {
		$this->_employeeArray = array();
		try{
			parent::__construct($Ajax, $Dbc, $Debug, $Message);

			if( MODE == 'sunLifeAddRecord' ){
				self::sunLifeAddRecord();
			}else if( MODE == 'sunLifeDeleteRate' ){
				self::sunLifeDeleteRate();
			}else if( MODE == 'sunLifeUpdate' ){
				self::sunLifeUpdate();
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


	public function beginAdpToSunLife($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) {
		/**
		 * @param $formFileInputName string
		 * @param $saveDirectory     string
		 * @param $outgoingDirectory string
		 * @param $databaseTableName string
		 * @return  bool  Returns true on success, otherwise false.
		 *
		 */
		date_default_timezone_set("America/Los_Angeles");
		try{
			$this->databaseTable = $databaseTableName;
			$this->outgoingDirectory = $outgoingDirectory;
			$this->Debug->add('bazooka');

			//Truncate database. We do this first because a thrown error may prevent the database from being truncated later.
			if( parent::truncateDatabase($this->databaseTable) === false ){
				throw new CustomException();
			}

			// Save the uploaded file to the save directory.
			if( self::saveIncomingFile($formFileInputName, $saveDirectory) === false ){
				throw new CustomException('Could not save the incoming file.');
			}

			// Save the data to the database.
			if( self::saveToDatabaseAdpToSunLife() === false ){
				throw new CustomException('Could not save the data to the database.');
			};

			// Get the data from the database to build the output file.
			if( self::getOutputData() === false ){
				throw new CustomException('Could not get the data from the database.');
			};
			self::outputFile($this->_outgoingFilename, $this->_outputCSV);
			$headerArray = array('Employee ID', 'First Name', 'Last Name', 'Date of Birth', 'Age', 'Annual Salary', 'Employee Life Amount', 'Employee ADD Amount', 'Spouse Life Amount', 'Spouse ADD Amount', 'Child Life Amount', 'Child ADD Amount', 'STD', 'LTD', 'Employee Critical Illness', 'Spouse Critical Illness', 'Child Critical Illness', 'Employee Tobacco Status', 'Spouse Tobacco Status');
			self::addToOutput('<div>Your browser has been promted to download a CSV file. Please check your downloads folder for a file called ' . $this->_outgoingFilename . '</div><div>This file has all of the information that Sun Life needs.</div>' . self::getInvalidData() . self::getDuplicateEntries($headerArray) . $this->_outputTable);
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

	private function getOutputData() {
		/**
		 * Get all of the benefit data.
		 * @return  array|bool  Returns an array of data, otherwise false.
		 */

		/*
		 * The sun_life_rates table lists 104 benefits grouped by:
		Short-Term Disability
		Long-Term Disability
		Employee Voluntary Life
		Spouse Voluntary Life
		Child Voluntary Life
		Employee Voluntary AD&D
		Spouse Voluntary AD&D
		Child Voluntary AD&D
		Employee Critical Illness
		Spouse Critical Illness
		Child Critical Illness

		The sun_life_rates table shows these fields for each benefit:
		Benefit, Option, Age Start, Age End, Lives, Rate, Calculate.

		The Employee Voluntary Life, Spouse Voluntary Life, Employee Critical Illness, and Spouse Critical Illness are broken into age bands and tobacco vs non-tobacco. The queries are generated programatically. The employee_tobacco_status field is related to the employee_life_amount field. This is a very long query, but it works efficiently.
		 */

		try{
			$this->_outputTable = "<table><tr><td>Benefit</td><td>Option</td><td>Age</td><td>Lives</td><td>Rate</td><td>Calculate</td><td>Volume</td><td>Premium</td></tr>";
			$premiumTotal = 0;
			$premiumTotalArray = array();

			// Short-Term Disability
			/*
			 * The STD eligible volume is calculated in a unique manner. This is the formula:
			 * Sum the annual salaries of employees with STD election (capped at $130,000 per employee), divide by 52, multiply by 0.600 (the weekly benefit percentage), then multiply by the 0.800 rate, and divide by 10. This relies on information not clearly defined in the Sun Life Self-Bill PDF document.
			 */
			$stdQuery = $this->Dbc->query("SELECT
	sun_life_rates.benefit AS 'benefit',
	sun_life_rates.option AS 'option',
	sun_life_rates.age_start AS 'age_start',
	sun_life_rates.age_end AS 'age_end',
	COUNT(annual_salary) AS 'lives',
	sun_life_rates.rate AS 'rate',
	SUM(IF(adp_to_sun_life.annual_salary > 130000,130000,adp_to_sun_life.annual_salary))/52 * .6 AS 'volume',
	sun_life_rates.calculate AS 'calculate',
	ROUND((SUM(IF(adp_to_sun_life.annual_salary > 130000,130000,adp_to_sun_life.annual_salary))/52 * .6 * sun_life_rates.rate) / sun_life_rates.calculate,2) AS 'premium'
FROM
	adp_to_sun_life, sun_life_rates
WHERE
	adp_to_sun_life.std = 1 AND
	sun_life_rates.benefit = 'Short-Term Disability'");
			$stdQuery->execute();
			$foundRows = false;
			// Add records to an output variable.
			while( $row = $stdQuery->fetch(PDO::FETCH_ASSOC) ){
				$foundRows = true;
				$this->_outputCSV .= $row['benefit'] . ',"' . $row['option'] . '",' . $row['age_start'] . ' - ' . $row['age_end'] . ',' . $row['lives'] . ',' . $row['rate'] . ',' . $row['calculate'] . ',' . round($row['volume']) . ',' . sprintf('%0.2f', round($row['premium'], 2)) . "\n";

				$this->_outputTable .= '<tr><td>' . $row['benefit'] . '</td><td>' . $row['option'] . '</td><td>' . $row['age_start'] . ' - ' . $row['age_end'] . '</td><td>' . $row['lives'] . '</td><td>' . $row['rate'] . '</td><td>Per ' . $row['calculate'] . '</td><td>' . number_format($row['volume'], 0) . '</td><td>$' . self::formatNumberWithCommas($row['premium']) . '</td></tr>';
				$premiumTotal += $row['premium'];
			}
			if( !$foundRows ){
				$this->_outputTable .= '<tr><td colspan="8">No results were found for Short-Term Disability.</td></tr>';
			}

			// Long-Term Disability
			/*
			 * For LTD, the maximum monthly benefit is $7,500. 7500 / .60 = 12500. This is the capped payroll amount per month. To make it easier to determine who is capped I would multiply by 12 to get a yearly salary cap of $150,000. There is a minimum of $100/month, which equates to a $2000 annual salary.
			 * To get the eligible volume I would sum the annual salaries of employees with LTD election (capped at $150,000 per employee), divide by 12 to get the monthly sum, multiply by the LTD rate of 0.480, and then divide by 100. This follows the self-bill PDF document instructions.
			*/
			$ltdQuery = $this->Dbc->query("SELECT
 	sun_life_rates.benefit AS 'benefit',
	sun_life_rates.option AS 'option',
	sun_life_rates.age_start AS 'age_start',
	sun_life_rates.age_end AS 'age_end',
	COUNT(annual_salary) AS 'lives',
	sun_life_rates.rate AS 'rate',
	ROUND(SUM(IF(adp_to_sun_life.annual_salary > 150000,150000,adp_to_sun_life.annual_salary))/12) AS 'volume',
	sun_life_rates.calculate AS 'calculate',
	ROUND(((SUM(IF(adp_to_sun_life.annual_salary > 150000,150000,adp_to_sun_life.annual_salary))/12) * sun_life_rates.rate) / sun_life_rates.calculate, 2) AS 'premium'
FROM
	adp_to_sun_life, sun_life_rates
WHERE
	adp_to_sun_life.ltd = 1 AND
	sun_life_rates.benefit = 'Long-Term Disability'");
			$ltdQuery->execute();
			$foundRows = false;
			// Add records to an output variable.
			while( $row = $ltdQuery->fetch(PDO::FETCH_ASSOC) ){
				$foundRows = true;
				$this->_outputCSV .= $row['benefit'] . ',"' . $row['option'] . '",' . $row['age_start'] . ' - ' . $row['age_end'] . ',' . $row['lives'] . ',' . $row['rate'] . ',' . $row['calculate'] . ',' . round($row['volume']) . ',' . sprintf('%0.2f', round($row['premium'], 2)) . "\n";

				$this->_outputTable .= '<tr><td>' . $row['benefit'] . '</td><td>' . $row['option'] . '</td><td>' . $row['age_start'] . ' - ' . $row['age_end'] . '</td><td>' . $row['lives'] . '</td><td>' . $row['rate'] . '</td><td>Per ' . $row['calculate'] . '</td><td>' . number_format($row['volume'], 0) . '</td><td>$' . self::formatNumberWithCommas($row['premium']) . '</td></tr>';
				$premiumTotal += $row['premium'];
			}
			if( !$foundRows ){
				$this->_outputTable .= '<tr><td colspan="8">No results were found for Long-Term Disability.</td></tr>';
			}

			// Employee Voluntary ADD
			$employeeAddQuery = $this->Dbc->query("SELECT
	sun_life_rates.benefit AS 'benefit',
	sun_life_rates.option AS 'option',
	sun_life_rates.age_start AS 'age_start',
	sun_life_rates.age_end AS 'age_end',
	(SELECT COUNT(adp_to_sun_life.employee_add_amount)FROM
			adp_to_sun_life, sun_life_rates
		WHERE
			adp_to_sun_life.employee_add_amount <> 0 AND
			sun_life_rates.benefit = 'Employee Voluntary AD&D') AS 'lives',
	sun_life_rates.rate AS 'rate',
	SUM(adp_to_sun_life.employee_add_amount) AS 'volume',
	sun_life_rates.calculate AS 'calculate',
	ROUND(SUM(adp_to_sun_life.employee_add_amount) * sun_life_rates.rate / sun_life_rates.calculate,2) AS 'premium'
FROM
	adp_to_sun_life, sun_life_rates
WHERE
	sun_life_rates.benefit = 'Employee Voluntary AD&D'");
			$employeeAddQuery->execute();
			$foundRows = false;
			// Add records to an output variable.
			while( $row = $employeeAddQuery->fetch(PDO::FETCH_ASSOC) ){
				$foundRows = true;
				$this->_outputCSV .= $row['benefit'] . ',"' . $row['option'] . '",' . $row['age_start'] . ' - ' . $row['age_end'] . ',' . $row['lives'] . ',' . $row['rate'] . ',' . $row['calculate'] . ',' . round($row['volume']) . ',' . sprintf('%0.2f', round($row['premium'], 2)) . "\n";

				$this->_outputTable .= '<tr><td>' . $row['benefit'] . '</td><td>' . $row['option'] . '</td><td>' . $row['age_start'] . ' - ' . $row['age_end'] . '</td><td>' . $row['lives'] . '</td><td>' . $row['rate'] . '</td><td>Per ' . $row['calculate'] . '</td><td>' . number_format($row['volume'], 0) . '</td><td>$' . self::formatNumberWithCommas($row['premium']) . '</td></tr>';
				$premiumTotal += $row['premium'];
			}
			if( !$foundRows ){
				$this->_outputTable .= '<tr><td colspan="8">No results were found for Employee Voluntary AD&D.</td></tr>';
			}

			// Spouse Voluntary ADD
			$spouseAddQuery = $this->Dbc->query("SELECT
	sun_life_rates.benefit AS 'benefit',
	sun_life_rates.option AS 'option',
	sun_life_rates.age_start AS 'age_start',
	sun_life_rates.age_end AS 'age_end',
	(SELECT COUNT(adp_to_sun_life.spouse_add_amount)FROM
			adp_to_sun_life, sun_life_rates
		WHERE
			adp_to_sun_life.spouse_add_amount <> 0 AND
			sun_life_rates.benefit = 'Spouse Voluntary AD&D') AS 'lives',
	sun_life_rates.rate AS 'rate',
	SUM(adp_to_sun_life.spouse_add_amount) AS 'volume',
	sun_life_rates.calculate AS 'calculate',
	ROUND(SUM(adp_to_sun_life.spouse_add_amount) * sun_life_rates.rate / sun_life_rates.calculate,2) AS 'premium'
FROM
	adp_to_sun_life, sun_life_rates
WHERE
	sun_life_rates.benefit = 'Spouse Voluntary AD&D'");
			$spouseAddQuery->execute();
			$foundRows = false;
			// Add records to an output variable.
			while( $row = $spouseAddQuery->fetch(PDO::FETCH_ASSOC) ){
				$foundRows = true;
				$this->_outputCSV .= $row['benefit'] . ',"' . $row['option'] . '",' . $row['age_start'] . ' - ' . $row['age_end'] . ',' . $row['lives'] . ',' . $row['rate'] . ',' . $row['calculate'] . ',' . round($row['volume']) . ',' . sprintf('%0.2f', round($row['premium'], 2)) . "\n";

				$this->_outputTable .= '<tr><td>' . $row['benefit'] . '</td><td>' . $row['option'] . '</td><td>' . $row['age_start'] . ' - ' . $row['age_end'] . '</td><td>' . $row['lives'] . '</td><td>' . $row['rate'] . '</td><td>Per ' . $row['calculate'] . '</td><td>' . number_format($row['volume'], 0) . '</td><td>$' . self::formatNumberWithCommas($row['premium']) . '</td></tr>';
				$premiumTotal += $row['premium'];
			}
			if( !$foundRows ){
				$this->_outputTable .= '<tr><td colspan="8">No results were found for Spouse Voluntary AD&D.</td></tr>';
			}

			// Child Voluntary ADD
			$childAddQuery = $this->Dbc->query("SELECT
	sun_life_rates.benefit AS 'benefit',
	sun_life_rates.option AS 'option',
	sun_life_rates.age_start AS 'age_start',
	sun_life_rates.age_end AS 'age_end',
	(SELECT COUNT(adp_to_sun_life.child_add_amount)FROM
			adp_to_sun_life, sun_life_rates
		WHERE
			adp_to_sun_life.child_add_amount <> 0 AND
			sun_life_rates.benefit = 'Child Voluntary AD&D') AS 'lives',
	sun_life_rates.rate AS 'rate',
	SUM(adp_to_sun_life.child_add_amount) AS 'volume',
	sun_life_rates.calculate AS 'calculate',
	ROUND(SUM(adp_to_sun_life.child_add_amount) * sun_life_rates.rate / sun_life_rates.calculate,2) AS 'premium'
FROM
	adp_to_sun_life, sun_life_rates
WHERE
	sun_life_rates.benefit = 'Child Voluntary AD&D'");
			$childAddQuery->execute();
			$foundRows = false;
			// Add records to an output variable.
			while( $row = $childAddQuery->fetch(PDO::FETCH_ASSOC) ){
				$foundRows = true;
				$this->_outputCSV .= $row['benefit'] . ',"' . $row['option'] . '",' . $row['age_start'] . ' - ' . $row['age_end'] . ',' . $row['lives'] . ',' . $row['rate'] . ',' . $row['calculate'] . ',' . round($row['volume']) . ',' . sprintf('%0.2f', round($row['premium'], 2)) . "\n";

				$this->_outputTable .= '<tr><td>' . $row['benefit'] . '</td><td>' . $row['option'] . '</td><td>' . $row['age_start'] . ' - ' . $row['age_end'] . '</td><td>' . $row['lives'] . '</td><td>' . $row['rate'] . '</td><td>Per ' . $row['calculate'] . '</td><td>' . number_format($row['volume'], 0) . '</td><td>$' . self::formatNumberWithCommas($row['premium']) . '</td></tr>';
				$premiumTotal += $row['premium'];
			}
			if( !$foundRows ){
				$this->_outputTable .= '<tr><td colspan="8">No results were found for Child Voluntary AD&D.</td></tr>';
			}

			// Child Voluntary Life
			$childLifeQuery = $this->Dbc->prepare("SELECT
	sun_life_rates.benefit AS 'benefit',
	sun_life_rates.option AS 'option',
	sun_life_rates.age_start AS 'age_start',
	sun_life_rates.age_end AS 'age_end',
	(SELECT COUNT(adp_to_sun_life.child_life_amount) FROM
			adp_to_sun_life, sun_life_rates
		WHERE
			adp_to_sun_life.child_life_amount <> 0 AND
			sun_life_rates.benefit = 'Child Voluntary Life') AS 'lives',
	sun_life_rates.rate AS 'rate',
	SUM(adp_to_sun_life.child_life_amount) AS 'volume',
	sun_life_rates.calculate AS 'calculate',
	ROUND(SUM(adp_to_sun_life.child_life_amount) * sun_life_rates.rate / sun_life_rates.calculate,2) AS 'premium'
FROM
	adp_to_sun_life, sun_life_rates
WHERE
	sun_life_rates.benefit = 'Child Voluntary Life'");
			$childLifeQuery->execute();
			$foundRows = false;
			// Add records to an output variable.
			while( $row = $childLifeQuery->fetch(PDO::FETCH_ASSOC) ){
				$foundRows = true;
				$this->_outputCSV .= $row['benefit'] . ',"' . $row['option'] . '",' . $row['age_start'] . ' - ' . $row['age_end'] . ',' . $row['lives'] . ',' . $row['rate'] . ',' . $row['calculate'] . ',' . round($row['volume']) . ',' . sprintf('%0.2f', round($row['premium'], 2)) . "\n";

				$this->_outputTable .= '<tr><td>' . $row['benefit'] . '</td><td>' . $row['option'] . '</td><td>' . $row['age_start'] . ' - ' . $row['age_end'] . '</td><td>' . $row['lives'] . '</td><td>' . $row['rate'] . '</td><td>Per ' . $row['calculate'] . '</td><td>' . number_format($row['volume'], 0) . '</td><td>$' . self::formatNumberWithCommas($row['premium']) . '</td></tr>';
				$premiumTotal += $row['premium'];
			}
			if( !$foundRows ){
				$this->_outputTable .= '<tr><td colspan="8">No results were found for Child Voluntary Life.</td></tr>';
			}

			// Child Critical Illness
			$childCriticalIllness = $this->Dbc->prepare("SELECT
	sun_life_rates.benefit AS 'benefit',
	sun_life_rates.option AS 'option',
	sun_life_rates.age_start AS 'age_start',
	sun_life_rates.age_end AS 'age_end',
	(SELECT COUNT(adp_to_sun_life.child_critical_illness) FROM adp_to_sun_life, sun_life_rates
		WHERE
			adp_to_sun_life.child_critical_illness <> '' AND
			sun_life_rates.benefit = 'Child Critical Illness') AS 'lives',
	sun_life_rates.rate AS 'rate',
	SUM(adp_to_sun_life.child_critical_illness) AS 'volume',
	sun_life_rates.calculate AS 'calculate',
	ROUND(SUM(adp_to_sun_life.child_critical_illness) * sun_life_rates.rate / sun_life_rates.calculate,2) AS 'premium'
FROM
	adp_to_sun_life, sun_life_rates
WHERE
	sun_life_rates.benefit = 'Child Critical Illness'");
			$childCriticalIllness->execute();
			$foundRows = false;
			// Add records to an output variable.
			while( $row = $childCriticalIllness->fetch(PDO::FETCH_ASSOC) ){
				$foundRows = true;
				$this->_outputCSV .= $row['benefit'] . ',"' . $row['option'] . '",' . $row['age_start'] . ' - ' . $row['age_end'] . ',' . $row['lives'] . ',' . $row['rate'] . ',' . $row['calculate'] . ',' . round($row['volume']) . ',' . sprintf('%0.2f', round($row['premium'], 2)) . "\n";

				$this->_outputTable .= '<tr><td>' . $row['benefit'] . '</td><td>' . $row['option'] . '</td><td>' . $row['age_start'] . ' - ' . $row['age_end'] . '</td><td>' . $row['lives'] . '</td><td>' . $row['rate'] . '</td><td>Per ' . $row['calculate'] . '</td><td>' . number_format($row['volume'], 0) . '</td><td>$' . self::formatNumberWithCommas($row['premium']) . '</td></tr>';
				$premiumTotal += $row['premium'];
			}
			if( !$foundRows ){
				$this->_outputTable .= '<tr><td colspan="8">No results were found for Child Critical Illness.</td></tr>';
			}

			/* Get the sun_life_rates table info to build the query that gets all of the lives, volumes, and premium totals for:
				Employee Voluntary Life,
				Spouse Voluntary Life,
				Employee Critical Illness,
				Spouse Critical Illness.
			*/
			$ratesQuery = $this->Dbc->query("SELECT
			benefit AS 'benefit',
			`option` AS 'option',
			age_start AS 'age_start',
			age_end AS 'age_end',
			rate AS 'rate',
			calculate AS 'calculate'
FROM
	sun_life_rates
WHERE
	benefit IN('Employee Voluntary Life','Employee Voluntary Life - Smoker','Spouse Voluntary Life','Employee Critical Illness','Employee Critical Illness - Smoker','Spouse Critical Illness','Spouse Critical Illness - Smoker')");
			$ratesQuery->execute();

			/*
			This section builds a very long query to get all of the benefit election data by looping through the sun_life_rates table.

			There are 30 results for Employee Voluntary life with Smoker and Non-Smoker for a total of 90 values.
			There are 15 results for Spouse Voluntary life for a total of 45 values.
			There are 26 results for Employee Critical Illness, with Smoker and Non-Smoker for a total of 78 values.
			There are 26 results for Spouse Critical Illness, with Smoker and Non-Smoker for a total of 78 values.

			97 * 3 = 291
			*/
			$employeeVoluntaryLifeQuery = '';
			$spouseVoluntaryLifeQuery = '';
			$employeeCriticalIllnessQuery = '';
			$spouseCriticalIllnessQuery = '';
			$keepThis = array();
			while( $row = $ratesQuery->fetch(PDO::FETCH_ASSOC) ){
				$keepThis[] = $row;
				if( strpos($row['benefit'], 'Employee Voluntary Life') !== false ){
					// Build the lives, volumes, and premium queries for Employee Voluntary Life.
					$employeeVoluntaryLifeQuery .= $employeeVoluntaryLifeQuery == '' ? '' : ',';
					if( $row['benefit'] == 'Employee Voluntary Life' ){
						$employeeVoluntaryLifeNonSmokerParams = 'FROM adp_to_sun_life, sun_life_rates
	WHERE
		adp_to_sun_life.employee_life_amount <> 0 AND
		adp_to_sun_life.employee_tobacco_status = 0 AND
		sun_life_rates.benefit = \'Employee Voluntary Life\' AND
		adp_to_sun_life.age >= ' . $row['age_start'] . ' AND
		adp_to_sun_life.age <= ' . $row['age_end'] . ' AND
        sun_life_rates.age_start = ' . $row['age_start'] . ' AND
        sun_life_rates.age_end = ' . $row['age_end'] . ') AS \'' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' ';

						$employeeVoluntaryLifeQuery .= '	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' Non-Smoker Lives */
	(SELECT COUNT(adp_to_sun_life.employee_id)' . $employeeVoluntaryLifeNonSmokerParams . 'Lives\',
	
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Non-Smoker Volume */
	(SELECT SUM(adp_to_sun_life.employee_life_amount) ' . $employeeVoluntaryLifeNonSmokerParams . 'Volume\',
	
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Non-Smoker Premium */
	(SELECT SUM(adp_to_sun_life.employee_life_amount) * sun_life_rates.rate / sun_life_rates.calculate ' . $employeeVoluntaryLifeNonSmokerParams . 'Premium\'';
					}

					if( $row['benefit'] == 'Employee Voluntary Life - Smoker' ){
						$employeeVoluntaryLifeSmokerParams = 'FROM adp_to_sun_life, sun_life_rates
	WHERE
		adp_to_sun_life.employee_life_amount <> 0 AND
		adp_to_sun_life.employee_tobacco_status = 1 AND
		sun_life_rates.benefit = \'Employee Voluntary Life - Smoker\' AND
		adp_to_sun_life.age >= ' . $row['age_start'] . ' AND
		adp_to_sun_life.age <= ' . $row['age_end'] . ' AND
        sun_life_rates.age_start = ' . $row['age_start'] . ' AND
        sun_life_rates.age_end = ' . $row['age_end'] . ') AS \'' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' ';

						$employeeVoluntaryLifeQuery .= '/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Smoker Lives */
	(SELECT COUNT(adp_to_sun_life.employee_id) ' . $employeeVoluntaryLifeSmokerParams . 'Lives\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Smoker Volume */
	(SELECT SUM(adp_to_sun_life.employee_life_amount) ' . $employeeVoluntaryLifeSmokerParams . 'Volume\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Smoker Premium */
	(SELECT (SUM(adp_to_sun_life.employee_life_amount) * sun_life_rates.rate) / sun_life_rates.calculate ' . $employeeVoluntaryLifeSmokerParams . 'Premium\'';
					}
				}

				if( $row['benefit'] == 'Spouse Voluntary Life' ){
					// Build the Spouse Voluntary life Lives, Volume, and Premium queries for Spouse Voluntary Life.
					$spouseVoluntaryLifeQuery .= $spouseVoluntaryLifeQuery == '' ? '' : ',';
					$spouseVoluntaryLifeParams = 'FROM adp_to_sun_life, sun_life_rates
	WHERE
		adp_to_sun_life.spouse_life_amount <> 0 AND
		sun_life_rates.benefit = \'Spouse Voluntary Life\' AND
		adp_to_sun_life.age >= ' . $row['age_start'] . ' AND
		adp_to_sun_life.age <= ' . $row['age_end'] . ' AND
        sun_life_rates.age_start = ' . $row['age_start'] . ' AND
        sun_life_rates.age_end = ' . $row['age_end'] . ') AS \'' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' ';

					$spouseVoluntaryLifeQuery .= '	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Lives */
	(SELECT COUNT(adp_to_sun_life.spouse_life_amount) ' . $spouseVoluntaryLifeParams . 'Lives\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Volume */
	(SELECT SUM(adp_to_sun_life.spouse_life_amount) ' . $spouseVoluntaryLifeParams . 'Volume\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Premium */
	(SELECT SUM(adp_to_sun_life.spouse_life_amount) * sun_life_rates.rate / sun_life_rates.calculate ' . $spouseVoluntaryLifeParams . 'Premium\'';
				}

				if( strpos($row['benefit'], 'Employee Critical Illness') !== false ){
					// Build the Employee Critical Illness Lives, Volume, and Premium queries for Employee Critical Illness.
					$employeeCriticalIllnessQuery .= $employeeCriticalIllnessQuery == '' ? '' : ',';
					if( $row['benefit'] == 'Employee Critical Illness' ){
						$employeeCriticalIllnessNonSmokerParams = 'FROM adp_to_sun_life, sun_life_rates
	WHERE
		adp_to_sun_life.employee_critical_illness <> \'\' AND
		adp_to_sun_life.employee_tobacco_status = 0 AND
		sun_life_rates.benefit = \'Employee Critical Illness\' AND
		adp_to_sun_life.age >= ' . $row['age_start'] . ' AND
		adp_to_sun_life.age <= ' . $row['age_end'] . ' AND
        sun_life_rates.age_start = ' . $row['age_start'] . ' AND
        sun_life_rates.age_end = ' . $row['age_end'] . ') AS \'' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' ';

						$employeeCriticalIllnessQuery .= '	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Lives*/
	(SELECT COUNT(adp_to_sun_life.employee_critical_illness) ' . $employeeCriticalIllnessNonSmokerParams . 'Lives\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Volume*/
	(SELECT SUM(adp_to_sun_life.employee_critical_illness) ' . $employeeCriticalIllnessNonSmokerParams . 'Volume\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Premium*/
	(SELECT SUM(adp_to_sun_life.employee_critical_illness) * sun_life_rates.rate / sun_life_rates.calculate ' . $employeeCriticalIllnessNonSmokerParams . 'Premium\'';
					}
					if( $row['benefit'] == 'Employee Critical Illness - Smoker' ){
						$employeeCriticalIllnessSmokerParams = 'FROM adp_to_sun_life, sun_life_rates
	WHERE
		adp_to_sun_life.employee_critical_illness <> \'\' AND
		adp_to_sun_life.employee_tobacco_status = 1 AND
		sun_life_rates.benefit = \'Employee Critical Illness - Smoker\' AND
		adp_to_sun_life.age >= ' . $row['age_start'] . ' AND
		adp_to_sun_life.age <= ' . $row['age_end'] . ' AND
        sun_life_rates.age_start = ' . $row['age_start'] . ' AND
        sun_life_rates.age_end = ' . $row['age_end'] . ') AS \'' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' ';

						$employeeCriticalIllnessQuery .= '	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Lives*/
	(SELECT COUNT(adp_to_sun_life.employee_critical_illness) ' . $employeeCriticalIllnessSmokerParams . 'Lives\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Volume*/
	(SELECT SUM(adp_to_sun_life.employee_critical_illness) ' . $employeeCriticalIllnessSmokerParams . 'Volume\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Premium*/
	(SELECT SUM(adp_to_sun_life.employee_critical_illness) * sun_life_rates.rate / sun_life_rates.calculate ' . $employeeCriticalIllnessSmokerParams . 'Premium\'';
					}
				}

				if( strpos($row['benefit'], 'Spouse Critical Illness') !== false ){
					// Build the Spouse Critical Illness Lives, Volume, and Premium queries for Employee Critical Illness.
					$spouseCriticalIllnessQuery .= $spouseCriticalIllnessQuery == '' ? '' : ',';
					if( $row['benefit'] == 'Spouse Critical Illness' ){
						$spouseCriticalIllnessNonSmokerParams = 'FROM adp_to_sun_life, sun_life_rates
	WHERE
		adp_to_sun_life.spouse_critical_illness <> \'\' AND
		adp_to_sun_life.spouse_tobacco_status = 0 AND
		sun_life_rates.benefit = \'Spouse Critical Illness\' AND
		adp_to_sun_life.age >= ' . $row['age_start'] . ' AND
		adp_to_sun_life.age <= ' . $row['age_end'] . ' AND
        sun_life_rates.age_start = ' . $row['age_start'] . ' AND
        sun_life_rates.age_end = ' . $row['age_end'] . ') AS \'' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' ';

						$spouseCriticalIllnessQuery .= '	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Lives*/
	(SELECT COUNT(adp_to_sun_life.employee_critical_illness) ' . $spouseCriticalIllnessNonSmokerParams . 'Lives\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Volume*/
	(SELECT SUM(adp_to_sun_life.employee_critical_illness) ' . $spouseCriticalIllnessNonSmokerParams . 'Volume\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Premium*/
	(SELECT SUM(adp_to_sun_life.employee_critical_illness) * sun_life_rates.rate / sun_life_rates.calculate ' . $spouseCriticalIllnessNonSmokerParams . 'Premium\'';
					}
					if( $row['benefit'] == 'Spouse Critical Illness - Smoker' ){
						$spouseCriticalIllnessSmokerParams = 'FROM adp_to_sun_life, sun_life_rates
	WHERE
		adp_to_sun_life.spouse_critical_illness <> \'\' AND
		adp_to_sun_life.spouse_tobacco_status = 1 AND
		sun_life_rates.benefit = \'Spouse Critical Illness - Smoker\' AND
		adp_to_sun_life.age >= ' . $row['age_start'] . ' AND
		adp_to_sun_life.age <= ' . $row['age_end'] . ' AND
        sun_life_rates.age_start = ' . $row['age_start'] . ' AND
        sun_life_rates.age_end = ' . $row['age_end'] . ') AS \'' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' ';

						$spouseCriticalIllnessQuery .= '	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Lives*/
	(SELECT COUNT(adp_to_sun_life.employee_critical_illness) ' . $spouseCriticalIllnessSmokerParams . 'Lives\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Volume*/
	(SELECT SUM(adp_to_sun_life.employee_critical_illness) ' . $spouseCriticalIllnessSmokerParams . 'Volume\',
	/* ' . $row['age_start'] . '-' . $row['age_end'] . ' ' . $row['benefit'] . ' Premium*/
	(SELECT SUM(adp_to_sun_life.employee_critical_illness) * sun_life_rates.rate / sun_life_rates.calculate ' . $spouseCriticalIllnessSmokerParams . 'Premium\'';
					}
				}
			}
			$longQuery = 'SELECT ' . $employeeVoluntaryLifeQuery . ', ' . $spouseVoluntaryLifeQuery . ', ' . $employeeCriticalIllnessQuery . ', ' . $spouseCriticalIllnessQuery . ' FROM adp_to_sun_life LIMIT 1';
//			$this->Debug->add('$wholeQuery: ' . $longQuery);

			$lifeAndCriticalIllnessQuery = $this->Dbc->query($longQuery);
			$lifeAndCriticalIllnessQuery->execute();
			$foundRows = false;
			// Add records to an output variable.
			$lifeAndCriticalIllnessOutput = '<tr>';
			$lifeAndCriticalIllnessCount = 0;
			while( $row = $lifeAndCriticalIllnessQuery->fetch(PDO::FETCH_ASSOC) ){
				$foundRows = true;
				$ratesCount = 0;
				// match up the sun_life_rates information with the data from ADP_to_sun_life results.
				foreach( $row as $key => $value ){
					if( $lifeAndCriticalIllnessCount % 3 == 0 ){
						$outputCSV1 = $keepThis[$ratesCount]['benefit'] . ',"' . $keepThis[$ratesCount]['option'] . '",' . $keepThis[$ratesCount]['age_start'] . ' - ' . $keepThis[$ratesCount]['age_end'];
						$outputCSV2 = $keepThis[$ratesCount]['rate'] . ',' . $keepThis[$ratesCount]['calculate'] . ',';
						$lifeAndCriticalIllnessOutput .= '</tr><tr>';
						$infoStuff1 = '<td>' . $keepThis[$ratesCount]['benefit'] . '</td><td>' . $keepThis[$ratesCount]['option'] . '</td><td>' . $keepThis[$ratesCount]['age_start'] . ' - ' . $keepThis[$ratesCount]['age_end'] . '</td>';
						$infoStuff2 = '<td>' . $keepThis[$ratesCount]['rate'] . '</td><td>' . $keepThis[$ratesCount]['calculate'] . '</td>';
						$ratesCount++;
					}
					if( strpos($key, 'Volume') !== false ){
						$this->_outputCSV .= round($value) . ',';
						$lifeAndCriticalIllnessOutput .= '<td>' . self::formatNumberWithCommas($value, 0) . '</td>';
					}elseif( strpos($key, 'Premium') !== false ){
						$tempPremium = round($value, 2);
						$this->_outputCSV .= $tempPremium . "\n";
						$lifeAndCriticalIllnessOutput .= '<td>$' . self::formatNumberWithCommas($value) . '</td>';
						$premiumTotal += $tempPremium;
					}else{
						$this->_outputCSV .= $outputCSV1 . ',' . $value . ',' . $outputCSV2;
						$lifeAndCriticalIllnessOutput .= $infoStuff1 . '<td>' . $value . '</td>' . $infoStuff2;
					}
					$lifeAndCriticalIllnessCount++;
				}
			}
			$lifeAndCriticalIllnessOutput .= '</tr>';
			$this->_outputTable .= $lifeAndCriticalIllnessOutput;
			if( !$foundRows ){
				throw  new CustomException('No results were found for Employee Voluntary AD&D.');
			}

			//Build the CSV output.
			$this->_outputCSV = '"Embassy Management, LLC",Group Number 242736, Report for ' . $this->_dateOfReport . ',,,,,"Total Premium $' . self::formatNumberWithCommas($premiumTotal) . '"' . "\n" . "Benefit,Option,Age,Lives,Rate,Calculate,Volume,Premium\n" . $this->_outputCSV;

			// Build the output table. This is just to provide a visual on-screen result. The end user will also receive a CSV file to send to Sun Life.
			$this->_outputTable .= '<tr><td colspan="6"></td><td colspan="2" style="font-weight: bold">Total Premium: $' . self::formatNumberWithCommas($premiumTotal) . '</td></tr>
</table>';

			// Build the serveFile code.
			$this->_outgoingFilename = 'Embassy_Management_Sun_Life_Self_Reporting_' . $this->_dateOfReport . '.csv';
			$this->_outputTable .= '<iframe class="hiddenFileDownload" id="' . $this->_outgoingFilename . '" src="./ServeFile.php?mode=serveFile&fileName=' . $this->_outgoingFilename . '&filePath=./' . $this->outgoingDirectory . '/' . $this->_outgoingFilename . '"></iframe>';
		}catch( CustomPDOException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( PDOException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
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

	public static function manageRates($Dbc, $Debug) {
		/**
		 * Build the manage rates section.
		 * @return  string  Returns html.
		 */
		$output = '';
		try{
			$query = "SELECT
	id AS 'id',
 	benefit AS 'benefit',
 	`option` AS 'option',
 	age_start AS 'age_start',
 	age_end AS 'age_end',
 	rate AS 'rate',
 	calculate AS 'calculate' 	
 FROM sun_life_rates";
			$selectQuery = $Dbc->prepare($query);
			$selectQuery->execute();
			$headerRow = '<tr><td>Delete</td><td>Benefit</td><td>Option</td><td>Age Start</td><td>Age End</td><td>Rate</td><td>Calculate</td><td></td></tr>';
			$foundRows = false;
			$trRows = '';
			while( $row = $selectQuery->fetch($Dbc::FETCH_ASSOC) ){
				$foundRows = true;
				$trRows .= '<tr class="sunLifeEdit" data-id="' . $row['id'] . '">';
				// Build the editable rows.
				$trRows .= '<td><i class="fa fa-close red sunLifeDeleteRate" data-id="' . $row['id'] . '"></i></td>
<td><input class="sunLifeInput benefit" type="text" data-type="string" data-name="Benefit" data-parametername="benefit" value="' . $row['benefit'] . '"></td>
<td><input class="sunLifeInput" type="text" data-type="string" data-name="Option" data-parametername="option" value="' . $row['option'] . '"></td>
<td><input class="sunLifeInput" type="text" data-type="integer" data-name="Age Start" data-parametername="agestart" value="' . $row['age_start'] . '"></td>
<td><input class="sunLifeInput" type="text" data-type="integer" data-name="Age End" data-parametername="ageend" value="' . $row['age_end'] . '"></td>
<td><input class="sunLifeInput" type="text" data-type="decimal" data-name="Rate" data-parametername="rate" value="' . $row['rate'] . '"></td>
<td><input class="sunLifeInput" type="text" data-type="integer" data-name="Calculate" data-parametername="calculate" value="' . $row['calculate'] . '"></td>
<td><span class="makeButtonInline sunLifeUpdateButton" data-id="' . $row['id'] . '">Update</span></td>
';
				$trRows .= '</tr>';
			}
			if( !$foundRows ){
				$output = 'No results were found.';
			}else{
				$output .= '<table>' . $headerRow . $trRows . '</table>';
			}
			$output .= '<div class="toggleButtonInline" id="sunLifeNewRecordButton">Add New Record</div>
<div class="toggleMe">
<p>All fields are required.</p>
<div class="error" id="SunLifeAddRecordMessage"></div>
<table style="text-align: center" id="sunLifeNewRecordTable"><tr><td>Benefit</td><td>Option</td><td>Age Start</td><td>Age End</td><td>Rate</td><td>Calculate</td><td></td></tr>
<td>(i.e. Child Voluntary AD&D)<br><input class="sunLifeInput" type="text" id="sunLifeNewBenefit" data-type="string" data-name="Benefit"></td>
<td>(i.e. Increments of $1,000)<br><input class="sunLifeInput" type="text" id="sunLifeNewOption" data-type="string" data-name="Option" value="Choice 1 - All Employees"></td>
<td>(i.e. 0 through 99)<br><input class="sunLifeInput" type="text" id="sunLifeNewAgeStart" data-type="integer" data-name="Age Start"></td>
<td>(i.e. 0 through 99)<br><input class="sunLifeInput" type="text" id="sunLifeNewAgeEnd" data-type="integer" data-name="Age End"></td>
<td>(i.e. 0.063)<br><input class="sunLifeInput" type="text" id="sunLifeNewRate" data-type="decimal" data-name="Rate"></td>
<td>(i.e. 100, 1000)<br><input class="sunLifeInput" type="text" id="sunLifeNewCalculate" data-type="integer" data-name="Calculate" value="1000"></td>
</table>
<div class="makeButtonInline" id="sunLifeAddRecordButton">Add Record</div></div> ';
		}catch( CustomPDOException $e ){
			$Debug->error(__LINE__, '', $e);
			return false;
		}catch( PDOException $e ){
			$Debug->error(__LINE__, '', $e);
			return false;
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
		return $output;
	}

	private function saveToDatabaseAdpToSunLife() {
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
  employee_id = ?,
  first_name = ?,
  last_name = ?,
  date_of_birth = ?,
  age = ?,
  annual_salary = ?,
  employee_life_amount = ?,
  employee_add_amount = ?,
  spouse_life_amount = ?,
  spouse_add_amount = ?,
  child_life_amount = ?,
  child_add_amount = ?,
  std = ?,
  ltd = ?,
  employee_critical_illness = ?,
  spouse_critical_illness = ?,
  child_critical_illness = ?,
  employee_tobacco_status = ?,
  spouse_tobacco_status = ?,
  date_of_report = ?");
				$rowCount = 0;
				while( ($row = fgetcsv($handle, 0, ",")) !== FALSE ){
					// Count the total columns in the row.
					$field_count = count($row);

					if( $field_count != 20 ){
						throw new CustomException('Are you sure you uploaded a CSV file? The number of fields in the CSV file has changed since this application was last edited. There is supposed to be 20 fields.', 'There are ' . $field_count . ' fields in the CSV file instead of 20.');
					}
					// Ignore the header row.
					if( strpos($row[1], 'First Name') === false ){
						// Validate the data
						$validatedData = self::validateIncomingAdpToSunLifeCSVData($row);
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

	private function sunLifeAddRecord() {
		try{
			$errors = array();
			// We need all of the fields.
			if( empty($_POST['sunLifeNewBenefit']) ){
				$errors[] = array('', '$_POST[\'sunLifeNewBenefit\'] is empty.');
			}
			if( empty($_POST['sunLifeNewOption']) ){
				$errors[] = array('', '$_POST[\'sunLifeNewOption\'] is empty.');
			}
			if( !isset($_POST['sunLifeNewAgeStart']) ){
				$errors[] = array('', '$_POST[\'sunLifeNewAgeStart\'] is empty.');
			}
			if( empty($_POST['sunLifeNewAgeEnd']) ){
				$errors[] = array('', '$_POST[\'sunLifeNewAgeEnd\'] is empty.');
			}
			if( !isset($_POST['sunLifeNewRate']) ){
				$errors[] = array('', '$_POST[\'sunLifeNewRate\'] is empty.');
			}
			if( !isset($_POST['sunLifeNewCalculate']) ){
				$errors[] = array('', '$_POST[\'sunLifeNewCalculate\'] is empty.');
			}

			// Validate the data.
			if( strlen($_POST['sunLifeNewBenefit']) > 256 ){
				$errors[] = array('Benefit must be 256 or less characters.', '$_POST[\'sunLifeNewBenefit\'] is greater than 256 characters.');
			}
			if( strlen($_POST['sunLifeNewOption']) > 256 ){
				$errors[] = array('Option must be 256 or less characters.', '$_POST[\'sunLifeNewOption\'] is greater than 256 characters.');
			}
			if( !is_int(intThis($_POST['sunLifeNewAgeStart'])) ){
				$errors[] = array('Age Start must be an integer.', '$_POST[\'sunLifeNewAgeStart\'] is not an integer.');
			}
			if( !is_int(intThis($_POST['sunLifeNewAgeEnd'])) ){
				$errors[] = array('Age End must be an integer.', '$_POST[\'sunLifeNewAgeEnd\'] is not an integer.');
			}
			if( strlen(str_replace('.', '', $_POST['sunLifeNewRate'])) > 5 ){
				$errors[] = array('Rate must be less than 6 digits.', '$_POST[\'rate\'] is more than 5 digits.');
			}
			if( !is_numeric($_POST['sunLifeNewRate']) ){
				$errors[] = array('Rate must be numeric.', '$_POST[\'rate\'] is not numeric.');
			}
			if( !is_int(intThis($_POST['sunLifeNewCalculate'])) ){
				$errors[] = array('Calculate must be an integer.', '$_POST[\'sunLifeNewCalculate\'] is not an integer.');
			}
			if( count($errors) > 0 ){
				// Loop through the errors and throw an exception.
				$publicMessage = '';
				$debugMessage = '';
				foreach( $errors as $key => $arrayOfMessages ){
					$publicMessage .= '<div>' . $arrayOfMessages[0] . '</div>';
					$debugMessage .= '<div>' . $arrayOfMessages[1] . '</div>';
				}
				$this->Debug->add($debugMessage);
				$this->Message->add($publicMessage);
			}else{
				$insertQuery = $this->Dbc->prepare("INSERT INTO sun_life_rates SET
	benefit = ?,
	`option` = ?,
	age_start = ?,
	age_end = ?,
	rate = ?,
	calculate = ?");
				$params = array($_POST['sunLifeNewBenefit'], $_POST['sunLifeNewOption'], $_POST['sunLifeNewAgeStart'], $_POST['sunLifeNewAgeEnd'], $_POST['sunLifeNewRate'], $_POST['sunLifeNewCalculate']);
				$insertQuery->execute($params);
				$this->Ajax->SetSuccess(true);
				$this->Ajax->AddValue(array('list' => self::manageRates($this->Dbc, $this->Debug)));
				$this->Message->add('Added the record.');
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
		}finally{
			if( MODE == 'sunLifeAddRecord' ){
				$this->Ajax->ReturnData();
			}else{
				return '';
			}
		}
		if( MODE == 'sunLifeAddRecord' ){
			$this->Ajax->ReturnData();
		}else{
			return '';
		}
	}

	public function sunLifeDeleteRate() {
		try{
			if( empty($_POST['rateId']) ){
				throw new CustomException('', '$_POST[\'rateId\'] is empty.');
			}
			$deleteRateStmt = $this->Dbc->prepare("DELETE FROM
	sun_life_rates
WHERE
	id = ?
LIMIT 1;");
			$params = array($_POST['rateId']);
			$deleteRateStmt->execute($params);
			$this->Ajax->SetSuccess(true);
			$this->Ajax->AddValue(array('list' => self::manageRates($this->Dbc, $this->Debug)));
			$this->Message->add('Deleted the rate.');
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

	private function sunLifeUpdate() {
		try{
			$errors = array();
			// We need all of the fields.
			if( !isset($_POST['id']) ){
				$errors[] = array('', '$_POST[\'id\'] is empty.');
			}
			if( empty($_POST['benefit']) ){
				$errors[] = array('', '$_POST[\'benefit\'] is empty.');
			}
			if( empty($_POST['option']) ){
				$errors[] = array('', '$_POST[\'option\'] is empty.');
			}
			if( !isset($_POST['agestart']) ){
				$errors[] = array('', '$_POST[\'agestart\'] is empty.');
			}
			if( empty($_POST['ageend']) ){
				$errors[] = array('', '$_POST[\'ageend\'] is empty.');
			}
			if( !isset($_POST['rate']) ){
				$errors[] = array('', '$_POST[\'rate\'] is empty.');
			}
			if( !isset($_POST['calculate']) ){
				$errors[] = array('', '$_POST[\'calculate\'] is empty.');
			}

			// Validate the data.
			if( !is_numeric($_POST['id']) ){
				$errors[] = array('', '$_POST[\'id\'] is not numeric.');
			}
			if( strlen($_POST['benefit']) > 256 ){
				$errors[] = array('Benefit must be 256 or less characters.', '$_POST[\'benefit\'] is greater than 256 characters.');
			}
			if( strlen($_POST['option']) > 256 ){
				$errors[] = array('Option must be 256 or less characters.', '$_POST[\'option\'] is greater than 256 characters.');
			}
			if( !is_int(intThis($_POST['agestart'])) ){
				$errors[] = array('Age Start must be an integer.', '$_POST[\'agestart\'] is not an integer.');
			}
			if( !is_int(intThis($_POST['ageend'])) ){
				$errors[] = array('Age End must be an integer.', '$_POST[\'ageend\'] is not an integer.');
			}
			if( strlen(str_replace('.', '', $_POST['sunLifeNewRate'])) > 5 ){
				$errors[] = array('Rate must be less than 6 digits.', '$_POST[\'rate\'] is more than 5 digits.');
			}
			if( !is_numeric($_POST['rate']) ){
				$errors[] = array('Rate must be numeric.', '$_POST[\'rate\'] is not numeric.');
			}
			if( !is_int(intThis($_POST['calculate'])) ){
				$errors[] = array('Calculate must be an integer.', '$_POST[\'calculate\'] is not an integer.');
			}
			if( count($errors) > 0 ){
				// Loop through the errors and throw an exception.
				$publicMessage = '';
				$debugMessage = '';
				foreach( $errors as $key => $arrayOfMessages ){
					$publicMessage .= '<div>' . $arrayOfMessages[0] . '</div>';
					$debugMessage .= '<div>' . $arrayOfMessages[1] . '</div>';
				}
				$this->Debug->add($debugMessage);
				$this->Message->add($publicMessage);
			}else{
				$insertQuery = $this->Dbc->prepare("UPDATE sun_life_rates SET
	benefit = ?,
	`option` = ?,
	age_start = ?,
	age_end = ?,
	rate = ?,
	calculate = ?
WHERE
	id = ?");
				$params = array($_POST['benefit'], $_POST['option'], $_POST['agestart'], $_POST['ageend'], $_POST['rate'], $_POST['calculate'], $_POST['id']);
				$insertQuery->execute($params);
				$this->Ajax->SetSuccess(true);
				$this->Ajax->AddValue(array('list' => self::manageRates($this->Dbc, $this->Debug)));
				$this->Message->Add('Updated the record.');
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
		}finally{
			if( MODE == 'sunLifeUpdate' ){
				$this->Ajax->ReturnData();
			}else{
				return '';
			}
		}
		if( MODE == 'sunLifeUpdate' ){
			$this->Ajax->ReturnData();
		}else{
			return '';
		}
	}

	private function validateIncomingAdpToSunLifeCSVData($row) {
		/**
		 * Check for data problems.
		 *
		 * There are a number of validations and checks performed here before the data is inserted into the database.
		 *
		 * @author  Mark O'Russa    <mark@orussa.com>
		 * @param   array $row The current row being validated.
		 * @throws  CustomException    Execution is stopped when any validation fails.
		 *
		 * @return  array|bool    Returns an array or false. If there are validation errors there is an additional 'validationMessage' key added to the output array. This can be checked for to see if errors were raised.
		 */
		// Validation *****************************************************************************
		/*
		 * array (
  0 'employee_id' => '4828',
  1 'first_name' => 'Sally',
  2 'last_name' => 'Jones',
  3 'date_of_birth' => '01-01-1980',
  4 'annual_salary' => '35,145.00',
  5 'employee_life_amount' => '150,000.00',
  6 'employee_add_amount' => '5,000.00',
  7 'spouse_life_amount' => '50,000.00',
  8 'spouse_add_amount' => '10,000.00',
  9 'child_life_amount' => '10,000.00',
  10 'child_add_amount' => '5,000.00',
  11 'std' => 'Short-Term Disability',
  12 'ltd' => 'Long-Term Disability',
  13 'employee_critical_illness' => '$25,000 Coverage (Non-Smoker)',
  14 'spouse_critical_illness' => '$25,000 Coverage (Non-Smoker)',
  15 'child_critical_illness' => '$5,000 Coverage',
  16 'employee_tobacco_status' => 'Non Tobacco',
  17 'spouse_tobacco_status' => 'Non Tobacco',
  18 'Benefit Elections - Effective Date' => 'Effective as of 01/01/2016 - 01/01/2016',
  19 'Job Information - Effective Date' => 'Effective as of 01/01/2016 - 01/01/2016',
  20 'Job Information - Effective Sequence' => 'Effective as of 01/01/2016 - 01/01/2016'

			)
		 */
		try{
			// Remove all of the double quotes.
			foreach( $row as $value ){
				str_replace('"', '', $value);
			}
			$reportObject = new SunLifeReportValidateRows($this->Debug, $this->Message); // I'm using a class here to make it easier to deal with all of these variables. They have to be modified and then exported in a specific order to work with the related mysql query. If I was to use an array it would be too easy for them to get out of order, miss a value, or be  incorrectly indexed. The class handles all of the data easily in one organized file and can be easily modified to make changes or add fields.

			// Look for missing employee_id.
			$errors = array();
			$row[0] = intval($row[0]);

			if( empty($row[0]) || preg_match("/^\s*$/", $row[0]) === 1 ){
				$errors[] = 'The employee id is missing.';
			}

			// Is employee_id numeric?
			if( !is_numeric($row[0]) ){
				$errors[] = 'The employee id is not numeric.';
			}
			$reportObject->setEmployeeId($row[0]);
			$reportObject->setFirstName($row[1]);
			$reportObject->setLastName($row[2]);

			// Look for missing date of birth.
			if( empty($row[3]) ){
				$errors[] = 'Date of birth missing.';
			}

			// Is date of birth a real date?
			if( !Time::isRealDate($row[3]) ){
				$errors[] = 'Date of birth is not a real date.';
			}

			// Format data for database storage. Dates should be date compatible.
			$reportObject->setDateOfBirth($row[3]);

			// Convert string currency values to integers.
			$reportObject->setAnnualSalary(parent::numberWithCommasAndDecimalToInt($row[4]));
			$reportObject->setEmployeeLifeAmount(parent::numberWithCommasAndDecimalToInt($row[5]));
			$reportObject->setEmployeeAddAmount(parent::numberWithCommasAndDecimalToInt($row[6]));
			$reportObject->setSpouseLifeAmount(parent::numberWithCommasAndDecimalToInt($row[7]));
			$reportObject->setSpouseAddAmount(parent::numberWithCommasAndDecimalToInt($row[8]));
			$reportObject->setChildLifeAmount(parent::numberWithCommasAndDecimalToInt($row[9]));
			$reportObject->setChildAddAmount(parent::numberWithCommasAndDecimalToInt($row[10]));

			// Convert STD and LTD to booleans.
			$reportObject->setStd($row[11] == 'Short-Term Disability' ? true : false);
			$reportObject->setLtd($row[12] == 'Long-Term Disability' ? true : false);

			// Is critical illness inside 256 characters?
			if( strlen($row[13]) > 256 ){
				$errors[] = 'The employee critical illness field is longer than the maximum 256 characters allowed in the database.';
			}

			if( strlen($row[14]) > 256 ){
				$errors[] = 'The spouse critical illness field is longer than the maximum 256 characters allowed in the database.';
			}
			$reportObject->setSpouseCriticalIllness($row[14]);

			if( strlen($row[15]) > 256 ){
				$errors[] = 'The child critical illness field is longer than the maximum 256 characters allowed in the database.';
			}
			$reportObject->setChildCriticalIllness($row[15]);

			// Split the employee critical illness field into the amount and smoker vs non-smoker.
			$reportObject->employeeTobaccoStatus($row[13], $row[16]);

			// Split the spouse critical illness field into the amount and smoker vs non-smoker.
			$reportObject->spouseTobaccoStatus($row[14], '');

			// Set the date of report.
			$reportObject->setDateOfReport($row[17]);
			$this->_dateOfReport = $reportObject->getDateOfReport();

			// Set the age.
			$reportObject->setAge();
			if( array_key_exists($reportObject->getEmployeeId(), $this->_employeeArray) ){
				$errors[] = 'The employee is listed twice.';
				$this->duplicateArray[] = array($this->_employeeArray[$reportObject->getEmployeeId()], $row);
			}

			$this->_employeeArray[$reportObject->getEmployeeId()] = $row;

			if( !empty($errors) ){
				$row['validationErrors'] = $errors;
			}
			$outputArray = $reportObject->outputForQuery();
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			return false;
		}
		return $outputArray;
	}

	public static function wrapInTd($string) {
		return "<td>$string</td>";
	}
}