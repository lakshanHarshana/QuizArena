<?php
/**
 * QUIZARENA — Role-Based Authentication & Authorization Helpers
 * Ensures students cannot access teacher-only routes, and vice-versa.
 */

require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? ''
    ];
}

function isStudent(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] === 'student');
}

function isTeacher(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] === 'teacher');
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to continue.";
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

function requireStudent(): void {
    requireLogin();
    if (!isStudent()) {
        $_SESSION['flash_error'] = "Access denied: Student access only.";
        header("Location: " . BASE_URL . "/teacher/dashboard.php");
        exit();
    }
}

function requireTeacher(): void {
    requireLogin();
    if (!isTeacher()) {
        $_SESSION['flash_error'] = "Access denied: Teacher access only.";
        header("Location: " . BASE_URL . "/student/dashboard.php");
        exit();
    }
}
