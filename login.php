<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    if (isStudent()) {
        header("Location: " . BASE_URL . "/student/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/teacher/dashboard.php");
    }
    exit();
}

$pageTitle = "Login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="arena-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="fa-solid fa-right-to-bracket fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Welcome Back</h3>
                    <p class="text-secondary small">Sign in to your QuizArena account</p>
                </div>

                <form action="<?= BASE_URL ?>/auth/login_process.php" method="POST" id="loginForm" novalidate>
                    <div class="mb-3">
                        <label for="login_id" class="form-label">Username or Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input type="text" class="form-control form-arena" id="login_id" name="login_id" required placeholder="e.g. kasun_p or you@example.com">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-arena" id="password" name="password" required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-arena-primary w-100 py-2 mb-3">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Sign In
                    </button>
                </form>

                <!-- Quick Test Credentials Box -->
                <div class="p-3 rounded-3 bg-dark border border-secondary mt-3 mb-4">
                    <div class="small fw-bold text-light mb-2"><i class="fa-solid fa-bolt text-warning me-1"></i> Quick Test Credentials:</div>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-info flex-fill" onclick="fillCreds('silva_teacher', 'Teacher@123')">
                                <i class="fa-solid fa-chalkboard-user me-1"></i> Teacher (Username)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="fillCreds('kasun_p', 'Student@123')">
                                <i class="fa-solid fa-user-graduate me-1"></i> Student (Username)
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="fillCreds('teacher@quizarena.com', 'Teacher@123')">
                                <i class="fa-solid fa-envelope me-1"></i> Teacher (Email)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" onclick="fillCreds('student@quizarena.com', 'Student@123')">
                                <i class="fa-solid fa-envelope me-1"></i> Student (Email)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="text-center text-secondary small">
                    Don't have an account? <a href="<?= BASE_URL ?>/register.php" class="text-primary text-decoration-none fw-semibold">Register here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillCreds(loginId, password) {
    document.getElementById('login_id').value = loginId;
    document.getElementById('password').value = password;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
