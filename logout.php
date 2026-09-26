<?php
/**
 * QUIZARENA — User Logout
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Unset all session variables
$_SESSION = [];

// Delete session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
session_start();

setFlash('success', "You have been logged out successfully.");
header("Location: " . BASE_URL . "/login.php");
exit();
