<?php

/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 8/18/2016
 * Time: 4:55 PM
 */
class SunLifeReportValidateRows {
	protected $_Debug;
	protected $_Message;

	public $_employee_id;
	public $_first_name;
	public $_last_name;
	public $_date_of_birth;
	public $_age;
	public $_annual_salary;
	public $_employee_life_amount;
	public $_employee_add_amount;
	public $_spouse_life_amount;
	public $_spouse_add_amount;
	public $_child_life_amount;
	public $_child_add_amount;
	public $_std;
	public $_ltd;
	public $_employee_critical_illness;
	public $_spouse_critical_illness;
	public $_child_critical_illness;
	public $_employee_tobacco_status;
	public $_spouse_tobacco_status;
	public $_date_of_report;

	public function __construct() {
		global $Debug, $Message;
		$this->_Debug = &$Debug;
		$this->_Message = &$Message;
	}

	/**
	 * @return mixed
	 */
	public function getEmployeeId() {
		return $this->_employee_id;
	}

	/**
	 * @param mixed $employee_id
	 */
	public function setEmployeeId($employee_id) {
		$this->_employee_id = $employee_id;
	}

	/**
	 * @return mixed
	 */
	public function getFirstName() {
		return $this->_first_name;
	}

	/**
	 * @param mixed $first_name
	 */
	public function setFirstName($first_name) {
		$this->_first_name = $first_name;
	}

	/**
	 * @return mixed
	 */
	public function getLastName() {
		return $this->_last_name;
	}

	/**
	 * @param mixed $last_name
	 */
	public function setLastName($last_name) {
		$this->_last_name = $last_name;
	}

	/**
	 * @return mixed
	 */
	public function getDateOfBirth() {
		return $this->_date_of_birth;
	}

	/**
	 * @param mixed $date_of_birth
	 */
	public function setDateOfBirth($date_of_birth) {
		$dob = Time::convertToDateTime($date_of_birth);
		$date_of_birth = Time::mysqlDate($dob);
		$this->_date_of_birth = $date_of_birth == '1969-12-31' ? '' : $date_of_birth;
	}

	/**
	 * @return mixed
	 */
	public function getAge() {
		return $this->_age;
	}

	/**
	 * @param mixed $age
	 */
	public function setAge() {
		// Calculate the age.
		$date_of_reportDatetime = new DateTime(self::getDateOfReport());
		$date_of_birthDatetime = new DateTime(self::getDateOfBirth());
		$diff = $date_of_birthDatetime->diff($date_of_reportDatetime);
		$this->_age = $diff->format('%y');
	}

	/**
	 * @return mixed
	 */
	public function getAnnualSalary() {
		return $this->_annual_salary;
	}

	/**
	 * @param mixed $annual_salary
	 */
	public function setAnnualSalary($annual_salary) {
		$this->_annual_salary = $annual_salary;
	}

	/**
	 * @return mixed
	 */
	public function getEmployeeLifeAmount() {
		return $this->_employee_life_amount;
	}

	/**
	 * @param mixed $employee_life_amount
	 */
	public function setEmployeeLifeAmount($employee_life_amount) {
		$this->_employee_life_amount = $employee_life_amount;
	}

	/**
	 * @return mixed
	 */
	public function getEmployeeAddAmount() {
		return $this->_employee_add_amount;
	}

	/**
	 * @param mixed $employee_add_amount
	 */
	public function setEmployeeAddAmount($employee_add_amount) {
		$this->_employee_add_amount = $employee_add_amount;
	}

	/**
	 * @return mixed
	 */
	public function getSpouseLifeAmount() {
		return $this->_spouse_life_amount;
	}

	/**
	 * @param mixed $spouse_life_amount
	 */
	public function setSpouseLifeAmount($spouse_life_amount) {
		$this->_spouse_life_amount = $spouse_life_amount;
	}

	/**
	 * @return mixed
	 */
	public function getSpouseAddAmount() {
		return $this->_spouse_add_amount;
	}

	/**
	 * @param mixed $spouse_add_amount
	 */
	public function setSpouseAddAmount($spouse_add_amount) {
		$this->_spouse_add_amount = $spouse_add_amount;
	}

	/**
	 * @return mixed
	 */
	public function getChildLifeAmount() {
		return $this->_child_life_amount;
	}

	/**
	 * @param mixed $child_life_amount
	 */
	public function setChildLifeAmount($child_life_amount) {
		$this->_child_life_amount = $child_life_amount;
	}

	/**
	 * @return mixed
	 */
	public function getChildAddAmount() {
		return $this->_child_add_amount;
	}

	/**
	 * @param mixed $child_add_amount
	 */
	public function setChildAddAmount($child_add_amount) {
		$this->_child_add_amount = $child_add_amount;
	}

	/**
	 * @return mixed
	 */
	public function getStd() {
		return $this->_std;
	}

	/**
	 * @param mixed $std
	 */
	public function setStd($std) {
		$this->_std = $std;
	}

	/**
	 * @return mixed
	 */
	public function getLtd() {
		return $this->_ltd;
	}

	/**
	 * @param mixed $ltd
	 */
	public function setLtd($ltd) {
		$this->_ltd = $ltd;
	}

	/**
	 * @return mixed
	 */
	public function getEmployeeCriticalIllness() {
		return $this->_employee_critical_illness;
	}

	/**
	 * @param mixed $employee_critical_illness
	 */
	public function setEmployeeCriticalIllness($employee_critical_illness) {
		$this->_employee_critical_illness = $employee_critical_illness;
	}

	/**
	 * @return mixed
	 */
	public function getSpouseCriticalIllness() {
		return $this->_spouse_critical_illness;
	}

	/**
	 * @param mixed $spouse_critical_illness
	 */
	public function setSpouseCriticalIllness($spouse_critical_illness) {
		$this->_spouse_critical_illness = $spouse_critical_illness;
	}

	/**
	 * @return mixed
	 */
	public function getChildCriticalIllness() {
		return $this->_child_critical_illness;
	}

	/**
	 * @param mixed $child_critical_illness
	 */
	public function setChildCriticalIllness($child_critical_illness) {
		if( !empty($child_critical_illness) ){
			$pattern = '/^\$(\d+),?(\d+)? Coverage( \((\w+-*\w*)\))?$/';
			preg_match_all($pattern, $child_critical_illness, $matches);
			array_splice($matches, 0, 1);
			$criticalIllness = $matches[0][0] . $matches[1][0];
		}else{
			$criticalIllness = 0;
		}
		$this->_child_critical_illness = $criticalIllness;
	}

	/**
	 * @return mixed
	 */
	public function getEmployeeTobaccoStatus() {
		return $this->_employee_tobacco_status;
	}

	/**
	 * @param mixed $employee_tobacco_status
	 */
	public function setEmployeeTobaccoStatus($employee_tobacco_status) {
		$this->_employee_tobacco_status = $employee_tobacco_status;
	}

	/**
	 * @return mixed
	 */
	public function getSpouseTobaccoStatus() {
		return $this->_spouse_tobacco_status;
	}

	/**
	 * @param mixed $spouse_tobacco_status
	 */
	public function setSpouseTobaccoStatus($spouse_tobacco_status) {
		$this->_spouse_tobacco_status = $spouse_tobacco_status;
	}

	/**
	 * @return mixed
	 */
	public function getDateOfReport() {
		return $this->_date_of_report;
	}

	/**
	 * @param mixed $date_of_report
	 */
	public function setDateOfReport($string) {
		// The date of the report. This affects the calculation of the employee age.
		preg_match('/\d{2}\/\d{2}\/\d{4}/', $string, $date_of_report); // Pull the date out of the string.
		$date_of_report = $date_of_report[0];
		if( Time::isRealDate($date_of_report) === false ){
			throw new CustomException('There is a problem with the date of the report.', 'There is a problem with the date of the report. The incoming date column is ' . $string . '. This is stripped to ' . $date_of_report . '.');
		}
		$date_of_report = Time::mysqlDate($date_of_report);
		if( $date_of_report === false ){
			throw new CustomException('', 'The date of report could not be converted to a mysql date.');
		}
		$this->_date_of_report = $date_of_report;
	}
	
	public function outputForQuery() {
		/*
		 * The output needs to match the insert query.
		 *
		 * employee_id = ?,
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
  date_of_report = ?
		 */
		return array(
			$this->_employee_id,
			$this->_first_name,
			$this->_last_name,
			$this->_date_of_birth,
			$this->_age,
			$this->_annual_salary,
			$this->_employee_life_amount,
			$this->_employee_add_amount,
			$this->_spouse_life_amount,
			$this->_spouse_add_amount,
			$this->_child_life_amount,
			$this->_child_add_amount,
			$this->_std,
			$this->_ltd,
			$this->_employee_critical_illness,
			$this->_spouse_critical_illness,
			$this->_child_critical_illness,
			$this->_employee_tobacco_status,
			$this->_spouse_tobacco_status,
			$this->_date_of_report
		);
	}

	public function employeeTobaccoStatus($criticalIllness, $tobacco) {
		/**
		 * This is for splitting the employee critical illness fields into amount and tobacco status.
		 * This does not apply to child critical illness as they don't have a tobacco status.
		 * @param $criticalIllness    The field to be split.
		 * @param $tobacco            The tobacco status, if set.
		 * @return    null    This sets the values for critical illness and tobacco status.
		 */
		if( !empty($criticalIllness) ){
			$pattern = '/^\$(\d+),?(\d+)? Coverage( \((\w+-*\w*)\))?$/';
			preg_match_all($pattern, $criticalIllness, $matches);
			array_splice($matches, 0, 1);
			$criticalIllness = $matches[0][0] . $matches[1][0];
		}else{
			$matches[2][0] = '';
			$criticalIllness = 0;
		}
		// Set tobacco status. This can be derived from two different fields: tobacco_status, or part of critical illness. These are the only two that report this information and they ought to be in agreement with each other, but both will probably not co-exist.
		if( $tobacco == 'Tobacco' || $matches[2][0] == 'Smoker' ){
			$tobacco = true;
		}else{
			$tobacco = false;
		}
		self::setEmployeeCriticalIllness($criticalIllness);
		self::setEmployeeTobaccoStatus($tobacco);
	}

	public function spouseTobaccoStatus($criticalIllness, $tobacco) {
		/**
		 * This is for splitting the spouse critical illness fields into amount and tobacco status.
		 * This does not apply to child critical illness as they don't have a tobacco status.
		 * @param $criticalIllness    The field to be split.
		 * @param $tobacco            The tobacco status, if set.
		 * @return    null    This sets the values for critical illness and tobacco status.
		 */
		if( !empty($criticalIllness) ){
			$pattern = '/^\$(\d+),?(\d+)? Coverage \((\w+-*\w*)\)$/';
			preg_match_all($pattern, $criticalIllness, $matches);
			array_splice($matches, 0, 1);
			$criticalIllness = $matches[0][0] . $matches[1][0];
		}else{
			$matches[2][0] = '';
			$criticalIllness = 0;
		}
		// Set tobacco status. This can be derived from two different fields: tobacco_status, or part of critical illness. These are the only two that report this information and they ought to be in agreement with each other, but both will probably not co-exist.
		if( $tobacco == 'Tobacco' || $matches[2][0] == 'Smoker' ){
			$tobacco = true;
		}else{
			$tobacco = false;
		}
		self::setSpouseCriticalIllness($criticalIllness);
		self::setSpouseTobaccoStatus($tobacco);
	}

}