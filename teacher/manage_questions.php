<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireTeacher();

$teacherId = (int)$_SESSION['user_id'];
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : (isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0);

$db = getDB();

// Verify Quiz ownership
$qStmt = $db->prepare("SELECT * FROM quizzes WHERE id = ? AND teacher_id = ? LIMIT 1");
$qStmt->execute([$quizId, $teacherId]);
$quiz = $qStmt->fetch();

if (!$quiz) {
    setFlash('error', "Quiz not found or unauthorized.");
    header("Location: " . BASE_URL . "/teacher/dashboard.php");
    exit();
}

$editQuestion = null;
$error = '';

// Handle Delete Question
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['question_id'])) {
    $questionId = (int)$_GET['question_id'];
    $del = $db->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
    $del->execute([$questionId, $quizId]);
    setFlash('success', "Question removed successfully.");
    header("Location: " . BASE_URL . "/teacher/manage_questions.php?quiz_id=" . $quizId);
    exit();
}

// Handle Edit Fetch
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['question_id'])) {
    $questionId = (int)$_GET['question_id'];
    $fetchQ = $db->prepare("SELECT * FROM questions WHERE id = ? AND quiz_id = ? LIMIT 1");
    $fetchQ->execute([$questionId, $quizId]);
    $editQuestion = $fetchQ->fetch();
}

// Handle Finalize Schedule & Done Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_finalize'])) {
    $startDatetime = trim($_POST['start_datetime'] ?? '');
    $endDatetime = trim($_POST['end_datetime'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';
    $maxAttempts = max(1, (int)($_POST['max_attempts'] ?? 1));

    // Verify at least 1 question exists
    $countQ = $db->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
    $countQ->execute([$quizId]);
    $numQuestions = (int)$countQ->fetchColumn();

    if ($numQuestions === 0) {
        $error = "Please add at least one question before defining the schedule and finalizing the quiz.";
    } elseif (empty($startDatetime) || empty($endDatetime)) {
        $error = "Both start and end dates/times are required.";
    } elseif (strtotime($endDatetime) <= strtotime($startDatetime)) {
        $error = "End date and time must be later than the start date and time.";
    } else {
        $upd = $db->prepare("
            UPDATE quizzes 
            SET start_datetime = ?, end_datetime = ?, status = ?, max_attempts = ?
            WHERE id = ? AND teacher_id = ?
        ");
        $upd->execute([$startDatetime, $endDatetime, $status, $maxAttempts, $quizId, $teacherId]);

        setFlash('success', "🎉 Quiz '<strong>" . htmlspecialchars($quiz['title']) . "</strong>' (Quiz ID: <strong>" . htmlspecialchars($quiz['quiz_code']) . "</strong>) has been scheduled with {$numQuestions} questions and is ready!");
        header("Location: " . BASE_URL . "/teacher/dashboard.php");
        exit();
    }
}

// Handle Add / Update Question Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action_finalize'])) {
    $questionText = trim($_POST['question_text'] ?? '');
    $optionA = trim($_POST['option_a'] ?? '');
    $optionB = trim($_POST['option_b'] ?? '');
    $optionC = trim($_POST['option_c'] ?? '');
    $optionD = trim($_POST['option_d'] ?? '');
    $correctAnswer = strtoupper(trim($_POST['correct_answer'] ?? ''));
    $timeLimit = max(10, (int)($_POST['time_limit'] ?? 60));
    $marks = max(1, (int)($_POST['marks'] ?? 1));
    $targetQuestionId = (int)($_POST['question_id'] ?? 0);

    // Strict validation
    if (empty($questionText)) {
        $error = "Question text cannot be empty.";
    } elseif (empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD)) {
        $error = "All 4 options (A, B, C, D) must be provided.";
    } elseif (!in_array($correctAnswer, ['A', 'B', 'C', 'D'])) {
        $error = "Please select a valid correct answer (A, B, C, or D).";
    } else {
        if ($targetQuestionId > 0) {
            // Update existing question
            $updQ = $db->prepare("
                UPDATE questions 
                SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?,
                    correct_answer = ?, time_limit = ?, marks = ?
                WHERE id = ? AND quiz_id = ?
            ");
            $updQ->execute([
                $questionText, $optionA, $optionB, $optionC, $optionD,
                $correctAnswer, $timeLimit, $marks,
                $targetQuestionId, $quizId
            ]);
            setFlash('success', "Question updated successfully.");
        } else {
            // Insert new question (calculate next order)
            $orderStmt = $db->prepare("SELECT COALESCE(MAX(question_order), 0) + 1 FROM questions WHERE quiz_id = ?");
            $orderStmt->execute([$quizId]);
            $nextOrder = (int)$orderStmt->fetchColumn();

            $insQ = $db->prepare("
                INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, time_limit, marks, question_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insQ->execute([
                $quizId, $questionText, $optionA, $optionB, $optionC, $optionD,
                $correctAnswer, $timeLimit, $marks, $nextOrder
            ]);
            setFlash('success', "Question added to quiz successfully.");
        }

        header("Location: " . BASE_URL . "/teacher/manage_questions.php?quiz_id=" . $quizId);
        exit();
    }
}

// Fetch all questions for this quiz
$questionsStmt = $db->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY question_order ASC, id ASC");
$questionsStmt->execute([$quizId]);
$questions = $questionsStmt->fetchAll();

// Dynamic Overall Quiz Time Calculation
$totalSeconds = getQuizTotalTime($quizId);
$totalMarks = getQuizTotalMarks($quizId);

$pageTitle = "Manage Questions";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header with Dynamic Quiz Specifications -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="quiz-code-badge fs-6"><i class="fa-solid fa-bolt me-1"></i><?= sanitize($quiz['quiz_code']) ?></span>
                <h3 class="fw-bold text-white mb-0"><?= sanitize($quiz['title']) ?></h3>
            </div>
            <p class="text-secondary small mb-0">Manage multiple choice questions, individual question timers, and marks.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="#scheduleSection" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-calendar-check me-1"></i> Define Date, Time & Done
            </a>
            <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/teacher/results.php?quiz_id=<?= $quizId ?>" class="btn btn-outline-warning btn-sm rounded-pill px-3">
                <i class="fa-solid fa-trophy me-1"></i> View Results
            </a>
        </div>
    </div>

    <!-- 2-Step Workflow Guide -->
    <div class="p-3 rounded-3 bg-dark border border-secondary mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill px-3 py-2 fw-semibold">
                <i class="fa-solid fa-list-ol me-1"></i> Step 1: Set Questions & Answers
            </span>
            <i class="fa-solid fa-arrow-right text-secondary"></i>
            <a href="#scheduleSection" class="badge <?= count($questions) > 0 ? 'bg-success text-white' : 'bg-secondary text-light' ?> rounded-pill px-3 py-2 fw-semibold text-decoration-none">
                <i class="fa-solid fa-calendar-days me-1"></i> Step 2: Define Date, Time & Click Done
            </a>
        </div>
        <div class="small text-secondary">
            <span class="text-white fw-bold"><?= count($questions) ?></span> question(s) added
        </div>
    </div>

    <!-- Automatically Calculated Overall Time Banner (Section 9 & 26) -->
    <div class="arena-card p-4 mb-4 border-info border-opacity-50">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-25 text-info d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.4rem;">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-white mb-1">Automatic Overall Quiz Time Calculation</h5>
                        <p class="text-secondary small mb-0">
                            The overall duration is dynamically derived from: <code>SUM(Question Timers)</code> = 
                            <strong><?= count($questions) ?> questions</strong> totaling <strong><?= $totalSeconds ?> seconds</strong>.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="p-2 rounded bg-dark border border-secondary">
                            <div class="text-secondary small">Overall Time</div>
                            <div class="fw-bold text-warning fs-5"><?= formatDurationHuman($totalSeconds) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-dark border border-secondary">
                            <div class="text-secondary small">Total Marks</div>
                            <div class="fw-bold text-success fs-5"><?= $totalMarks ?> Pts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Question Form (Add / Edit) -->
        <div class="col-lg-5">
            <div class="arena-card p-4 sticky-top" style="top: 85px; z-index: 10;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-white mb-0">
                        <i class="fa-solid <?= $editQuestion ? 'fa-pen text-warning' : 'fa-plus text-primary' ?> me-2"></i>
                        <?= $editQuestion ? 'Edit Question' : 'Add New Question' ?>
                    </h5>
                    <?php if ($editQuestion): ?>
                        <a href="<?= BASE_URL ?>/teacher/manage_questions.php?quiz_id=<?= $quizId ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                            Cancel Edit
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small mb-3"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/teacher/manage_questions.php" method="POST" id="questionForm" novalidate>
                    <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                    <?php if ($editQuestion): ?>
                        <input type="hidden" name="question_id" value="<?= $editQuestion['id'] ?>">
                    <?php endif; ?>

                    <!-- Step 1: Enter Question -->
                    <div class="mb-3">
                        <label for="question_text" class="form-label">1. Question Text <span class="text-danger">*</span></label>
                        <textarea class="form-control form-arena" id="question_text" name="question_text" rows="3" required placeholder="Type the MCQ question here..."><?= $editQuestion ? sanitize($editQuestion['question_text']) : '' ?></textarea>
                    </div>

                    <!-- Steps 2-5: Options A, B, C, D -->
                    <div class="mb-3">
                        <label class="form-label">2-5. Answer Options <span class="text-danger">*</span></label>
                        <div class="d-flex flex-column gap-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-dark border-secondary text-primary fw-bold">A</span>
                                <input type="text" class="form-control form-arena" name="option_a" required placeholder="Option A text" value="<?= $editQuestion ? sanitize($editQuestion['option_a']) : '' ?>">
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-dark border-secondary text-primary fw-bold">B</span>
                                <input type="text" class="form-control form-arena" name="option_b" required placeholder="Option B text" value="<?= $editQuestion ? sanitize($editQuestion['option_b']) : '' ?>">
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-dark border-secondary text-primary fw-bold">C</span>
                                <input type="text" class="form-control form-arena" name="option_c" required placeholder="Option C text" value="<?= $editQuestion ? sanitize($editQuestion['option_c']) : '' ?>">
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-dark border-secondary text-primary fw-bold">D</span>
                                <input type="text" class="form-control form-arena" name="option_d" required placeholder="Option D text" value="<?= $editQuestion ? sanitize($editQuestion['option_d']) : '' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Select Correct Answer -->
                    <div class="mb-3">
                        <label class="form-label">6. Correct Answer <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <?php foreach (['A', 'B', 'C', 'D'] as $opt): 
                                $checked = ($editQuestion && $editQuestion['correct_answer'] === $opt) ? 'checked' : (($opt === 'A' && !$editQuestion) ? 'checked' : '');
                            ?>
                                <div class="col-3">
                                    <input type="radio" class="btn-check" name="correct_answer" id="correct_<?= $opt ?>" value="<?= $opt ?>" <?= $checked ?>>
                                    <label class="btn btn-outline-success w-100 py-1 fw-bold rounded-2" for="correct_<?= $opt ?>"><?= $opt ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row g-2 mb-4">
                        <!-- Step 7: Question Time -->
                        <div class="col-6">
                            <label for="time_limit" class="form-label small">7. Time (seconds)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-arena" id="time_limit" name="time_limit" min="10" max="600" value="<?= $editQuestion ? (int)$editQuestion['time_limit'] : 60 ?>" required>
                                <span class="input-group-text bg-dark border-secondary text-secondary">sec</span>
                            </div>
                            <div class="form-text text-secondary" style="font-size: 0.7rem;">Min 10s</div>
                        </div>

                        <!-- Step 8: Marks -->
                        <div class="col-6">
                            <label for="marks" class="form-label small">8. Marks</label>
                            <input type="number" class="form-control form-arena form-control-sm" id="marks" name="marks" min="1" max="20" value="<?= $editQuestion ? (int)$editQuestion['marks'] : 1 ?>" required>
                        </div>
                    </div>

                    <!-- Step 9: Save Question -->
                    <button type="submit" class="btn btn-arena-primary w-100 py-2">
                        <i class="fa-solid fa-floppy-disk me-1"></i>
                        <?= $editQuestion ? 'Update Question' : 'Save Question' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Questions List (Fixed Sequential Order) -->
        <div class="col-lg-7">
            <div class="arena-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-white mb-0">
                        <i class="fa-solid fa-list-ol text-primary me-2"></i>Question Bank (<?= count($questions) ?>)
                    </h5>
                </div>

                <?php if (empty($questions)): ?>
                    <div class="text-center py-5">
                        <div class="text-secondary mb-2"><i class="fa-regular fa-circle-question fa-3x"></i></div>
                        <h6 class="text-light">No questions added yet.</h6>
                        <p class="text-secondary small">Use the form on the left to add your first question.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($questions as $idx => $q): ?>
                            <div class="p-3 rounded-3 bg-dark border border-secondary position-relative">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary">#<?= ($idx + 1) ?></span>
                                        <span class="badge bg-secondary"><i class="fa-regular fa-clock me-1"></i><?= (int)$q['time_limit'] ?>s</span>
                                        <span class="badge bg-info text-dark"><?= (int)$q['marks'] ?> Mark<?= $q['marks'] > 1 ? 's' : '' ?></span>
                                    </div>
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= BASE_URL ?>/teacher/manage_questions.php?quiz_id=<?= $quizId ?>&action=edit&question_id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-info" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/teacher/manage_questions.php?quiz_id=<?= $quizId ?>&action=delete&question_id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Remove this question?');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="fw-semibold text-white mb-2"><?= sanitize($q['question_text']) ?></div>

                                <div class="row g-2 small">
                                    <?php foreach (['A', 'B', 'C', 'D'] as $let): 
                                        $isCorrect = ($q['correct_answer'] === $let);
                                        $optKey = 'option_' . strtolower($let);
                                    ?>
                                        <div class="col-sm-6">
                                            <div class="p-2 rounded <?= $isCorrect ? 'bg-success bg-opacity-25 border border-success text-success fw-bold' : 'bg-dark border border-secondary text-secondary' ?>">
                                                <span class="badge <?= $isCorrect ? 'bg-success' : 'bg-secondary' ?> me-1"><?= $let ?></span>
                                                <?= sanitize($q[$optKey]) ?>
                                                <?php if ($isCorrect): ?>
                                                    <i class="fa-solid fa-check ms-1"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Step 2: Define Schedule (Date & Time) & Done Button -->
            <div id="scheduleSection" class="arena-card p-4 mt-4 border-success border-opacity-75 shadow-lg">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 pb-3 border-bottom border-secondary">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-25 text-success d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; font-size: 1.5rem;">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1">Define Date, Time &amp; Finalize Quiz</h5>
                            <p class="text-secondary small mb-0">Once questions and answers are set, define when the quiz is active and click <strong>Done</strong>.</p>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">
                            <i class="fa-solid fa-check-double me-1"></i> <?= count($questions) ?> Question<?= count($questions) === 1 ? '' : 's' ?> Added
                        </span>
                    </div>
                </div>

                <form action="<?= BASE_URL ?>/teacher/manage_questions.php?quiz_id=<?= $quizId ?>" method="POST" id="scheduleFinalizeForm">
                    <input type="hidden" name="action_finalize" value="1">
                    <input type="hidden" name="quiz_id" value="<?= $quizId ?>">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="start_datetime" class="form-label fw-semibold">Start Date &amp; Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control form-arena" id="start_datetime" name="start_datetime" required value="<?= date('Y-m-d\TH:i', strtotime($quiz['start_datetime'])) ?>">
                            <div class="form-text text-secondary" style="font-size: 0.75rem;">When the quiz becomes active for students</div>
                        </div>

                        <div class="col-md-6">
                            <label for="end_datetime" class="form-label fw-semibold">End Date &amp; Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control form-arena" id="end_datetime" name="end_datetime" required value="<?= date('Y-m-d\TH:i', strtotime($quiz['end_datetime'])) ?>">
                            <div class="form-text text-secondary" style="font-size: 0.75rem;">Deadline after which attempts are closed</div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Publishing Status</label>
                            <select class="form-select form-arena" id="status" name="status">
                                <option value="published" <?= $quiz['status'] === 'published' ? 'selected' : '' ?>>Published (Live according to schedule)</option>
                                <option value="draft" <?= $quiz['status'] === 'draft' ? 'selected' : '' ?>>Draft (Hidden until ready)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="max_attempts" class="form-label fw-semibold">Maximum Attempts per Student</label>
                            <input type="number" class="form-control form-arena" id="max_attempts" name="max_attempts" min="1" max="10" value="<?= (int)$quiz['max_attempts'] ?>">
                            <div class="form-text text-secondary" style="font-size: 0.75rem;">Default is 1 for competitive assessments</div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-3 border-top border-secondary">
                        <div class="text-secondary small">
                            <i class="fa-solid fa-clock text-warning me-1"></i> Calculated Total Duration: <strong class="text-white"><?= formatDurationHuman($totalSeconds) ?></strong> (<?= count($questions) ?> questions)
                        </div>
                        <button type="submit" class="btn btn-success btn-lg px-5 py-2 rounded-pill fw-bold shadow" <?= empty($questions) ? 'disabled title="Please add at least 1 question before finalizing"' : '' ?>>
                            <i class="fa-solid fa-circle-check me-2"></i>Done — Save &amp; Finish Quiz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
