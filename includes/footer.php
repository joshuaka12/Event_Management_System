</main>
<footer class="site-footer">
    <div class="container">
        <div class="footer-brand">◈ CampusEMS</div>
        <div class="footer-copy">© <?= date('Y') ?> Campus Event Management System. All rights reserved.</div>
        <div class="footer-links">
            <a href="<?= base_url('index.php') ?>">Browse Events</a> ·
            <a href="<?= base_url('login.php') ?>">Login</a> ·
            <a href="<?= base_url('register.php') ?>">Register</a>
        </div>
    </div>
</footer>
<script>
    const toggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    if (toggle && navLinks) toggle.addEventListener('click', () => navLinks.classList.toggle('open'));
    const flash = document.getElementById('flashMsg');
    if (flash) setTimeout(() => { flash.style.opacity = '0'; setTimeout(() => flash.remove(), 300); }, 5000);
</script>
</body>
</html>