<?php
$pageTitle = "Student Dashboard";
require_once __DIR__ . '/../includes/header.php';
requireStudent();

$studentId = (int)$_SESSION['user_id'];
$db = getDB();

// Fetch student profile details
$profileStmt = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ? LIMIT 1");
$profileStmt->execute([$studentId]);
$profile = $profileStmt->fetch();

// Fetch student stats
$statsStmt = $db->prepare("
    SELECT 
        COUNT(id) AS total_attempts,
        COALESCE(AVG(percentage), 0) AS avg_score,
        COALESCE(MAX(percentage), 0) AS highest_score
    FROM attempts 
    WHERE student_id = ? AND status IN ('SUBMITTED', 'AUTO_SUBMITTED')
");
$statsStmt->execute([$studentId]);
$stats = $statsStmt->fetch();

// Fetch all published quizzes
$quizzesStmt = $db->query("
    SELECT q.*, c.name AS category_name, u.name AS teacher_name,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count,
           (SELECT COALESCE(SUM(time_limit), 0) FROM questions WHERE quiz_id = q.id) AS total_seconds,
           (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE quiz_id = q.id) AS total_marks
    FROM quizzes q
    JOIN categories c ON q.category_id = c.id
    JOIN users u ON q.teacher_id = u.id
    WHERE q.status = 'published'
    ORDER BY q.id DESC
");
$quizzes = $quizzesStmt->fetchAll();

// Fetch categories for filtering
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch recent 3 attempts
$recentAttemptsStmt = $db->prepare("
    SELECT a.*, q.title AS quiz_title, q.quiz_code
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    WHERE a.student_id = ? AND a.status IN ('SUBMITTED', 'AUTO_SUBMITTED')
    ORDER BY a.id DESC
    LIMIT 3
");
$recentAttemptsStmt->execute([$studentId]);
$recentAttempts = $recentAttemptsStmt->fetchAll();
?>

<div class="container py-4">
    <!-- Student Header & Summary Stats -->
    <div class="arena-card p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: bold;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-white mb-0">Welcome, <?= sanitize($_SESSION['user_name']) ?>!</h4>
                        <div class="text-secondary small">
                            <span><i class="fa-solid fa-id-card text-info me-1"></i><?= sanitize($profile['student_id'] ?? 'N/A') ?></span>
                            <span class="mx-2">•</span>
                            <span><i class="fa-solid fa-book-open text-primary me-1"></i><?= sanitize($profile['course'] ?? 'General') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 rounded bg-dark border border-secondary">
                            <div class="fw-bold text-primary fs-5"><?= (int)$stats['total_attempts'] ?></div>
                            <div class="text-secondary" style="font-size: 0.72rem;">Attempts</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-dark border border-secondary">
                            <div class="fw-bold text-success fs-5"><?= round((float)$stats['avg_score'], 1) ?>%</div>
                            <div class="text-secondary" style="font-size: 0.72rem;">Avg Score</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-dark border border-secondary">
                            <div class="fw-bold text-warning fs-5"><?= round((float)$stats['highest_score'], 1) ?>%</div>
                            <div class="text-secondary" style="font-size: 0.72rem;">Best Score</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Enter Quiz ID Bar (Prominent Section 22) -->
    <div class="arena-card p-4 mb-4 border-primary border-opacity-50">
        <div class="row align-items-center g-3">
            <div class="col-lg-5">
                <h5 class="fw-bold text-white mb-1"><i class="fa-solid fa-key text-primary me-2"></i>Join by Unique Quiz ID</h5>
                <p class="text-secondary small mb-0">Have a specific Quiz ID from your lecturer? Enter it here to jump directly into the quiz.</p>
            </div>
            <div class="col-lg-7">
                <form action="<?= BASE_URL ?>/student/join_quiz.php" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="fa-solid fa-hashtag"></i>
                        </span>
                        <input type="text" name="quiz_code" class="form-control form-arena font-monospace text-uppercase fw-bold" placeholder="e.g. QUIZ-7F3A21" required>
                    </div>
                    <button type="submit" class="btn btn-arena-primary flex-shrink-0 px-4">
                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Join Quiz
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Search & Filters Toolbar -->
    <div class="arena-card p-3 mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-lg-4 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="quizSearchInput" class="form-control form-arena" placeholder="Search by Quiz ID or Title...">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <select id="categoryFilter" class="form-select form-arena">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= sanitize($cat['name']) ?>"><?= sanitize($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <select id="difficultyFilter" class="form-select form-arena">
                    <option value="">All Difficulties</option>
                    <option value="Easy">Easy</option>
                    <option value="Medium">Medium</option>
                    <option value="Hard">Hard</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <select id="statusFilter" class="form-select form-arena">
                    <option value="">All Statuses</option>
                    <option value="LIVE">Live Now</option>
                    <option value="UPCOMING">Upcoming</option>
                    <option value="EXPIRED">Expired</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Available Quizzes Grid -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-white mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>Available Quizzes</h4>
            <span class="text-secondary small"><?= count($quizzes) ?> total quizzes</span>
        </div>

        <div class="row g-4" id="quizzesGrid">
            <?php if (empty($quizzes)): ?>
                <div class="col-12 text-center py-5">
                    <div class="text-secondary mb-2"><i class="fa-regular fa-folder-open fa-3x"></i></div>
                    <h5 class="text-light">No published quizzes available at the moment.</h5>
                    <p class="text-secondary small">Please check back soon or ask your teacher for a Quiz ID.</p>
                </div>
            <?php else: ?>
                <?php foreach ($quizzes as $q): 
                    $statusInfo = getQuizStatus($q);
                    $attemptsMade = getStudentAttemptCount($q['id'], $studentId);
                    $attemptsLeft = $q['max_attempts'] - $attemptsMade;
                    $canAttempt = ($attemptsLeft > 0) && ($statusInfo['code'] === 'LIVE');
                ?>
                    <div class="col-md-6 col-lg-4 quiz-item-card" 
                         data-code="<?= sanitize($q['quiz_code']) ?>"
                         data-title="<?= sanitize($q['title']) ?>"
                         data-category="<?= sanitize($q['category_name']) ?>"
                         data-difficulty="<?= sanitize($q['difficulty']) ?>"
                         data-status="<?= $statusInfo['code'] ?>">
                        <div class="arena-card p-4 h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="quiz-code-badge" onclick="copyQuizCode('<?= sanitize($q['quiz_code']) ?>', this)" style="cursor: pointer;" title="Click to copy">
                                    <i class="fa-regular fa-copy me-1"></i><?= sanitize($q['quiz_code']) ?>
                                </span>
                                <?= $statusInfo['badge'] ?>
                            </div>

                            <h5 class="fw-bold text-white mb-2"><?= sanitize($q['title']) ?></h5>
                            <p class="text-secondary small flex-grow-1"><?= sanitize($q['description'] ?? 'No description provided.') ?></p>

                            <!-- Quiz metadata card -->
                            <div class="p-3 rounded bg-dark border border-secondary mb-3 small text-secondary">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fa-solid fa-user-tie me-1 text-primary"></i> Teacher:</span>
                                    <span class="text-light fw-medium"><?= sanitize($q['teacher_name']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fa-solid fa-layer-group me-1 text-info"></i> Category:</span>
                                    <span class="text-light fw-medium"><?= sanitize($q['category_name']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fa-solid fa-gauge-high me-1 text-warning"></i> Difficulty:</span>
                                    <span class="badge bg-secondary"><?= sanitize($q['difficulty']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fa-regular fa-clock me-1 text-primary"></i> Total Time:</span>
                                    <span class="text-light fw-medium"><?= formatDurationHuman($q['total_seconds']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="fa-solid fa-rotate-right me-1 text-danger"></i> Attempts:</span>
                                    <span class="<?= $attemptsLeft > 0 ? 'text-success' : 'text-danger' ?> fw-semibold">
                                        <?= $attemptsMade ?> / <?= $q['max_attempts'] ?> Used
                                    </span>
                                </div>
                            </div>

                            <?php if ($attemptsLeft <= 0): ?>
                                <button class="btn btn-secondary w-100 disabled" disabled>
                                    <i class="fa-solid fa-ban me-1"></i> Attempt Limit Reached
                                </button>
                            <?php elseif ($statusInfo['code'] === 'LIVE'): ?>
                                <a href="<?= BASE_URL ?>/student/join_quiz.php?quiz_code=<?= urlencode($q['quiz_code']) ?>" class="btn btn-arena-primary w-100 py-2">
                                    <i class="fa-solid fa-play me-1"></i> Start Quiz Now
                                </a>
                            <?php elseif ($statusInfo['code'] === 'UPCOMING'): ?>
                                <button class="btn btn-outline-warning w-100 disabled" disabled>
                                    <i class="fa-regular fa-clock me-1"></i> Starts <?= date('M d, H:i', strtotime($q['start_datetime'])) ?>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-danger w-100 disabled" disabled>
                                    <i class="fa-solid fa-lock me-1"></i> Quiz Closed
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div id="quizEmptyState" class="col-12 text-center py-5" style="display: none;">
                <div class="text-secondary mb-2"><i class="fa-solid fa-filter-circle-xmark fa-3x"></i></div>
                <h5 class="text-light">No matching quizzes found</h5>
                <p class="text-secondary small">Try changing your search keyword or clearing the filters.</p>
            </div>
        </div>
    </div>

    <!-- Recent Attempts Section -->
    <?php if (!empty($recentAttempts)): ?>
        <div class="arena-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-white mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>My Recent Attempts</h5>
                <a href="<?= BASE_URL ?>/student/history.php" class="text-primary small text-decoration-none fw-semibold">View Full History <i class="fa-solid fa-arrow-right fa-xs"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-arena mb-0">
                    <thead>
                        <tr>
                            <th>Quiz Code</th>
                            <th>Quiz Title</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Time Taken</th>
                            <th>Submitted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAttempts as $att): ?>
                            <tr>
                                <td><span class="quiz-code-badge"><?= sanitize($att['quiz_code']) ?></span></td>
                                <td class="fw-semibold text-white"><?= sanitize($att['quiz_title']) ?></td>
                                <td><span class="fw-bold text-light"><?= (int)$att['score'] ?></span> / <?= (int)$att['total_marks'] ?></td>
                                <td>
                                    <span class="badge <?= $att['percentage'] >= 75 ? 'bg-success' : ($att['percentage'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                        <?= round($att['percentage'], 1) ?>%
                                    </span>
                                </td>
                                <td><i class="fa-regular fa-clock me-1 text-secondary"></i><?= formatDuration($att['completion_time']) ?></td>
                                <td class="text-secondary small"><?= date('M d, Y H:i', strtotime($att['submitted_at'])) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/student/result.php?attempt_id=<?= $att['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fa-solid fa-eye me-1"></i> View Result
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
