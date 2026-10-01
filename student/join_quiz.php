<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireStudent();

$studentId = (int)$_SESSION['user_id'];
$quizCode = trim($_GET['quiz_code'] ?? ($_POST['quiz_code'] ?? ''));
$db = getDB();

$quiz = null;
$error = null;

if (!empty($quizCode)) {
    // Look up quiz by unique Quiz Code
    $stmt = $db->prepare("
        SELECT q.*, c.name AS category_name, u.name AS teacher_name,
               (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count,
               (SELECT COALESCE(SUM(time_limit), 0) FROM questions WHERE quiz_id = q.id) AS total_seconds,
               (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE quiz_id = q.id) AS total_marks
        FROM quizzes q
        JOIN categories c ON q.category_id = c.id
        JOIN users u ON q.teacher_id = u.id
        WHERE UPPER(q.quiz_code) = UPPER(?)
        LIMIT 1
    ");
    $stmt->execute([$quizCode]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        $error = "Quiz ID \"{$quizCode}\" not found. Please verify the code and try again.";
    } elseif ($quiz['status'] !== 'published') {
        $error = "This quiz is currently unpublished or in draft mode.";
    } else {
        $statusInfo = getQuizStatus($quiz);
        if ($statusInfo['code'] === 'UPCOMING') {
            $error = "This quiz has not started yet. Scheduled to start at: " . date('M d, Y H:i', strtotime($quiz['start_datetime']));
        } elseif ($statusInfo['code'] === 'EXPIRED') {
            $error = "This quiz has expired and is no longer accepting attempts.";
        }

        // Check student attempt limit
        $attemptsMade = getStudentAttemptCount($quiz['id'], $studentId);
        if ($attemptsMade >= $quiz['max_attempts']) {
            $error = "You have already completed the maximum number of attempts ({$quiz['max_attempts']}) allowed for this quiz.";
        }

        // Check if there are questions in the quiz
        if ($quiz['question_count'] <= 0) {
            $error = "This quiz does not have any questions configured yet.";
        }
    }
}

// Handle Start Quiz Action (POST request to create/resume attempt)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start' && $quiz && !$error) {
    // Check if an existing IN_PROGRESS attempt exists
    $existingAttempt = getActiveAttempt($quiz['id'], $studentId);

    if ($existingAttempt) {
        // Resume existing attempt
        header("Location: " . BASE_URL . "/student/quiz.php?attempt_id=" . $existingAttempt['id']);
        exit();
    } else {
        // Create new attempt
        $totalMarks = (int)$quiz['total_marks'];
        $insertAttempt = $db->prepare("
            INSERT INTO attempts (quiz_id, student_id, started_at, total_marks, status)
            VALUES (?, ?, NOW(), ?, 'IN_PROGRESS')
        ");
        $insertAttempt->execute([$quiz['id'], $studentId, $totalMarks]);
        $attemptId = (int)$db->lastInsertId();

        header("Location: " . BASE_URL . "/student/quiz.php?attempt_id=" . $attemptId);
        exit();
    }
}

$pageTitle = "Join Quiz";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if (!$quiz || $error): ?>
                <!-- Enter / Re-enter Quiz ID Form -->
                <div class="arena-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                            <i class="fa-solid fa-gamepad fa-2x"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2">Join Quiz Arena</h3>
                        <p class="text-secondary small">Enter the unique Quiz ID provided by your teacher to access the quiz.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center bg-danger bg-opacity-25 border-danger text-danger-emphasis rounded-3 mb-4">
                            <i class="fa-solid fa-circle-exclamation fa-lg me-2 text-danger"></i>
                            <div><?= sanitize($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/student/join_quiz.php" method="GET" class="mb-4">
                        <div class="mb-3">
                            <label for="quiz_code" class="form-label">Quiz ID / Access Code</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-hashtag"></i>
                                </span>
                                <input type="text" class="form-control form-arena font-monospace text-uppercase fw-bold" 
                                       id="quiz_code" name="quiz_code" value="<?= sanitize($quizCode) ?>" 
                                       placeholder="e.g. QUIZ-7F3A21" required>
                            </div>
                            <div class="form-text text-secondary">Quiz IDs are usually 11 characters starting with <code>QUIZ-</code>.</div>
                        </div>

                        <button type="submit" class="btn btn-arena-primary w-100 py-2">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Verify & Find Quiz
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="<?= BASE_URL ?>/student/dashboard.php" class="text-secondary small text-decoration-none">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back to Student Dashboard
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Quiz Found Confirmation Screen -->
                <div class="arena-card p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="quiz-code-badge fs-6"><i class="fa-solid fa-bolt me-1"></i><?= sanitize($quiz['quiz_code']) ?></span>
                        <span class="badge bg-success pulse-badge"><i class="fa-solid fa-check me-1"></i>Verified Ready</span>
                    </div>

                    <h2 class="fw-bold text-white mb-2"><?= sanitize($quiz['title']) ?></h2>
                    <p class="text-secondary mb-4"><?= sanitize($quiz['description'] ?? 'No description.') ?></p>

                    <!-- Key Quiz Specifications Table -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-md-3">
                            <div class="stat-pill">
                                <div class="text-secondary small mb-1">Total Time</div>
                                <div class="fw-bold text-warning fs-5"><?= formatDurationHuman($quiz['total_seconds']) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="stat-pill">
                                <div class="text-secondary small mb-1">Questions</div>
                                <div class="fw-bold text-primary fs-5"><?= (int)$quiz['question_count'] ?> MCQs</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="stat-pill">
                                <div class="text-secondary small mb-1">Total Marks</div>
                                <div class="fw-bold text-success fs-5"><?= (int)$quiz['total_marks'] ?> Pts</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="stat-pill">
                                <div class="text-secondary small mb-1">Difficulty</div>
                                <div class="fw-bold text-info fs-5"><?= sanitize($quiz['difficulty']) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Game Rules & Instructions -->
                    <div class="p-3 rounded-3 bg-dark border border-secondary mb-4 small text-secondary">
                        <h6 class="text-white fw-bold mb-2"><i class="fa-solid fa-circle-info text-primary me-2"></i>Arena Rules:</h6>
                        <ul class="mb-0 ps-3">
                            <li class="mb-1"><strong>Instant Feedback:</strong> Selecting an answer immediately reveals <span class="text-success fw-bold">GREEN (Correct)</span> or <span class="text-danger fw-bold">RED (Wrong)</span>.</li>
                            <li class="mb-1"><strong>Answer Locking:</strong> Once an answer is chosen, it is permanently locked and cannot be altered.</li>
                            <li class="mb-1"><strong>Dual Timers:</strong> Individual question timer counts down per question. The overall timer counts down the full quiz duration.</li>
                            <li><strong>Priority Rule:</strong> When the overall timer expires, the quiz automatically submits and finalizes your score.</li>
                        </ul>
                    </div>

                    <!-- Start Form -->
                    <form action="<?= BASE_URL ?>/student/join_quiz.php?quiz_code=<?= urlencode($quiz['quiz_code']) ?>" method="POST">
                        <input type="hidden" name="action" value="start">
                        <div class="d-flex gap-3">
                            <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-secondary px-4 py-2 rounded-pill flex-shrink-0">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-arena-primary px-4 py-2 rounded-pill flex-grow-1 fs-5 fw-bold">
                                <i class="fa-solid fa-play me-2"></i>Start Quiz Now
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
