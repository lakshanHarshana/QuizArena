<?php
/**
 * QUIZARENA — User Registration Processor
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/register.php");
    exit();
}

$role = trim($_POST['role'] ?? '');
$username = trim($_POST['username'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Specific profile fields
$studentId = trim($_POST['student_id'] ?? '');
$course = trim($_POST['course'] ?? '');
$department = trim($_POST['department'] ?? '');

$errors = [];

// Auto-generate username from email if left blank
if (empty($username)) {
    $emailParts = explode('@', $email);
    $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $emailParts[0]));
    if (strlen($username) < 3) {
        $username = 'user_' . substr(uniqid(), -5);
    }
}

// Basic Validations
if (strlen($username) < 3) {
    $errors[] = "Username must be at least 3 characters.";
}

if (empty($name) || strlen($name) < 2) {
    $errors[] = "Please enter your full name.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if (empty($password) || strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters long.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

if (!in_array($role, ['student', 'teacher'])) {
    $errors[] = "Invalid account role selected.";
}

if ($role === 'student') {
    if (empty($studentId)) $errors[] = "Student ID is required.";
    if (empty($course)) $errors[] = "Course/Program is required.";
} elseif ($role === 'teacher') {
    if (empty($department)) $errors[] = "Department is required.";
}

$db = getDB();

// Check for duplicate username
$stmtUser = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmtUser->execute([$username]);
if ($stmtUser->rowCount() > 0) {
    $errors[] = "The username '{$username}' is already taken. Please choose another username.";
}

// Check for duplicate email
$stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    $errors[] = "This email address is already registered. Please log in instead.";
}

// Check duplicate student ID
if ($role === 'student' && empty($errors)) {
    $stmt = $db->prepare("SELECT id FROM student_profiles WHERE student_id = ? LIMIT 1");
    $stmt->execute([$studentId]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "This Student ID is already registered.";
    }
}

if (!empty($errors)) {
    setFlash('error', implode('<br>', $errors));
    header("Location: " . BASE_URL . "/register.php");
    exit();
}

// Hash password securely with BCRYPT
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    $db->beginTransaction();

    // Insert user record with username
    $stmt = $db->prepare("INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $name, $email, $hashedPassword, $role]);
    $userId = (int)$db->lastInsertId();

    // Insert profile record
    if ($role === 'student') {
        $stmtProfile = $db->prepare("INSERT INTO student_profiles (user_id, student_id, course) VALUES (?, ?, ?)");
        $stmtProfile->execute([$userId, $studentId, $course]);
    } else {
        $stmtProfile = $db->prepare("INSERT INTO teacher_profiles (user_id, department) VALUES (?, ?)");
        $stmtProfile->execute([$userId, $department]);
    }

    $db->commit();

    // Set active session
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_username'] = $username;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;

    setFlash('success', "Welcome to QuizArena, {$name}! Your account was created successfully.");

    if ($role === 'student') {
        header("Location: " . BASE_URL . "/student/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/teacher/dashboard.php");
    }
    exit();

} catch (Exception $e) {
    $db->rollBack();
    error_log("Registration Error: " . $e->getMessage());
    setFlash('error', "Registration failed due to a system error. Please try again.");
    header("Location: " . BASE_URL . "/register.php");
    exit();
}
