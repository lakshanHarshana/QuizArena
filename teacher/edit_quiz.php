<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireTeacher();

$teacherId = (int)$_SESSION['user_id'];
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

$db = getDB();

// Verify ownership
$stmt = $db->prepare("SELECT * FROM quizzes WHERE id = ? AND teacher_id = ? LIMIT 1");
$stmt->execute([$quizId, $teacherId]);
$quiz = $stmt->fetch();

if (!$quiz) {
    setFlash('error', "Quiz not found or access denied.");
    header("Location: " . BASE_URL . "/teacher/dashboard.php");
    exit();
}

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $difficulty = trim($_POST['difficulty'] ?? 'Medium');
    $startDatetime = trim($_POST['start_datetime'] ?? '');
    $endDatetime = trim($_POST['end_datetime'] ?? '');
    $maxAttempts = max(1, (int)($_POST['max_attempts'] ?? 1));
    $status = in_array($_POST['status'] ?? '', ['published', 'draft', 'closed']) ? $_POST['status'] : 'published';

    if (empty($title)) {
        $error = "Quiz title is required.";
    } elseif ($categoryId <= 0) {
        $error = "Please select a valid category.";
    } elseif (empty($startDatetime) || empty($endDatetime)) {
        $error = "Start and end dates/times are required.";
    } elseif (strtotime($endDatetime) <= strtotime($startDatetime)) {
        $error = "End date and time must be later than the start date and time.";
    } else {
        $upd = $db->prepare("
            UPDATE quizzes 
            SET title = ?, description = ?, category_id = ?, difficulty = ?, 
                start_datetime = ?, end_datetime = ?, max_attempts = ?, status = ?
            WHERE id = ? AND teacher_id = ?
        ");
        $upd->execute([
            $title, $description, $categoryId, $difficulty,
            $startDatetime, $endDatetime, $maxAttempts, $status,
            $quizId, $teacherId
        ]);

        setFlash('success', "Quiz updated successfully.");
        header("Location: " . BASE_URL . "/teacher/dashboard.php");
        exit();
    }
}

$pageTitle = "Edit Quiz";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-white mb-1"><i class="fa-solid fa-pen text-primary me-2"></i>Edit Quiz Details</h3>
                    <p class="text-secondary small mb-0">Modify configuration, schedules, and active availability.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/teacher/manage_questions.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-outline-info btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-list me-1"></i> Questions
                    </a>
                    <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
                    </a>
                </div>
            </div>

            <div class="arena-card p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center bg-danger bg-opacity-25 border-danger text-danger-emphasis rounded-3 mb-4">
                        <i class="fa-solid fa-circle-exclamation fa-lg me-2 text-danger"></i>
                        <div><?= sanitize($error) ?></div>
                    </div>
                <?php endif; ?>

                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-secondary">
                    <div>
                        <span class="text-secondary small">Unique Quiz ID:</span>
                        <div class="quiz-code-badge fs-5 mt-1"><?= sanitize($quiz['quiz_code']) ?></div>
                    </div>
                    <a href="<?= BASE_URL ?>/teacher/manage_questions.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="fa-solid fa-pen-ruler me-1"></i> Manage Questions
                    </a>
                </div>

                <form action="<?= BASE_URL ?>/teacher/edit_quiz.php" method="POST" id="quizForm" novalidate>
                    <input type="hidden" name="id" value="<?= $quiz['id'] ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-arena" id="title" name="title" required value="<?= sanitize($quiz['title']) ?>">
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control form-arena" id="description" name="description" rows="3"><?= sanitize($quiz['description'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select form-arena" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ((int)$quiz['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                        <?= sanitize($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="difficulty" class="form-label">Difficulty</label>
                            <select class="form-select form-arena" id="difficulty" name="difficulty">
                                <option value="Easy" <?= ($quiz['difficulty'] === 'Easy') ? 'selected' : '' ?>>Easy</option>
                                <option value="Medium" <?= ($quiz['difficulty'] === 'Medium') ? 'selected' : '' ?>>Medium</option>
                                <option value="Hard" <?= ($quiz['difficulty'] === 'Hard') ? 'selected' : '' ?>>Hard</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="start_datetime" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control form-arena" id="start_datetime" name="start_datetime" required value="<?= date('Y-m-d\TH:i', strtotime($quiz['start_datetime'])) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="end_datetime" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control form-arena" id="end_datetime" name="end_datetime" required value="<?= date('Y-m-d\TH:i', strtotime($quiz['end_datetime'])) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="max_attempts" class="form-label">Max Attempts</label>
                            <input type="number" class="form-control form-arena" id="max_attempts" name="max_attempts" min="1" max="10" value="<?= (int)$quiz['max_attempts'] ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select form-arena" id="status" name="status">
                                <option value="published" <?= ($quiz['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= ($quiz['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                                <option value="closed" <?= ($quiz['status'] === 'closed') ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>

                        <div class="col-12 mt-4 pt-2 border-top border-secondary d-flex gap-2">
                            <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="btn btn-secondary px-4 py-2 rounded-pill">Cancel</a>
                            <button type="submit" class="btn btn-arena-primary flex-grow-1 py-2 rounded-pill">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
