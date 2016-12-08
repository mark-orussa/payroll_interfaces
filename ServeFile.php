<?php
/**
 * Created by PhpStorm.
 * User: Mark O'Russa
 * Date: 6/3/2016
 * Time: 4:23 PM
 */
require_once('config.php');
$Page->setTitleAndFilename('Serve File', 'ServeFile.php');
$loadPayrollInterface = new Embassy\PayrollInterface($Ajax, $Dbc, $Debug, $Message);
if( !empty($Message) ){
	$Page->addBody('<div>' . $Message . '</div>');
}
echo $Page;
$Debug->writeToLog();