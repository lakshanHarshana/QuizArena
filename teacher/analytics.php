<?php
$pageTitle = "Teacher Analytics";
require_once __DIR__ . '/../includes/header.php';
requireTeacher();

$teacherId = (int)$_SESSION['user_id'];
$db = getDB();

// Fetch teacher's quizzes for selector
$quizzesStmt = $db->prepare("SELECT id, quiz_code, title FROM quizzes WHERE teacher_id = ? ORDER BY id DESC");
$quizzesStmt->execute([$teacherId]);
$quizzes = $quizzesStmt->fetchAll();

$selectedQuizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

$metrics = [
    'total_attempts' => 0,
    'avg_percentage' => 0,
    'max_percentage' => 0,
    'min_percentage' => 0,
    'avg_completion_time' => 0
];
$totalCorrect = 0;
$totalWrong = 0;
$totalUnanswered = 0;
$pctCorrect = 0;
$pctWrong = 0;
$pctUnanswered = 0;
$brackets = [
    'bracket_low' => 0,
    'bracket_med' => 0,
    'bracket_high' => 0,
    'bracket_elite' => 0
];

try {
    // Aggregate metrics
    $aggQuery = "
        SELECT 
            COUNT(a.id) AS total_attempts,
            COALESCE(AVG(a.percentage), 0) AS avg_percentage,
            COALESCE(MAX(a.percentage), 0) AS max_percentage,
            COALESCE(MIN(a.percentage), 0) AS min_percentage,
            COALESCE(AVG(a.completion_time), 0) AS avg_completion_time
        FROM attempts a
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE q.teacher_id = ? AND a.status IN ('SUBMITTED', 'AUTO_SUBMITTED')
    ";
    $aggParams = [$teacherId];

    if ($selectedQuizId > 0) {
        $aggQuery .= " AND a.quiz_id = ?";
        $aggParams[] = $selectedQuizId;
    }

    $aggStmt = $db->prepare($aggQuery);
    $aggStmt->execute($aggParams);
    $fetchedMetrics = $aggStmt->fetch();
    if ($fetchedMetrics) {
        $metrics = $fetchedMetrics;
    }

    // Answers breakdown (Correct vs Wrong vs Unanswered)
    $ansBreakdownQuery = "
        SELECT 
            SUM(CASE WHEN ans.is_correct = 1 THEN 1 ELSE 0 END) AS total_correct,
            SUM(CASE WHEN ans.is_correct = 0 AND ans.selected_answer IS NOT NULL THEN 1 ELSE 0 END) AS total_wrong,
            SUM(CASE WHEN ans.selected_answer IS NULL THEN 1 ELSE 0 END) AS total_unanswered
        FROM answers ans
        JOIN attempts a ON ans.attempt_id = a.id
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE q.teacher_id = ? AND a.status IN ('SUBMITTED', 'AUTO_SUBMITTED')
    ";
    $ansParams = [$teacherId];
    if ($selectedQuizId > 0) {
        $ansBreakdownQuery .= " AND a.quiz_id = ?";
        $ansParams[] = $selectedQuizId;
    }
    $ansStmt = $db->prepare($ansBreakdownQuery);
    $ansStmt->execute($ansParams);
    $ansMetrics = $ansStmt->fetch();

    $totalCorrect = (int)($ansMetrics['total_correct'] ?? 0);
    $totalWrong = (int)($ansMetrics['total_wrong'] ?? 0);
    $totalUnanswered = (int)($ansMetrics['total_unanswered'] ?? 0);
    $totalAnswerInstances = $totalCorrect + $totalWrong + $totalUnanswered;

    $pctCorrect = ($totalAnswerInstances > 0) ? round(($totalCorrect / $totalAnswerInstances) * 100, 1) : 0;
    $pctWrong = ($totalAnswerInstances > 0) ? round(($totalWrong / $totalAnswerInstances) * 100, 1) : 0;
    $pctUnanswered = ($totalAnswerInstances > 0) ? round(($totalUnanswered / $totalAnswerInstances) * 100, 1) : 0;

    // Score Bracket Distribution
    $bracketQuery = "
        SELECT 
            SUM(CASE WHEN a.percentage < 50 THEN 1 ELSE 0 END) AS bracket_low,
            SUM(CASE WHEN a.percentage >= 50 AND a.percentage < 70 THEN 1 ELSE 0 END) AS bracket_med,
            SUM(CASE WHEN a.percentage >= 70 AND a.percentage < 90 THEN 1 ELSE 0 END) AS bracket_high,
            SUM(CASE WHEN a.percentage >= 90 THEN 1 ELSE 0 END) AS bracket_elite
        FROM attempts a
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE q.teacher_id = ? AND a.status IN ('SUBMITTED', 'AUTO_SUBMITTED')
    ";
    $bracketParams = [$teacherId];
    if ($selectedQuizId > 0) {
        $bracketQuery .= " AND a.quiz_id = ?";
        $bracketParams[] = $selectedQuizId;
    }
    $bStmt = $db->prepare($bracketQuery);
    $bStmt->execute($bracketParams);
    $fetchedBrackets = $bStmt->fetch();
    if ($fetchedBrackets) {
        $brackets = $fetchedBrackets;
    }
} catch (PDOException $e) {
    error_log("Analytics query error: " . $e->getMessage());
}
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Performance Analytics</h3>
            <p class="text-secondary small mb-0">Insights on student scores, answer accuracy rates, and completion times.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/teacher/results.php" class="btn btn-outline-warning btn-sm rounded-pill px-3">
                <i class="fa-solid fa-trophy me-1"></i> Leaderboard
            </a>
        </div>
    </div>

    <!-- Quiz Selection Toolbar -->
    <div class="arena-card p-3 mb-4">
        <form action="<?= BASE_URL ?>/teacher/analytics.php" method="GET" class="d-flex align-items-center gap-3">
            <label for="quiz_id" class="text-secondary small fw-bold flex-shrink-0 mb-0">Filter by Quiz:</label>
            <select name="quiz_id" id="quiz_id" class="form-select form-arena flex-grow-1" onchange="this.form.submit()">
                <option value="0">All My Quizzes (Aggregate Analytics)</option>
                <?php foreach ($quizzes as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= ($selectedQuizId === (int)$q['id']) ? 'selected' : '' ?>>
                        [<?= sanitize($q['quiz_code']) ?>] <?= sanitize($q['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Total Attempts</div>
                <div class="stat-number text-white"><?= (int)$metrics['total_attempts'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Average Score</div>
                <div class="stat-number text-primary"><?= round((float)$metrics['avg_percentage'], 1) ?>%</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Highest Score</div>
                <div class="stat-number text-success"><?= round((float)$metrics['max_percentage'], 1) ?>%</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Lowest Score</div>
                <div class="stat-number text-danger"><?= round((float)$metrics['min_percentage'], 1) ?>%</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Avg Time Taken</div>
                <div class="stat-number text-warning" style="font-size: 1.3rem;">
                    <?= formatDuration((int)$metrics['avg_completion_time']) ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-pill">
                <div class="text-secondary small mb-1">Correct Rate</div>
                <div class="stat-number text-info"><?= $pctCorrect ?>%</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- Answer Accuracy Distribution -->
        <div class="col-lg-6">
            <div class="arena-card p-4 h-100">
                <h5 class="fw-bold text-white mb-3">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>Answer Accuracy Ratio
                </h5>
                <div style="height: 280px; position: relative;">
                    <canvas id="accuracyChart"></canvas>
                </div>
                <div class="row g-2 text-center mt-3 small">
                    <div class="col-4">
                        <div class="p-2 rounded bg-dark border border-secondary">
                            <span class="text-success fw-bold"><?= $totalCorrect ?> (<?= $pctCorrect ?>%)</span>
                            <div class="text-secondary" style="font-size: 0.72rem;">Correct</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-dark border border-secondary">
                            <span class="text-danger fw-bold"><?= $totalWrong ?> (<?= $pctWrong ?>%)</span>
                            <div class="text-secondary" style="font-size: 0.72rem;">Wrong</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-dark border border-secondary">
                            <span class="text-warning fw-bold"><?= $totalUnanswered ?> (<?= $pctUnanswered ?>%)</span>
                            <div class="text-secondary" style="font-size: 0.72rem;">Unanswered</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Score Brackets -->
        <div class="col-lg-6">
            <div class="arena-card p-4 h-100">
                <h5 class="fw-bold text-white mb-3">
                    <i class="fa-solid fa-chart-column text-info me-2"></i>Score Distribution
                </h5>
                <div style="height: 280px; position: relative;">
                    <canvas id="bracketsChart"></canvas>
                </div>
                <div class="text-center text-secondary small mt-3">
                    Performance groupings across all submitted attempts.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Accuracy Donut Chart
    const ctxAcc = document.getElementById('accuracyChart');
    if (ctxAcc) {
        new Chart(ctxAcc, {
            type: 'doughnut',
            data: {
                labels: ['Correct', 'Wrong', 'Unanswered'],
                datasets: [{
                    data: [<?= $totalCorrect ?>, <?= $totalWrong ?>, <?= $totalUnanswered ?>],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#1e293b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#cbd5e1', font: { family: 'Inter' } }
                    }
                }
            }
        });
    }

    // Score Brackets Bar Chart
    const ctxBrk = document.getElementById('bracketsChart');
    if (ctxBrk) {
        new Chart(ctxBrk, {
            type: 'bar',
            data: {
                labels: ['< 50%', '50% - 69%', '70% - 89%', '90% - 100%'],
                datasets: [{
                    label: 'Students',
                    data: [
                        <?= (int)($brackets['bracket_low'] ?? 0) ?>,
                        <?= (int)($brackets['bracket_med'] ?? 0) ?>,
                        <?= (int)($brackets['bracket_high'] ?? 0) ?>,
                        <?= (int)($brackets['bracket_elite'] ?? 0) ?>
                    ],
                    backgroundColor: ['#ef4444', '#f59e0b', '#6366f1', '#10b981'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8', stepSize: 1 },
                        grid: { color: '#334155' }
                    },
                    x: {
                        ticks: { color: '#cbd5e1' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
