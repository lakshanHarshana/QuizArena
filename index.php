<?php
$pageTitle = "Home";
require_once __DIR__ . '/includes/header.php';

// Fetch quick live stats
$db = getDB();
$quizCount = $db->query("SELECT COUNT(*) FROM quizzes WHERE status = 'published'")->fetchColumn() ?: 0;
$attemptCount = $db->query("SELECT COUNT(*) FROM attempts WHERE status IN ('SUBMITTED', 'AUTO_SUBMITTED')")->fetchColumn() ?: 0;
$studentCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?: 0;
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

            <div class="col-lg-5 text-center">
                <!-- Pure Rotating Discipline Cards Carousel Arena -->
                <div class="cards-orbit-arena mx-auto position-relative" id="cardsOrbitArena">
                    <!-- Central Quiz Core Hub -->
                    <div class="arena-central-core shadow-lg" onclick="showDisciplineInfo('core')">
                        <div class="core-icon-wrap">
                            <i class="fa-solid fa-trophy text-warning fa-2x core-trophy-icon"></i>
                            <i class="fa-solid fa-bolt text-warning core-bolt-icon"></i>
                        </div>
                        <div class="core-title mt-1">QuizArena</div>
                    </div>

                    <!-- Orbital Track Circles -->
                    <div class="orbit-track-ring ring-main"></div>
                    <div class="orbit-track-ring ring-outer"></div>

                    <!-- Continuous 360-Degree Rotating Cards Carousel Ring -->
                    <div class="orbiting-cards-ring" id="orbitingCardsRing">
                        <!-- Card 1: 0 deg (Top) -->
                        <div class="orbit-slot slot-1" onclick="showDisciplineInfo('tech')">
                            <div class="orbit-counter-rotate">
                                <div class="orbit-mini-card card-tech">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="card-icon bg-info bg-opacity-25 text-info">
                                            <i class="fa-solid fa-code"></i>
                                        </div>
                                        <div class="text-start">
                                            <div class="card-title text-white">Technology</div>
                                            <div class="card-desc text-info">Coding &amp; AI</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: 60 deg (Top-Right) -->
                        <div class="orbit-slot slot-2" onclick="showDisciplineInfo('science')">
                            <div class="orbit-counter-rotate">
                                <div class="orbit-mini-card card-science">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="card-icon bg-success bg-opacity-25 text-success">
                                            <i class="fa-solid fa-atom"></i>
                                        </div>
                                        <div class="text-start">
                                            <div class="card-title text-white">Science</div>
                                            <div class="card-desc text-success">Physics &amp; Bio</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: 120 deg (Bottom-Right) -->
                        <div class="orbit-slot slot-3" onclick="showDisciplineInfo('math')">
                            <div class="orbit-counter-rotate">
                                <div class="orbit-mini-card card-math">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="card-icon bg-warning bg-opacity-25 text-warning">
                                            <i class="fa-solid fa-calculator"></i>
                                        </div>
                                        <div class="text-start">
                                            <div class="card-title text-white">Mathematics</div>
                                            <div class="card-desc text-warning">Calculus &amp; Logic</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: 180 deg (Bottom) -->
                        <div class="orbit-slot slot-4" onclick="showDisciplineInfo('arts')">
                            <div class="orbit-counter-rotate">
                                <div class="orbit-mini-card card-arts">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="card-icon bg-danger bg-opacity-25 text-danger">
                                            <i class="fa-solid fa-palette"></i>
                                        </div>
                                        <div class="text-start">
                                            <div class="card-title text-white">Arts &amp; Design</div>
                                            <div class="card-desc text-danger">Creative &amp; Media</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 5: 240 deg (Bottom-Left) -->
                        <div class="orbit-slot slot-5" onclick="showDisciplineInfo('history')">
                            <div class="orbit-counter-rotate">
                                <div class="orbit-mini-card card-history">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="card-icon bg-secondary bg-opacity-25 text-light">
                                            <i class="fa-solid fa-book-bookmark"></i>
                                        </div>
                                        <div class="text-start">
                                            <div class="card-title text-white">Humanities</div>
                                            <div class="card-desc text-light">History &amp; Lit</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 6: 300 deg (Top-Left) -->
                        <div class="orbit-slot slot-6" onclick="showDisciplineInfo('multi')">
                            <div class="orbit-counter-rotate">
                                <div class="orbit-mini-card card-multi">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="card-icon bg-primary bg-opacity-25 text-primary">
                                            <i class="fa-solid fa-graduation-cap"></i>
                                        </div>
                                        <div class="text-start">
                                            <div class="card-title text-white">Multi-Domain</div>
                                            <div class="card-desc text-primary">General IQ Arena</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Live HUD Banner -->
                    <div class="cards-hud-badge p-1 px-3 rounded-pill bg-dark bg-opacity-75 border border-secondary shadow small" id="cardsHudMsg">
                        <i class="fa-solid fa-hand-pointer text-warning me-1"></i> Hover to pause • Click card to view details
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

<!-- Interactive Feature Showcase Slider -->
<section id="arena-slider" class="py-5 bg-dark bg-opacity-25 border-bottom border-secondary">
    <div class="container py-2">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill mb-2">
                    <i class="fa-solid fa-sliders me-1"></i> Interactive Feature Carousel
                </span>
                <h2 class="fw-bold text-white mb-1">Explore Platform Capabilities</h2>
                <p class="text-secondary small mb-0">High-performance game mechanics built for real-time online quizzes</p>
            </div>
            
            <!-- Quick Jump Smooth-Scroll Navigation Bar -->
            <div class="arena-jump-bar" id="sliderJumpBar">
                <a href="#hero" class="arena-jump-link"><i class="fa-solid fa-arrow-up me-1"></i> Top</a>
                <a href="#arena-slider" class="arena-jump-link active"><i class="fa-solid fa-sliders me-1"></i> Showcase</a>
                <a href="#how-it-works" class="arena-jump-link"><i class="fa-solid fa-diagram-project me-1"></i> Workflow</a>
                <a href="#cta" class="arena-jump-link"><i class="fa-solid fa-gamepad me-1"></i> Join</a>
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

<!-- How It Works Section (Role-Based: Student & Teacher Tracks) -->
<section id="how-it-works" class="py-5">
    <div class="container py-4">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill mb-2">Role-Based Workflows</span>
            <h2 class="fw-bold text-white">How QuizArena Works</h2>
            <p class="text-secondary" style="max-width: 620px; margin: 0 auto;">
                Tailored, intuitive step-by-step journeys for both students challenging their knowledge and educators managing quizzes.
            </p>
        </div>

        <!-- Role Selector Tabs -->
        <div class="d-flex justify-content-center mb-5">
            <ul class="nav nav-pills bg-dark p-1 rounded-pill border border-secondary" id="roleWorkflowTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 py-2 fw-semibold" id="student-tab" data-bs-toggle="pill" data-bs-target="#student-workflow" type="button" role="tab" aria-controls="student-workflow" aria-selected="true">
                        <i class="fa-solid fa-graduation-cap me-2 text-info"></i>For Students
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2 fw-semibold" id="teacher-tab" data-bs-toggle="pill" data-bs-target="#teacher-workflow" type="button" role="tab" aria-controls="teacher-workflow" aria-selected="false">
                        <i class="fa-solid fa-chalkboard-user me-2 text-warning"></i>For Teachers
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="roleWorkflowContent">
            <!-- STUDENT WORKFLOW TRACK -->
            <div class="tab-pane fade show active" id="student-workflow" role="tabpanel" aria-labelledby="student-tab">
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="arena-card p-4 h-100 text-center position-relative">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1 mb-3 small fw-bold">Step 1</span>
                            <div class="d-inline-flex p-3 rounded-circle bg-info bg-opacity-10 text-info mb-3">
                                <i class="fa-solid fa-key fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">Enter Quiz ID</h5>
                            <p class="text-secondary small mb-0">
                                Grab the unique Quiz ID provided by your teacher (e.g. <code>QUIZ-7F3A21</code>) and instantly launch the arena lobby.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="arena-card p-4 h-100 text-center position-relative">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 mb-3 small fw-bold">Step 2</span>
                            <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                                <i class="fa-solid fa-stopwatch fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">Dual Live Timers</h5>
                            <p class="text-secondary small mb-0">
                                Answer before the individual question timer expires while tracking total remaining time on synchronized countdowns.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="arena-card p-4 h-100 text-center position-relative">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 mb-3 small fw-bold">Step 3</span>
                            <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-3">
                                <i class="fa-solid fa-bolt fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">Instant Feedback</h5>
                            <p class="text-secondary small mb-0">
                                Select your MCQ option and experience immediate Green/Red response animations, audio signals, and auto-progression.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="arena-card p-4 h-100 text-center position-relative">
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1 mb-3 small fw-bold">Step 4</span>
                            <div class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-3">
                                <i class="fa-solid fa-trophy fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">Leaderboard &amp; Ranks</h5>
                            <p class="text-secondary small mb-0">
                                Receive automated grading, review detailed question-by-question analytics, and view your position on the class leaderboard.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-arena-primary px-4 py-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#quickJoinModal">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Join a Quiz as Student
                    </button>
                </div>
            </div>

            <!-- TEACHER WORKFLOW TRACK -->
            <div class="tab-pane fade" id="teacher-workflow" role="tabpanel" aria-labelledby="teacher-tab">
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="arena-card p-4 h-100 text-center position-relative">
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1 mb-3 small fw-bold">Step 1</span>
                            <div class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-3">
                                <i class="fa-solid fa-heading fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">Title &amp; Concept</h5>
                            <p class="text-secondary small mb-0">
                                Enter quiz title and description. The system instantly reserves an auto-generated unique Quiz ID for your session.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="arena-card p-4 h-100 text-center position-relative">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 mb-3 small fw-bold">Step 2</span>
                            <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                                <i class="fa-solid fa-list-check fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">Set MCQs &amp; Timers</h5>
                            <p class="text-secondary small mb-0">
                                Input questions, 4 options, marks, and specific question timers. Total quiz duration is automatically calculated!
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="arena-card p-4 h-100 text-center position-relative">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1 mb-3 small fw-bold">Step 3</span>
                            <div class="d-inline-flex p-3 rounded-circle bg-info bg-opacity-10 text-info mb-3">
                                <i class="fa-solid fa-calendar-check fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">Schedule &amp; Done</h5>
                            <p class="text-secondary small mb-0">
                                Define start date/time, deadline, and allowed attempts, then hit <strong>Done</strong> to publish live for your students.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="arena-card p-4 h-100 text-center position-relative">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 mb-3 small fw-bold">Step 4</span>
                            <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-3">
                                <i class="fa-solid fa-chart-pie fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">Live Insights</h5>
                            <p class="text-secondary small mb-0">
                                Monitor student submissions, question-by-question pass rates, score distributions, and export results directly.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="<?= BASE_URL ?>/teacher/create_quiz.php" class="btn btn-arena-primary px-4 py-2 rounded-pill">
                        <i class="fa-solid fa-plus-circle me-2"></i>Create a Quiz as Teacher
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>



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

<!-- Interactive Discipline Orbit & Smooth-Scroll Active Navigation State Observer -->
<script>
const disciplineDict = {
    'core': '<i class="fa-solid fa-trophy text-warning me-1"></i> <strong>QuizArena</strong>: Real-time competitive assessment arena across all academic domains!',
    'tech': '<i class="fa-solid fa-code text-info me-1"></i> <strong>Technology &amp; Computing</strong>: Python, AI, Web Development, Databases &amp; Cybersecurity.',
    'science': '<i class="fa-solid fa-atom text-success me-1"></i> <strong>Natural Sciences</strong>: Physics, Organic Chemistry, Genetics &amp; Biology.',
    'math': '<i class="fa-solid fa-calculator text-warning me-1"></i> <strong>Mathematics &amp; Statistics</strong>: Algebra, Calculus, Discrete Math &amp; Probability.',
    'arts': '<i class="fa-solid fa-palette text-danger me-1"></i> <strong>Arts &amp; Creative Design</strong>: Visual Arts, UI/UX, Architecture &amp; Media Design.',
    'history': '<i class="fa-solid fa-book-bookmark text-light me-1"></i> <strong>Humanities &amp; Literature</strong>: World History, Philosophy, Linguistics &amp; Civilization.',
    'multi': '<i class="fa-solid fa-graduation-cap text-primary me-1"></i> <strong>Multi-Domain General IQ</strong>: Logic puzzles, current affairs &amp; general knowledge.'
};

function showDisciplineInfo(key) {
    const hud = document.getElementById('cardsHudMsg');
    if (!hud) return;
    const msg = disciplineDict[key] || disciplineDict['core'];
    hud.innerHTML = msg;
    hud.classList.remove('pulse-hud');
    void hud.offsetWidth; // trigger reflow
    hud.classList.add('pulse-hud');
}

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
