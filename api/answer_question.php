<?php
/**
 * QUIZARENA — Real-Time Answer Processing API
 * Evaluates student MCQ selection securely against the database,
 * locks the answer, awards marks, and returns instant feedback.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isStudent()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Student session required.']);
    exit();
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$attemptId = isset($data['attempt_id']) ? (int)$data['attempt_id'] : 0;
$questionId = isset($data['question_id']) ? (int)$data['question_id'] : 0;
$selectedAnswer = isset($data['selected_answer']) ? strtoupper(trim((string)$data['selected_answer'])) : null;
$studentId = (int)$_SESSION['user_id'];

if ($attemptId <= 0 || $questionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid attempt or question ID.']);
    exit();
}

$db = getDB();

// 1. Verify Attempt ownership and active status
$attemptStmt = $db->prepare("SELECT * FROM attempts WHERE id = ? AND student_id = ? LIMIT 1");
$attemptStmt->execute([$attemptId, $studentId]);
$attempt = $attemptStmt->fetch();

if (!$attempt) {
    echo json_encode(['success' => false, 'message' => 'Attempt not found or access denied.']);
    exit();
}

if ($attempt['status'] !== 'IN_PROGRESS') {
    echo json_encode(['success' => false, 'message' => 'Quiz attempt is already closed.']);
    exit();
}

// 2. Check if this question was already answered in this attempt (Answer Locking)
$checkAnsStmt = $db->prepare("SELECT id, selected_answer, is_correct FROM answers WHERE attempt_id = ? AND question_id = ? LIMIT 1");
$checkAnsStmt->execute([$attemptId, $questionId]);
$existingAnswer = $checkAnsStmt->fetch();

if ($existingAnswer) {
    echo json_encode([
        'success' => true,
        'already_answered' => true,
        'is_correct' => (bool)$existingAnswer['is_correct'],
        'message' => 'Question was already answered and locked.'
    ]);
    exit();
}

// 3. Fetch Question details securely from DB
$qStmt = $db->prepare("SELECT id, quiz_id, correct_answer, marks FROM questions WHERE id = ? AND quiz_id = ? LIMIT 1");
$qStmt->execute([$questionId, $attempt['quiz_id']]);
$question = $qStmt->fetch();

if (!$question) {
    echo json_encode(['success' => false, 'message' => 'Question does not belong to this quiz.']);
    exit();
}

// 4. Evaluate correctness
$validAnswers = ['A', 'B', 'C', 'D'];
$isCorrect = 0;
$marksObtained = 0;

if ($selectedAnswer && in_array($selectedAnswer, $validAnswers)) {
    if ($selectedAnswer === $question['correct_answer']) {
        $isCorrect = 1;
        $marksObtained = (int)$question['marks'];
    }
} else {
    $selectedAnswer = null; // Unanswered
}

// 5. Insert committed answer
$insertStmt = $db->prepare("
    INSERT INTO answers (attempt_id, question_id, selected_answer, is_correct, marks_obtained, answered_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$insertStmt->execute([$attemptId, $questionId, $selectedAnswer, $isCorrect, $marksObtained]);

echo json_encode([
    'success' => true,
    'question_id' => $questionId,
    'selected_answer' => $selectedAnswer,
    'is_correct' => (bool)$isCorrect,
    'correct_answer' => $question['correct_answer'], // Returned only after answer is committed
    'marks_obtained' => $marksObtained
]);
