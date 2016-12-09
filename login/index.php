<?php
/**
 * Created by PhpStorm.
 * User: morussa
 * Date: 11/18/2016
 * Time: 5:00 PM
 */

require '../config.php';
$Page->setTitleAndFilename('Login','login/index.php');
$Page->addJs('https://www.google.com/recaptcha/api.js','async defer');
$Page->addBody($Auth->buildLogin());
echo $Page;