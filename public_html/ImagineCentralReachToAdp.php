<?php
require_once('../config.php');
$Page->setTitleAndFilename('Imagine CentralReach to ADP Interface Output', 'ImagineCentralReachToAdp.php');
$Page->addJs('interfaces.js');
$Debug->add('Starting the interface.');
$ImagineCentralReachToAdp = new Embassy\ImagineCentralReachToAdp($Ajax, $Dbc, $Debug, $Message,'imagine_file', './uploads', './downloads', 'centralreach_payroll_interface');
$fileInfo = $ImagineCentralReachToAdp->getFileInfo();
$Page->addBody('<!-- Link back to start page -->
    <p><a class="makeButtonInline" href="index.php"><i class="fa fa-arrow-right fa-rotate-180"></i>Return to Payroll Interfaces</a></p>
    <div>Incoming file: ' . $fileInfo['filename'] . ' (' . round($fileInfo['size'], 1) . ' KB)</div>
	<span style="font-weight:bold">Step 3:</span>' . $ImagineCentralReachToAdp->getOutgoingRegularFileButton() . $ImagineCentralReachToAdp->getOutgoingTravelFileButton() . $ImagineCentralReachToAdp->getInvalidData() . $ImagineCentralReachToAdp->getDuplicateEntries() . $ImagineCentralReachToAdp->getOverlappingEntries() . $ImagineCentralReachToAdp->getUnrecognizedJobCodes());
/*if(stripos($_SERVER['PHP_SELF'],'Imagine') !== false){
	$tempFiles = $_FILES;
	print_r($_FILES);
	die();
}*/
echo $Page;