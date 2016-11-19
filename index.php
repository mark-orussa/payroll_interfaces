<?php
/**
 * Created by PhpStorm.
 * User: Mark O'Russa
 * Date: 5/18/2016
 * Time: 11:48 AM
 */
define('FORCEHTTPS', true, true);
require_once('config.php');
$Page->setTitleAndFilename('Payroll Interfaces', 'index.php');
$Page->addJs('interfaces.js');

$Interface = new Embassy\PayrollManagement();
$OtherTable = new Embassy\OtherTable();
$SunLife = new Embassy\ADPToSunLife();
// Title and javascript warning.
$Page->addBody('<div class="pageTitle">' . $Page->getTitle() . '</div>
<div><noscript>JavaScript must be enabled to use this page. <a href="https://www.google.com/search?q=how+to+enable+javascript" target="_blank">How to enable Javascript</a></noscript></div>');

// Documentation
$Page->addBody('<div class="toggleButton">Documentation</div>
<div class="toggleMe" style="text-align:center">
<p><a href="' . LINKDOCUMENTS . '/How_to_use_the_Imagine_CentralReach_to_ADP_Interface.pdf" target="_blank">How_to_use_the_Imagine_CentralReach_to_ADP_Interface.pdf<i class="fa fa-external-link"></i></a></p>
<p><a href="' . LINKDOCUMENTS . '/How_to_use_the_SLStart_CentralReach_to_ADP_Interface.pdf" target="_blank">How_to_use_the_SLStart_CentralReach_to_ADP_Interface.pdf<i class="fa fa-external-link"></i></a></p>
<p><a href="' . LINKDOCUMENTS . '/How_to_use_the_Payroll_Interfaces_Management_Sections.pdf" target="_blank">How_to_use_the_Payroll_Interfaces_Management_Sections.pdf<i class="fa fa-external-link"></i></a></p>
</div>');

// Imagine Interface
$Page->addBody('<div class="toggleButton">
	<img src="images/imagine.png"> <img src="images/centralreach.png"><i class="fa fa-arrow-right"></i><img src="images/adp_logo_red.png"></a>
</div>
<div class="toggleMe">
	<p>This interface has been designed to process CSV files directly from Imagine Central Reach without any modifications or sorting.</p>
	<p>It will produce two CSV files - one with regular hours, one with travel hours.</p>
	<p style="color:red">* Does not work with IDS. *</p>
	<form action="ImagineCentralReachToAdp.php" method="post" enctype="multipart/form-data">
		<label for="imagine_file">Step 1:</label>
		<input type="file" name="imagine_file" id="imagine_file">
		<p>
		<label for="submit">Step 2:</label>
		<input type="submit" name="submit" value="Process">
		</p>
	</form>
	<div class="toggleButton">Manage Master Level Employees</div>
	<div class="toggleMe">
	<p>The employees referenced by the EmpXRef codes in the list below will have a 1 or 2 appended to their job codes, but only for these specific job codes:</p>
	<p>Level 1: BSMABILL, BSMANONBILL, BSMAOB, BSPHDBILL, BSPHDNONBILL, BSPHDOB.<br>
	Level 2: BSMABILL, BSMANONBILL</p>
	<p>Enter an EmpXRef code and level to add a new entry.</p>
		<table>
			<tr>
				<td align="center">Master Level EmpXRef<div style="font-weight: normal">(i.e. 2568)</div></td><td align="center">Level<div style="font-weight: normal">(i.e. 1 or 2)</div></td>
			</tr>
			<tr>
				<td align="center"><input name="newMasterLevelEmpXRef" id="newMasterLevelEmpXRef"></td><td align="center"><input name="newMasterLevel" id="newMasterLevel"></td>
			</tr>
			<tr>
				<td align="center" colspan="2"><input type="button" id="addNewMasterLevelEmpXRef" class="makeButton" value="Add Master Level EmpXRef to List"> <span id="MasterLevelMessage" class="error"></span>
				<div id="MasterLevelEmpXRefMessage" class="error" style="padding-top:.5em"></div>
				</td>
			</tr>
		</table>
		<p>List of Master Level EmpXRef</p>
		<div class="XRefContainer" id="masterLevelEmpXRefContainer">' . $Interface->listMasterLevelEmpXRef() . '</div>
	</div>
</div>
');

// SLStart Interface
$Page->addBody('<div class="toggleButton">
	<img src="images/sl-start.png"><img src="images/centralreach.png"><i class="fa fa-arrow-right"></i><img src="images/adp_logo_red.png"></a>
</div>
<div class="toggleMe">
	<p>This interface has been designed to process CSV files directly from SL Start Central Reach without any modifications or sorting.</p>
	<p>It will produce one CSV file suitable for upload to ADP.</p>
	<p style="color:red">* Does not work with IDS. *</p>
	<form action="SLStartCentralReachToAdp.php" method="post" enctype="multipart/form-data">
		<label for="slstart_file">Step 1:</label>
		<input type="file" name="slstart_file" id="slstart_file">
		<p>
		<label for="submit">Step 2:</label>
		<input type="submit" name="submit" value="Process">
		</p>
	</form>
</div>
');

// JobXRef Management Section
$Page->addBody('	<div class="toggleButton">
	Manage ADP Job Codes
</div>
<div class="toggleMe">
	<div>
		<p>Job Codes need to be updated periodically. This management section provides the ability to add or delete them.</p>
		<p>Enter a JobXRef and JobCode to add a new set.</p>
		<table>
			<tr>
				<td align="center">JobXRef<div style="font-weight: normal">(i.e. HSMGMT, DTCHILDPARA)</div></td><td align="center">Job Code<div style="font-weight: normal">(i.e. 0632)</div></td>
			</tr>
			<tr>
				<td align="center"><input name="newJobXRef" id="newJobXRef"></td><td align="center"><input name="newJobCode" id="newJobCode"></td>
			</tr>
			<tr>
				<td align="center" colspan="2"><input type="button" id="addNewJobXRef" class="makeButton" value="Add JobXRef to List">
				<div id="JobXRefMessage" class="error" style="padding-top:.5em"></div>
				</td>
			</tr>
		</table>
	</div>
	<p>Below are the current JobXRef codes. Click on the red X to remove them from the list.</p>
	<div class="XRefContainer" id="JobXRefContainer">' . $Interface->listJobXRef() . '</div>
</div>
');

// Salaried Employee Management Section
$Page->addBody('<div class="toggleButton">
			Manage Salaried Employees
		</div>
		<div class="toggleMe">
			<p>The Central interfaces exclude salaried employees. This section is used to manage the salaried employees that will be ignored.</p>
			<label for="newEmpXRef">Enter the EmpXRef code for salaried employees, one at a time: </label><input name="newEmpXRef" id="newEmpXRef"> <input type="button" id="addNewEmpXRef" class="makeButton" value="Add EmpXRef to List"> <span id="EmpXRefMessage" class="error"></span>
			<p>The numbers below are current salaried EmpXRef numbers. These employees are not included in the Central Reach interfaces.<br>
			Click on the red X to remove the number from the list.</p>
			<div class="XRefContainer" id="EmpXRefContainer">' . $Interface->listEmpXRef() . '</div>
		</div>');

// For running other database files.
if( isset($_SESSION['admin']) && $_SESSION['admin'] === true ){
	$Page->addBody('<div class="toggleButton">
	Other Database Tools
</div>
<div class="toggleMe" id="otherTableContainer">
<p>This section allows for dynamic creation of tables and columns to allow for quick testing and manipulation of data. It accepts a CSV file that has a header row.</p>
<div id="otherTableProblem"></div>
	<form action="OtherTable.php" method="post" enctype="multipart/form-data">
		<div style="font-weight:bold">Step 1: Select a table</div>
			<div id="otherTableExistingTablesContainer">
				' . $OtherTable->otherTableGetTables() . '
			</div>
		<div class="toggleButtonInline">Add a table</div>
		<div class="toggleMe">
			<label for="otherTableTableName">New Table Name</label>
			<input type="text" id="otherTableTableName">(No spaces or special characters)
			<div id="otherTableFieldsContainer">' . $OtherTable->otherTableAddColumn() . '</div>
		</div>
		<div>
		<label for="other_file">Step 2: Select a file to upload</label>
		<input type="file" name="other_file" id="other_file">
		<p>
			<label for="submit">Step 3:</label>
			<input type="submit" name="submit" value="Process">
		</p>
		</div>
	</form>
</div>');
}

// Sun Life Interface
$Page->addBody('<div class="toggleButton">
			<img src="images/adp_logo_red.png"> <i class="fa fa-arrow-right"></i> <img src="images/sunLife.png"> (Beta)
		</div>
		<div class="toggleMe">
			<p>This interface will process a specific CSV-formatted report from ADP and produce calculated premiums for self-reporting benefit election information to Sun Life Financial.</p>
			<p><span class="bold">Step 1:</span> Log into <a href="http://adpvantage.adp.com/">http://adpvantage.adp.com/</a> and run the "Sun Life Self-Bill" ADP report. It is a public report that can be found under Run > Run Custom Reports. The report usually takes 3-4 minutes to process in ADP. This report has been specifically designed to work with this interface. The fields in the report should not be modified, but there are some settings that need to be set as shown in the image below.</p>
			<p>In the image below you can see that the dates are set for January 1, 2016. This will produce a report with benefit election information at that point in time.</p>
			<img src="images/sunLifeSelfBillDateSelection.jpg">
			<p><span class="bold">Step 2:</span> Once the report has finished processing you should download it in CSV format. This has been set as the default format so you should be able to click on the report name, but you can also click on the arrow to the right and select CSV (see the image below). Your browser will either prompt you to download it or automatically download it to your downloads folder.</p>
			<img src="images/selectingCSV.jpg">
			<div><span class="bold">Step 3:</span> Click the button below to select the CSV-formatted report you downloaded in the previous step.</div>
			<form action="ADPToSunLife.php" method="post" enctype="multipart/form-data">
				<input type="file" name="adp_to_sun_life_file" id="adp_to_sun_life_file">
				<p>
				<label for="submit">Step 4:</label>
				<input type="submit" name="submit" value="Process">
				</p>
			</form>
			<p>These are some useful documents about Sun Life Reporting:</p>
			<div><a href="documents/Embassy_Management_-_Sun_Life_Premium_Statement.xlsx">Embassy_Management_-_Sun_Life_Premium_Statement.xls</a></div>
			<div><a href="documents/Sun_Life_Self-Bill_Administration.pdf">Sun_Life_Self-Bill_Administration.pdf</a></div>
			<div class="toggleButton">Manage Rates</div>
			<div class="toggleMe">
				<p>Make changes to the Sun Life age bands, rates, volume calculation and more. Click the save button when finish.</p>
				<p>The information entered here directly affects the calculation of premiums for benefits.</p>
				<div id="sunLifeMessageContainer" class="interfaceResponse"></div>
				<div id="sunLifeRatesContainer">' . Embassy\ADPToSunLife::manageRates($Dbc, $Debug) . '</div>
			</div>
		</div>');
echo $Page->toString();