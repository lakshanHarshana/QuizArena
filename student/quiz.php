<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudent();

$studentId = (int)$_SESSION['user_id'];
$attemptId = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

if ($attemptId <= 0) {
    setFlash('error', "Invalid quiz attempt specified.");
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit();
}

$db = getDB();

// 1. Verify Attempt ownership and active status
$attemptStmt = $db->prepare("
    SELECT a.*, q.title AS quiz_title, q.quiz_code, q.id AS quiz_id
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    WHERE a.id = ? AND a.student_id = ?
    LIMIT 1
");
$attemptStmt->execute([$attemptId, $studentId]);
$attempt = $attemptStmt->fetch();

if (!$attempt) {
    setFlash('error', "Quiz attempt not found or unauthorized.");
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit();
}

if ($attempt['status'] !== 'IN_PROGRESS') {
    // Attempt already finalized
    header("Location: " . BASE_URL . "/student/result.php?attempt_id=" . $attemptId);
    exit();
}

// 2. Calculate dynamic overall time remaining
$totalAllowedSeconds = getQuizTotalTime($attempt['quiz_id']);
$startedTime = strtotime($attempt['started_at']);
$currentTime = time();
$elapsedSeconds = max(0, $currentTime - $startedTime);
$overallRemainingSeconds = $totalAllowedSeconds - $elapsedSeconds;

if ($overallRemainingSeconds <= 0) {
    // Overall time already expired -> auto-submit immediately
    header("Location: " . BASE_URL . "/student/submit_quiz.php?attempt_id=" . $attemptId . "&type=AUTO");
    exit();
}

// 3. Fetch questions securely WITHOUT correct_answer!
$qStmt = $db->prepare("
    SELECT id, question_text, option_a, option_b, option_c, option_d, time_limit, marks, question_order
    FROM questions
    WHERE quiz_id = ?
    ORDER BY question_order ASC, id ASC
");
$qStmt->execute([$attempt['quiz_id']]);
$questions = $qStmt->fetchAll();

if (empty($questions)) {
    setFlash('error', "No questions found for this quiz.");
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($attempt['quiz_title']) ?> — Live Quiz Arena</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background: #0b1120; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Sticky Top Quiz Bar with Dual Timers -->
<header class="quiz-header-bar shadow-sm">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <!-- Quiz Info -->
            <div class="d-flex align-items-center gap-2">
                <span class="quiz-code-badge"><i class="fa-solid fa-bolt me-1"></i><?= sanitize($attempt['quiz_code']) ?></span>
                <h5 class="fw-bold text-white mb-0 d-none d-md-block"><?= sanitize($attempt['quiz_title']) ?></h5>
            </div>

            <!-- Timers Section -->
            <div class="d-flex align-items-center gap-3">
                <!-- Question Timer Badge -->
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary small fw-medium d-none d-sm-inline">Question Time:</span>
                    <div class="question-timer-badge d-flex align-items-center gap-1">
                        <i class="fa-solid fa-stopwatch text-primary"></i>
                        <span id="questionTimerText" class="font-monospace">00:00</span>
                    </div>
                </div>

                <!-- Overall Timer Box (Higher Priority) -->
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary small fw-medium d-none d-sm-inline">Overall Left:</span>
                    <div class="timer-box" id="overallTimerBox" title="Overall Quiz Time Remaining">
                        <i class="fa-solid fa-hourglass-half text-warning"></i>
                        <span id="overallTimerDisplay" class="text-light">00:00</span>
                    </div>
                </div>

                <!-- Manual Submit Button -->
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold" id="manualSubmitBtn">
                    <i class="fa-solid fa-flag-checkered me-1"></i> Submit Quiz
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Main Quiz Interface -->
<main class="container py-4 flex-grow-1 d-flex flex-column justify-content-center">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <!-- Progress Tracker -->
            <div class="d-flex justify-content-between align-items-center mb-2 text-secondary small fw-semibold">
                <div>
                    <span>Question </span>
                    <span class="text-white fw-bold fs-6" id="qCurrentIndex">1</span>
                    <span> of </span>
                    <span class="text-white fw-bold fs-6" id="qTotalCount"><?= count($questions) ?></span>
                </div>
                <div>
                    <span class="badge bg-dark border border-secondary text-info" id="questionMarks">1 Mark</span>
                </div>
            </div>

            <div class="arena-progress mb-4">
                <div class="arena-progress-bar" id="qProgressBar" role="progressbar" style="width: 10%;" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <!-- Question Card -->
            <div class="question-card" id="questionCard">
                <div class="question-text" id="questionText">
                    Loading question...
                </div>

                <!-- 4 Options Container (A, B, C, D) -->
                <div id="optionsContainer">
                    <!-- Dynamic Option Buttons will be rendered here by quiz.js -->
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Manual Submit Confirmation Modal -->
<div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-labelledby="submitConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-arena">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="submitConfirmModalLabel">
                    <i class="fa-solid fa-circle-question text-warning me-2"></i>Confirm Quiz Submission
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-white mb-3">Are you sure you want to finish and submit your quiz attempt?</p>
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6">
                        <div class="p-3 rounded bg-dark border border-secondary">
                            <div class="text-secondary small">Questions Answered</div>
                            <div class="fw-bold text-success fs-4" id="modalAnsweredCount">0</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded bg-dark border border-secondary">
                            <div class="text-secondary small">Unanswered</div>
                            <div class="fw-bold text-warning fs-4" id="modalUnansweredCount">0</div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-secondary py-2 small mb-0 text-secondary">
                    <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> Once submitted, your answers cannot be changed.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-arena-primary rounded-pill px-4" id="confirmSubmitBtn">
                    <i class="fa-solid fa-check me-1"></i> Yes, Submit Quiz
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Submit Notification Modal (When overall timer expires) -->
<div class="modal fade" id="autoSubmitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-arena text-center py-4">
            <div class="modal-body">
                <div class="text-danger mb-3">
                    <i class="fa-solid fa-hourglass-end fa-3x fa-shake"></i>
                </div>
                <h4 class="fw-bold text-white mb-2">Overall Quiz Time Expired!</h4>
                <p class="text-secondary mb-3">Your overall time has elapsed. The arena is automatically submitting your quiz...</p>
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Submitting...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Timer & Game Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/timer.js"></script>
<script src="<?= BASE_URL ?>/assets/js/quiz.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize QuizGameEngine with secure PHP payload
    const questionsPayload = <?= json_encode($questions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    
    new QuizGameEngine({
        attemptId: <?= $attemptId ?>,
        quizId: <?= $attempt['quiz_id'] ?>,
        questions: questionsPayload,
        overallSeconds: <?= $overallRemainingSeconds ?>,
        apiUrl: '<?= BASE_URL ?>/api/answer_question.php',
        submitUrl: '<?= BASE_URL ?>/student/submit_quiz.php'
    });
});
</script>
</body>
</html>
