<nav class="navbar">
    <a href="/pickleball/index.php" class="brand">
        <i class="fa-solid fa-table-tennis-paddle-ball"></i>
        <span>PickleBall Hub</span>
    </a>
    <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <ul class="nav-links" id="navLinks">
        <li><a href="/pickleball/index.php#court">Court</a></li>
        <li><a href="/pickleball/index.php#announcements">Announcements</a></li>
        <li><a href="/pickleball/index.php#events">Events</a></li>
        <li><a href="/pickleball/index.php#merch">Merch</a></li>
        <li><a href="/pickleball/index.php#cafe">Lounge</a></li>
        <li><a href="/pickleball/index.php#rules">Rules</a></li>
        <li><a href="/pickleball/about.php">About Us</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role_id'] == 1): ?>
                <li><a href="/pickleball/admin/dashboard.php">Admin Panel</a></li>
            <?php elseif ($_SESSION['role_id'] == 2): ?>
                <li><a href="/pickleball/staff/dashboard.php">Staff Dashboard</a></li>
            <?php else: ?>
                <li><a href="/pickleball/customer/dashboard.php">My Dashboard</a></li>
                <li><a href="/pickleball/customer/book.php">Book Court</a></li>
                <li><a href="/pickleball/customer/profile.php">My Profile</a></li>
            <?php endif; ?>
            <li><a href="/pickleball/auth/logout.php" class="btn btn-primary" style="padding: 0.5rem 1rem;">Logout</a></li>
        <?php else: ?>
            <li><a href="/pickleball/auth/login.php">Login</a></li>
            <li><a href="/pickleball/auth/register.php" class="btn btn-primary" style="padding: 0.5rem 1rem;">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
<script>
(function() {
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.getElementById('navLinks');
    if (!navToggle || !navLinks) return;
    navToggle.addEventListener('click', function() {
        const isOpen = navLinks.classList.toggle('nav-open');
        this.setAttribute('aria-expanded', isOpen);
    });
})();
</script>