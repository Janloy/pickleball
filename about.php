<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="about-page">
    <section class="about-hero section-light">
        <div class="about-copy">
            <span class="hero-label">Who we are</span>
            <h1>PickleBall Hub is where court booking meets community.</h1>
            <p>We built a booking platform that matches fast-paced gameplay with modern payment convenience and clear court availability.</p>
            <div class="hero-actions">
                <a href="/pickleball/customer/book.php" class="btn btn-primary">Book a Court</a>
                <a href="/pickleball/index.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
        <div class="about-visual glass-card">
            <span class="panel-pill">Our promise</span>
            <h2>Effortless court access, every time.</h2>
            <p>From booking to payment confirmation, every step is designed to keep you on the court faster.</p>
            <div class="about-grid">
                <div class="about-card">
                    <h3>Quick booking</h3>
                    <p>Browse courts and book with fewer clicks.</p>
                </div>
                <div class="about-card">
                    <h3>Secure payment</h3>
                    <p>Upload QR payment proof right from your phone.</p>
                </div>
                <div class="about-card">
                    <h3>Easy management</h3>
                    <p>See your upcoming sessions and reservation history in one place.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section-dark about-details">
        <div class="section-intro">
            <span class="section-label">Our values</span>
            <h2>More than a link. It’s a better way to play.</h2>
            <p>We help players find courts, reserve sessions, and pay with minimal friction so they can focus on the game.</p>
        </div>
        <div class="about-story-grid">
            <article class="about-story-card glass-card animate-scale-up delay-1">
                <h3>Built for players</h3>
                <p>Every part of the experience is optimized for court time, not paperwork.</p>
            </article>
            <article class="about-story-card glass-card animate-scale-up delay-2">
                <h3>Community first</h3>
                <p>Make your bookings with friends, teams, and local groups in mind.</p>
            </article>
            <article class="about-story-card glass-card animate-scale-up delay-3">
                <h3>Future-ready</h3>
                <p>Modern booking screens, mobile-friendly layouts, and fast load speeds.</p>
            </article>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
