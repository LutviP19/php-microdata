<?php 

/**
 * Router url to Base Model file
 */


// Static router for execute file php langsung
include "static-router.php";

// Mapping Models
$models = include "models.php";

// Dynamic Models router
$router = [
    'auth' => 'Auth',
    'roles' => 'Auth',
    'home' => 'Dashboard',
    'dashboard' => 'Dashboard',
];
