<?php
/**
 * QUIZARENA — User Login Processor
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$loginId = trim($_POST['login_id'] ?? $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($loginId) || empty($password)) {
    setFlash('error', "Please enter both your username/email and password.");
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$db = getDB();
$stmt = $db->prepare("SELECT id, username, name, email, password, role FROM users WHERE email = ? OR username = ? LIMIT 1");
$stmt->execute([$loginId, $loginId]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // Regenerate session id to protect against session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_username'] = $user['username'] ?? '';
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    setFlash('success', "Welcome back, {$user['name']}!");

    if ($user['role'] === 'student') {
        header("Location: " . BASE_URL . "/student/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/teacher/dashboard.php");
    }
    exit();
} else {
    setFlash('error', "Invalid username/email or password. Please try again.");
    header("Location: " . BASE_URL . "/login.php");
    exit();
}
