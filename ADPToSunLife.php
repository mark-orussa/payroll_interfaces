<?php require_once('config.php');
$Page->setTitleAndFilename('ADP to Sun Life Interface', 'ADPToSunLife.php');
$Page->addJs('interfaces.js');
$adpToSunLife = new Embassy\ADPToSunLife($Ajax, $Dbc, $Debug, $Message);
$adpToSunLife->beginAdpToSunLife('adp_to_sun_life_file', './uploads', './downloads', 'adp_to_sun_life');
$Page->addBody('<!-- Link back to start page -->
    <p><a class="makeButtonInline" href="index.php"><i class="fa fa-arrow-right fa-rotate-180"></i>Return to Payroll Interfaces</a></p>' .
	$adpToSunLife->output()
);
echo $Page;
$Debug->writeToLog();