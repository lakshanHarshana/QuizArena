<?php
/**
 * QUIZARENA — Global Header Template
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= APP_NAME ?> — Real-Time Online MCQ Quiz Platform</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom QuizArena Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-quizarena sticky-top">
    <div class="container">
        <a class="navbar-brand navbar-brand-arena" href="<?= BASE_URL ?>/index.php">
            <i class="fa-solid fa-gamepad text-primary"></i>
            <span>Quiz<span style="color: #a855f7;">Arena</span></span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#arenaNavbar" aria-controls="arenaNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="arenaNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">
                        <i class="fa-solid fa-house me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/about.php">
                        <i class="fa-solid fa-circle-info me-1"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/contact.php">
                        <i class="fa-solid fa-envelope me-1"></i> Contact
                    </a>
                </li>

                <?php if (isStudent()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/student/') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>/student/dashboard.php">
                            <i class="fa-solid fa-gauge me-1"></i> Student Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'history.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/student/history.php">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> My History
                        </a>
                    </li>
                <?php elseif (isTeacher()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/teacher/') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>/teacher/dashboard.php">
                            <i class="fa-solid fa-chalkboard-user me-1"></i> Teacher Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'create_quiz.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/teacher/create_quiz.php">
                            <i class="fa-solid fa-plus-circle me-1"></i> Create Quiz
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'results.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/teacher/results.php">
                            <i class="fa-solid fa-trophy me-1"></i> Results & Rankings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'analytics.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/teacher/analytics.php">
                            <i class="fa-solid fa-chart-pie me-1"></i> Analytics
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 py-1 px-3 rounded-pill text-light border-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.8rem; font-weight: bold;">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                            <span class="small fw-semibold"><?= sanitize($user['name']) ?></span>
                            <span class="badge <?= $user['role'] === 'teacher' ? 'bg-info' : 'bg-primary' ?> text-capitalize small" style="font-size: 0.7rem;"><?= $user['role'] ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                            <li class="px-3 py-2 border-bottom border-secondary mb-1">
                                <div class="small text-muted">Signed in as</div>
                                <div class="fw-bold text-truncate" style="max-width: 200px;"><?= sanitize($user['email']) ?></div>
                            </li>
                            <?php if (isStudent()): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/dashboard.php"><i class="fa-solid fa-gauge me-2"></i>My Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/history.php"><i class="fa-solid fa-list-check me-2"></i>Attempt History</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/teacher/dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Teacher Console</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/teacher/create_quiz.php"><i class="fa-solid fa-plus me-2"></i>New Quiz</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-arena-outline btn-sm">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                    </a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-arena-primary btn-sm">
                        <i class="fa-solid fa-user-plus me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Flash Notifications -->
<div class="container mt-3">
    <?php if ($flashSuccess = getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center bg-success bg-opacity-25 border-success text-success-emphasis rounded-3" role="alert">
            <i class="fa-solid fa-circle-check fa-lg me-2 text-success"></i>
            <div><?= sanitize($flashSuccess) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashError = getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center bg-danger bg-opacity-25 border-danger text-danger-emphasis rounded-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation fa-lg me-2 text-danger"></i>
            <div><?= sanitize($flashError) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>

<main>
