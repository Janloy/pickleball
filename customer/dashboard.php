<?php
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: /pickleball/auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/navbar.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Cancel Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $bookingId = (int)$_POST['booking_id'];
        $stmt = $db->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$bookingId, $userId]);
    }
}

$allowedStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
$statusFilter = isset($_GET['status']) && in_array($_GET['status'], $allowedStatuses) ? $_GET['status'] : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;

$countSql = "SELECT COUNT(*) FROM bookings b WHERE b.user_id = ?";
$countParams = [$userId];
if ($statusFilter) {
    $countSql .= " AND b.booking_status = ?";
    $countParams[] = $statusFilter;
}
$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$totalBookings = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalBookings / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$bookingsSql = "SELECT b.*, c.court_name, c.court_number 
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.user_id = ?";
$bookingsParams = [$userId];
if ($statusFilter) {
    $bookingsSql .= " AND b.booking_status = ?";
    $bookingsParams[] = $statusFilter;
}
$bookingsSql .= " ORDER BY b.created_at DESC, b.id DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($bookingsSql);
$stmt->execute($bookingsParams);
$bookings = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <main class="main-content">
        <h2 style="margin-bottom: 1.5rem;">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></h2>
        
        <div class="glass-card">
            <div class="dashboard-card-header dashboard-card-header-center">
                <div>
                    <h3>My Reservations</h3>
                    <p class="muted">Newest bookings appear first. Choose a status to filter automatically.</p>
                </div>
                <div class="dashboard-filter">
                    <form method="get" class="filter-form" id="filterForm">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control" style="min-width: 220px;">
                            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All statuses</option>
                            <option value="Confirmed" <?= $statusFilter === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </form>
                </div>
            </div>
            <script>
                document.getElementById('status').addEventListener('change', function() {
                    document.getElementById('filterForm').submit();
                });
            </script>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Booking Ref</th>
                            <th>Court</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Amount</th>
                            <th>Proof</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="8">No bookings found. <a href="book.php">Book a court now</a>.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $b): ?>
                                <?php
                                    $proofLabel = 'None';
                                    if (!empty($b['payment_proof'])) {
                                        if (strpos($b['payment_proof'], 'uploads/') === 0) {
                                            $proofLabel = 'Screenshot uploaded';
                                        } elseif (strpos($b['payment_proof'], 'reference:') === 0) {
                                            $proofLabel = 'Reference number';
                                        } else {
                                            $proofLabel = 'Proof provided';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['booking_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['court_name']) ?> (<?= htmlspecialchars($b['court_number']) ?>)</td>
                                    <td><?= htmlspecialchars($b['booking_date']) ?></td>
                                    <td><?= htmlspecialchars($b['start_time']) ?> - <?= htmlspecialchars($b['end_time']) ?></td>
                                    <td>₱<?= number_format($b['total_amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($proofLabel) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $b['booking_status'] === 'Confirmed' ? 'success' : ($b['booking_status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                            <?= htmlspecialchars($b['booking_status']) ?>
                                        </span>
                                    </td>
                                    <td style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                                        <a href="booking_details.php?booking_id=<?= $b['id'] ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Details</a>
                                        <?php if ($b['booking_status'] === 'Pending' || $b['booking_status'] === 'Confirmed'): ?>
                                            <form action="dashboard.php" method="POST" onsubmit="return confirm('Cancel this reservation?');" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <button type="submit" name="cancel_booking" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <?php for ($pageIndex = 1; $pageIndex <= $totalPages; $pageIndex++): ?>
                        <a href="dashboard.php?status=<?= urlencode($statusFilter) ?>&page=<?= $pageIndex ?>" class="pagination-link <?= $pageIndex === $page ? 'active' : '' ?>"><?= $pageIndex ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>