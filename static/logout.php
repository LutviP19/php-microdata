<?php 
// declare(strict_types=1);

// Pastikan session dimulai
if (session_status() === PHP_SESSION_NONE) {
    bp_session_start();
}

// Hancurkan session
\App\Core\Support\Session::destroy();

// Regenerate SessioId
$oldSessionId = session_id();
$headers = bp_session_regenerate_id($oldSessionId);
setHeaders($headers);

/**
 * STRATEGI HTMX REDIRECT
 * Jika request datang dari HTMX, gunakan HX-Redirect.
 * Jika request manual/biasa, gunakan header Location standar.
 */
if (isset($_SERVER['HTTP_HX_REQUEST'])) {
    header("HX-Redirect: " . url('/login'));
    header('HX-Trigger: {"doRedirect": "/login"}');
} else {
    header("Location: " . url('/login'));
}

echo '<div hx-trigger="load" hx-get="'.url('/login').'" hx-target="body" hx-push-url="true">
        Redirecting...
      </div>';
exit();