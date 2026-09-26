<?php
$pageTitle = "Quiz Result";
require_once __DIR__ . '/../includes/header.php';
requireStudent();

$studentId = (int)$_SESSION['user_id'];
$attemptId = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

if ($attemptId <= 0) {
    setFlash('error', "Invalid attempt specified.");
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit();
}

$db = getDB();

// 1. Fetch Attempt with Quiz details
$stmt = $db->prepare("
    SELECT a.*, q.title AS quiz_title, q.quiz_code, q.max_attempts, q.id AS quiz_id,
           u.name AS teacher_name, c.name AS category_name
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    JOIN users u ON q.teacher_id = u.id
    JOIN categories c ON q.category_id = c.id
    WHERE a.id = ? AND a.student_id = ?
    LIMIT 1
");
$stmt->execute([$attemptId, $studentId]);
$attempt = $stmt->fetch();

if (!$attempt || $attempt['status'] === 'IN_PROGRESS') {
    setFlash('error', "Attempt not found or still in progress.");
    header("Location: " . BASE_URL . "/student/dashboard.php");
    exit();
}

// 2. Fetch answer breakdown
$ansStmt = $db->prepare("
    SELECT q.question_text, q.option_a, q.option_b, q.option_c, q.option_d,
           q.correct_answer, q.marks AS question_marks,
           a.selected_answer, a.is_correct, a.marks_obtained
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    WHERE a.attempt_id = ?
    ORDER BY q.question_order ASC, q.id ASC
");
$ansStmt->execute([$attemptId]);
$answers = $ansStmt->fetchAll();

$totalQuestions = count($answers);
$correctCount = 0;
$wrongCount = 0;
$unansweredCount = 0;

foreach ($answers as $row) {
    if (empty($row['selected_answer'])) {
        $unansweredCount++;
    } elseif ($row['is_correct']) {
        $correctCount++;
    } else {
        $wrongCount++;
    }
}

$percentage = (float)$attempt['percentage'];

// Attempt limits check
$attemptsUsed = getStudentAttemptCount($attempt['quiz_id'], $studentId);
$canRetake = ($attemptsUsed < $attempt['max_attempts']);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Animated Result Card -->
            <div class="result-card text-center mb-4">
                <div class="mb-3">
                    <span class="quiz-code-badge fs-6"><i class="fa-solid fa-bolt me-1"></i><?= sanitize($attempt['quiz_code']) ?></span>
                </div>
                <h3 class="fw-bold text-white mb-1"><?= sanitize($attempt['quiz_title']) ?></h3>
                <p class="text-secondary small mb-4">Instructor: <?= sanitize($attempt['teacher_name']) ?> • <?= sanitize($attempt['category_name']) ?></p>

                <!-- Performance Tier Badge & Score Circle -->
                <?php if ($percentage >= 90): ?>
                    <div class="result-score-circle score-gold">
                        <i class="fa-solid fa-trophy fa-2x mb-1 text-warning"></i>
                        <span class="fs-2 fw-bold text-white"><?= round($percentage) ?>%</span>
                        <span class="small text-warning text-uppercase fw-bold" style="font-size: 0.7rem;">Elite Rank</span>
                    </div>
                    <h2 class="fw-extrabold text-warning mb-1">Outstanding Achievement!</h2>
                    <p class="text-secondary small mb-4">Masterful performance! You dominated this arena challenge with top-tier accuracy.</p>
                <?php elseif ($percentage >= 60): ?>
                    <div class="result-score-circle score-silver">
                        <i class="fa-solid fa-medal fa-2x mb-1 text-primary"></i>
                        <span class="fs-2 fw-bold text-white"><?= round($percentage) ?>%</span>
                        <span class="small text-info text-uppercase fw-bold" style="font-size: 0.7rem;">Great Job</span>
                    </div>
                    <h2 class="fw-extrabold text-white mb-1">Well Done!</h2>
                    <p class="text-secondary small mb-4">Solid score! You demonstrated strong comprehension across the key topics.</p>
                <?php else: ?>
                    <div class="result-score-circle score-bronze">
                        <i class="fa-solid fa-award fa-2x mb-1 text-secondary"></i>
                        <span class="fs-2 fw-bold text-white"><?= round($percentage) ?>%</span>
                        <span class="small text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Keep Going</span>
                    </div>
                    <h2 class="fw-extrabold text-white mb-1">Good Effort!</h2>
                    <p class="text-secondary small mb-4">Review the answers below to reinforce your understanding and sharpen your skills.</p>
                <?php endif; ?>

                <!-- Detailed Statistics Matrix -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-pill">
                            <div class="text-secondary small mb-1">Score Obtained</div>
                            <div class="stat-number text-white"><?= (int)$attempt['score'] ?> / <?= (int)$attempt['total_marks'] ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-pill">
                            <div class="text-secondary small mb-1">Correct</div>
                            <div class="stat-number text-success"><?= $correctCount ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-pill">
                            <div class="text-secondary small mb-1">Wrong</div>
                            <div class="stat-number text-danger"><?= $wrongCount ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-pill">
                            <div class="text-secondary small mb-1">Unanswered</div>
                            <div class="stat-number text-warning"><?= $unansweredCount ?></div>
                        </div>
                    </div>
                </div>

                <!-- Timing & Submission Details -->
                <div class="p-3 rounded-3 bg-dark border border-secondary mb-4 small text-secondary">
                    <div class="row g-2">
                        <div class="col-sm-6 text-sm-start">
                            <i class="fa-regular fa-clock text-primary me-1"></i>
                            <span>Completion Time: </span>
                            <strong class="text-white"><?= formatDuration((int)$attempt['completion_time']) ?></strong>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <i class="fa-solid fa-flag-checkered text-info me-1"></i>
                            <span>Submission Type: </span>
                            <span class="badge <?= $attempt['submission_type'] === 'AUTO' ? 'bg-warning text-dark' : 'bg-primary' ?>">
                                <?= sanitize($attempt['submission_type']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Actions -->
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <?php if ($canRetake): ?>
                        <a href="<?= BASE_URL ?>/student/join_quiz.php?quiz_code=<?= urlencode($attempt['quiz_code']) ?>" class="btn btn-arena-primary rounded-pill px-4">
                            <i class="fa-solid fa-rotate-right me-1"></i> Retake Quiz (<?= $attemptsUsed ?>/<?= $attempt['max_attempts'] ?> Used)
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fa-solid fa-house me-1"></i> Student Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>/student/history.php" class="btn btn-outline-info rounded-pill px-4">
                        <i class="fa-solid fa-list-check me-1"></i> My Attempt History
                    </a>
                </div>
            </div>

            <!-- Detailed Question Breakdown -->
            <div class="arena-card p-4">
                <h4 class="fw-bold text-white mb-3"><i class="fa-solid fa-clipboard-check text-primary me-2"></i>Question Review</h4>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($answers as $idx => $q): 
                        $statusClass = empty($q['selected_answer']) ? 'border-warning' : ($q['is_correct'] ? 'border-success' : 'border-danger');
                    ?>
                        <div class="p-3 rounded-3 bg-dark border <?= $statusClass ?> position-relative">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-secondary">Question <?= ($idx + 1) ?></span>
                                <?php if (empty($q['selected_answer'])): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Unanswered (0 Marks)</span>
                                <?php elseif ($q['is_correct']): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Correct (+<?= (int)$q['marks_obtained'] ?> Marks)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i>Wrong (0 Marks)</span>
                                <?php endif; ?>
                            </div>

                            <div class="text-white fw-semibold mb-3"><?= sanitize($q['question_text']) ?></div>

                            <div class="row g-2 small">
                                <?php foreach (['A', 'B', 'C', 'D'] as $optLetter): 
                                    $optKey = 'option_' . strtolower($optLetter);
                                    $isUserChoice = ($q['selected_answer'] === $optLetter);
                                    $isCorrectChoice = ($q['correct_answer'] === $optLetter);

                                    $optBg = 'bg-dark border border-secondary text-secondary';
                                    if ($isCorrectChoice) {
                                        $optBg = 'bg-success bg-opacity-25 border border-success text-success fw-bold';
                                    } elseif ($isUserChoice && !$q['is_correct']) {
                                        $optBg = 'bg-danger bg-opacity-25 border border-danger text-danger fw-bold';
                                    }
                                ?>
                                    <div class="col-md-6">
                                        <div class="p-2 rounded <?= $optBg ?>">
                                            <span class="badge bg-secondary me-1"><?= $optLetter ?></span>
                                            <?= sanitize($q[$optKey]) ?>
                                            <?php if ($isCorrectChoice): ?>
                                                <i class="fa-solid fa-check-circle ms-1 text-success"></i>
                                            <?php endif; ?>
                                            <?php if ($isUserChoice && !$q['is_correct']): ?>
                                                <i class="fa-solid fa-times-circle ms-1 text-danger"></i> (Your Choice)
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trigger Confetti if high score -->
<?php if ($percentage >= 90): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof confetti === 'function') {
        const duration = 3 * 1000;
        const end = Date.now() + duration;

        (function frame() {
            confetti({
                particleCount: 4,
                angle: 60,
                spread: 55,
                origin: { x: 0 }
            });
            confetti({
                particleCount: 4,
                angle: 120,
                spread: 55,
                origin: { x: 1 }
            });

            if (Date.now() < end) {
                requestAnimationFrame(frame);
            }
        }());
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
