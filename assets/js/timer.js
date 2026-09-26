/**
 * QUIZARENA — Independent Timer Engine
 * Controls:
 * 1. Question Countdown Timer (Individual question duration)
 * 2. Overall Quiz Countdown Timer (Calculated total quiz duration, PRIORITY)
 */

class QuizTimerEngine {
    constructor(options = {}) {
        this.questionDuration = options.questionDuration || 60; // in seconds
        this.overallDuration = options.overallDuration || 300;   // in seconds
        
        this.onQuestionTick = options.onQuestionTick || null;
        this.onQuestionExpire = options.onQuestionExpire || null;
        
        this.onOverallTick = options.onOverallTick || null;
        this.onOverallExpire = options.onOverallExpire || null;

        this.questionRemaining = this.questionDuration;
        this.overallRemaining = this.overallDuration;

        this.questionInterval = null;
        this.overallInterval = null;
        this.isStopped = false;
    }

    /**
     * Start the overall quiz timer. Runs continuously with top priority.
     */
    startOverallTimer() {
        if (this.overallInterval) clearInterval(this.overallInterval);
        
        if (this.onOverallTick) {
            this.onOverallTick(this.overallRemaining, this.formatTime(this.overallRemaining));
        }

        this.overallInterval = setInterval(() => {
            if (this.isStopped) return;

            this.overallRemaining--;

            if (this.onOverallTick) {
                this.onOverallTick(this.overallRemaining, this.formatTime(this.overallRemaining));
            }

            // Overall timer expiration has highest priority
            if (this.overallRemaining <= 0) {
                clearInterval(this.overallInterval);
                this.stopAll();
                if (this.onOverallExpire) {
                    this.onOverallExpire();
                }
            }
        }, 1000);
    }

    /**
     * Start or reset the individual question timer
     */
    startQuestionTimer(durationSeconds) {
        if (this.questionInterval) clearInterval(this.questionInterval);
        if (this.isStopped) return;

        this.questionDuration = durationSeconds;
        this.questionRemaining = durationSeconds;

        if (this.onQuestionTick) {
            this.onQuestionTick(this.questionRemaining, this.formatTime(this.questionRemaining));
        }

        this.questionInterval = setInterval(() => {
            if (this.isStopped) return;

            this.questionRemaining--;

            if (this.onQuestionTick) {
                this.onQuestionTick(this.questionRemaining, this.formatTime(this.questionRemaining));
            }

            if (this.questionRemaining <= 0) {
                clearInterval(this.questionInterval);
                if (this.onQuestionExpire) {
                    this.onQuestionExpire();
                }
            }
        }, 1000);
    }

    /**
     * Pause individual question timer (e.g. while showing answer feedback)
     */
    pauseQuestionTimer() {
        if (this.questionInterval) {
            clearInterval(this.questionInterval);
            this.questionInterval = null;
        }
    }

    /**
     * Stop both timers permanently (upon submit or expiration)
     */
    stopAll() {
        this.isStopped = true;
        if (this.questionInterval) clearInterval(this.questionInterval);
        if (this.overallInterval) clearInterval(this.overallInterval);
    }

    /**
     * Format seconds into mm:ss
     */
    formatTime(seconds) {
        if (seconds < 0) seconds = 0;
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}

// Export to window for global access
window.QuizTimerEngine = QuizTimerEngine;
