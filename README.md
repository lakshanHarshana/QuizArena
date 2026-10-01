# 🎮 QuizArena — Real-Time Online MCQ Quiz Platform

**QuizArena** is a modern, responsive, game-style online Multiple Choice Question (MCQ) quiz platform developed for university-level academic evaluations. 
The system satisfies and exceeds all specifications outlined in the **ICT 1209 / ICT 2209 — Web Technologies Mini Project** guidelines (Rajarata University of Sri Lanka).

---

## 📌 Academic Information
* **Course:** ICT 1209 / ICT 2209 — Web Technologies
* **Module:** Web Technologies Mini Project — Individual Submission
* **Institution:** Faculty of Applied Sciences / Faculty of Technology, Rajarata University of Sri Lanka
* **Stack:** HTML5, CSS3, Bootstrap 5.3, Vanilla JavaScript, PHP 8.x, MySQL (PDO)
* **Local Server Compatibility:** XAMPP / WAMP / Built-in PHP CLI Server

---

## 🚀 System Architecture & Core Requirements Mapping

### 1. Structure & Layout (Assignment Section 2.1 & 2.2)
* **Semantic HTML5:** Built using standard `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, and `<footer>` elements.
* **Modern CSS3 & Bootstrap 5.3:** Fluid grid layout, custom game-arena dark palette, responsive flexbox components, CSS custom properties (`:root`), cards, and modals.
* **Device Responsiveness:** Fully tested on mobile (360px+), tablet (768px+), and desktop screens (1024px, 1440px+).

---

### 2. Client-Side Interactivity (Assignment Section 2.3)
The assignment requires at least 3 distinct JavaScript features. **QuizArena implements all 6 features:**

| # | Required Feature (Section 2.3) | QuizArena Implementation Details | File / Location |
|---|---|---|---|
| **1** | **Dynamic Content Filtering** | Real-time search and multi-criteria live filtering (by Title, Category, Difficulty, Status) without page reloads. | `quizzes.php`, `student/dashboard.php`, `assets/js/main.js` |
| **2** | **Interactive Image Slider / Carousel** | Auto-playing interactive carousel (with pause-on-hover, previous/next controls, and indicator dots) showcasing platform features. | `index.php` (`#arenaCarousel`), `images/slider_*.svg` |
| **3** | **Real-Time Form Validation** | Instant client-side validation on Registration, Login, Contact form, and Quiz creation with clear feedback. | `assets/js/validation.js`, `register.php`, `contact.php` |
| **4** | **Smooth Scrolling & Navigation** | Smooth anchor scrolling (`#hero`, `#arena-slider`, `#how-it-works`, `#featured-quizzes`) with active scroll-state highlighting. | `index.php`, `assets/css/style.css` |
| **5** | **Interactive Modals / Popups** | Bootstrap modal for quick Quiz ID joining, delete confirmations, and quiz submission summary. | `includes/footer.php`, `student/quiz.php` |
| **6** | **Custom Dynamic Animations** | Dual animated progress bars, pulsing countdown warnings, animated podium rankings, and full-screen victory confetti. | `student/quiz.php`, `student/result.php`, `assets/js/quiz.js` |

---

### 3. Server-Side Processing & Security (Assignment Section 3.1 & 3.2)
* **Role-Based Authentication:**
  * Supports dual roles: **Student** and **Teacher**.
  * Registered users have a unique `username`, `email`, full name, and password hashed via **BCRYPT** (`password_hash` / `password_verify`).
  * Flexible login supports authentication via **either Username or Email Address**.
  * Role-based session guards (`isLoggedIn()`, `isStudent()`, `isTeacher()`) restrict unauthorized page access.
* **Session Security:** `session_regenerate_id(true)` prevents session fixation; flash messages communicate feedback seamlessly across requests.
* **Database Security (PDO Prepared Statements):** 100% of SQL queries utilize parameterized prepared statements, eliminating SQL Injection vulnerabilities. All user inputs are sanitized with `htmlspecialchars` against Cross-Site Scripting (XSS).
* **Contact Query Persistence (Section 3.4):** Submissions via `contact.php` are strictly validated on the backend and saved into the `messages` table (`id`, `name`, `email`, `message`, `created_at`).

---

### 4. Game-Style Quiz Mechanics (Specialized Requirements)

#### A. Unique Quiz ID
* Every created quiz receives an automatically generated unique Quiz Code (e.g., `QUIZ-7F3A21`).
* Students can identify and join any assessment directly using this Quiz ID.

#### B. Dynamic Total Duration
* Teachers **do not** manually guess or enter the total quiz duration.
* The total duration is calculated automatically by the system as the sum of all individual question time limits:
  $$\text{Total Duration} = \sum_{i=1}^{N} \text{Question}_i\text{ Time Limit}$$

#### C. Dual Synchronized Timers
1. **Question Timer:** Counts down the seconds allocated for the active question. Upon reaching zero, the question auto-locks and advances to the next question.
2. **Overall Quiz Timer (Top Priority):** Runs continuously. If overall time expires, the quiz immediately auto-submits, freezing further attempts regardless of the active question state.

#### D. Instant Answer Validation & Server-Side Locking
* Choosing an option highlights **GREEN** for correct or **RED** for incorrect.
* Answer options lock immediately to prevent alterations.
* Evaluated securely via server API (`api/answer_question.php`) to prevent client-side answer inspection in DOM/devtools.

#### E. Leaderboard Ranking Algorithm
* Submissions are dynamically ranked:
  1. Primary: **Score DESC** (highest mark first)
  2. Secondary (Tie-Breaker): **Time Taken ASC** (fastest student wins)

---

## 🗄️ Database Schema (`database.sql`)

The database strictly complies with MySQL 8.0+ and MariaDB 10.4+:

```sql
users (id, username, name, email, password, role, created_at)
student_profiles (id, user_id, student_id, course, created_at)
teacher_profiles (id, user_id, department, created_at)
categories (id, name, description)
quizzes (id, quiz_code, teacher_id, category_id, title, description, difficulty, start_datetime, end_datetime, max_attempts, status, created_at)
questions (id, quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, time_limit, marks, question_order)
attempts (id, quiz_id, student_id, score, total_marks, percentage, time_taken_seconds, status, created_at)
answers (id, attempt_id, question_id, selected_option, is_correct, marks_obtained, answered_at)
messages (id, name, email, message, created_at)
```

---

## 🔑 Test Credentials (1-Click Login Ready)

| Role | Username | Email | Password | Role Details |
|---|---|---|---|---|
| **Teacher** | `silva_teacher` | `teacher@quizarena.com` | `Teacher@123` | Dept. of Information & Communication Technology |
| **Student 1** | `kasun_p` | `student@quizarena.com` | `Student@123` | Kasun Perera (`IT220901`) — BSc in IT |
| **Student 2** | `nimal_s` | `nimal@quizarena.com` | `Student@123` | Nimal Silva (`IT220902`) — BSc in IT |

> **Tip:** The `login.php` interface includes convenient **1-Click Test Credentials Buttons** to test both Teacher and Student accounts instantly without manual typing.

---

## 💻 Setup & Installation Instructions

### Option 1: XAMPP / WAMP Installation (Standard)
1. Copy or clone the `QuizArena` folder to your web server root:
   * **XAMPP:** `C:\xampp\htdocs\QuizArena`
   * **WAMP:** `C:\wamp64\www\QuizArena`
2. Start **Apache** and **MySQL** via the control panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Create database `quizarena` and import `database.sql`.
5. Access the application:
   ```text
   http://localhost/QuizArena/
   ```

### Option 2: PHP CLI Built-In Server
```bash
cd QuizArena
php -S localhost:8000
```
Open your browser at `http://localhost:8000`.

---

## 📂 Folder Structure (Per Section 4 Specifications)

```text
QuizArena/
├── index.php                   # Home page with hero, interactive carousel, stats & quizzes
├── about.php                   # Academic details & system overview
├── contact.php                 # Contact form saving to MySQL messages table
├── dashboard.php               # Root entry router (routes to student or teacher console)
├── login.php                   # User login view (Username or Email)
├── register.php                # User registration view (Student vs Teacher)
├── logout.php                  # Session termination
├── database.sql                # Complete relational schema & demo seed records
├── README.md                   # Full documentation & requirements checklist
│
├── config/
│   └── database.php            # PDO database connection with multi-port auto fallback
│
├── includes/
│   ├── db.php                  # Database helper (Section 4 compliance)
│   ├── header.php              # Global navbar, alerts & CSS includes
│   ├── footer.php              # Global footer, Quick Join modal & scripts
│   ├── auth.php                # Session helpers & role authorization checks
│   └── functions.php           # Helper utilities, sanitization & badges
│
├── auth/
│   ├── login.php               # Auth login router
│   ├── register.php            # Auth register router
│   ├── logout.php              # Auth logout router
│   ├── login_process.php       # Authentication processing (Username/Email + Password)
│   └── register_process.php    # Registration validation & user persistence
│
├── api/
│   └── answer_question.php     # Real-time answer validation & locking API
│
├── student/
│   ├── dashboard.php           # Student portal with quiz cards & live search filters
│   ├── join_quiz.php           # Quiz ID verification & pre-start instructions
│   ├── quiz.php                # Game-style quiz arena with synchronized dual timers
│   ├── submit_quiz.php         # Quiz submission & grading engine
│   ├── result.php              # Result card with trophies, medals & confetti
│   └── history.php             # Comprehensive past attempt logs
│
├── teacher/
│   ├── dashboard.php           # Teacher overview with live statistics
│   ├── create_quiz.php         # Quiz creation with auto-generated Quiz ID
│   ├── edit_quiz.php           # Quiz settings editor
│   ├── manage_questions.php    # Question manager & automated duration calculator
│   ├── results.php             # Dynamic leaderboard table (Score DESC, Time ASC)
│   └── analytics.php           # Performance analytics & score distribution charts
│
├── css/ & assets/css/
│   └── style.css               # Custom game-arena dark theme & responsive layout
│
├── js/ & assets/js/
│   ├── main.js                 # Dynamic search filters & UI helpers
│   ├── timer.js                # Dual countdown timer engine
│   ├── quiz.js                 # Real-time answer validation & question transitions
│   └── validation.js           # Client-side form validation
│
└── images/ & assets/images/
    ├── slider_gameplay.svg     # Carousel banner: Real-time gameplay & instant feedback
    ├── slider_timer.svg        # Carousel banner: Synchronized dual timers
    ├── slider_leaderboard.svg  # Carousel banner: Dynamic leaderboards & podium
    └── slider_analytics.svg    # Carousel banner: Teacher analytics & Quiz IDs
```

---

## 🛡️ Academic Compliance Verification Summary

- [x] **Section 2.1 & 2.2:** Semantic HTML5, CSS3, Bootstrap 5.3, mobile-responsive design.
- [x] **Section 2.3:** Client-Side Interactivity (All 6 JavaScript features implemented).
- [x] **Section 3.1 & 3.2:** Secure PHP session authentication, username + email login, password hashing.
- [x] **Section 3.3:** Full PDO CRUD operations, prepared statements, prepared parameter validation.
- [x] **Section 3.4:** Contact form persists inquiries into the `messages` table.
- [x] **Section 4:** Exact project folder structure and file organization.
- [x] **Quiz Mechanics:** Unique Quiz IDs, automated total duration `SUM(time_limit)`, dual timers, green/red feedback, tie-breaker leaderboard.
