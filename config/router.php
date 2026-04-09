<?php 

/**
 * Router url to Base Model file
 */


// Static router for execute single php file
include "static-router.php";

// Mapping Models
$models = include "models.php";

// Dynamic router path to Model
$router = [
    'auth' => 'Auth',
    'roles' => 'Auth',
    'dashboard' => 'Dashboard',

    // Version 1
    '/api/v1/dashboard' => 'v1/Dashboard',
];
