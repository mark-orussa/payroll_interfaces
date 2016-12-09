<?php require_once('config.php');
$Page->setTitleAndFilename('Other File Output', 'OtherFile.php');
$Page->addJs('interfaces.js');
if(!empty($_POST['otherTableSelectTable'])){
	$OtherFile = new Embassy\OtherTable($Ajax, $Dbc, $Debug, $Message);
	$OtherFile->submitFile('other_file', './uploads', './downloads', $_POST['otherTableSelectTable']);
	$Page->addBody('<!-- Link back to start page -->
    <p><a class="makeButtonInline" href="index.php"><i class="fa fa-arrow-right fa-rotate-180"></i>Return to Payroll Interfaces</a></p>');
}else{
	$Debug->add('Couln\'t find the file.');
}
echo $Page;