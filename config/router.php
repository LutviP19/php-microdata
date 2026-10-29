<?php

/**
 * Router url to Base Model file
 * file: config/router.php
 */
return [    
    "auth" => "Auth",
    "roles" => "Auth",
    "dashboard" => "Dashboard",

    // Auth
    "/api/v2/auth-login" => "AuthLogin",

    // Version 1 - run on worker FrankenPHP
    "/api/v1/auth" => "v1/Auth",
    "/api/v1/dashboard" => "v1/Dashboard",

    // Version 2
    "/api/v2/auth" => "v2/Auth",
    "/api/v2/dashboard" => "v2/Dashboard",
];
