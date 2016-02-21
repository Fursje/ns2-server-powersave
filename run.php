<?php

require_once(dirname(__FILE__)."/powersave.class.php");

$ps = new powersave();
$ps->AddServer("80.127.150.123","29011");
$ps->run();

?>
