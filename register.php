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

$pageTitle = "Register";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-7">
            <div class="arena-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="fa-solid fa-user-plus fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Create an Account</h3>
                    <p class="text-secondary small">Join QuizArena to host or challenge competitive quizzes</p>
                </div>

                <form action="<?= BASE_URL ?>/auth/register_process.php" method="POST" id="registerForm" novalidate>
                    <!-- Role Selection -->
                    <div class="mb-4">
                        <label class="form-label d-block text-center fw-semibold text-light mb-2">Select Your Role</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_student" value="student" checked>
                                <label class="btn btn-outline-primary w-100 py-3 rounded-3 d-flex flex-column align-items-center gap-1" for="role_student">
                                    <i class="fa-solid fa-user-graduate fa-lg"></i>
                                    <span class="fw-bold">Student</span>
                                    <span class="small text-muted" style="font-size: 0.75rem;">Join and answer quizzes</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_teacher" value="teacher">
                                <label class="btn btn-outline-info w-100 py-3 rounded-3 d-flex flex-column align-items-center gap-1" for="role_teacher">
                                    <i class="fa-solid fa-chalkboard-user fa-lg"></i>
                                    <span class="fw-bold">Teacher</span>
                                    <span class="small text-muted" style="font-size: 0.75rem;">Create & manage quizzes</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-at"></i></span>
                                <input type="text" class="form-control form-arena" id="username" name="username" required pattern="^[a-zA-Z0-9_]{3,30}$" placeholder="e.g. kasun_p">
                            </div>
                            <div class="invalid-feedback">Username must be 3-30 letters, numbers, or underscores.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control form-arena" id="name" name="name" required placeholder="e.g. Kasun Perera">
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control form-arena" id="email" name="email" required placeholder="you@example.com">
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>

                        <!-- Student specific fields -->
                        <div id="studentFields" class="col-12">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="student_id" class="form-label">Student ID / Registration No.</label>
                                    <input type="text" class="form-control form-arena" id="student_id" name="student_id" placeholder="e.g. STU1001">
                                    <div class="invalid-feedback">Student ID is required.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="course" class="form-label">Course / Degree Program</label>
                                    <input type="text" class="form-control form-arena" id="course" name="course" placeholder="e.g. Computer Science">
                                    <div class="invalid-feedback">Course/Program is required.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Teacher specific fields -->
                        <div id="teacherFields" class="col-12 d-none">
                            <label for="department" class="form-label">Department / Faculty</label>
                            <input type="text" class="form-control form-arena" id="department" name="department" placeholder="e.g. Department of Computing">
                            <div class="invalid-feedback">Department is required.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control form-arena" id="password" name="password" minlength="6" required placeholder="Min 6 characters">
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control form-arena" id="confirm_password" name="confirm_password" required placeholder="Re-type password">
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-arena-primary w-100 py-2">
                            <i class="fa-solid fa-user-check me-2"></i>Create Account
                        </button>
                    </div>
                </form>

                <div class="text-center text-secondary small mt-4">
                    Already registered? <a href="<?= BASE_URL ?>/login.php" class="text-primary text-decoration-none fw-semibold">Sign in here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
