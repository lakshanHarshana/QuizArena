<?php
/**
 * QUIZARENA — Auth Registration Router / Handler (auth/register.php)
 * Satisfies Section 4 Sample Folder Structure.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/register_process.php';
} else {
    require_once __DIR__ . '/../register.php';
}
