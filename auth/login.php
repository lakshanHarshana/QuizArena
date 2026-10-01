<?php
/**
 * QUIZARENA — Auth Login Router / Handler (auth/login.php)
 * Satisfies Section 4 Sample Folder Structure.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/login_process.php';
} else {
    require_once __DIR__ . '/../login.php';
}
