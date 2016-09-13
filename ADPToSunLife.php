<?php require_once('includes/config.php');
$Page = new Page('ADP to Sun Life Interface', 'ADPToSunLife.php');
$Page->setRequireAuth(true);
$Page->addJs('interfaces.js');
$adpToSunLife = new ADPToSunLife();
$adpToSunLife->beginAdpToSunLife('adp_to_sun_life_file', './uploads', './downloads', 'adp_to_sun_life');
$Page->addBody('<!-- Link back to start page -->
    <p><a class="makeButtonInline" href="index.php"><i class="fa fa-arrow-right fa-rotate-180"></i>Return to Payroll Interfaces</a></p>' .
	$adpToSunLife->output()
);
echo $Page->output();