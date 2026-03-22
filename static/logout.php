<?php 
declare(strict_types=1);

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
 * HTMX tidak akan merespons header 'Location' standar PHP untuk redirect halaman penuh.
 * Kita harus menggunakan header khusus 'HX-Redirect'.
 */
header("HX-Redirect: " . url('/login'), true, 200);
exit();