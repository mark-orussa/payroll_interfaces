<?php
require '../config.php';
$Page->setTitle('Debug Log');
$Page->addBody($Debug->readLog());
print $Page->specialSauce();