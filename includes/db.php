<?php
/**
 * QUIZARENA — Database Connection Helper (includes/db.php)
 * Satisfies Section 4 Sample Folder Structure requirement.
 */

require_once __DIR__ . '/../config/database.php';

// Expose $pdo / $conn variable for scripts expecting procedural or object access
$pdo = getDB();
$conn = $pdo;
