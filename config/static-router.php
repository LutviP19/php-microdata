<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */
// file: config/static-router.php
// simple routing untuk execute file static php
// You can also run FFi ​​code if needed with single php script

$staticFile = BASEPATH . '/static/' .$page.'.php';
if (file_exists($staticFile)) {
    // Include File PHP static.
    include $staticFile;
    exit();
}
