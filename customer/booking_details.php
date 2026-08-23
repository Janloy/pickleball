<?php
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: /pickleball/auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/navbar.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

$details = null;

if ($bookingId > 0) {
    $stmt = $db->prepare(
        "SELECT b.*, c.court_name, c.court_number, c.type AS court_type, c.price_per_hour, c.image AS court_image " .
        "FROM bookings b " .
        "JOIN courts c ON b.court_id = c.id " .
        "WHERE b.id = ? AND b.user_id = ?"
    );
    $stmt->execute([$bookingId, $userId]);
    $details = $stmt->fetch();
}

?>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="margin-bottom: 1.5rem;">Booking Details</h2>

        <?php if (!$details): ?>
            <div class="glass-card" style="max-width: 700px; margin: 0 auto;">
                <p>No booking found or you do not have access to this reservation.</p>
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="glass-card" style="max-width: 780px; margin: 0 auto;">
                <div style="display:flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <h3 style="margin:0;">Reference: <?= htmlspecialchars($details['booking_number']) ?></h3>
                        <p style="margin:0.25rem 0 0 0;">Status: <strong><?= htmlspecialchars($details['booking_status']) ?></strong></p>
                    </div>
                    <div style="text-align:right;">
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>

                <div class="booking-detail-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="detail-card" style="background:#f8fafc; padding:1rem; border-radius:14px; border:1px solid #e2e8f0;">
                        <h4>Selected Court</h4>
                        <?php if (!empty($details['court_image'])): ?>
                            <div style="margin-bottom:1rem; border-radius:16px; overflow:hidden; background:#fff; border:1px solid #e2e8f0;">
                                <img src="/pickleball/uploads/courts/<?= htmlspecialchars($details['court_image']) ?>" alt="<?= htmlspecialchars($details['court_name']) ?>" style="width:100%; display:block; object-fit:cover; max-height:210px;">
                            </div>
                        <?php endif; ?>
                        <p><strong>Court:</strong> <?= htmlspecialchars($details['court_name']) ?> (<?= htmlspecialchars($details['court_number']) ?>)</p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($details['court_type']) ?></p>
                        <p><strong>Rate:</strong> ₱<?= number_format($details['price_per_hour'], 2) ?> / hour</p>
                        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($details['booking_date'])) ?></p>
                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($details['start_time'])) ?> - <?= date('g:i A', strtotime($details['end_time'])) ?></p>
                    </div>
                    <div class="detail-card" style="background:#f8fafc; padding:1rem; border-radius:14px; border:1px solid #e2e8f0;">
                        <h4>Payment & Cost</h4>
                        <p><strong>Amount:</strong> ₱<?= number_format($details['total_amount'], 2) ?></p>
                        <p><strong>Method:</strong> <?= htmlspecialchars($details['payment_method'] ?? 'N/A') ?></p>
                        <?php if (!empty($details['payment_proof'])): ?>
                            <?php if (strpos($details['payment_proof'], 'uploads/') === 0): ?>
                                <p><strong>Proof:</strong> Uploaded screenshot</p>
                                <button id="view_proof_button" type="button" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size:0.9rem;" data-proof-type="image" data-proof-url="/pickleball/<?= htmlspecialchars($details['payment_proof']) ?>">View Proof</button>
                            <?php elseif (strpos($details['payment_proof'], 'reference:') === 0): ?>
                                <?php $proofReference = substr($details['payment_proof'], strlen('reference:')); ?>
                                <p><strong>Proof:</strong> Reference number</p>
                                <p style="margin:0.5rem 0 0 0; font-size:0.95rem; padding:0.75rem 1rem; background:#f1f5f9; border-radius:12px; color:#0f172a; word-break:break-all;"><?= htmlspecialchars($proofReference) ?></p>
                            <?php else: ?>
                                <p><strong>Proof:</strong> <?= htmlspecialchars($details['payment_proof']) ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><strong>Proof:</strong> Not provided</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top:1.5rem; background:#ffffff; padding:1rem; border-radius:14px; border:1px solid #e2e8f0;">
                    <h4 style="margin-top:0;">Notes</h4>
                    <p>Please save your booking reference number. Show it when you arrive at the court, and you can return to this page anytime from your dashboard.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<div id="proof_modal" class="proof-modal" style="display:none;">
    <div class="proof-modal-backdrop"></div>
    <div class="proof-modal-card">
        <button id="proof_modal_close" class="proof-modal-close" type="button">×</button>
        <div id="proof_modal_content" class="proof-modal-content"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const proofButton = document.getElementById('view_proof_button');
    const proofModal = document.getElementById('proof_modal');
    const proofModalContent = document.getElementById('proof_modal_content');
    const proofModalClose = document.getElementById('proof_modal_close');

    if (proofButton) {
        proofButton.addEventListener('click', () => {
            const proofType = proofButton.dataset.proofType;
            proofModalContent.innerHTML = '';

            if (proofType === 'image') {
                const url = proofButton.dataset.proofUrl;
                proofModalContent.innerHTML = `<img src="${url}" alt="Payment Proof" style="max-width:100%; max-height:80vh; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,0.2);" />`;
            } else if (proofType === 'text') {
                const text = proofButton.dataset.proofText;
                proofModalContent.innerHTML = `<div style="padding:1.25rem; background:#0f172a; color:#f8fafc; border-radius:12px; max-width:560px; word-break:break-word;"><h4 style="margin-top:0;">Payment Reference</h4><p style="font-size:1.1rem; letter-spacing:0.03em;">${text}</p></div>`;
            }

            proofModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    }

    function closeProofModal() {
        proofModal.style.display = 'none';
        proofModalContent.innerHTML = '';
        document.body.style.overflow = '';
    }

    if (proofModalClose) {
        proofModalClose.addEventListener('click', closeProofModal);
    }

    proofModal.addEventListener('click', (e) => {
        if (e.target === proofModal || e.target.classList.contains('proof-modal-backdrop')) {
            closeProofModal();
        }
    });
});
</script>

<style>
.proof-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.proof-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(8, 15, 35, 0.8);
    backdrop-filter: blur(6px);
}
.proof-modal-card {
    position: relative;
    z-index: 1;
    background: #0f172a;
    border-radius: 22px;
    padding: 1.25rem;
    max-width: 92vw;
    width: min(760px, 92vw);
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
}
.proof-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    background: transparent;
    border: none;
    color: #e2e8f0;
    font-size: 2rem;
    line-height: 1;
    cursor: pointer;
}
.proof-modal-content {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 260px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
