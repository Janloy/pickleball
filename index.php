<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = getDBConnection();
$courtsStmt = $db->query("SELECT * FROM courts WHERE status = 'Available'");
$courts = $courtsStmt->fetchAll();
$featuredCourt = !empty($courts) ? $courts[0] : null;
$bookingsStmt = $db->prepare("SELECT court_id, booking_date, start_time, end_time FROM bookings WHERE booking_status IN ('Pending', 'Confirmed') AND booking_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY) ORDER BY booking_date, start_time");
$bookingsStmt->execute();
$upcomingBookings = $bookingsStmt->fetchAll();
?>

<main class="home-page">
    <section class="hero-zone js-scroll-animate">
        <div class="hero-copy">
            <p class="hero-eyebrow">Pickleball with purpose</p>
            <h1>Reserve courts faster, play harder, and keep every rally on track.</h1>
            <p>PickleBall Hub makes court booking feel polished and modern with smart schedules, QR payments, and a dashboard built for players.</p>
            <div class="hero-actions">
                <a href="/pickleball/customer/book.php" class="btn btn-primary">Book a court</a>
                <a href="/pickleball/about.php" class="btn btn-secondary">Our story</a>
            </div>
            <div class="hero-highlights">
                <div>
                    <strong>Instant slots</strong>
                    <span>Real-time availability</span>
                </div>
                <div>
                    <strong>QR-ready</strong>
                    <span>GCash & Maya payment support</span>
                </div>
                <div>
                    <strong>Event ready</strong>
                    <span>Private play and parties</span>
                </div>
            </div>
        </div>

        <div class="hero-panel glass-card">
            <div class="hero-panel-keyline"></div>
            <span class="panel-pill">Featured court</span>
            <h2><?= htmlspecialchars($featuredCourt['court_name'] ?? 'Smash Central') ?></h2>
            <p><?= htmlspecialchars($featuredCourt['description'] ?? 'Indoor court with premium lighting, cushioned flooring, and flexible booking for training, groups, or solo play.') ?></p>
            <div class="hero-panel-meta">
                <div>
                    <span>₱<?= number_format($featuredCourt['price_per_hour'] ?? 25, 2) ?>/hr</span>
                    <small>standard rate</small>
                </div>
                <div>
                    <span>8AM–3AM</span>
                    <small>late night play</small>
                </div>
            </div>
            <div class="hero-panel-actions">
                <a href="/pickleball/customer/book.php" class="btn btn-primary">Reserve now</a>
            </div>
        </div>
    </section>

    <section class="section-surface js-scroll-animate">
        <div class="section-intro">
            <span class="section-label">Core experience</span>
            <h2>Built around the way players move.</h2>
        </div>
        <div class="surface-grid">
            <article class="surface-card glass-card animate-scale-up delay-1">
                <h3>Fast discovery</h3>
                <p>Find open courts, view prices, and book in one flow.</p>
            </article>
            <article class="surface-card glass-card animate-scale-up delay-2">
                <h3>QR checkout</h3>
                <p>Upload payment proof quickly and keep your booking verified.</p>
            </article>
            <article class="surface-card glass-card animate-scale-up delay-3">
                <h3>Clear scheduling</h3>
                <p>See court availability and avoid overlap with clean, simple visuals.</p>
            </article>
        </div>
    </section>

    <section class="section-light availability-section js-scroll-animate" id="availability">
        <div class="section-intro">
            <span class="section-label">Live availability</span>
            <h2>See available courts and open time slots.</h2>
            <p>Choose a date and explore court schedules in a polished calendar-style view.</p>
        </div>
        <div class="availability-panel glass-card">
            <div class="availability-header">
                <div class="availability-date-picker">
                    <label for="availability_date">Select a date</label>
                    <input type="date" id="availability_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="availability-summary">
                    <span>Courts available</span>
                    <strong id="availableCourtCount"><?= count($courts) ?></strong>
                </div>
            </div>
            <div id="availability_grid" class="availability-grid"></div>
            <div class="availability-legend">
                <span class="badge badge-success">Available</span>
                <span class="badge badge-danger">Booked</span>
            </div>
        </div>
    </section>

    <section class="section-dark steps-section js-scroll-animate">
        <div class="section-intro">
            <span class="section-label">How it works</span>
            <h2>Three steps from browse to play.</h2>
        </div>
        <div class="steps-grid">
            <article class="step-card glass-card animate-fade-in-up delay-1">
                <span>1</span>
                <h3>Pick your court</h3>
                <p>Choose a court type, time slot, and pricing that suits your group.</p>
            </article>
            <article class="step-card glass-card animate-fade-in-up delay-2">
                <span>2</span>
                <h3>Confirm payment</h3>
                <p>Use GCash or Maya QR codes and upload proof from your phone.</p>
            </article>
            <article class="step-card glass-card animate-fade-in-up delay-3">
                <span>3</span>
                <h3>Play and repeat</h3>
                <p>Show up on time, enjoy the court, and book again effortlessly.</p>
            </article>
        </div>
    </section>

    <section class="section-light js-scroll-animate" id="court">
        <div class="section-intro">
            <span class="section-label">Court</span>
            <h2>Open courts waiting for your next rally.</h2>
        </div>
        <div class="court-grid">
            <?php foreach ($courts as $court): ?>
                <article class="court-card glass-card animate-scale-up delay-1">
                    <div class="court-card-title">
                        <h3><?= htmlspecialchars($court['court_name']) ?></h3>
                        <span><?= htmlspecialchars($court['type']) ?> court</span>
                    </div>
                    <p><?= htmlspecialchars($court['description']) ?></p>
                    <div class="court-card-footer">
                        <span>₱<?= number_format($court['price_per_hour'], 2) ?>/hr</span>
                        <a href="/pickleball/customer/book.php" class="btn btn-primary btn-sm">Book now</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section-dark announcement-section js-scroll-animate" id="announcements">
        <div class="section-intro">
            <span class="section-label">Latest updates</span>
            <h2>Announcements</h2>
            <p>News, promos, and community updates appear here as soon as they are posted.</p>
        </div>
        <article class="announcement-card glass-card animate-fade-in-up">
            <div class="announcement-tag">To be posted</div>
            <h3>Stay connected</h3>
            <p>Check back here for new announcements, promos, and booking reminders.</p>
        </article>
    </section>

    <section class="section-light showcase-section js-scroll-animate" id="events">
        <div class="section-intro">
            <span class="section-label">Events</span>
            <h2>Make court time feel like a celebration.</h2>
            <p>Rent courts for private sessions, team trainings, or small group parties.</p>
        </div>
        <div class="showcase-grid">
            <article class="showcase-card glass-card animate-scale-up delay-1">
                <h3>Regular booking</h3>
                <p>Perfect for casual games with friends and family.</p>
            </article>
            <article class="showcase-card glass-card animate-scale-up delay-2">
                <h3>Private sessions</h3>
                <p>Host team drills, coaching, and practice matches with clarity.</p>
            </article>
            <article class="showcase-card glass-card animate-scale-up delay-3">
                <h3>Parties</h3>
                <p>Celebrate birthdays and socials around a shared game.</p>
            </article>
        </div>
    </section>

    <section class="section-dark bottom-panel js-scroll-animate" id="rules">
        <div class="bottom-panel-left glass-card">
            <span class="section-label">Court rules</span>
            <h2>Keep every match smooth and safe.</h2>
            <p>Follow these simple guidelines so the court stays ready for every player.</p>
        </div>
        <div class="bottom-panel-right">
            <div class="rule-card glass-card">
                <h3>Reservations</h3>
                <p>Bookings depend on court availability and confirmation.</p>
            </div>
            <div class="rule-card glass-card">
                <h3>Player conduct</h3>
                <p>Respect facilities, equipment, and your fellow players.</p>
            </div>
            <div class="rule-card glass-card">
                <h3>Attire</h3>
                <p>Court shoes and proper sportswear are required.</p>
            </div>
        </div>
    </section>
</main>

<script>
window.addEventListener('DOMContentLoaded', () => {
    const availabilityDateInput = document.getElementById('availability_date');
    const availabilityGrid = document.getElementById('availability_grid');
    const availableCourtCount = document.getElementById('availableCourtCount');
    const courts = <?= json_encode($courts); ?>;
    const bookingsData = <?= json_encode($upcomingBookings); ?>;

    function padTime(value) {
        return String(value).padStart(2, '0');
    }

    function formatPhilippineTimeLabel(hour) {
        let suffix = 'AM';
        let displayHour = hour;

        if (hour >= 24) {
            displayHour = hour - 24;
            suffix = 'AM';
        } else if (hour >= 12) {
            suffix = 'PM';
            displayHour = hour > 12 ? hour - 12 : hour;
        }

        if (displayHour === 0) {
            displayHour = 12;
        }

        return `${displayHour}:00 ${suffix}`;
    }

    function parseTimeToMinutes(time) {
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    }

    function getBookingRangesForDate(booking, date) {
        const bookingStart = parseTimeToMinutes(booking.start_time);
        const bookingEnd = parseTimeToMinutes(booking.end_time);
        const ranges = [];

        if (booking.booking_date === date) {
            if (bookingEnd <= bookingStart) {
                ranges.push({ start: bookingStart, end: 24 * 60 });
            } else {
                ranges.push({ start: bookingStart, end: bookingEnd });
            }
            return ranges;
        }

        const bookingDateObj = new Date(booking.booking_date);
        const nextDateObj = new Date(bookingDateObj);
        nextDateObj.setDate(nextDateObj.getDate() + 1);
        const nextDate = nextDateObj.toISOString().slice(0, 10);

        if (bookingEnd <= bookingStart && nextDate === date) {
            ranges.push({ start: 0, end: bookingEnd });
        }

        return ranges;
    }

    function isSlotBooked(courtId, date, slotStart, slotEnd) {
        const slotStartMinutes = parseTimeToMinutes(slotStart);
        const slotEndMinutes = parseTimeToMinutes(slotEnd);

        return bookingsData.some((booking) => {
            if (booking.court_id !== courtId) {
                return false;
            }

            const bookingRanges = getBookingRangesForDate(booking, date);
            return bookingRanges.some((range) => slotStartMinutes < range.end && slotEndMinutes > range.start);
        });
    }

    function renderAvailabilityGrid() {
        const selectedDate = availabilityDateInput.value || new Date().toISOString().slice(0, 10);
        availabilityGrid.innerHTML = '';

        if (!courts || courts.length === 0) {
            availabilityGrid.innerHTML = '<p style="padding: 1rem; text-align: center;">No courts available.</p>';
            availableCourtCount.textContent = '0';
            return;
        }

        courts.forEach((court) => {
            const courtCard = document.createElement('article');
            courtCard.className = 'availability-card glass-card animate-scale-up delay-1';

            const imageUrl = court.image && court.image.trim() !== '' ? `/pickleball/uploads/courts/${court.image}` : '/pickleball/uploads/courts/default_court.jpg';
            const imageWrap = document.createElement('div');
            imageWrap.className = 'availability-image-wrap';
            imageWrap.innerHTML = `
                <img src="${imageUrl}" alt="${court.court_name}">
                <div class="availability-image-tag">${court.type} court</div>
            `;

            const header = document.createElement('div');
            header.className = 'availability-card-header';
            header.innerHTML = `
                <div>
                    <h3>${court.court_name}</h3>
                    <p style="margin:0; color: var(--text-muted);">${court.type} court · ₱${parseFloat(court.price_per_hour).toFixed(2)}/hr</p>
                </div>
                <a href="/pickleball/customer/book.php" class="btn btn-secondary">Book now</a>
            `;

            const infoWrap = document.createElement('div');
            infoWrap.className = 'availability-info';
            infoWrap.appendChild(header);

            const slotRow = document.createElement('div');
            slotRow.className = 'availability-slot-row';

            const startHour = 8;
            const endHour = 27;
            const todayString = new Date().toISOString().slice(0, 10);
            const nowMinutes = new Date().getHours() * 60 + new Date().getMinutes();

            for (let hour = startHour; hour < endHour; hour += 1) {
                const slotStart = `${padTime(hour % 24)}:00`;
                const slotEnd = `${padTime((hour + 1) % 24)}:00`;
                const booked = isSlotBooked(court.id, selectedDate, slotStart, slotEnd);
                const isPastToday = selectedDate === todayString && hour >= startHour && hour < 24 && parseTimeToMinutes(slotStart) <= nowMinutes;
                
                const slotBadge = document.createElement('span');
                slotBadge.className = `slot-pill ${booked ? 'slot-busy' : isPastToday ? 'slot-past' : 'slot-free'}`;
                slotBadge.textContent = `${formatPhilippineTimeLabel(hour)} - ${formatPhilippineTimeLabel(hour + 1)}${booked ? ' (Booked)' : isPastToday ? ' (Past)' : ''}`;
                
                if (booked) {
                    slotBadge.title = 'Booked';
                } else if (isPastToday) {
                    slotBadge.title = 'Past time';
                } else {
                    slotBadge.title = 'Available';
                }
                slotRow.appendChild(slotBadge);
            }

            courtCard.appendChild(imageWrap);
            courtCard.appendChild(infoWrap);
            courtCard.appendChild(slotRow);
            availabilityGrid.appendChild(courtCard);
        });

        availableCourtCount.textContent = courts.length;
    }

    if (availabilityDateInput) {
        availabilityDateInput.addEventListener('change', renderAvailabilityGrid);
    }
    
    renderAvailabilityGrid();

    // Modal Image Viewer Handling
    const imageModal = document.getElementById('availability_image_modal');
    const modalClose = document.querySelector('#availability_image_modal .modal-close');
    const modalImg = document.getElementById('availability_modal_img');
    const modalCaption = document.getElementById('availability_modal_caption');

    function openImageModal(src, captionText) {
        if (!imageModal) return;
        modalImg.src = src;
        modalCaption.textContent = captionText;
        imageModal.style.display = 'flex';
    }

    function closeImageModal() {
        if (!imageModal) return;
        imageModal.style.display = 'none';
    }

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (target.closest('.availability-image-wrap')) {
            const image = target.closest('.availability-image-wrap').querySelector('img');
            if (image && image.src) {
                openImageModal(image.src, image.alt);
            }
        }
    });

    if (modalClose) {
        modalClose.addEventListener('click', closeImageModal);
    }
    if (imageModal) {
        imageModal.addEventListener('click', (event) => {
            if (event.target === imageModal) {
                closeImageModal();
            }
        });
    }
});
</script>

<div id="availability_image_modal" class="image-modal">
    <span class="modal-close">&times;</span>
    <img class="modal-content-img" id="availability_modal_img" alt="Court View">
    <div id="availability_modal_caption" class="modal-caption"></div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>