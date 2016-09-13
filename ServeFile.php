<?php
/**
 * Created by PhpStorm.
 * User: Mark O'Russa
 * Date: 6/3/2016
 * Time: 4:23 PM
 */
require_once('includes/config.php');
$Page = new Page('Serve File', 'ServeFile.php');
$loadPayrollInterface = new PayrollInterface();
if( !empty($Message) ){
	$Page->addBody('<div>' . $Message . '</div>');
}
echo $Page->output();