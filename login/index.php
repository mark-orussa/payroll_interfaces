<?php
/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 11/18/2016
 * Time: 5:00 PM
 */

require '../config.php';
$Auth = new Embassy\Auth($Debug, $Dbc, $Message);
$Page->addBody($Auth->buildLogin());
echo $Page->toString();