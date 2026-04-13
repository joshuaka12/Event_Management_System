/**
 * assets/js/main.js
 * Campus EMS – Global JavaScript
 */

/* ── Mobile nav toggle ──────────────────────────────────────────── */
const navToggle = document.getElementById('navToggle');
const navLinks  = document.getElementById('navLinks');

if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
        navLinks.classList.toggle('open');
        navToggle.textContent = navLinks.classList.contains('open') ? '✕' : '☰';
    });
    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
            navLinks.classList.remove('open');
            navToggle.textContent = '☰';
        }
    });
}

/* ── Flash message auto-dismiss (5s) ───────────────────────────── */
const flashMsg = document.getElementById('flashMsg');
if (flashMsg) {
    setTimeout(() => {
        flashMsg.style.opacity = '0';
        flashMsg.style.transition = 'opacity .4s';
        setTimeout(() => flashMsg.remove(), 400);
    }, 5000);
}

/* ── Confirm delete prompts ─────────────────────────────────────── */
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
        if (!confirm(el.dataset.confirm || 'Are you sure?')) {
            e.preventDefault();
        }
    });
});

/* ── Live search filter on event cards (homepage) ──────────────── */
const searchInput = document.getElementById('searchInput');
const eventCards  = document.querySelectorAll('.event-card');

if (searchInput && eventCards.length) {
    searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        eventCards.forEach(card => {
            const text = card.textContent.toLowerCase();
            const show = !q || text.includes(q);
            card.closest('.event-card-wrap')
                ? (card.closest('.event-card-wrap').style.display = show ? '' : 'none')
                : (card.style.display = show ? '' : 'none');
            if (show) visibleCount++;
        });
        const noResults = document.getElementById('noResults');
        if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    });
}

/* ── Character counters for textareas ──────────────────────────── */
document.querySelectorAll('textarea[data-maxlength]').forEach(ta => {
    const max     = parseInt(ta.dataset.maxlength, 10);
    const counter = document.createElement('span');
    counter.className = 'form-hint';
    counter.textContent = `0 / ${max} characters`;
    ta.insertAdjacentElement('afterend', counter);
    ta.addEventListener('input', () => {
        counter.textContent = `${ta.value.length} / ${max} characters`;
        counter.style.color = ta.value.length > max * 0.9 ? 'var(--accent)' : '';
    });
});

/* ── Animate stat numbers on page load ──────────────────────────── */
function animateNumber(el) {
    const target = parseInt(el.textContent, 10);
    if (isNaN(target) || target === 0) return;
    let start = 0;
    const step = Math.ceil(target / 40);
    const timer = setInterval(() => {
        start = Math.min(start + step, target);
        el.textContent = start.toLocaleString();
        if (start >= target) clearInterval(timer);
    }, 25);
}
document.querySelectorAll('.stat-number').forEach(el => animateNumber(el));
