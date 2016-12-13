<?php require_once('../config.php');
$Page->setTitleAndFilename('SL Start CentralReach to ADP Interface Output', 'SLStartCentralReachToAdp.php');
$Page->addJs('interfaces.js');
$SlstartCentralReachToAdp = new Embassy\SLStartCentralReachToAdp($Ajax, $Dbc, $Debug, $Message,'slstart_file', './uploads', './downloads', 'centralreach_payroll_interface');
$fileInfo = $SlstartCentralReachToAdp->getFileInfo();
$Page->addBody('
    <!-- Link back to start page -->
    <p><a class="makeButtonInline" href="index.php"><i class="fa fa-arrow-right fa-rotate-180"></i>Return to Payroll Interfaces</a></p>
        <div>Incoming file: ' . $fileInfo['filename'] . ' (' . round($fileInfo['size'], 1) . ' KB)</div>
        <span style="font-weight:bold">Step 3:</span>' . $SlstartCentralReachToAdp->getOutgoingFile() . $SlstartCentralReachToAdp->getInvalidData() . $SlstartCentralReachToAdp->getDuplicateEntries() . $SlstartCentralReachToAdp->getOverlappingEntries() . $SlstartCentralReachToAdp->getUnrecognizedJobCodes());
echo $Page;
