<?php
// Pastikan session dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Hapus semua data session
$_SESSION = [];

// 2. Hapus cookie session jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session
session_destroy();

/**
 * STRATEGI HTMX REDIRECT
 * HTMX tidak akan merespons header 'Location' standar PHP untuk redirect halaman penuh.
 * Kita harus menggunakan header khusus 'HX-Redirect'.
 */
header("HX-Redirect: " . url('/login'));
exit();