<?php
/**
 * QUIZARENA — Quiz Submission & Final Scoring Processor
 * Finalizes the attempt, validates answers, computes score, percentage,
 * completion time, sets submission type, and redirects to animated result page.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudent();

$studentId = (int)$_SESSION['user_id'];
$attemptId = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : (isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0);
$submissionType = isset($_POST['submission_type']) ? strtoupper(trim((string)$_POST['submission_type'])) : (isset($_GET['type']) ? strtoupper(trim((string)$_GET['type'])) : 'MANUAL');

if (!in_array($submissionType, ['MANUAL', 'AUTO'])) {
    $submissionType = 'MANUAL';
}

if ($attemptId <= 0) {
    setFlash('error', "Invalid attempt ID for submission.");
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit();
}

$db = getDB();

// 1. Fetch Attempt
$stmt = $db->prepare("SELECT * FROM attempts WHERE id = ? AND student_id = ? LIMIT 1");
$stmt->execute([$attemptId, $studentId]);
$attempt = $stmt->fetch();

if (!$attempt) {
    setFlash('error', "Attempt not found or access denied.");
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit();
}

// If already submitted, redirect to result without re-calculating (prevent duplicate submissions)
if ($attempt['status'] !== 'IN_PROGRESS') {
    header("Location: " . BASE_URL . "/student/result.php?attempt_id=" . $attemptId);
    exit();
}

$quizId = (int)$attempt['quiz_id'];

// 2. Fetch total questions and calculate potential total marks
$qStmt = $db->prepare("SELECT id, marks, correct_answer FROM questions WHERE quiz_id = ?");
$qStmt->execute([$quizId]);
$questions = $qStmt->fetchAll();

$totalQuestions = count($questions);
$totalMarks = 0;
$questionMap = [];
foreach ($questions as $q) {
    $totalMarks += (int)$q['marks'];
    $questionMap[$q['id']] = $q;
}

// 3. Fetch all recorded answers for this attempt
$ansStmt = $db->prepare("SELECT question_id, selected_answer, is_correct, marks_obtained FROM answers WHERE attempt_id = ?");
$ansStmt->execute([$attemptId]);
$answers = $ansStmt->fetchAll();

$answeredMap = [];
$obtainedMarks = 0;
foreach ($answers as $ans) {
    $answeredMap[$ans['question_id']] = $ans;
    $obtainedMarks += (int)$ans['marks_obtained'];
}

// Insert missing unanswered questions if any
foreach ($questions as $q) {
    if (!isset($answeredMap[$q['id']])) {
        $ins = $db->prepare("INSERT INTO answers (attempt_id, question_id, selected_answer, is_correct, marks_obtained, answered_at) VALUES (?, ?, NULL, 0, 0, NOW())");
        $ins->execute([$attemptId, $q['id']]);
    }
}

// 4. Calculate Percentage and Completion Time
$percentage = ($totalMarks > 0) ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0.00;

$startedAt = strtotime($attempt['started_at']);
$now = time();
$maxAllowedSeconds = getQuizTotalTime($quizId);
$timeTakenSeconds = max(1, $now - $startedAt);

// Cap at maximum allowed quiz duration if auto-submitted
if ($timeTakenSeconds > $maxAllowedSeconds && $maxAllowedSeconds > 0) {
    $timeTakenSeconds = $maxAllowedSeconds;
}

$status = ($submissionType === 'AUTO') ? 'AUTO_SUBMITTED' : 'SUBMITTED';

// 5. Update Attempt record
$updateStmt = $db->prepare("
    UPDATE attempts 
    SET submitted_at = NOW(),
        completion_time = ?,
        score = ?,
        total_marks = ?,
        percentage = ?,
        submission_type = ?,
        status = ?
    WHERE id = ?
");
$updateStmt->execute([
    $timeTakenSeconds,
    $obtainedMarks,
    $totalMarks,
    $percentage,
    $submissionType,
    $status,
    $attemptId
]);

// Redirect to animated result showcase
header("Location: " . BASE_URL . "/student/result.php?attempt_id=" . $attemptId);
exit();
