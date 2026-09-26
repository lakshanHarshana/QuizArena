<?php
/**
 * QUIZARENA — Core Application Functions & Business Logic
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize strings for safe HTML output
 */
function sanitize($data): string {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a unique Quiz Code in the specified format: QUIZ-XXXXXX
 * (e.g., QUIZ-7F3A21, QUIZ-A82K91)
 */
function generateUniqueQuizCode(): string {
    $db = getDB();
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $maxTries = 20;

    for ($i = 0; $i < $maxTries; $i++) {
        $randomPart = '';
        for ($j = 0; $j < 6; $j++) {
            $randomPart .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $code = 'QUIZ-' . $randomPart;

        $stmt = $db->prepare("SELECT id FROM quizzes WHERE quiz_code = ? LIMIT 1");
        $stmt->execute([$code]);
        if ($stmt->rowCount() === 0) {
            return $code;
        }
    }
    // Fallback if max tries reached
    return 'QUIZ-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Calculate the overall quiz time (in seconds) dynamically from question time limits.
 * Formula: SUM(time_limit) for all questions in the quiz.
 */
function getQuizTotalTime(int $quizId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COALESCE(SUM(time_limit), 0) AS total_seconds FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quizId]);
    $row = $stmt->fetch();
    return (int)($row['total_seconds'] ?? 0);
}

/**
 * Calculate the total potential marks for a quiz
 */
function getQuizTotalMarks(int $quizId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COALESCE(SUM(marks), 0) AS total_marks FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quizId]);
    $row = $stmt->fetch();
    return (int)($row['total_marks'] ?? 0);
}

/**
 * Format seconds into mm:ss (or Xh Ym Zs for longer)
 */
function formatDuration(int $seconds): string {
    if ($seconds < 0) $seconds = 0;
    $minutes = floor($seconds / 60);
    $remSeconds = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $remSeconds);
}

/**
 * Format duration into human-readable text (e.g. 5 mins 30 secs)
 */
function formatDurationHuman(int $seconds): string {
    if ($seconds <= 0) return '0 secs';
    $m = floor($seconds / 60);
    $s = $seconds % 60;
    $parts = [];
    if ($m > 0) $parts[] = $m . ' min' . ($m > 1 ? 's' : '');
    if ($s > 0 || empty($parts)) $parts[] = $s . ' sec' . ($s > 1 ? 's' : '');
    return implode(' ', $parts);
}

/**
 * Determine dynamic quiz status: DRAFT, UPCOMING, LIVE, COMPLETED, EXPIRED
 */
function getQuizStatus(array $quiz): array {
    if ($quiz['status'] === 'draft') {
        return [
            'code' => 'DRAFT',
            'label' => 'Draft',
            'badge' => '<span class="badge bg-secondary"><i class="fa-solid fa-pen-ruler me-1"></i>Draft</span>',
            'can_join' => false
        ];
    }
    if ($quiz['status'] === 'closed') {
        return [
            'code' => 'CLOSED',
            'label' => 'Closed',
            'badge' => '<span class="badge bg-dark"><i class="fa-solid fa-lock me-1"></i>Closed</span>',
            'can_join' => false
        ];
    }

    $now = new DateTime('now');
    $start = new DateTime($quiz['start_datetime']);
    $end = new DateTime($quiz['end_datetime']);

    if ($now < $start) {
        return [
            'code' => 'UPCOMING',
            'label' => 'Upcoming',
            'badge' => '<span class="badge bg-warning text-dark"><i class="fa-regular fa-clock me-1"></i>Upcoming</span>',
            'can_join' => false
        ];
    } elseif ($now > $end) {
        return [
            'code' => 'EXPIRED',
            'label' => 'Expired',
            'badge' => '<span class="badge bg-danger"><i class="fa-solid fa-hourglass-end me-1"></i>Expired</span>',
            'can_join' => false
        ];
    } else {
        return [
            'code' => 'LIVE',
            'label' => 'Live Now',
            'badge' => '<span class="badge bg-success pulse-badge"><i class="fa-solid fa-bolt me-1"></i>LIVE</span>',
            'can_join' => true
        ];
    }
}

/**
 * Check count of attempts already made by a student for a quiz
 */
function getStudentAttemptCount(int $quizId, int $studentId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM attempts WHERE quiz_id = ? AND student_id = ? AND status IN ('SUBMITTED', 'AUTO_SUBMITTED')");
    $stmt->execute([$quizId, $studentId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Check if an active IN_PROGRESS attempt already exists for a student
 */
function getActiveAttempt(int $quizId, int $studentId): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM attempts WHERE quiz_id = ? AND student_id = ? AND status = 'IN_PROGRESS' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$quizId, $studentId]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Flash message helpers
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): ?string {
    if (isset($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return null;
}
