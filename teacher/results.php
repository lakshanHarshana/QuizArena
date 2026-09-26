<?php
$pageTitle = "Results & Rankings";
require_once __DIR__ . '/../includes/header.php';
requireTeacher();

$teacherId = (int)$_SESSION['user_id'];
$db = getDB();

// Fetch teacher's quizzes for selector
$quizzesStmt = $db->prepare("SELECT id, quiz_code, title FROM quizzes WHERE teacher_id = ? ORDER BY id DESC");
$quizzesStmt->execute([$teacherId]);
$quizzes = $quizzesStmt->fetchAll();

$selectedQuizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$searchStudent = trim($_GET['search'] ?? '');

// Base query for teacher's quiz attempts
$query = "
    SELECT a.*, q.title AS quiz_title, q.quiz_code, q.total_marks,
           u.name AS student_name, u.email AS student_email,
           sp.student_id, sp.course
    FROM attempts a
    JOIN quizzes q ON a.quiz_id = q.id
    JOIN users u ON a.student_id = u.id
    LEFT JOIN student_profiles sp ON u.id = sp.user_id
    WHERE q.teacher_id = ? AND a.status IN ('SUBMITTED', 'AUTO_SUBMITTED')
";
$params = [$teacherId];

if ($selectedQuizId > 0) {
    $query .= " AND a.quiz_id = ?";
    $params[] = $selectedQuizId;
}

if (!empty($searchStudent)) {
    $query .= " AND (u.name LIKE ? OR sp.student_id LIKE ? OR u.email LIKE ?)";
    $like = '%' . $searchStudent . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Section 20 Ranking Logic: Score DESC, then Completion Time ASC!
$query .= " ORDER BY a.score DESC, a.completion_time ASC, a.submitted_at ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1"><i class="fa-solid fa-trophy text-warning me-2"></i>Results & Leaderboard Rankings</h3>
            <p class="text-secondary small mb-0">
                Ranked strictly by <strong>Highest Score (DESC)</strong>, and tie-broken by <strong>Faster Completion Time (ASC)</strong>.
            </p>
        </div>
        <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="arena-card p-3 mb-4">
        <form action="<?= BASE_URL ?>/teacher/results.php" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <label for="quiz_id" class="visually-hidden">Select Quiz</label>
                <select name="quiz_id" id="quiz_id" class="form-select form-arena" onchange="this.form.submit()">
                    <option value="0">All My Quizzes (Global Leaderboard)</option>
                    <?php foreach ($quizzes as $q): ?>
                        <option value="<?= $q['id'] ?>" <?= ($selectedQuizId === (int)$q['id']) ? 'selected' : '' ?>>
                            [<?= sanitize($q['quiz_code']) ?>] <?= sanitize($q['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-5">
                <label for="search" class="visually-hidden">Search Student</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" id="search" class="form-control form-arena" placeholder="Search by Student Name or Student ID..." value="<?= sanitize($searchStudent) ?>">
                </div>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-arena-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <?php if ($selectedQuizId > 0 || !empty($searchStudent)): ?>
                    <a href="<?= BASE_URL ?>/teacher/results.php" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Leaderboard Ranking Table (Section 19) -->
    <div class="arena-card p-4">
        <?php if (empty($results)): ?>
            <div class="text-center py-5">
                <div class="text-secondary mb-2"><i class="fa-solid fa-user-slash fa-3x"></i></div>
                <h5 class="text-light">No student attempt submissions found.</h5>
                <p class="text-secondary small">Once students join and complete the quiz, rankings will appear here automatically.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-arena mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Rank</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Quiz</th>
                            <th class="text-end">Score</th>
                            <th class="text-end">Percentage</th>
                            <th class="text-end">Completion Time</th>
                            <th>Submission</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $currentRank = 1;
                        foreach ($results as $res): 
                            $isTop1 = ($currentRank === 1);
                            $isTop2 = ($currentRank === 2);
                            $isTop3 = ($currentRank === 3);

                            $rankBadge = "<span class='badge bg-secondary rounded-pill px-2'>#{$currentRank}</span>";
                            if ($isTop1) {
                                $rankBadge = "<span class='badge bg-warning text-dark rounded-pill px-2 fs-6 fw-bold'><i class='fa-solid fa-crown me-1'></i>1</span>";
                            } elseif ($isTop2) {
                                $rankBadge = "<span class='badge bg-light text-dark rounded-pill px-2 fs-6 fw-bold'><i class='fa-solid fa-medal me-1 text-secondary'></i>2</span>";
                            } elseif ($isTop3) {
                                $rankBadge = "<span class='badge bg-warning bg-opacity-50 text-white rounded-pill px-2 fs-6 fw-bold'><i class='fa-solid fa-award me-1'></i>3</span>";
                            }
                        ?>
                            <tr class="<?= $isTop1 ? 'table-active' : '' ?>">
                                <td><?= $rankBadge ?></td>
                                <td>
                                    <div class="fw-bold text-white"><?= sanitize($res['student_name']) ?></div>
                                    <div class="text-secondary" style="font-size: 0.72rem;"><?= sanitize($res['course'] ?? 'Student') ?></div>
                                </td>
                                <td>
                                    <span class="font-monospace text-light fw-semibold"><?= sanitize($res['student_id'] ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <div><span class="quiz-code-badge small"><?= sanitize($res['quiz_code']) ?></span></div>
                                    <div class="small text-secondary text-truncate" style="max-width: 150px;"><?= sanitize($res['quiz_title']) ?></div>
                                </td>
                                <td class="text-end font-monospace">
                                    <strong class="text-white fs-6"><?= (int)$res['score'] ?></strong> / <?= (int)$res['total_marks'] ?>
                                </td>
                                <td class="text-end">
                                    <span class="badge <?= $res['percentage'] >= 75 ? 'bg-success' : ($res['percentage'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                        <?= round($res['percentage'], 1) ?>%
                                    </span>
                                </td>
                                <td class="text-end font-monospace text-light">
                                    <i class="fa-regular fa-clock me-1 text-primary"></i><?= formatDuration((int)$res['completion_time']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $res['submission_type'] === 'AUTO' ? 'bg-warning text-dark' : 'bg-primary' ?>">
                                        <?= sanitize($res['submission_type']) ?>
                                    </span>
                                </td>
                                <td class="text-secondary small">
                                    <?= date('M d, H:i', strtotime($res['submitted_at'])) ?>
                                </td>
                            </tr>
                        <?php 
                            $currentRank++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
