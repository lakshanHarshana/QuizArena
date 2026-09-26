/**
 * QUIZARENA — Core Client Interactions & Game Audio Effects
 */

// Simple synthesized sound generator via Web Audio API
const ArenaSound = {
    audioCtx: null,

    init() {
        if (!this.audioCtx && (window.AudioContext || window.webkitAudioContext)) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContextClass();
        }
    },

    playTone(frequency, type, duration, delay = 0) {
        try {
            this.init();
            if (!this.audioCtx) return;
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = type;
            osc.frequency.setValueAtTime(frequency, this.audioCtx.currentTime + delay);

            gain.gain.setValueAtTime(0.12, this.audioCtx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.0001, this.audioCtx.currentTime + delay + duration);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(this.audioCtx.currentTime + delay);
            osc.stop(this.audioCtx.currentTime + delay + duration);
        } catch (e) {
            // Audio context policy safe ignore
        }
    },

    playCorrect() {
        // Melodic victory chord
        this.playTone(523.25, 'sine', 0.15, 0.0);   // C5
        this.playTone(659.25, 'sine', 0.25, 0.08);  // E5
    },

    playWrong() {
        // Low error buzz
        this.playTone(220.0, 'sawtooth', 0.25, 0.0); // A3
    },

    playTick() {
        // Subtle countdown tick
        this.playTone(800.0, 'triangle', 0.04, 0.0);
    }
};

// Copy Quiz Code to clipboard helper
function copyQuizCode(code, buttonElement) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(() => {
            const originalHtml = buttonElement.innerHTML;
            buttonElement.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i>Copied!';
            setTimeout(() => {
                buttonElement.innerHTML = originalHtml;
            }, 1800);
        });
    } else {
        // Fallback for older browsers
        const tempInput = document.createElement('input');
        tempInput.value = code;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('Copied Quiz ID: ' + code);
    }
}

// Live Search & Multi-criteria Filtering for Quizzes
function initQuizFilters() {
    const searchInput = document.getElementById('quizSearchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const difficultyFilter = document.getElementById('difficultyFilter');
    const statusFilter = document.getElementById('statusFilter');
    const quizCards = document.querySelectorAll('.quiz-item-card');
    const emptyState = document.getElementById('quizEmptyState');

    if (!searchInput && !categoryFilter && !difficultyFilter && !statusFilter) return;

    function applyFilter() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const cat = categoryFilter ? categoryFilter.value.toLowerCase() : '';
        const diff = difficultyFilter ? difficultyFilter.value.toLowerCase() : '';
        const stat = statusFilter ? statusFilter.value.toUpperCase() : '';

        let visibleCount = 0;

        quizCards.forEach(card => {
            const cardCode = (card.getAttribute('data-code') || '').toLowerCase();
            const cardTitle = (card.getAttribute('data-title') || '').toLowerCase();
            const cardCat = (card.getAttribute('data-category') || '').toLowerCase();
            const cardDiff = (card.getAttribute('data-difficulty') || '').toLowerCase();
            const cardStat = (card.getAttribute('data-status') || '').toUpperCase();

            const matchesSearch = !query || cardCode.includes(query) || cardTitle.includes(query);
            const matchesCat = !cat || cardCat === cat;
            const matchesDiff = !diff || cardDiff === diff;
            const matchesStat = !stat || cardStat === stat;

            if (matchesSearch && matchesCat && matchesDiff && matchesStat) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (emptyState) {
            emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);
    if (categoryFilter) categoryFilter.addEventListener('change', applyFilter);
    if (difficultyFilter) difficultyFilter.addEventListener('change', applyFilter);
    if (statusFilter) statusFilter.addEventListener('change', applyFilter);
}

document.addEventListener('DOMContentLoaded', () => {
    initQuizFilters();
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
});

window.ArenaSound = ArenaSound;
window.copyQuizCode = copyQuizCode;
