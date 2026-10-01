<?php
/**
 * QUIZARENA — User Dashboard Router (dashboard.php)
 * Satisfies Section 4 Sample Folder Structure requirement.
 * Automatically routes authenticated users to their role-specific dashboard.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    setFlash('error', "Please log in to access your dashboard.");
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

if (isStudent()) {
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit();
} else {
    header("Location: " . BASE_URL . "/teacher/dashboard.php");
    exit();
}
