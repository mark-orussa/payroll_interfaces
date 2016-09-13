<?php require_once('includes/config.php');
$Page = new Page('Other File Output', 'OtherFile.php');
$Page->setRequireAuth(true);
$Page->addJs('interfaces.js');
if(!empty($_POST['otherTableSelectTable'])){
	$OtherFile = new OtherTable();
	$OtherFile->submitFile('other_file', './uploads', './downloads', $_POST['otherTableSelectTable']);
	$Page->addBody('<!-- Link back to start page -->
    <p><a class="makeButtonInline" href="index.php"><i class="fa fa-arrow-right fa-rotate-180"></i>Return to Payroll Interfaces</a></p>');
}else{
	$Debug->add('Couln\'t find the file.');
}
echo $Page->output();