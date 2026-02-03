<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */
// static-router.php
// simple routing untuk execute file static php 
// bisa juga untuk menjalankan kode FFi jika diperlukan

$staticFile = BASEPATH . '/static/' .$page.'.php';
if (file_exists($staticFile)) {
    // Include File PHP static.
    include $staticFile;
    exit();
}
