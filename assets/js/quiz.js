/**
 * QUIZARENA — Interactive Game-Style MCQ Quiz Engine
 * Features:
 * - Dynamic sequential question loading
 * - Question countdown timer + Overall quiz timer (priority)
 * - Instant GREEN/RED answer feedback + sound effects
 * - Permanent answer locking per question
 * - Automatic question transitions (no Next button needed)
 * - Confirmation modal for manual submission
 * - Seamless auto-submission when overall timer expires
 */

class QuizGameEngine {
    constructor(config) {
        this.attemptId = config.attemptId;
        this.quizId = config.quizId;
        this.questions = config.questions || [];
        this.overallSeconds = config.overallSeconds || 300;
        this.apiUrl = config.apiUrl || '../api/answer_question.php';
        this.submitUrl = config.submitUrl || 'submit_quiz.php';
        
        this.currentIndex = 0;
        this.answeredCount = 0;
        this.isSubmitting = false;
        this.isTransitioning = false;

        // UI Element References
        this.qIndexEl = document.getElementById('qCurrentIndex');
        this.qTotalEl = document.getElementById('qTotalCount');
        this.qProgressBar = document.getElementById('qProgressBar');
        this.qCard = document.getElementById('questionCard');
        this.qTextEl = document.getElementById('questionText');
        this.qMarksEl = document.getElementById('questionMarks');
        this.qTimerText = document.getElementById('questionTimerText');
        this.optionsContainer = document.getElementById('optionsContainer');
        this.overallTimerDisplay = document.getElementById('overallTimerDisplay');
        this.overallTimerBox = document.getElementById('overallTimerBox');

        this.init();
    }

    init() {
        if (!this.questions.length) {
            alert('No questions found in this quiz.');
            return;
        }

        // Initialize Dual Timer Engine
        this.timer = new QuizTimerEngine({
            questionDuration: this.questions[0].time_limit,
            overallDuration: this.overallSeconds,
            onQuestionTick: (rem, formatted) => {
                if (this.qTimerText) {
                    this.qTimerText.textContent = formatted;
                    if (rem <= 10) {
                        this.qTimerText.parentElement.classList.add('text-danger', 'border-danger');
                        if (rem <= 5) ArenaSound.playTick();
                    } else {
                        this.qTimerText.parentElement.classList.remove('text-danger', 'border-danger');
                    }
                }
            },
            onQuestionExpire: () => {
                this.handleQuestionTimeout();
            },
            onOverallTick: (rem, formatted) => {
                if (this.overallTimerDisplay) {
                    this.overallTimerDisplay.textContent = formatted;
                }
                if (this.overallTimerBox) {
                    if (rem <= 60) {
                        this.overallTimerBox.classList.add('danger');
                        this.overallTimerBox.classList.remove('warning');
                    } else if (rem <= 180) {
                        this.overallTimerBox.classList.add('warning');
                    }
                }
            },
            onOverallExpire: () => {
                this.handleOverallTimeout();
            }
        });

        // Start overall timer
        this.timer.startOverallTimer();

        // Render the first question
        this.renderQuestion(0);

        // Bind manual submission button & confirmation modal
        this.bindSubmissionEvents();
    }

    renderQuestion(index) {
        if (index < 0 || index >= this.questions.length) {
            // End of questions -> auto finalize
            this.showQuizCompletePrompt();
            return;
        }

        this.currentIndex = index;
        this.isTransitioning = false;
        const q = this.questions[index];

        // Update progress counter
        if (this.qIndexEl) this.qIndexEl.textContent = (index + 1);
        if (this.qTotalEl) this.qTotalEl.textContent = this.questions.length;
        if (this.qMarksEl) this.qMarksEl.textContent = q.marks + ' Mark' + (q.marks > 1 ? 's' : '');

        const percent = Math.round(((index + 1) / this.questions.length) * 100);
        if (this.qProgressBar) {
            this.qProgressBar.style.width = percent + '%';
            this.qProgressBar.setAttribute('aria-valuenow', percent);
        }

        // Render Question Text
        if (this.qTextEl) {
            this.qTextEl.textContent = q.question_text;
        }

        // Render Options A, B, C, D
        if (this.optionsContainer) {
            this.optionsContainer.innerHTML = '';
            const optionLetters = ['A', 'B', 'C', 'D'];

            optionLetters.forEach(letter => {
                const optKey = 'option_' + letter.toLowerCase();
                const optText = q[optKey] || '';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'option-btn';
                btn.setAttribute('data-option', letter);
                btn.innerHTML = `
                    <span class="option-prefix">${letter}</span>
                    <span class="option-text">${this.escapeHtml(optText)}</span>
                    <i class="fa-solid fa-circle-check option-icon icon-correct"></i>
                    <i class="fa-solid fa-circle-xmark option-icon icon-wrong"></i>
                `;

                btn.addEventListener('click', () => {
                    this.handleOptionSelection(letter, btn, q.id);
                });

                this.optionsContainer.appendChild(btn);
            });
        }

        // Reset and start individual question countdown
        this.timer.startQuestionTimer(parseInt(q.time_limit) || 60);
    }

    handleOptionSelection(letter, clickedBtn, questionId) {
        if (this.isTransitioning || this.isSubmitting) return;
        this.isTransitioning = true;

        // 1. Pause question timer
        this.timer.pauseQuestionTimer();

        // 2. ANSWER LOCKING: Immediately lock all option buttons to prevent changes
        const allButtons = this.optionsContainer.querySelectorAll('.option-btn');
        allButtons.forEach(btn => {
            btn.classList.add('locked');
            if (btn !== clickedBtn) {
                btn.classList.add('dimmed');
            }
        });

        // 3. Post answer to backend API securely
        fetch(this.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                attempt_id: this.attemptId,
                question_id: questionId,
                selected_answer: letter
            })
        })
        .then(res => res.json())
        .then(data => {
            this.answeredCount++;
            
            if (data.is_correct) {
                // Correct answer selected: GREEN feedback
                clickedBtn.classList.remove('dimmed');
                clickedBtn.classList.add('correct');
                ArenaSound.playCorrect();
            } else {
                // Wrong answer selected: RED feedback
                clickedBtn.classList.remove('dimmed');
                clickedBtn.classList.add('wrong');
                ArenaSound.playWrong();

                // Highlight the correct answer if available
                if (data.correct_answer) {
                    const correctBtn = this.optionsContainer.querySelector(`[data-option="${data.correct_answer}"]`);
                    if (correctBtn) {
                        correctBtn.classList.remove('dimmed');
                        correctBtn.classList.add('correct');
                    }
                }
            }

            // 4. Automatic Question Transition after short game delay (1200ms)
            setTimeout(() => {
                this.moveToNextQuestion();
            }, 1200);
        })
        .catch(err => {
            console.error('Answer submission error:', err);
            setTimeout(() => {
                this.moveToNextQuestion();
            }, 1200);
        });
    }

    handleQuestionTimeout() {
        if (this.isTransitioning || this.isSubmitting) return;
        this.isTransitioning = true;

        // Lock options and mark as unanswered
        const allButtons = this.optionsContainer.querySelectorAll('.option-btn');
        allButtons.forEach(btn => btn.classList.add('locked', 'dimmed'));

        const q = this.questions[this.currentIndex];

        // Register unanswered on server
        fetch(this.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                attempt_id: this.attemptId,
                question_id: q.id,
                selected_answer: null
            })
        }).finally(() => {
            // Automatically advance to next question
            this.moveToNextQuestion();
        });
    }

    moveToNextQuestion() {
        if (this.currentIndex + 1 < this.questions.length) {
            this.renderQuestion(this.currentIndex + 1);
        } else {
            // All questions finished -> automatically submit
            this.executeSubmission('MANUAL');
        }
    }

    handleOverallTimeout() {
        if (this.isSubmitting) return;
        // Overall timer has HIGHEST PRIORITY
        this.timer.stopAll();

        // Lock everything
        const allButtons = document.querySelectorAll('.option-btn');
        allButtons.forEach(btn => btn.classList.add('locked', 'dimmed'));

        // Show prominent toast/modal notification
        const autoModal = document.getElementById('autoSubmitModal');
        if (autoModal && typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(autoModal);
            modalInstance.show();
        }

        setTimeout(() => {
            this.executeSubmission('AUTO');
        }, 1500);
    }

    bindSubmissionEvents() {
        const manualSubmitBtn = document.getElementById('manualSubmitBtn');
        const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');

        if (manualSubmitBtn) {
            manualSubmitBtn.addEventListener('click', () => {
                const unansweredCount = this.questions.length - this.answeredCount;
                const modalAns = document.getElementById('modalAnsweredCount');
                const modalUnans = document.getElementById('modalUnansweredCount');

                if (modalAns) modalAns.textContent = this.answeredCount;
                if (modalUnans) modalUnans.textContent = unansweredCount;

                const submitConfirmModal = new bootstrap.Modal(document.getElementById('submitConfirmModal'));
                submitConfirmModal.show();
            });
        }

        if (confirmSubmitBtn) {
            confirmSubmitBtn.addEventListener('click', () => {
                this.executeSubmission('MANUAL');
            });
        }
    }

    showQuizCompletePrompt() {
        this.executeSubmission('MANUAL');
    }

    executeSubmission(submissionType = 'MANUAL') {
        if (this.isSubmitting) return;
        this.isSubmitting = true;

        this.timer.stopAll();

        // Submit form programmatically to prevent any browser back manipulation
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = this.submitUrl;

        const inputAttempt = document.createElement('input');
        inputAttempt.type = 'hidden';
        inputAttempt.name = 'attempt_id';
        inputAttempt.value = this.attemptId;
        form.appendChild(inputAttempt);

        const inputType = document.createElement('input');
        inputType.type = 'hidden';
        inputType.name = 'submission_type';
        inputType.value = submissionType;
        form.appendChild(inputType);

        document.body.appendChild(form);
        form.submit();
    }

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

window.QuizGameEngine = QuizGameEngine;
