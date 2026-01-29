<?php
/**
 * Init the Application
 * @author LutviP19 <lutvip19@gmail.com>
 */

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../..');
}

// Set defaut PHP conf
date_default_timezone_set('Asia/Jakarta');

/**
 * Require the composer autoload File.
 */
require_once BASEPATH .'/vendor/autoload.php';


// // Use Throwable for PHP 7+ to catch Errors as well
// set_exception_handler(function (Throwable $exception) {
//     // // Log the error internally (e.g., to a file)
//     // error_log("Uncaught exception: " . $exception->getMessage());

//     // Handler JSON Output
//     if (is_json_request()) { 
//         $status = 500;
//         $message = 'An internal error occurred. Please try again later.';
//         json_response([], $status, $message);
//     }
    
//     // Present a user-friendly view/response
//     http_response_code(500);
//     // echo "<h1>An internal error occurred. Please try again later.</h1>";
//     // // In a production environment, avoid echoing the raw message
//     include BASEPATH . "/views/500.php";
//     die();
// });