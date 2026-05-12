<?php

/**
 * Router url to Base Model file
 * file: config/router.php
 */
return [
    "auth" => "Auth",
    "roles" => "Auth",
    "dashboard" => "Dashboard",

    // Version 1 - run on worker FrankenPHP
    "/api/v1/auth" => "v1/Auth",
    "/api/v1/dashboard" => "v1/Dashboard",
];
