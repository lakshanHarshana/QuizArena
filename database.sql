-- =====================================================================
-- QUIZARENA: Real-Time Online MCQ Quiz Platform
-- Database Schema & Comprehensive Seed Data
-- Designed for ICT 2209: Web Technologies Mini Project
-- Compatible with MySQL 8.0+ and MariaDB 10.4+ (XAMPP / WAMP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `quizarena` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `quizarena`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. Table: users
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'teacher') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_username` (`username`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Table: student_profiles
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `student_profiles`;
CREATE TABLE `student_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `student_id` VARCHAR(50) NOT NULL UNIQUE,
  `course` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Table: teacher_profiles
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `teacher_profiles`;
CREATE TABLE `teacher_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `department` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_teacher_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Table: categories
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Table: quizzes
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE `quizzes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_code` VARCHAR(20) NOT NULL UNIQUE,
  `teacher_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `difficulty` ENUM('Easy', 'Medium', 'Hard') NOT NULL DEFAULT 'Medium',
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NOT NULL,
  `max_attempts` INT NOT NULL DEFAULT 1,
  `status` ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_quiz_code` (`quiz_code`),
  INDEX `idx_quiz_status` (`status`),
  CONSTRAINT `fk_quiz_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quiz_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Table: questions
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `question_text` TEXT NOT NULL,
  `option_a` VARCHAR(255) NOT NULL,
  `option_b` VARCHAR(255) NOT NULL,
  `option_c` VARCHAR(255) NOT NULL,
  `option_d` VARCHAR(255) NOT NULL,
  `correct_answer` ENUM('A', 'B', 'C', 'D') NOT NULL,
  `time_limit` INT NOT NULL DEFAULT 60 COMMENT 'Time limit in seconds',
  `marks` INT NOT NULL DEFAULT 1,
  `question_order` INT NOT NULL DEFAULT 1,
  INDEX `idx_question_quiz` (`quiz_id`),
  CONSTRAINT `fk_question_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Table: attempts
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `attempts`;
CREATE TABLE `attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `started_at` DATETIME NOT NULL,
  `submitted_at` DATETIME NULL,
  `completion_time` INT DEFAULT 0 COMMENT 'Total time taken in seconds',
  `score` INT DEFAULT 0,
  `total_marks` INT DEFAULT 0,
  `percentage` DECIMAL(5,2) DEFAULT 0.00,
  `submission_type` ENUM('MANUAL', 'AUTO') DEFAULT 'MANUAL',
  `status` ENUM('IN_PROGRESS', 'SUBMITTED', 'AUTO_SUBMITTED') NOT NULL DEFAULT 'IN_PROGRESS',
  INDEX `idx_attempt_quiz` (`quiz_id`),
  INDEX `idx_attempt_student` (`student_id`),
  CONSTRAINT `fk_attempt_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Table: answers
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `answers`;
CREATE TABLE `answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `attempt_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `selected_answer` VARCHAR(5) NULL COMMENT 'A, B, C, D, or NULL if unanswered',
  `is_correct` TINYINT(1) DEFAULT 0,
  `marks_obtained` INT DEFAULT 0,
  `answered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_answer_attempt` (`attempt_id`),
  INDEX `idx_answer_question` (`question_id`),
  CONSTRAINT `fk_answer_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. Table: messages (Contact Form)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- Default Passwords:
-- Teacher: Teacher@123
-- Students: Student@123
-- =====================================================================

-- Users
INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `role`) VALUES
(1, 'silva_teacher', 'Mr. Silva', 'teacher@quizarena.com', '$2y$10$Hb5Ur8qivYKYk2oKh/IeZu5wlMS.Wp0LNXwanAOJdyhpeJG2GY6d6', 'teacher'),
(2, 'kasun_p', 'Kasun Perera', 'student@quizarena.com', '$2y$10$Fn.ofAvAGB7iEWzH64KPOOe97ryGWjuPCxMNbsVE0FCnyMYeRMZ7S', 'student'),
(3, 'nimal_s', 'Nimal Silva', 'nimal@quizarena.com', '$2y$10$Fn.ofAvAGB7iEWzH64KPOOe97ryGWjuPCxMNbsVE0FCnyMYeRMZ7S', 'student'),
(4, 'amali_w', 'Amali Wickramasinghe', 'amali@quizarena.com', '$2y$10$Fn.ofAvAGB7iEWzH64KPOOe97ryGWjuPCxMNbsVE0FCnyMYeRMZ7S', 'student');

-- Profiles
INSERT INTO `teacher_profiles` (`user_id`, `department`) VALUES
(1, 'Information & Communication Technology');

INSERT INTO `student_profiles` (`user_id`, `student_id`, `course`) VALUES
(2, 'IT220901', 'BSc in Information & Communication Technology'),
(3, 'IT220902', 'BSc in Information & Communication Technology'),
(4, 'IT220903', 'BSc in Software Engineering');

-- Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Database Systems', 'Relational database concepts, SQL queries, normalization, and ACID properties.'),
(2, 'Web Technologies', 'HTML5, CSS3, JavaScript, PHP backend, DOM manipulation, and responsive web design.'),
(3, 'Java Programming', 'Object-Oriented Programming, polymorphism, inheritance, and exception handling in Java.'),
(4, 'Computer Networks', 'OSI reference model, TCP/IP, IP addressing, routing protocols, and subnetting.'),
(5, 'Cybersecurity', 'Network security, encryption standards, authentication models, and vulnerability mitigation.');

-- Quizzes
INSERT INTO `quizzes` (`id`, `quiz_code`, `teacher_id`, `category_id`, `title`, `description`, `difficulty`, `start_datetime`, `end_datetime`, `max_attempts`, `status`) VALUES
(1, 'QUIZ-7F3A21', 1, 1, 'Database Fundamentals', 'Test your knowledge on relational schemas, SQL joins, primary keys, and indexing fundamentals.', 'Medium', '2026-01-01 08:00:00', '2027-12-31 23:59:59', 2, 'published'),
(2, 'QUIZ-K92LM1', 1, 2, 'Web Technologies & PHP', 'Interactive assessment covering HTML5 semantic elements, CSS Grid/Flexbox, JavaScript events, and PHP sessions.', 'Easy', '2026-01-01 08:00:00', '2027-12-31 23:59:59', 3, 'published'),
(3, 'QUIZ-X7P312', 1, 3, 'Java OOP Mastery', 'Deep dive into Java OOP principles, abstract classes, collections, and multi-threading.', 'Hard', '2026-01-01 08:00:00', '2027-12-31 23:59:59', 1, 'published'),
(4, 'QUIZ-M45RT8', 1, 4, 'Computer Networks & Protocols', 'Comprehensive quiz on TCP vs UDP, DNS resolution, CIDR notation, and network layers.', 'Medium', '2026-01-01 08:00:00', '2027-12-31 23:59:59', 2, 'published');

-- Questions for Quiz 1: Database Fundamentals (QUIZ-7F3A21)
-- Total questions: 6. Total time = 45+60+45+60+50+40 = 300 seconds (5 minutes)
INSERT INTO `questions` (`quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `time_limit`, `marks`, `question_order`) VALUES
(1, 'Which SQL clause is used to filter records resulting from a GROUP BY statement?', 'WHERE', 'ORDER BY', 'HAVING', 'FILTER BY', 'C', 45, 1, 1),
(1, 'What normal form addresses and eliminates transitive functional dependencies?', 'First Normal Form (1NF)', 'Second Normal Form (2NF)', 'Third Normal Form (3NF)', 'Boyce-Codd Normal Form (BCNF)', 'C', 60, 1, 2),
(1, 'Which property of ACID transactions guarantees that database changes persist even across system crashes?', 'Atomicity', 'Consistency', 'Isolation', 'Durability', 'D', 45, 1, 3),
(1, 'Which SQL command is classified under Data Definition Language (DDL)?', 'INSERT', 'ALTER', 'UPDATE', 'SELECT', 'B', 60, 1, 4),
(1, 'What index structure is most commonly employed in relational databases for rapid range and equality searches?', 'Binary Search Tree', 'B-Tree / B+Tree', 'Hash Table', 'Linked List', 'B', 50, 1, 5),
(1, 'Which SQL join returns all rows from the left table, and matching rows from the right table?', 'INNER JOIN', 'RIGHT JOIN', 'LEFT JOIN', 'FULL OUTER JOIN', 'C', 40, 1, 6);

-- Questions for Quiz 2: Web Technologies & PHP (QUIZ-K92LM1)
-- Total questions: 5. Total time = 45+50+45+60+40 = 240 seconds (4 minutes)
INSERT INTO `questions` (`quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `time_limit`, `marks`, `question_order`) VALUES
(2, 'Which language is mainly used to structure content on a webpage?', 'CSS', 'HTML', 'Java', 'Python', 'B', 45, 1, 1),
(2, 'Which superglobal variable in PHP is used to access form data submitted via HTTP POST?', '$_GET', '$_POST', '$_REQUEST_DATA', '$_SERVER', 'B', 50, 1, 2),
(2, 'In modern CSS, which property allows two-dimensional grid-based layout systems?', 'display: flex;', 'display: grid;', 'float: left;', 'position: absolute;', 'B', 45, 1, 3),
(2, 'Which PHP function securely hashes user passwords using strong one-way hashing algorithms?', 'md5()', 'sha1()', 'password_hash()', 'base64_encode()', 'C', 60, 1, 4),
(2, 'Which JavaScript method attaches an event listener to a selected DOM element?', 'attachEvent()', 'addEventListener()', 'bindEvent()', 'listen()', 'B', 40, 1, 5);

-- Questions for Quiz 3: Java OOP Mastery (QUIZ-X7P312)
INSERT INTO `questions` (`quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `time_limit`, `marks`, `question_order`) VALUES
(3, 'Which keyword in Java prevents a class from being inherited or sub-classed?', 'static', 'abstract', 'final', 'private', 'C', 45, 1, 1),
(3, 'Which interface in Java Collections Framework represents a collection that contains no duplicate elements?', 'List', 'Set', 'Map', 'Queue', 'B', 60, 1, 2),
(3, 'What is the default initial capacity of an ArrayList in Java standard library?', '5', '10', '16', '32', 'B', 45, 1, 3);

-- Questions for Quiz 4: Computer Networks (QUIZ-M45RT8)
INSERT INTO `questions` (`quiz_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `time_limit`, `marks`, `question_order`) VALUES
(4, 'At which layer of the OSI model does the Transmission Control Protocol (TCP) operate?', 'Network Layer', 'Transport Layer', 'Data Link Layer', 'Session Layer', 'B', 45, 1, 1),
(4, 'What is the standard port number for secure HTTPS communication?', '80', '21', '443', '8080', 'C', 45, 1, 2),
(4, 'Which IP protocol provides automatic host configuration and IP address assignment?', 'DNS', 'DHCP', 'ARP', 'ICMP', 'B', 50, 1, 3);

-- Sample Attempts for Teacher Results & Rankings Demonstration
INSERT INTO `attempts` (`id`, `quiz_id`, `student_id`, `started_at`, `submitted_at`, `completion_time`, `score`, `total_marks`, `percentage`, `submission_type`, `status`) VALUES
(1, 1, 2, '2026-09-20 10:00:00', '2026-09-20 10:03:21', 201, 6, 6, 100.00, 'MANUAL', 'SUBMITTED'),
(2, 1, 3, '2026-09-20 10:05:00', '2026-09-20 10:09:10', 250, 6, 6, 100.00, 'AUTO', 'AUTO_SUBMITTED'),
(3, 1, 4, '2026-09-20 10:15:00', '2026-09-20 10:19:32', 272, 5, 6, 83.33, 'MANUAL', 'SUBMITTED');

-- Sample Answers for Attempt 1 (Kasun Perera - 100% score)
INSERT INTO `answers` (`attempt_id`, `question_id`, `selected_answer`, `is_correct`, `marks_obtained`) VALUES
(1, 1, 'C', 1, 1),
(1, 2, 'C', 1, 1),
(1, 3, 'D', 1, 1),
(1, 4, 'B', 1, 1),
(1, 5, 'B', 1, 1),
(1, 6, 'C', 1, 1);

-- Sample Contact Message
INSERT INTO `messages` (`name`, `email`, `message`) VALUES
('Kamal Gunathilake', 'kamal@example.com', 'Congratulations on the QuizArena platform! The real-time question timers and game UI are outstanding.');
