<?php
namespace Embassy;
/**
 * Created by PhpStorm.
 * User: Mark O'Russa
 * Date: 5/9/2016
 * Time.php: 3:38 PM
 *
 * This tool is for entering data into
 *
 * Sample CSV files can be found in a zip folder.
 * Better instructions can also be found in a file called "Imagine_CentralReach_To_ADP_Interface_Coding_Instructions.pdf"
 * This application uses a database to temporarily store and select data.
 */
Class OtherTable extends PayrollInterface {

	// Properties
	private $_dataTypes;

	public function __construct() {
		$this->_dataTypes = array('boolean' => 'BOOL', 'date' => 'DATE', 'datetime' => 'DATETIME', 'int' => 'INT(10)', 'float' => 'FLOAT', 'string' => 'VARCHAR (256)', 'text' => 'TEXT', 'decimal (currency)' => 'DECIMAL');
		try{
			parent::__construct();
			if( MODE == 'otherTableAddColumn' ){
				self::otherTableAddColumn();
			}elseif( MODE == 'otherTableAddColumn' ){
				self::otherTableAddColumn();
			}elseif( MODE == 'otherTableAddTable' ){
				self::otherTableAddTable();
			}elseif( MODE == 'otherTableDeleteTable' ){
				self::otherTableDeleteTable();
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

	public function submitFile($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) {
		try{
			if( parent::processOtherTable($formFileInputName, $saveDirectory, $outgoingDirectory, $databaseTableName) === false ){
				throw new CustomException('', 'The parent method process returned false.');
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
	}

	public function otherTableAddColumn() {
		$dataTypesOutput = '<select class="otherTableDataType"><option>Select</option>';
		foreach( $this->_dataTypes as $key => $value ){
			$dataTypesOutput .= '<option>' . $key . '</option>';
		}
		$dataTypesOutput .= '</select>';
		$tableStart = '<table>
	<tbody id="otherTableTbody">
		<tr>
			<td>Data Type</td><td>Column Name</td><td>Allow NULL?</td><td></td>
		</tr>
';
		$row = '<tr class="hasData"><td>' . $dataTypesOutput . '</td><td><input type="text" class="otherTableColumnName"></td><td><select class="otherTableAllowNull"><option>Yes</option><option>No</option></select></td><td><span class="makeButtonInline otherTableAddColumn"><i class="fa fa-plus"></i> Add Column</span><span class="makeButtonInline otherTableDeleteColumn hide"><i class="fa fa-minus"></i> Remove Column</span></td></tr>';
		$tableEnd = '	</tbody>
</table>
<div class="makeButtonInline" id="otherTableAddTable">Add Table</div> ';
		$rowSupply = '<table class="otherTableRowSupply hide">' . $row . '</table>';
		$this->Success = true;
		$this->ReturnThis['otherTableAddColumn'] = $row;
		if( MODE == 'otherTableAddColumn' ){
			returnData('otherTableAddColumn');
		}else{
			return $tableStart . $row . $tableEnd . $rowSupply;
		}
		// TODO: add a delete row button?
	}

	private function otherTableAddTable() {
		try{
			if( empty($_POST['otherTableDataType0']) ){
				throw new CustomException('', '$_POST[\'otherTableDataType0\'] is empty.');
			}
			$query = "CREATE TABLE IF NOT EXISTS otherTable" . $_POST['otherTableTableName'] . "(
			`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";
			for( $x = 0; isset($_POST['otherTableDataType' . $x]); $x++ ){
				$type = $this->_dataTypes[$_POST['otherTableDataType' . $x]] == 'DECIMAL' ? 'DECIMAL (10,2)' : $this->_dataTypes[$_POST['otherTableDataType' . $x]];
				$query .= ', `' . $_POST['otherTableColumnName' . $x] . '` ' . $type . ' ' . self::mysqlNull($_POST['otherTableAllowNull' . $x]);
				$this->Debug->add('otherTableDataType' . $x . ': ' . $_POST['otherTableDataType' . $x]);
				$this->Debug->add('otherTableColumnName' . $x . ': ' . $_POST['otherTableColumnName' . $x]);
				$this->Debug->add('otherTableAllowNull' . $x . ': ' . $_POST['otherTableAllowNull' . $x]);
			}
			$query .= ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
			$this->Debug->add('$query: ' . $query);
			$this->Dbc->query($query);
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
		$this->Success = true;
		$this->ReturnThis['list'] = self::otherTableGetTables();
		$this->ReturnThis['message'] = 'Added the table.';
		if( MODE == 'otherTableAddTable' ){
			returnData('otherTableAddTable');
		}else{
			return '';
		}
	}

	public function otherTableGetTables() {
		// Get all 'otherTable' tables.
		try{
			$otherTableGetTables = $this->Dbc->prepare("SHOW TABLES WHERE tables_in_payroll_interfaces LIKE '%othertable%' ");
			$otherTableGetTables->execute();
			$output = '<ul>';
			$foundRows = false;
			while( $row = $otherTableGetTables->fetch(PDO::FETCH_NUM) ){
				$describeTableStmt = $this->Dbc->query('DESCRIBE ' . $row[0]);
				$describeTableStmt->execute();
				// Build the table structure view.
				$tableRows = '';
				while( $tableInfo = $describeTableStmt->fetch(PDO::FETCH_ASSOC) ){
//					$this->Debug->add($tableInfo, '$tableInfo');
					$tableHeader = '<tr>';
					$tableRows .= '<tr>';
					foreach( $tableInfo as $key => $value ){
						$tableHeader .= '<td style="font-weight:bold">' . $key . '</td>';
						$tableRows .= '<td>' . $value . '</td>';
					}
					$tableRows .= '<tr>';
					$tableHeader .= '</tr>';
				}
				$output .= '<li>
<i class="fa fa-close otherTableDeleteTable red" data-tableName="' . self::removePrefix($row[0]) . '"></i> <input type="radio" name="otherTableSelectTable" value="' . $row[0] . '"> ' . self::removePrefix($row[0]) . '<div class="toggleButtonInline" style="margin-left:.5em">View Table Structure</div><div class="toggleMe"><table>' . $tableHeader . $tableRows . '</table></div>
</li>';
				$foundRows = true;
			}
			if( !$foundRows ){
				$output .= '<li>There are no existing tables.</li>';
			}
			$output .= '</ul>';
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
		$this->Success = true;
		$this->ReturnThis['otherTableGetTables'] = $output;
		if( MODE == 'otherTableGetTables' ){
			returnData('otherTableGetTables');
		}else{
			return $output;
		}
	}

	public function otherTableDeleteTable() {
		try{
			if( empty($_POST['tableName']) ){
				throw new CustomException('', '$_POST[\'tableName\'] is empty.');
			}
			$deleteTableStmt = $this->Dbc->prepare("DROP TABLE IF EXISTS othertable" . $_POST['tableName']);
			$deleteTableStmt->execute();
			$this->Success = true;
			$this->ReturnThis['list'] = self::otherTableGetTables();
			$this->ReturnThis['message'] = 'Deleted the table.';
		}catch( CustomException $e ){
			returnData('otherTableDeleteTable');
		}catch( ErrorException $e ){
			$this->Debug->error(__LINE__, '', $e);
			returnData('otherTableDeleteTable');
		}catch( Exception $e ){
			$this->Debug->error(__LINE__, '', $e);
			returnData('otherTableDeleteTable');
		}
		returnData('otherTableDeleteTable');
	}

	private function mysqlNull($test) {
		return $test == 'Yes' ? 'NULL' : 'NOT NULL';
	}

	private function removePrefix($thing) {
		// This is for removing the 'otherTable' prefix from the table names.
		$parts = explode('othertable', $thing);
		return $parts[1];
	}

}