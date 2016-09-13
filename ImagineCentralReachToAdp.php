<?php require_once('includes/config.php');
$Page = new Page('Imagine CentralReach to ADP Interface Output', 'ImagineCentralReachToAdp.php');
$Page->setRequireAuth(true);
$Page->addJs('interfaces.js');
$ImagineCentralReachToAdp = new ImagineCentralReachToAdp('imagine_file', './uploads', './downloads', 'centralreach_payroll_interface');
$fileInfo = $ImagineCentralReachToAdp->getFileInfo();
$Page->addBody('<!-- Link back to start page -->
    <p><a class="makeButtonInline" href="index.php"><i class="fa fa-arrow-right fa-rotate-180"></i>Return to Payroll Interfaces</a></p>
    <div>Incoming file: ' . $fileInfo['filename'] . ' (' . round($fileInfo['size'], 1) . ' KB)</div>
	<span style="font-weight:bold">Step 3:</span>' . $ImagineCentralReachToAdp->getOutgoingRegularFileButton() . $ImagineCentralReachToAdp->getOutgoingTravelFileButton() . $ImagineCentralReachToAdp->getInvalidData() . $ImagineCentralReachToAdp->getDuplicateEntries() . $ImagineCentralReachToAdp->getOverlappingEntries() . $ImagineCentralReachToAdp->getUnrecognizedJobCodes());
echo $Page->output();