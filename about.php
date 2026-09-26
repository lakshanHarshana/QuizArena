<?php
$pageTitle = "About";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header Banner -->
            <div class="text-center mb-5">
                <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill mb-2">Project Overview</span>
                <h1 class="fw-extrabold text-white mb-3">About QuizArena</h1>
                <p class="lead text-secondary" style="max-width: 720px; margin: 0 auto;">
                    An advanced, game-inspired online MCQ quiz platform combining real-time responsiveness, strict server-side security, and intuitive academic assessment workflows.
                </p>
            </div>

            <!-- Academic Assignment Card -->
            <div class="arena-card p-4 p-md-5 mb-5 border-primary border-opacity-50">
                <div class="row align-items-center g-4">
                    <div class="col-md-3 text-center">
                        <div class="d-inline-flex p-4 rounded-circle bg-primary bg-opacity-15 text-primary">
                            <i class="fa-solid fa-graduation-cap fa-3x"></i>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h4 class="fw-bold text-white mb-2">ICT 2209 — Web Technologies Mini Project</h4>
                        <div class="text-info fw-semibold mb-2">Department of Information & Communication Technology</div>
                        <div class="text-light mb-3">Faculty of Technology, Rajarata University of Sri Lanka</div>
                        <p class="text-secondary small mb-0">
                            Developed to demonstrate mastery over full-stack web technologies: semantic HTML5, modern CSS3 layout and animations, responsive Bootstrap design, interactive client-side JavaScript timers and audio synthesis, secure PHP 8.2 backend sessions and prepared statements, and normalized MySQL relational database architectures.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Core Architectural Highlights -->
            <h3 class="fw-bold text-white mb-4"><i class="fa-solid fa-microchip text-primary me-2"></i>Core Architecture & Innovation</h3>
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="arena-card p-4 h-100">
                        <div class="text-primary mb-3"><i class="fa-solid fa-bolt fa-2x"></i></div>
                        <h5 class="fw-bold text-white mb-2">1. Game-Style MCQ Feedback</h5>
                        <p class="text-secondary small mb-0">
                            Unlike traditional static web forms, selecting an answer delivers instantaneous visual confirmation (GREEN for correct, RED for wrong) along with sound feedback. Answers are immediately locked to prevent tampering, and smoothly transition to the next question automatically.
                        </p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="arena-card p-4 h-100">
                        <div class="text-info mb-3"><i class="fa-solid fa-stopwatch fa-2x"></i></div>
                        <h5 class="fw-bold text-white mb-2">2. Dual Synchronized Timers</h5>
                        <p class="text-secondary small mb-0">
                            QuizArena features both an individual question countdown timer and an overall quiz countdown timer. The overall duration is automatically calculated: <code>SUM(time_limit)</code> across all questions. The overall timer maintains top priority: when it expires, the quiz auto-submits instantly.
                        </p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="arena-card p-4 h-100">
                        <div class="text-warning mb-3"><i class="fa-solid fa-fingerprint fa-2x"></i></div>
                        <h5 class="fw-bold text-white mb-2">3. Unique Quiz Access Codes</h5>
                        <p class="text-secondary small mb-0">
                            Every quiz receives an auto-generated unique ID (e.g. <code>QUIZ-7F3A21</code>). Students use this code to join live and scheduled quizzes directly, eliminating confusion in multi-quiz academic environments.
                        </p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="arena-card p-4 h-100">
                        <div class="text-success mb-3"><i class="fa-solid fa-shield-halved fa-2x"></i></div>
                        <h5 class="fw-bold text-white mb-2">4. Server-Side Security & Anti-Cheat</h5>
                        <p class="text-secondary small mb-0">
                            Correct answers are never sent to the client browser prior to answer commitment. Questions, timing, score calculation, and max attempt limits are strictly enforced on the server using PDO prepared statements and bcrypt password hashing.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Technology Stack Table -->
            <div class="arena-card p-4">
                <h4 class="fw-bold text-white mb-3"><i class="fa-solid fa-layer-group text-primary me-2"></i>Technology Stack</h4>
                <div class="table-responsive">
                    <table class="table table-arena mb-0">
                        <thead>
                            <tr>
                                <th>Layer</th>
                                <th>Technologies Used</th>
                                <th>Role in Platform</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-white">Frontend</td>
                                <td>HTML5, CSS3, Bootstrap 5.3, Font Awesome 6.5</td>
                                <td>Responsive UI, custom game theme, accessibility, and modals</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-white">Interactivity</td>
                                <td>JavaScript (ES6+), Web Audio API, Canvas Confetti</td>
                                <td>Dual timers, instant green/red feedback, dynamic filters, sound effects</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-white">Backend</td>
                                <td>PHP 8.2 (Modular Architecture)</td>
                                <td>Session auth, role authorization, RESTful JSON APIs, validation</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-white">Database</td>
                                <td>MySQL / MariaDB (InnoDB, Foreign Keys, Indexes)</td>
                                <td>Normalized relational tables for users, quizzes, attempts, and answers</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
