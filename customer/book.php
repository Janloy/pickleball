<?php
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: /pickleball/auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/navbar.php';

$db = getDBConnection();
$error = '';
$success = '';
$bookingReference = '';

// Retrieve available courts
$courtsStmt = $db->query("SELECT * FROM courts WHERE status = 'Available'");
$courts = $courtsStmt->fetchAll();

$paymentQRCodes = [];
try {
    $qrStmt = $db->query("SELECT provider, image FROM payment_qr_codes WHERE provider IN ('GCash','Maya')");
    foreach ($qrStmt->fetchAll() as $qrRow) {
        $paymentQRCodes[$qrRow['provider']] = $qrRow['image'];
    }
} catch (PDOException $e) {
    $paymentQRCodes = [];
}

// Retrieve future bookings for availability display
$bookingsStmt = $db->prepare("SELECT court_id, booking_date, start_time, end_time FROM bookings WHERE booking_status IN ('Pending', 'Confirmed') AND booking_date >= CURDATE() ORDER BY booking_date, start_time");
$bookingsStmt->execute();
$existingBookings = $bookingsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF security token.';
    } else {
        $courtId = (int)$_POST['court_id'];
        $date = sanitizeInput($_POST['booking_date']);
        $startTime = sanitizeInput($_POST['start_time']);
        $endTime = sanitizeInput($_POST['end_time']);
        $paymentMethod = sanitizeInput($_POST['payment_method']);
        $paymentProofType = sanitizeInput($_POST['payment_proof_type'] ?? 'reference');
        $paymentReference = sanitizeInput($_POST['payment_reference'] ?? '');
        $paymentProof = null;

        if (empty($courtId) || empty($date) || empty($startTime) || empty($endTime)) {
            $error = 'Please fill in all required booking fields.';
        } else {
            $startDateTime = strtotime($date . ' ' . $startTime);
            $endDate = $endTime <= $startTime ? date('Y-m-d', strtotime($date . ' +1 day')) : $date;
            $endDateTime = strtotime($endDate . ' ' . $endTime);

            if (in_array($paymentMethod, ['GCash', 'Maya'], true)) {
                if ($paymentProofType === 'reference') {
                    if (empty($paymentReference)) {
                        $error = 'Please enter the payment reference number.';
                    } else {
                        $paymentProof = 'reference:' . $paymentReference;
                    }
                } elseif ($paymentProofType === 'screenshot') {
                    if (empty($_FILES['payment_screenshot']['name']) || $_FILES['payment_screenshot']['error'] !== UPLOAD_ERR_OK) {
                        $error = 'Please upload a payment screenshot.';
                    } else {
                        $uploadedFile = $_FILES['payment_screenshot'];
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!in_array($uploadedFile['type'], $allowedTypes, true)) {
                            $error = 'Payment screenshot must be a JPG, PNG, or GIF image.';
                        } else {
                            $paymentsDir = __DIR__ . '/../uploads/payments';
                            if (!is_dir($paymentsDir)) {
                                mkdir($paymentsDir, 0755, true);
                            }
                            $fileExt = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                            $fileName = 'payment_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $fileExt;
                            $destination = $paymentsDir . '/' . $fileName;
                            if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
                                $paymentProof = 'uploads/payments/' . $fileName;
                            } else {
                                $error = 'Unable to save the uploaded screenshot. Please try again.';
                            }
                        }
                    }
                } else {
                    $error = 'Please choose how to submit your payment proof.';
                }
            }

            if (empty($error) && $endDateTime <= $startDateTime) {
                $error = 'End time must be after start time.';
            }

            if (empty($error)) {
                $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
                $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));

                $checkStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE court_id = ? AND booking_status IN ('Pending', 'Confirmed') AND booking_date IN (?, ?, ?) AND (CONCAT(booking_date, ' ', start_time) < ? AND CONCAT(DATE_ADD(booking_date, INTERVAL IF(end_time <= start_time, 1, 0) DAY), ' ', end_time) > ?)");
                $checkStmt->execute([$courtId, $prevDate, $date, $nextDate, date('Y-m-d H:i:s', $endDateTime), date('Y-m-d H:i:s', $startDateTime)]);
                $isDoubleBooked = $checkStmt->fetchColumn() > 0;

                if ($isDoubleBooked) {
                    $error = 'The requested court is already reserved for this timeframe.';
                } else {
                    $rateStmt = $db->prepare("SELECT price_per_hour FROM courts WHERE id = ?");
                    $rateStmt->execute([$courtId]);
                    $pricePerHour = $rateStmt->fetchColumn();

                    $durationHours = ($endDateTime - $startDateTime) / 3600;
                    $totalAmount = $durationHours * $pricePerHour;
                    $bookingNum = 'BK-' . strtoupper(uniqid());

                    $insertStmt = $db->prepare("INSERT INTO bookings (booking_number, user_id, court_id, booking_date, start_time, end_time, total_amount, payment_method, payment_proof, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')");

                    if ($insertStmt->execute([$bookingNum, $_SESSION['user_id'], $courtId, $date, $startTime, $endTime, $totalAmount, $paymentMethod, $paymentProof])) {
                        $bookingReference = $bookingNum;
                        $success = "Booking successful! Your reference number is {$bookingNum}.";
                    } else {
                        $error = 'Failed to record reservation.';
                    }
                }
            }
        }
    }
}
?>

<div class="dashboard-container">
    <main class="main-content">
        <div style="max-width: 650px; margin: 0 auto;" class="glass-card">
            <h2>Book a Pickleball Court</h2>
            
            <?php if ($error): ?>
                <div style="background: #FEE2E2; color: #B91C1C; padding: 0.75rem; border-radius: 8px; margin: 1rem 0;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div id="fallback_success_message" style="display:none;">
                <?= htmlspecialchars($success) ?>
            </div>

            <form action="book.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label>Select Court</label>
                    <select name="court_id" id="court_id" class="form-control" required>
                        <option value="">-- Choose Court --</option>
                        <?php foreach ($courts as $court): ?>
                            <?php $courtImg = !empty($court['image']) ? $court['image'] : 'default_court.jpg'; ?>
                            <option value="<?= $court['id'] ?>" 
                                    data-price="<?= $court['price_per_hour'] ?>"
                                    data-image="/pickleball/uploads/courts/<?= htmlspecialchars($courtImg) ?>"
                                    data-name="<?= htmlspecialchars($court['court_name']) ?>"
                                    data-type="<?= htmlspecialchars($court['type']) ?>"
                                    data-description="<?= htmlspecialchars($court['description'] ?? '', ENT_QUOTES) ?>">
                                <?= htmlspecialchars($court['court_name']) ?> (₱<?= number_format($court['price_per_hour'], 2) ?>/hr)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dynamic Court Image Preview -->
                <div id="court_preview_card" class="court-preview-card" style="display: none;">
                    <div class="court-preview-img-wrap" id="preview_clickable">
                        <img id="preview_img" src="" alt="Court Preview" onerror="this.onerror=null; this.src='/pickleball/uploads/courts/default_court.jpg';">
                        <span id="preview_type" class="badge badge-success court-preview-badge">Indoor</span>
                        <div class="img-zoom-hint">🔍 Click to view full screen</div>
                    </div>
                    <div class="court-preview-info">
                        <h4 id="preview_name">Court Name</h4>
                        <p id="preview_description" class="muted" style="margin:0.5rem 0 0; line-height:1.4;">Court description will appear here.</p>
                        <p id="preview_rate" style="color: var(--primary-green); font-weight: 700; margin-top:0.75rem;">₱0.00 / hr</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="booking_date" id="booking_date" min="<?= date('Y-m-d') ?>" class="form-control" required>
                </div>

                <div id="slot_availability_panel" class="availability-panel" style="display:none;">
                    <h3>Slot Availability</h3>
                    <div class="availability-legend">
                        <span class="badge badge-success">Available</span>
                        <span class="badge badge-danger">Booked</span>
                    </div>
                    <div id="slot_action_text" class="slot-action-text">Select a start time from available Philippine time slots, then choose an end time.</div>
                    <div id="slot_grid" class="slot-grid"></div>
                    <p class="muted">Click a start slot, then click an end slot to book a continuous interval.</p>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-control" required>
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                        <option value="Maya">Maya</option>
                    </select>
                </div>

                <div id="payment_proof_section" class="form-group" style="display: none;">
                    <label>Payment Proof</label>
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:0.75rem;">
                        <label style="font-weight:normal;"><input type="radio" name="payment_proof_type" value="reference" checked> Reference number</label>
                        <label style="font-weight:normal;"><input type="radio" name="payment_proof_type" value="screenshot"> Upload screenshot</label>
                    </div>
                    <div id="payment_reference_group" class="form-group">
                        <input type="text" name="payment_reference" id="payment_reference" class="form-control" placeholder="Enter reference number">
                    </div>
                    <div id="payment_screenshot_group" class="form-group" style="display:none;">
                        <input type="file" name="payment_screenshot" id="payment_screenshot" accept="image/*" class="form-control">
                    </div>
                    <small class="muted">Upload your payment receipt or enter the transaction reference number.</small>
                </div>

                <div id="payment_qr_card" class="payment-qr-card" style="display:none; margin-bottom:1rem; padding:1rem; border:1px solid #d1d5db; border-radius:18px; background:#f8fafc;">
                    <h4 id="payment_qr_title" style="margin-top:0; margin-bottom:0.75rem;"></h4>
                    <img id="payment_qr_image" src="" alt="Payment QR Code" style="max-width:240px; width:100%; height:auto; display:block; margin-bottom:0.75rem; border-radius:16px;">
                    <p id="payment_qr_method" style="margin:0; color:#0f172a;"></p>
                </div>

                <div class="form-group">
                    <label>Total Calculated Price:</label>
                    <h3 id="total_price_display" style="color: var(--primary-green);">₱0.00</h3>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Confirm Booking</button>
            </form>
        </div>
    </main>
</div>

<!-- Floating Lightbox Modal -->
<div id="image_modal" class="image-modal">
    <span class="modal-close">&times;</span>
    <img class="modal-content-img" id="full_modal_image" alt="Fullscreen Court View">
    <div id="modal_caption" class="modal-caption"></div>
</div>

<div id="toast_backdrop" class="toast-backdrop" style="display:none;"></div>
<div id="success_toast" class="success-toast" style="display:none;">
    <div class="toast-checkmark">✓</div>
    <h3>Booking Confirmed</h3>
    <p id="toast_booking_ref"></p>
    <p class="toast-description">Save this booking reference number — it will be shown when you arrive at the court and you can view it again in your dashboard.</p>
    <button id="toast_ok_button" class="toast-ok-button" type="button">OK</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const courtSelect = document.getElementById('court_id');
    const bookingDateInput = document.getElementById('booking_date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const priceDisplay = document.getElementById('total_price_display');
    const slotPanel = document.getElementById('slot_availability_panel');
    const slotActionText = document.getElementById('slot_action_text');
    const slotGrid = document.getElementById('slot_grid');
    const bookingsData = <?= json_encode($existingBookings); ?>;
    let selectedSlotStart = '';
    let selectedSlotEnd = '';
    let selectedRawStart = null;
    let selectedRawEnd = null;
    
    // Preview Elements
    const previewCard = document.getElementById('court_preview_card');
    const previewImg = document.getElementById('preview_img');
    const previewName = document.getElementById('preview_name');
    const previewDescription = document.getElementById('preview_description');
    const previewType = document.getElementById('preview_type');
    const previewRate = document.getElementById('preview_rate');
    
    // Modal Elements
    const imageModal = document.getElementById('image_modal');
    const fullModalImage = document.getElementById('full_modal_image');
    const modalCaption = document.getElementById('modal_caption');
    const modalClose = document.querySelector('.modal-close');
    const previewClickable = document.getElementById('preview_clickable');
    const paymentMethodSelect = document.getElementById('payment_method');
    const paymentProofSection = document.getElementById('payment_proof_section');
    const paymentReferenceGroup = document.getElementById('payment_reference_group');
    const paymentScreenshotGroup = document.getElementById('payment_screenshot_group');
    const paymentQRCard = document.getElementById('payment_qr_card');
    const paymentQRTitle = document.getElementById('payment_qr_title');
    const paymentQRImage = document.getElementById('payment_qr_image');
    const paymentQRMethod = document.getElementById('payment_qr_method');
    const paymentQRCodes = <?= json_encode($paymentQRCodes); ?>;
    const toast = document.getElementById('success_toast');
    const toastBackdrop = document.getElementById('toast_backdrop');
    const toastBookingRef = document.getElementById('toast_booking_ref');
    const toastOkButton = document.getElementById('toast_ok_button');
    const bookingForm = document.querySelector('form[action="book.php"]');
    const submitButton = bookingForm.querySelector('button[type="submit"]');

    function updatePreviewAndTotal() {
        const selectedOption = courtSelect.options[courtSelect.selectedIndex];
        
        if (courtSelect.value !== "") {
            const imgSrc = selectedOption.getAttribute('data-image');
            const name = selectedOption.getAttribute('data-name');
            const type = selectedOption.getAttribute('data-type');
            const description = selectedOption.getAttribute('data-description') || '';
            const price = parseFloat(selectedOption.getAttribute('data-price')).toFixed(2);

            previewImg.src = imgSrc;
            previewName.textContent = name;
            previewDescription.textContent = description || 'No description available for this court.';
            previewType.textContent = type;
            previewRate.textContent = `₱${price} / hr`;

            previewCard.style.display = 'block';
            previewCard.classList.add('animate-pop');
        } else {
            previewCard.style.display = 'none';
        }

        const pricePerHour = parseFloat(selectedOption ? selectedOption.getAttribute('data-price') : 0);
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;

        if (pricePerHour && startTime && endTime) {
            const start = new Date(`1970-01-01T${startTime}:00`);
            let end = new Date(`1970-01-01T${endTime}:00`);
            let diffHours = (end - start) / (1000 * 60 * 60);

            if (diffHours <= 0) {
                end = new Date(start);
                end.setDate(end.getDate() + 1);
                const [endHour, endMinute] = endTime.split(':').map(Number);
                end.setHours(endHour, endMinute, 0, 0);
                diffHours = (end - start) / (1000 * 60 * 60);
            }

            if (diffHours > 0) {
                const total = diffHours * pricePerHour;
                priceDisplay.textContent = `₱${total.toFixed(2)}`;
                return;
            }
        }
        priceDisplay.textContent = '₱0.00';
    }

    function padTime(value) {
        return String(value).padStart(2, '0');
    }

    function parseTimeToMinutes(time) {
        const parts = time.split(':').map(Number);
        return parts[0] * 60 + parts[1];
    }

    function formatPhilippineTimeLabel(time) {
        const [hourString, minuteString] = time.split(':');
        let hour = Number(hourString);
        const minute = minuteString;
        const suffix = hour >= 12 ? 'PM' : 'AM';
        if (hour === 0) {
            hour = 12;
        } else if (hour > 12) {
            hour -= 12;
        }
        return `${hour}:${minute} ${suffix}`;
    }

    function getBookingRangesForDate(booking, date) {
        const bookingStart = parseTimeToMinutes(booking.start_time);
        const bookingEnd = parseTimeToMinutes(booking.end_time);
        const ranges = [];

        if (booking.booking_date === date) {
            if (bookingEnd <= bookingStart) {
                ranges.push({ start: bookingStart, end: 24 * 60 });
                ranges.push({ start: 0, end: bookingEnd });
            } else {
                ranges.push({ start: bookingStart, end: bookingEnd });
            }
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
            const ranges = getBookingRangesForDate(booking, date);
            return ranges.some((range) => slotStartMinutes < range.end && slotEndMinutes > range.start);
        });
    }

    function isRangeAvailable(courtId, date, startTime, endTime, rawStart = null, rawEnd = null) {
        let current;
        let target;
        if (rawStart !== null && rawEnd !== null) {
            current = rawStart * 60;
            target = rawEnd * 60;
        } else {
            current = parseTimeToMinutes(startTime);
            target = parseTimeToMinutes(endTime);
            if (target <= current) {
                target += 24 * 60;
            }
        }

        while (current < target) {
            const blockStartMinutes = current % (24 * 60);
            const blockEndMinutes = (current + 60) % (24 * 60);
            const blockStart = `${padTime(Math.floor(blockStartMinutes / 60))}:${padTime(blockStartMinutes % 60)}`;
            const blockEnd = `${padTime(Math.floor(blockEndMinutes / 60))}:${padTime(blockEndMinutes % 60)}`;
            if (isSlotBooked(courtId, date, blockStart, blockEnd)) {
                return false;
            }
            current += 60;
        }
        return true;
    }

    function updateSlotSelectionMessage() {
        if (selectedSlotStart && selectedSlotEnd) {
            slotActionText.textContent = `Selected range: ${selectedSlotStart} - ${selectedSlotEnd}`;
            return;
        }
        if (selectedSlotStart) {
            slotActionText.textContent = `Start selected at ${selectedSlotStart}. Choose an end time.`;
            return;
        }
        slotActionText.textContent = 'Select a start time from available 8:00 AM to 3:00 AM slots, then choose an end time.';
    }

    function updateSelectedRangeDisplay() {
        const buttons = slotGrid.querySelectorAll('.slot-button');
        buttons.forEach((button) => {
            const slotRawStart = Number(button.dataset.rawStart);
            const slotRawEnd = Number(button.dataset.rawEnd);
            button.classList.remove('selected');
            if (selectedRawStart !== null && selectedRawEnd !== null) {
                if (slotRawStart >= selectedRawStart && slotRawEnd <= selectedRawEnd) {
                    button.classList.add('selected');
                }
            } else if (selectedRawStart !== null && slotRawStart === selectedRawStart) {
                button.classList.add('selected');
            }
        });
    }

    function updatePaymentProofFields() {
        const method = paymentMethodSelect.value;
        if (method === 'GCash' || method === 'Maya') {
            paymentProofSection.style.display = 'block';
            updatePaymentQRDisplay();
        } else {
            paymentProofSection.style.display = 'none';
            paymentQRCard.style.display = 'none';
        }
    }

    function updatePaymentQRDisplay() {
        const method = paymentMethodSelect.value;
        if (method === 'GCash' || method === 'Maya') {
            const imagePath = paymentQRCodes[method] ?? null;
            if (imagePath) {
                paymentQRTitle.textContent = `${method} QR Code`;
                paymentQRImage.src = `/pickleball/uploads/payment_qr/${imagePath}`;
                paymentQRMethod.textContent = `Scan this QR with ${method} to pay.`;
                paymentQRCard.style.display = 'block';
                return;
            }
        }
        paymentQRCard.style.display = 'none';
    }

    function updatePaymentProofType() {
        const proofType = document.querySelector('input[name="payment_proof_type"]:checked').value;
        if (proofType === 'reference') {
            paymentReferenceGroup.style.display = 'block';
            paymentScreenshotGroup.style.display = 'none';
        } else {
            paymentReferenceGroup.style.display = 'none';
            paymentScreenshotGroup.style.display = 'block';
        }
    }

    function showSuccessToast(reference) {
        toastBookingRef.textContent = `Reference: ${reference}`;
        toastBackdrop.style.display = 'block';
        toastBackdrop.classList.add('visible');
        toast.style.display = 'block';
        toast.classList.add('toast-show');
        document.body.style.overflow = 'hidden';
    }

    function hideSuccessToast() {
        toastBackdrop.classList.remove('visible');
        toast.classList.remove('toast-show');
        setTimeout(() => {
            toastBackdrop.style.display = 'none';
            toast.style.display = 'none';
            document.body.style.overflow = '';
        }, 250);
    }

    function clearSlotSelection() {
        selectedSlotStart = '';
        selectedSlotEnd = '';
        selectedRawStart = null;
        selectedRawEnd = null;
        updateSlotSelectionMessage();
        updateSelectedRangeDisplay();
    }

    function renderSlotAvailability() {
        selectedSlotStart = '';
        selectedSlotEnd = '';
        updateSlotSelectionMessage();
        const courtId = parseInt(courtSelect.value, 10);
        const bookingDate = bookingDateInput.value;

        slotGrid.innerHTML = '';

        if (!courtId || !bookingDate) {
            slotPanel.style.display = 'none';
            return;
        }

        slotPanel.style.display = 'block';

        const startHour = 8;
        const endHour = 27;
        let hasAvailableSlot = false;

        const todayString = new Date().toISOString().slice(0, 10);
    const nowMinutes = new Date().getHours() * 60 + new Date().getMinutes();

    for (let hour = startHour; hour < endHour; hour += 1) {
            const slotStart = `${padTime(hour % 24)}:00`;
            const slotEnd = `${padTime((hour + 1) % 24)}:00`;
            const booked = isSlotBooked(courtId, bookingDate, slotStart, slotEnd);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `slot-button ${booked ? 'slot-unavailable' : 'slot-available'}`;
            button.textContent = `${formatPhilippineTimeLabel(slotStart)} - ${formatPhilippineTimeLabel(slotEnd)}`;
            button.dataset.start = slotStart;
            button.dataset.end = slotEnd;
            button.dataset.rawStart = hour;
            button.dataset.rawEnd = hour + 1;

            const isSameDaySlot = bookingDate === todayString && hour >= startHour && hour < 24;
            const isPastToday = isSameDaySlot && parseTimeToMinutes(slotStart) <= nowMinutes;
            const isUnavailable = booked || isPastToday;
            button.disabled = isUnavailable;
            button.classList.remove('slot-available', 'slot-unavailable', 'booked');

            if (booked) {
                button.classList.add('slot-unavailable', 'booked');
                button.textContent += ' (Booked)';
                button.title = 'Booked';
            } else if (isPastToday) {
                button.classList.add('slot-unavailable');
                button.textContent += ' (Past)';
                button.title = 'Past time';
            } else {
                button.classList.add('slot-available');
                button.title = 'Available';
            }

            if (!isUnavailable) {
                hasAvailableSlot = true;
                button.addEventListener('click', () => {
                    const rawStart = Number(button.dataset.rawStart);
                    const rawEnd = Number(button.dataset.rawEnd);

                    if (selectedRawStart === rawStart && selectedRawEnd === rawEnd) {
                        clearSlotSelection();
                        startTimeInput.value = '';
                        endTimeInput.value = '';
                        priceDisplay.textContent = '₱0.00';
                        return;
                    }

                    if (selectedRawStart === null) {
                        selectedRawStart = rawStart;
                        selectedRawEnd = rawEnd;
                        selectedSlotStart = slotStart;
                        selectedSlotEnd = slotEnd;
                        startTimeInput.value = selectedSlotStart;
                        endTimeInput.value = selectedSlotEnd;
                    } else if (rawEnd <= selectedRawStart) {
                        selectedRawStart = rawStart;
                        selectedRawEnd = rawEnd;
                        selectedSlotStart = slotStart;
                        selectedSlotEnd = slotEnd;
                        startTimeInput.value = selectedSlotStart;
                        endTimeInput.value = selectedSlotEnd;
                    } else {
                        selectedRawEnd = rawEnd;
                        selectedSlotEnd = slotEnd;
                        endTimeInput.value = selectedSlotEnd;
                    }

                    if (selectedRawStart !== null && selectedRawEnd !== null && !isRangeAvailable(courtId, bookingDate, selectedSlotStart, selectedSlotEnd, selectedRawStart, selectedRawEnd)) {
                        slotActionText.textContent = 'The selected range overlaps a booked slot. Please choose a shorter range or a later start time.';
                    } else {
                        updateSlotSelectionMessage();
                    }

                    updateSelectedRangeDisplay();
                    updatePreviewAndTotal();
                });
            }

            slotGrid.appendChild(button);
        }

        if (!hasAvailableSlot) {
            const note = document.createElement('div');
            note.className = 'slot-note';
            note.textContent = 'No standard hourly slots are available for this court and date. Please choose a different court or a different date.';
            slotGrid.appendChild(note);
        }
    }

    courtSelect.addEventListener('change', () => {
        updatePreviewAndTotal();
        renderSlotAvailability();
    });
    bookingDateInput.addEventListener('change', renderSlotAvailability);

    // Modal Events
    previewClickable.addEventListener('click', () => {
        if (previewImg.src) {
            imageModal.style.display = 'flex';
            fullModalImage.src = previewImg.src;
            modalCaption.textContent = previewName.textContent + ' (' + previewType.textContent + ')';
        }
    });

    modalClose.addEventListener('click', () => {
        imageModal.style.display = 'none';
    });

    imageModal.addEventListener('click', (e) => {
        if (e.target === imageModal) {
            imageModal.style.display = 'none';
        }
    });

    courtSelect.addEventListener('change', updatePreviewAndTotal);
    startTimeInput.addEventListener('change', updatePreviewAndTotal);
    endTimeInput.addEventListener('change', updatePreviewAndTotal);
    paymentMethodSelect.addEventListener('change', () => {
        updatePaymentProofFields();
        updatePaymentQRDisplay();
    });
    document.querySelectorAll('input[name="payment_proof_type"]').forEach((radio) => {
        radio.addEventListener('change', updatePaymentProofType);
    });
    toastOkButton.addEventListener('click', hideSuccessToast);
    bookingForm.addEventListener('submit', () => {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="loading-spinner"></span>Confirming...';
        submitButton.classList.add('loading');
    });

    updatePaymentProofFields();
    updatePaymentProofType();

    const bookingReferenceNumber = <?= json_encode($bookingReference); ?>;
    if (bookingReferenceNumber) {
        showSuccessToast(bookingReferenceNumber);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>