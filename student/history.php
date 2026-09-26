<?php
$pageTitle = "My Quiz History";
require_once __DIR__ . '/../includes/header.php';
requireStudent();

$studentId = (int)$_SESSION['user_id'];
$db = getDB();

$stmt = $db->prepare("
    SELECT a.*, q.title AS quiz_title, q.quiz_code, c.name AS category_name
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    JOIN categories c ON q.category_id = c.id
    WHERE a.student_id = ? AND a.status IN ('SUBMITTED', 'AUTO_SUBMITTED')
    ORDER BY a.id DESC
");
$stmt->execute([$studentId]);
$attempts = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>My Quiz History</h3>
            <p class="text-secondary small mb-0">Track all your completed attempts, scores, and completion times.</p>
        </div>
        <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <div class="arena-card p-4">
        <?php if (empty($attempts)): ?>
            <div class="text-center py-5">
                <div class="text-secondary mb-3"><i class="fa-solid fa-hourglass-start fa-3x"></i></div>
                <h5 class="text-light">No completed quiz attempts yet.</h5>
                <p class="text-secondary small mb-4">Explore available quizzes on your dashboard and complete your first challenge.</p>
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-arena-primary px-4 py-2">
                    <i class="fa-solid fa-list-check me-2"></i>Browse Quizzes
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-arena mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Quiz ID</th>
                            <th>Quiz Title</th>
                            <th>Category</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Time Taken</th>
                            <th>Submission</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attempts as $idx => $att): ?>
                            <tr>
                                <td><?= ($idx + 1) ?></td>
                                <td><span class="quiz-code-badge"><?= sanitize($att['quiz_code']) ?></span></td>
                                <td class="fw-semibold text-white"><?= sanitize($att['quiz_title']) ?></td>
                                <td class="text-secondary"><?= sanitize($att['category_name']) ?></td>
                                <td><span class="fw-bold text-light"><?= (int)$att['score'] ?></span> / <?= (int)$att['total_marks'] ?></td>
                                <td>
                                    <span class="badge <?= $att['percentage'] >= 75 ? 'bg-success' : ($att['percentage'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                        <?= round($att['percentage'], 1) ?>%
                                    </span>
                                </td>
                                <td><i class="fa-regular fa-clock me-1 text-secondary"></i><?= formatDuration((int)$att['completion_time']) ?></td>
                                <td>
                                    <span class="badge <?= $att['submission_type'] === 'AUTO' ? 'bg-warning text-dark' : 'bg-info text-dark' ?>">
                                        <?= sanitize($att['submission_type']) ?>
                                    </span>
                                </td>
                                <td class="text-secondary small"><?= date('M d, Y H:i', strtotime($att['submitted_at'])) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/student/result.php?attempt_id=<?= $att['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fa-solid fa-eye me-1"></i> Result
                                    </a>
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
