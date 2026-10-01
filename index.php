<?php
$pageTitle = "Home";
require_once __DIR__ . '/includes/header.php';

// Fetch quick live stats
$db = getDB();
$quizCount = $db->query("SELECT COUNT(*) FROM quizzes WHERE status = 'published'")->fetchColumn() ?: 0;
$attemptCount = $db->query("SELECT COUNT(*) FROM attempts WHERE status IN ('SUBMITTED', 'AUTO_SUBMITTED')")->fetchColumn() ?: 0;
$studentCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?: 0;

// Fetch 3 featured live/upcoming quizzes
$featuredStmt = $db->query("
    SELECT q.*, c.name AS category_name, u.name AS teacher_name,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count,
           (SELECT COALESCE(SUM(time_limit), 0) FROM questions WHERE quiz_id = q.id) AS total_seconds
    FROM quizzes q
    JOIN categories c ON q.category_id = c.id
    JOIN users u ON q.teacher_id = u.id
    WHERE q.status = 'published'
    ORDER BY q.id DESC
    LIMIT 3
");
$featuredQuizzes = $featuredStmt->fetchAll();
?>

<!-- Hero Section -->
<section id="hero" class="py-5 py-lg-6 position-relative overflow-hidden">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 text-center text-lg-start">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-10 border border-primary border-opacity-25 text-primary small fw-semibold mb-3">
                    <i class="fa-solid fa-fire fa-shake"></i> Next-Gen Real-Time MCQ Arena
                </div>
                <h1 class="display-4 fw-extrabold hero-gradient-text mb-3" style="letter-spacing: -1px; font-weight: 800;">
                    Challenge Your Knowledge.<br>
                    <span class="gradient-accent-text">Play. Answer. Compete.</span>
                </h1>
                <p class="lead text-secondary mb-4" style="max-width: 580px;">
                    Test your expertise with fast-paced, interactive online MCQ quizzes. Experience real-time game feedback, automated synchronized timers, and dynamic leaderboards.
                </p>

                <!-- Quick Quiz Code Join Bar -->
                <form action="<?= BASE_URL ?>/student/join_quiz.php" method="GET" class="mb-4">
                    <div class="input-group p-1 bg-dark rounded-pill border border-secondary shadow-lg" style="max-width: 480px;">
                        <span class="input-group-text bg-transparent border-0 text-secondary ps-3">
                            <i class="fa-solid fa-hashtag text-primary"></i>
                        </span>
                        <input type="text" name="quiz_code" class="form-control bg-transparent border-0 text-white font-monospace fw-bold text-uppercase" placeholder="Enter Quiz ID (e.g. QUIZ-7F3A21)" required>
                        <button type="submit" class="btn btn-arena-primary rounded-pill px-4">
                            <i class="fa-solid fa-gamepad me-1"></i> Join Arena
                        </button>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isStudent()): ?>
                            <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-arena-primary px-4 py-2">
                                <i class="fa-solid fa-gauge me-2"></i>Go to Student Dashboard
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/teacher/dashboard.php" class="btn btn-arena-primary px-4 py-2">
                                <i class="fa-solid fa-chalkboard-user me-2"></i>Teacher Console
                            </a>
                            <a href="<?= BASE_URL ?>/teacher/create_quiz.php" class="btn btn-arena-outline px-4 py-2">
                                <i class="fa-solid fa-plus-circle me-2"></i>Create New Quiz
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/register.php" class="btn btn-arena-outline px-4 py-2">
                            <i class="fa-solid fa-user-plus me-2"></i>Create an Account
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="arena-card p-4 p-md-5 floating-element position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="quiz-code-badge"><i class="fa-solid fa-bolt me-1"></i>QUIZ-7F3A21</span>
                        <span class="badge bg-success pulse-badge"><i class="fa-solid fa-signal me-1"></i>LIVE</span>
                    </div>
                    <h5 class="fw-bold text-white mb-2">Database Fundamentals</h5>
                    <p class="text-secondary small mb-4">Relational queries, ACID transactions, and indexes.</p>

                    <!-- Mock interactive preview -->
                    <div class="p-3 rounded-3 mb-3" style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981;">
                        <div class="d-flex align-items-center justify-content-between text-success fw-bold small mb-1">
                            <span><i class="fa-solid fa-check-circle me-1"></i> Option B Selected</span>
                            <span>+1 Mark</span>
                        </div>
                        <div class="text-light small">B. Structured Query Language</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary text-secondary small">
                        <span><i class="fa-regular fa-clock me-1 text-primary"></i> 5 Mins Overall</span>
                        <span><i class="fa-solid fa-list-check me-1 text-primary"></i> 6 Questions</span>
                        <span class="text-warning fw-semibold">Medium</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Counter -->
<section id="stats" class="py-4 border-top border-bottom border-secondary bg-dark bg-opacity-50">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="stat-number text-primary"><?= number_format($quizCount) ?>+</div>
                <div class="text-secondary small fw-medium">Available Quizzes</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-number text-success"><?= number_format($attemptCount) ?>+</div>
                <div class="text-secondary small fw-medium">Completed Attempts</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-number text-info"><?= number_format($studentCount) ?>+</div>
                <div class="text-secondary small fw-medium">Active Students</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-number text-warning">100%</div>
                <div class="text-secondary small fw-medium">Automated Timing</div>
            </div>
        </div>
    </div>
</section>

<!-- Interactive Feature Showcase Slider (Satisfies ICT 1209 Section 2.3 Item 2 & Item 4) -->
<section id="arena-slider" class="py-5 bg-dark bg-opacity-25 border-bottom border-secondary">
    <div class="container py-2">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill mb-2">
                    <i class="fa-solid fa-sliders me-1"></i> Interactive Feature Carousel
                </span>
                <h2 class="fw-bold text-white mb-1">Explore Platform Capabilities</h2>
                <p class="text-secondary small mb-0">High-performance game mechanics built for real-time university quizzes</p>
            </div>
            
            <!-- Quick Jump Smooth-Scroll Navigation Bar (Satisfies Section 2.3 Item 4) -->
            <div class="arena-jump-bar" id="sliderJumpBar">
                <a href="#hero" class="arena-jump-link"><i class="fa-solid fa-arrow-up me-1"></i> Top</a>
                <a href="#arena-slider" class="arena-jump-link active"><i class="fa-solid fa-sliders me-1"></i> Showcase</a>
                <a href="#how-it-works" class="arena-jump-link"><i class="fa-solid fa-diagram-project me-1"></i> Workflow</a>
                <a href="#featured-quizzes" class="arena-jump-link"><i class="fa-solid fa-list-check me-1"></i> Quizzes</a>
            </div>
        </div>

        <div class="arena-slider-container position-relative">
            <div id="arenaCarousel" class="carousel slide carousel-arena" data-bs-ride="carousel" data-bs-interval="4500" data-bs-pause="hover">
                <!-- Indicators -->
                <div class="carousel-indicators mb-3">
                    <button type="button" data-bs-target="#arenaCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1: Gameplay"></button>
                    <button type="button" data-bs-target="#arenaCarousel" data-bs-slide-to="1" aria-label="Slide 2: Dual Timers"></button>
                    <button type="button" data-bs-target="#arenaCarousel" data-bs-slide-to="2" aria-label="Slide 3: Leaderboard"></button>
                    <button type="button" data-bs-target="#arenaCarousel" data-bs-slide-to="3" aria-label="Slide 4: Analytics"></button>
                </div>

                <!-- Carousel Slides -->
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="<?= BASE_URL ?>/images/slider_gameplay.svg" class="d-block w-100" alt="Real-Time MCQ Gameplay & Instant Feedback">
                    </div>
                    <div class="carousel-item">
                        <img src="<?= BASE_URL ?>/images/slider_timer.svg" class="d-block w-100" alt="Dual Synchronized Timers & Auto-Calculated Limits">
                    </div>
                    <div class="carousel-item">
                        <img src="<?= BASE_URL ?>/images/slider_leaderboard.svg" class="d-block w-100" alt="Dynamic Leaderboards & Animated Achievements">
                    </div>
                    <div class="carousel-item">
                        <img src="<?= BASE_URL ?>/images/slider_analytics.svg" class="d-block w-100" alt="Teacher Console, Unique Quiz IDs & Analytics">
                    </div>
                </div>

                <!-- Manual Navigation Controls -->
                <button class="carousel-control-prev carousel-control-arena ms-3" type="button" data-bs-target="#arenaCarousel" data-bs-slide="prev" aria-label="Previous Slide">
                    <i class="fa-solid fa-chevron-left text-white"></i>
                </button>
                <button class="carousel-control-next carousel-control-arena me-3" type="button" data-bs-target="#arenaCarousel" data-bs-slide="next" aria-label="Next Slide">
                    <i class="fa-solid fa-chevron-right text-white"></i>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill mb-2">Interactive Workflow</span>
            <h2 class="fw-bold text-white">How QuizArena Works</h2>
            <p class="text-secondary" style="max-width: 600px; margin: 0 auto;">
                A seamless flow connecting teachers with students in a competitive real-time quiz arena.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="arena-card p-4 h-100 text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="fa-solid fa-pen-to-square fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">1. Teacher Creates</h5>
                    <p class="text-secondary small mb-0">
                        Teacher sets up the quiz, adds MCQs with individual question times. The system auto-generates a unique Quiz ID.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="arena-card p-4 h-100 text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-info bg-opacity-10 text-info mb-3">
                        <i class="fa-solid fa-calculator fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">2. Auto Total Time</h5>
                    <p class="text-secondary small mb-0">
                        The overall quiz duration is automatically calculated as the sum of all question times. No manual guesswork.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="arena-card p-4 h-100 text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-3">
                        <i class="fa-solid fa-bolt fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">3. Instant Feedback</h5>
                    <p class="text-secondary small mb-0">
                        Students pick answers and get instant visual GREEN / RED feedback, locked options, and smooth auto-transitions.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="arena-card p-4 h-100 text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-3">
                        <i class="fa-solid fa-trophy fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">4. Auto-Submit & Ranks</h5>
                    <p class="text-secondary small mb-0">
                        When overall time ends, the quiz auto-submits. Scores and leaderboard rankings are dynamically calculated.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Quizzes Preview -->
<?php if (!empty($featuredQuizzes)): ?>
<section id="featured-quizzes" class="py-5 bg-dark bg-opacity-25 border-top border-secondary">
    <div class="container py-2">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-white mb-1">Featured Quizzes</h3>
                <p class="text-secondary small mb-0">Explore live and scheduled assessments</p>
            </div>
            <button type="button" class="btn btn-sm btn-arena-outline" data-bs-toggle="modal" data-bs-target="#quickJoinModal">
                <i class="fa-solid fa-key me-1"></i> Join with Quiz ID
            </button>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredQuizzes as $q): 
                $statusInfo = getQuizStatus($q);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="arena-card p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="quiz-code-badge"><?= sanitize($q['quiz_code']) ?></span>
                            <?= $statusInfo['badge'] ?>
                        </div>
                        <h5 class="fw-bold text-white mb-1"><?= sanitize($q['title']) ?></h5>
                        <p class="text-secondary small flex-grow-1"><?= sanitize(substr($q['description'], 0, 90)) . (strlen($q['description']) > 90 ? '...' : '') ?></p>

                        <div class="p-2 rounded bg-dark border border-secondary mb-3 small text-secondary">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fa-solid fa-user-tie me-1 text-primary"></i> Teacher:</span>
                                <span class="text-light fw-medium"><?= sanitize($q['teacher_name']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fa-solid fa-layer-group me-1 text-info"></i> Category:</span>
                                <span class="text-light fw-medium"><?= sanitize($q['category_name']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="fa-regular fa-clock me-1 text-warning"></i> Duration:</span>
                                <span class="text-light fw-medium"><?= formatDurationHuman($q['total_seconds']) ?></span>
                            </div>
                        </div>

                        <a href="<?= BASE_URL ?>/student/join_quiz.php?quiz_code=<?= urlencode($q['quiz_code']) ?>" class="btn btn-arena-primary w-100 py-2">
                            <i class="fa-solid fa-play me-1"></i> Start / Join Quiz
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call To Action -->
<section id="cta" class="py-5">
    <div class="container">
        <div class="arena-card p-5 text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);">
            <h2 class="fw-bold text-white mb-2">Ready to test your knowledge?</h2>
            <p class="text-secondary lead mb-4" style="max-width: 600px; margin: 0 auto;">
                Join students in real-time academic assessments with instant feedback and animated achievements.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-arena-primary px-4 py-2" data-bs-toggle="modal" data-bs-target="#quickJoinModal">
                    <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Enter Quiz ID Now
                </button>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-arena-outline px-4 py-2">
                    <i class="fa-solid fa-user-plus me-2"></i>Create Account
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Smooth-Scroll Active Navigation State Observer (Satisfies ICT 1209 Section 2.3 Item 4) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.arena-jump-link');

    if (sections.length && navLinks.length) {
        window.addEventListener('scroll', function () {
            let current = '';
            const scrollPos = window.pageYOffset || document.documentElement.scrollTop;

            sections.forEach(section => {
                const sectionTop = section.offsetTop - 140;
                const sectionHeight = section.offsetHeight;
                if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
