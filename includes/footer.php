
</div><!-- /container -->
</main>

<footer>
    <div class="container">
        <div class="footer-brand">⌈ SOLO LEVELING FITNESS ⌋</div>
        <p style="margin:0;font-size:0.8rem;color:#4b5a78;">"Arise from the shadows and become the Monarch."</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Floating particles
(function() {
    const container = document.getElementById('particles');
    if (!container) return;
    for (let i = 0; i < 25; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `
            left: ${Math.random() * 100}%;
            animation-duration: ${8 + Math.random() * 14}s;
            animation-delay: ${Math.random() * 8}s;
            width: ${1 + Math.random() * 2}px;
            height: ${1 + Math.random() * 2}px;
            opacity: ${0.3 + Math.random() * 0.6};
        `;
        if (Math.random() > 0.6) p.style.background = '#c084fc';
        container.appendChild(p);
    }
})();

// Number counter animation
function animateCounters() {
    document.querySelectorAll('.stat-value[data-target]').forEach(el => {
        const target = parseInt(el.dataset.target);
        let current = 0;
        const step = Math.ceil(target / 40);
        const timer = setInterval(() => {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = current.toLocaleString();
        }, 30);
    });
}
document.addEventListener('DOMContentLoaded', animateCounters);
</script>
</body>
</html>