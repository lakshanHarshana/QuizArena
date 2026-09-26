# 🎮 QuizArena — Real-Time Online MCQ Quiz Platform

QuizArena is a responsive, competitive, game-style online Multiple Choice Question (MCQ) quiz platform developed for university-level academic evaluations. Teachers can effortlessly create and schedule multiple quizzes, configure individual question timers, and view real-time leaderboards. Students can easily identify and join quizzes using a unique auto-generated **Quiz ID** (e.g. `QUIZ-7F3A21`), answer questions with instant visual feedback (**GREEN** for correct, **RED** for wrong), and compete under strict dual-timer synchronization.

---

## 📌 Academic Metadata
* **Course: Web Technologies
* **Institution:** Faculty of Technology, Rajarata University of Sri Lanka
* **Architecture:** Full-Stack Web Application (HTML5, CSS3, JavaScript, PHP 8+, MySQL)
* **Local Server Compatibility:** XAMPP / WAMP / Built-in PHP Development Server

---

## 🚀 Key Features

### 1. Dual Main User Roles
* **Student:**
  * Secure registration and login with session management.
  * Student dashboard displaying live, upcoming, and completed quizzes.
  * Dynamic multi-criteria search and filter (by Quiz ID, title, category, difficulty, status).
  * Quick Quiz ID verification screen before entering.
  * Interactive game-style quiz player with instant feedback (**GREEN** / **RED**).
  * Individual question countdown timers + auto-transitioning (no manual "Next" button needed).
  * High-priority overall countdown timer with automatic submission on expiration.
  * Manual quiz submission with an answered/unanswered confirmation modal.
  * Animated celebration results (Trophy, Medal, Confetti for 90%+ scores) and detailed review.
  * Comprehensive attempt history tracking.

* **Teacher:**
  * Secure registration (with Department) and login.
  * Teacher dashboard with real-time KPI metrics (total quizzes, attempts, participating students).
  * Quiz creation with **automatically generated unique Quiz IDs** (e.g. `QUIZ-7F3A21`).
  * 9-step MCQ question creator: question text, options A–D, correct answer, individual time limit, marks.
  * **Automatic overall quiz duration calculation**: derived dynamically as `SUM(question time limits)`.
  * Quiz management: publish, unpublish, edit, delete, and question reordering.
  * Results and Leaderboard rankings table sorted primarily by **Score (DESC)**, tie-broken by **Faster Completion Time (ASC)**.
  * Performance analytics dashboard with Chart.js accuracy ratios and score distribution charts.

### 2. Game-Style Answer Interaction & Security
* **Instant Visual Feedback:** Selecting an answer instantly lights up **GREEN** (correct) or **RED** (wrong) with subtle Web Audio sound effects.
* **Answer Locking:** Once an option is clicked, all choices are immediately locked to prevent answer alterations or duplicate submissions.
* **Anti-Cheat Architecture:** Correct answers are **never** exposed in the client HTML/JS prior to answer submission. The answer is evaluated securely via a server-side JSON API endpoint (`api/answer_question.php`), and stored answers are immutable.
* **Attempt Limiting:** Server-enforced `max_attempts` restriction prevents students from exceeding allowed tries.

---

## ⏱️ How the Dual Quiz Timer Engine Works

The timing mechanism is designed around strict academic rules:
1. **No Manual Overall Duration:** The teacher does **not** type the overall duration. Instead, it is automatically calculated as:
   $$\text{Overall Quiz Time} = \sum_{i=1}^{N} \text{Question}_i\text{ Time Limit}$$
2. **Individual Question Timer:**
   * Each question counts down from its designated seconds (e.g. `60s`, `45s`).
   * When it reaches `00:00`, the question is marked unanswered, options are locked, and the system automatically advances to the next question.
3. **Overall Quiz Timer (Top Priority):**
   * Runs independently in the sticky header bar.
   * If the overall timer expires while a question is active, the overall timer **takes immediate priority**: answering is halted, and the quiz auto-submits.
4. **Server-Side Validation:** Time elapsed is validated against server timestamps, preventing client-side timer manipulation.

---

## 🗄️ Database Architecture

The relational schema is configured in `database.sql` (MySQL 8.0+ / MariaDB 10.4+):

| Table | Description |
|---|---|
| `users` | Core authentication table (`name`, `email`, `password`, `role`). |
| `student_profiles` | Student details (`student_id`, `course`, foreign key to `users`). |
| `teacher_profiles` | Faculty department details (foreign key to `users`). |
| `categories` | Academic subject areas (Database Systems, Web Technologies, etc.). |
| `quizzes` | Quiz headers (`quiz_code`, `title`, `difficulty`, `start_datetime`, `end_datetime`, `max_attempts`, `status`). |
| `questions` | MCQs (`quiz_id`, `question_text`, options A–D, `correct_answer`, `time_limit`, `marks`, `question_order`). |
| `attempts` | Student quiz attempts (`quiz_id`, `student_id`, `score`, `percentage`, `completion_time`, `submission_type`, `status`). |
| `answers` | Per-question answer logs (`attempt_id`, `question_id`, `selected_answer`, `is_correct`, `marks_obtained`). |
| `messages` | Contact submissions stored from `contact.php`. |

---

## 🔑 Test / Demo Accounts

| Role | Email | Password | Details |
|---|---|---|---|
| **Teacher** | `teacher@quizarena.com` | `Teacher@123` | Mr. Silva (Dept of ICT) |
| **Student 1** | `student@quizarena.com` | `Student@123` | Kasun Perera (`IT220901`) |
| **Student 2** | `nimal@quizarena.com` | `Student@123` | Nimal Silva (`IT220902`) |

> **Tip:** The login page features **1-click quick credentials buttons** to effortlessly test Teacher and Student accounts.

---

## 💻 Installation & Setup Guide

### Option A: Running via XAMPP / WAMP

1. **Clone or Copy Project:**
   Copy the `QuizArena` folder to your web server document root:
   * XAMPP: `C:/xampp/htdocs/QuizArena/`
   * WAMP: `C:/wamp64/www/QuizArena/`
2. **Start Services:**
   * Open the **XAMPP Control Panel** or **WAMP**.
   * Start **Apache** and **MySQL**.
3. **Database Import:**
   * Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   * Create a new database named `quizarena`.
   * Click **Import** and select `database.sql` from the project root.
   *(Note: `config/database.php` also features automatic migration on first run if connected to an empty MySQL instance).*
4. **Access Platform:**
   * Open your browser and navigate to:  
     `http://localhost/QuizArena/`

---

### Option B: Running via PHP Built-in Server

If you prefer to run directly from the command line:
```bash
cd QuizArena
php -S localhost:8000
```
Then visit: `http://localhost:8000`

---

## 📂 Project Structure

```text
QuizArena/
├── index.php                   # Landing page (Hero, live stats, workflow, featured quizzes)
├── about.php                   # Academic details & architecture
├── contact.php                 # Contact form saving to MySQL messages table
├── login.php                   # Authentication login view (with 1-click test buttons)
├── register.php                # Role-based registration view (Student vs Teacher)
├── logout.php                  # Secure session termination
├── database.sql                # Complete schema & comprehensive demo seed data
├── README.md                   # Project documentation
│
├── config/
│   └── database.php            # Multi-port PDO connection & auto-installer
│
├── includes/
│   ├── header.php              # Global navigation bar & notifications
│   ├── footer.php              # Footer & Quick Join Quiz modal
│   ├── auth.php                # Role verification & session guards
│   └── functions.php           # Quiz ID generator, duration calculators, badges
│
├── auth/
│   ├── login_process.php       # Login verification & routing
│   └── register_process.php    # Registration validation & profile creation
│
├── api/
│   └── answer_question.php     # Real-time answer evaluation & locking API
│
├── student/
│   ├── dashboard.php           # Student hub with live quizzes & search filters
│   ├── join_quiz.php           # Quiz code validation & pre-start confirmation
│   ├── quiz.php                # Fullscreen interactive game-style MCQ quiz player
│   ├── submit_quiz.php         # Final score calculation & attempt completion
│   ├── result.php              # Game-style animated results & question review
│   └── history.php             # Full attempt history & rankings
│
├── teacher/
│   ├── dashboard.php           # Teacher overview with quiz metrics
│   ├── create_quiz.php         # Quiz creator with auto Quiz ID generation
│   ├── edit_quiz.php           # Quiz settings & schedule editor
│   ├── manage_questions.php    # 9-step MCQ manager & total time calculator
│   ├── results.php             # Leaderboard table (Score DESC, Time ASC)
│   └── analytics.php           # Chart.js metrics & accuracy reports
│
├── assets/
│   ├── css/
│   │   └── style.css           # Game-inspired dark theme stylesheet
│   └── js/
│       ├── main.js             # Filter engine, copy helpers, Web Audio sound
│       ├── timer.js            # Dual countdown timer engine
│       ├── quiz.js             # Real-time MCQ quiz player controller
│       └── validation.js       # Client form validation
│
└── uploads/                    # Asset storage
```

---

## 🧪 Testing Checklist

- [x] **Authentication:** Student & Teacher registration, duplicate check, password hashing, role guard redirects.
- [x] **Quiz ID Generation:** Auto-generated unique format (`QUIZ-XXXXXX`), displayed on dashboards and join forms.
- [x] **Automatic Duration:** Total quiz time calculated automatically as `SUM(time_limit)`.
- [x] **Dual Timers:** Question timer countdown auto-advances at `00:00`; overall timer auto-submits at `00:00`.
- [x] **Game Feedback:** Instant **GREEN** / **RED** answer highlight + audio blip + answer locking.
- [x] **Ranking Order:** Primary sort by `Score DESC`, secondary sort by `Completion Time ASC`.
- [x] **Responsive Layout:** Tested seamlessly across Mobile, Tablet, and Desktop viewports.

---

## 📜 License
Developed for educational purposes under the Web Technologies module, Rajarata University of Sri Lanka.
