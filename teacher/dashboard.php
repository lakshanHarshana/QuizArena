<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireTeacher();

$teacherId = (int)$_SESSION['user_id'];
$db = getDB();

// Handle quick publish / unpublish / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $quizId = (int)($_POST['quiz_id'] ?? 0);

    // Verify ownership
    $checkOwner = $db->prepare("SELECT id FROM quizzes WHERE id = ? AND teacher_id = ? LIMIT 1");
    $checkOwner->execute([$quizId, $teacherId]);
    if ($checkOwner->fetch()) {
        if ($_POST['action'] === 'toggle_publish') {
            $currentStatus = $_POST['current_status'] ?? 'draft';
            $newStatus = ($currentStatus === 'published') ? 'draft' : 'published';
            $upd = $db->prepare("UPDATE quizzes SET status = ? WHERE id = ?");
            $upd->execute([$newStatus, $quizId]);
            setFlash('success', "Quiz status updated to " . ucfirst($newStatus) . ".");
        } elseif ($_POST['action'] === 'delete') {
            $del = $db->prepare("DELETE FROM quizzes WHERE id = ?");
            $del->execute([$quizId]);
            setFlash('success', "Quiz and associated questions deleted successfully.");
        }
    }
    header("Location: " . BASE_URL . "/teacher/dashboard.php");
    exit();
}

$pageTitle = "Teacher Dashboard";
require_once __DIR__ . '/../includes/header.php';

// Fetch teacher profile
$profStmt = $db->prepare("SELECT * FROM teacher_profiles WHERE user_id = ? LIMIT 1");
$profStmt->execute([$teacherId]);
$profile = $profStmt->fetch();

// Fetch summary metrics
$totalQuizzes = $db->prepare("SELECT COUNT(*) FROM quizzes WHERE teacher_id = ?");
$totalQuizzes->execute([$teacherId]);
$totalQuizzesCount = $totalQuizzes->fetchColumn();

$totalAttempts = $db->prepare("
    SELECT COUNT(a.id) 
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    WHERE q.teacher_id = ? AND a.status IN ('SUBMITTED', 'AUTO_SUBMITTED')
");
$totalAttempts->execute([$teacherId]);
$totalAttemptsCount = $totalAttempts->fetchColumn();

$totalStudents = $db->prepare("
    SELECT COUNT(DISTINCT a.student_id) 
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    WHERE q.teacher_id = ? AND a.status IN ('SUBMITTED', 'AUTO_SUBMITTED')
");
$totalStudents->execute([$teacherId]);
$totalStudentsCount = $totalStudents->fetchColumn();

// Fetch teacher's quizzes
$quizzesStmt = $db->prepare("
    SELECT q.*, c.name AS category_name,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count,
           (SELECT COALESCE(SUM(time_limit), 0) FROM questions WHERE quiz_id = q.id) AS total_seconds,
           (SELECT COUNT(*) FROM attempts WHERE quiz_id = q.id AND status IN ('SUBMITTED', 'AUTO_SUBMITTED')) AS attempt_count
    FROM quizzes q
    JOIN categories c ON q.category_id = c.id
    WHERE q.teacher_id = ?
    ORDER BY q.id DESC
");
$quizzesStmt->execute([$teacherId]);
$quizzes = $quizzesStmt->fetchAll();
?>

<div class="container py-4">
    <!-- Teacher Header Bar -->
    <div class="arena-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-25 text-info d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: bold;">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-white mb-0">Teacher Console — <?= sanitize($_SESSION['user_name']) ?></h4>
                        <div class="text-secondary small">
                            <i class="fa-solid fa-building-columns text-primary me-1"></i><?= sanitize($profile['department'] ?? 'Department of ICT') ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5 d-flex justify-content-md-end gap-2">
                <a href="<?= BASE_URL ?>/teacher/create_quiz.php" class="btn btn-arena-primary rounded-pill px-4">
                    <i class="fa-solid fa-plus-circle me-1"></i> Create New Quiz
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Total Quizzes</div>
                <div class="stat-number text-primary"><?= (int)$totalQuizzesCount ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Completed Attempts</div>
                <div class="stat-number text-success"><?= (int)$totalAttemptsCount ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Participating Students</div>
                <div class="stat-number text-info"><?= (int)$totalStudentsCount ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Fast Navigation</div>
                <a href="<?= BASE_URL ?>/teacher/results.php" class="btn btn-sm btn-outline-warning mt-1 w-100 rounded-pill">
                    <i class="fa-solid fa-trophy me-1"></i> Rankings
                </a>
            </div>
        </div>
    </div>

    <!-- Managed Quizzes Table Card -->
    <div class="arena-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-book-bookmark text-primary me-2"></i>My Quizzes</h4>
                <p class="text-secondary small mb-0">Manage questions, timings, schedules, and monitor student participation.</p>
            </div>
        </div>

        <?php if (empty($quizzes)): ?>
            <div class="text-center py-5">
                <div class="text-secondary mb-3"><i class="fa-regular fa-folder-open fa-3x"></i></div>
                <h5 class="text-light">You haven't created any quizzes yet.</h5>
                <p class="text-secondary small mb-3">Click below to generate a unique Quiz ID and add your MCQ questions.</p>
                <a href="<?= BASE_URL ?>/teacher/create_quiz.php" class="btn btn-arena-primary px-4 py-2">
                    <i class="fa-solid fa-plus me-1"></i> Create First Quiz
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-arena mb-0">
                    <thead>
                        <tr>
                            <th>Quiz ID</th>
                            <th>Quiz Title</th>
                            <th>Category</th>
                            <th>Questions</th>
                            <th>Auto Total Time</th>
                            <th>Schedule & Status</th>
                            <th>Attempts</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $q): 
                            $statusInfo = getQuizStatus($q);
                        ?>
                            <tr>
                                <td>
                                    <span class="quiz-code-badge" onclick="copyQuizCode('<?= sanitize($q['quiz_code']) ?>', this)" style="cursor: pointer;" title="Click to copy">
                                        <i class="fa-regular fa-copy me-1"></i><?= sanitize($q['quiz_code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-white"><?= sanitize($q['title']) ?></div>
                                    <div class="small text-secondary"><?= sanitize($q['difficulty']) ?> • Max Attempts: <?= (int)$q['max_attempts'] ?></div>
                                </td>
                                <td class="text-light"><?= sanitize($q['category_name']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/teacher/manage_questions.php?quiz_id=<?= $q['id'] ?>" class="badge bg-dark border border-secondary text-info text-decoration-none">
                                        <i class="fa-solid fa-list-ol me-1"></i><?= (int)$q['question_count'] ?> MCQs
                                    </a>
                                </td>
                                <td>
                                    <span class="text-warning fw-semibold font-monospace">
                                        <i class="fa-regular fa-clock me-1"></i><?= formatDurationHuman($q['total_seconds']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="mb-1"><?= $statusInfo['badge'] ?></div>
                                    <div class="text-secondary" style="font-size: 0.72rem;">
                                        <?= date('M d, H:i', strtotime($q['start_datetime'])) ?> - <?= date('M d, H:i', strtotime($q['end_datetime'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/teacher/results.php?quiz_id=<?= $q['id'] ?>" class="text-decoration-none fw-bold text-success">
                                        <?= (int)$q['attempt_count'] ?> Completed
                                    </a>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= BASE_URL ?>/teacher/manage_questions.php?quiz_id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-info" title="Manage Questions">
                                            <i class="fa-solid fa-list"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/teacher/edit_quiz.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Quiz Details">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        
                                        <!-- Toggle Publish -->
                                        <form action="<?= BASE_URL ?>/teacher/dashboard.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_publish">
                                            <input type="hidden" name="quiz_id" value="<?= $q['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $q['status'] ?>">
                                            <button type="submit" class="btn btn-sm <?= $q['status'] === 'published' ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $q['status'] === 'published' ? 'Unpublish Quiz' : 'Publish Quiz' ?>">
                                                <i class="fa-solid <?= $q['status'] === 'published' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Delete Quiz -->
                                        <form action="<?= BASE_URL ?>/teacher/dashboard.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this quiz and all its questions and results?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="quiz_id" value="<?= $q['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Quiz">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
