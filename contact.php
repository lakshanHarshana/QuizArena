<?php
$pageTitle = "Contact";
require_once __DIR__ . '/includes/header.php';

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || strlen($name) < 2) {
        $errorMsg = "Please enter your name.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } elseif (empty($message) || strlen($message) < 5) {
        $errorMsg = "Please enter a message of at least 5 characters.";
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO messages (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $message]);
            $successMsg = "Thank you, {$name}! Your message has been sent successfully. We will get back to you shortly.";
        } catch (PDOException $e) {
            error_log("Contact form error: " . $e->getMessage());
            $errorMsg = "A database error occurred while sending your message. Please try again later.";
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="arena-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="fa-solid fa-envelope-open-text fa-2x"></i>
                    </div>
                    <h2 class="fw-bold text-white mb-2">Get in Touch</h2>
                    <p class="text-secondary small">Have a question or feedback regarding the QuizArena platform? Send us a message.</p>
                </div>

                <?php if (!empty($successMsg)): ?>
                    <div class="alert alert-success d-flex align-items-center bg-success bg-opacity-25 border-success text-success-emphasis rounded-3 mb-4">
                        <i class="fa-solid fa-circle-check fa-lg me-2 text-success"></i>
                        <div><?= sanitize($successMsg) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger d-flex align-items-center bg-danger bg-opacity-25 border-danger text-danger-emphasis rounded-3 mb-4">
                        <i class="fa-solid fa-triangle-exclamation fa-lg me-2 text-danger"></i>
                        <div><?= sanitize($errorMsg) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/contact.php" method="POST" id="contactForm" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Your Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input type="text" class="form-control form-arena" id="name" name="name" required placeholder="Kasun Perera" value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control form-arena" id="email" name="email" required placeholder="kasun@university.ac.lk" value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control form-arena" id="message" name="message" rows="5" required placeholder="Write your message here..."><?= isset($_POST['message']) ? sanitize($_POST['message']) : '' ?></textarea>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-arena-primary w-100 py-2">
                                <i class="fa-solid fa-paper-plane me-2"></i>Send Message
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-5 pt-4 border-top border-secondary text-center text-secondary small">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <i class="fa-solid fa-building-columns text-primary me-1"></i>
                            <div>Rajarata University of Sri Lanka</div>
                        </div>
                        <div class="col-md-4">
                            <i class="fa-solid fa-envelope text-primary me-1"></i>
                            <div>support@quizarena.com</div>
                        </div>
                        <div class="col-md-4">
                            <i class="fa-solid fa-code text-primary me-1"></i>
                            <div>ICT 2209 Web Technologies</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
