<?php
 
require 'tinklit/tinklit_lib.php';
require 'includes/application_top.php';

$filename = DIR_FS_LOGS.'/tinklit_'.date('Ymd-his').'.log';
$fp = fopen($filename, "w");

fwrite($fp, 'POST:'."\r\n");
foreach ($_POST as $key => $value) {
    fwrite($fp, $key.' => '.$value."\r\n");
}

fwrite($fp, 'GET:'."\r\n");
foreach ($_GET as $key => $value) {
    fwrite($fp, $key.' => '.$value."\r\n");
}

fclose($fp);

?>