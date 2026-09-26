<?php
/**
 * QUIZARENA — Global Footer Template
 */
?>
</main>

<!-- Quick Join Quiz Modal (Accessible from anywhere) -->
<div class="modal fade" id="quickJoinModal" tabindex="-1" aria-labelledby="quickJoinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-arena">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title fw-bold" id="quickJoinModalLabel">
                    <i class="fa-solid fa-gamepad text-primary me-2"></i>Join Live Arena Quiz
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/student/join_quiz.php" method="GET">
                <div class="modal-body py-4">
                    <p class="text-secondary small mb-3">
                        Enter the unique Quiz ID provided by your teacher (e.g. <code>QUIZ-7F3A21</code>).
                    </p>
                    <div class="mb-3">
                        <label for="modalQuizCode" class="form-label">Quiz ID / Access Code</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-secondary">
                                <i class="fa-solid fa-hashtag"></i>
                            </span>
                            <input type="text" class="form-control form-arena text-uppercase fw-bold font-monospace" 
                                   id="modalQuizCode" name="quiz_code" placeholder="QUIZ-XXXXXX" required
                                   pattern="QUIZ-[A-Za-z0-9]{4,10}" title="Format: QUIZ-XXXXXX">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-arena-primary rounded-pill px-4">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Verify & Join
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer-arena">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-5 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-gamepad text-primary fa-lg"></i>
                    <h5 class="text-white fw-bold mb-0">Quiz<span style="color: #a855f7;">Arena</span></h5>
                </div>
                <p class="text-secondary small mb-3">
                    A competitive, real-time game-inspired online MCQ quiz platform designed for academic assessments with dynamic dual-timer synchronization, instant interactive feedback, and multi-quiz management.
                </p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#quickJoinModal">
                        <i class="fa-solid fa-key me-1"></i> Quick Join Quiz
                    </button>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3 text-uppercase small" style="letter-spacing: 1px;">Quick Links</h6>
                <ul class="list-unstyled text-secondary small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-secondary hover-white"><i class="fa-solid fa-chevron-right me-1 fa-xs"></i> Home</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php" class="text-decoration-none text-secondary hover-white"><i class="fa-solid fa-chevron-right me-1 fa-xs"></i> About Platform</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php" class="text-decoration-none text-secondary hover-white"><i class="fa-solid fa-chevron-right me-1 fa-xs"></i> Support & Contact</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= BASE_URL ?>/logout.php" class="text-decoration-none text-secondary hover-white"><i class="fa-solid fa-chevron-right me-1 fa-xs"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>/login.php" class="text-decoration-none text-secondary hover-white"><i class="fa-solid fa-chevron-right me-1 fa-xs"></i> Login</a></li>
                        <li><a href="<?= BASE_URL ?>/register.php" class="text-decoration-none text-secondary hover-white"><i class="fa-solid fa-chevron-right me-1 fa-xs"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-lg-4 col-md-12">
                <h6 class="text-white fw-bold mb-3 text-uppercase small" style="letter-spacing: 1px;">Academic Assignment</h6>
                <div class="p-3 rounded-3 bg-dark border border-secondary text-secondary small">
                    <div class="fw-semibold text-light mb-1">ICT 2209 — Web Technologies Mini Project</div>
                    <div class="mb-2">Faculty of Technology, Rajarata University of Sri Lanka</div>
                    <div class="badge bg-secondary">HTML5 • CSS3 • JS • PHP • MySQL</div>
                </div>
            </div>
        </div>

        <div class="pt-3 border-top border-secondary text-center text-secondary small">
            &copy; <?= date('Y') ?> QuizArena. All rights reserved. Developed with clean modular PHP and modern interactive JavaScript.
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Canvas Confetti CDN (For high performance celebration animations) -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>

<!-- Chart.js CDN (For Teacher Analytics) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Core Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
</body>
</html>
