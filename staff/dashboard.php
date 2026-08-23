<?php
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 1)) {
    header("Location: /pickleball/auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/navbar.php';

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $bookingId = (int)$_POST['booking_id'];
        $status = sanitizeInput($_POST['status']);
        
        $stmt = $db->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
        $stmt->execute([$status, $bookingId]);
    }
}

$today = date('Y-m-d');
$stmt = $db->prepare("
    SELECT b.*, CONCAT(u.first_name, ' ', u.last_name) AS full_name, c.court_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN courts c ON b.court_id = c.id
    WHERE b.booking_date = ?
    ORDER BY b.start_time ASC
");
$stmt->execute([$today]);
$todayBookings = $stmt->fetchAll();
$statusCounts = array_count_values(array_column($todayBookings, 'booking_status'));
$totalBookings = count($todayBookings);
$confirmedCount = $statusCounts['Confirmed'] ?? 0;
$completedCount = $statusCounts['Completed'] ?? 0;
$cancelledCount = $statusCounts['Cancelled'] ?? 0;
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dashboard-header">
            <div>
                <p class="section-label">Staff Desk</p>
                <h2>Today's Schedule</h2>
                <p class="dashboard-subtitle">A quick summary of active reservations for <?= $today ?>.</p>
            </div>
            <div class="dashboard-stats-grid">
                <div class="dashboard-stat-card">
                    <span>Total bookings</span>
                    <strong><?= $totalBookings ?></strong>
                </div>
                <div class="dashboard-stat-card">
                    <span>Confirmed</span>
                    <strong><?= $confirmedCount ?></strong>
                </div>
                <div class="dashboard-stat-card">
                    <span>Completed</span>
                    <strong><?= $completedCount ?></strong>
                </div>
                <div class="dashboard-stat-card">
                    <span>Cancelled</span>
                    <strong><?= $cancelledCount ?></strong>
                </div>
            </div>
        </div>

        <div class="glass-card schedule-card">
            <div class="table-responsive">
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Customer</th>
                            <th>Court</th>
                            <th>Time</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todayBookings)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">No bookings scheduled for today.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($todayBookings as $tb): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tb['booking_number']) ?></td>
                                    <td><?= htmlspecialchars($tb['full_name']) ?></td>
                                    <td><?= htmlspecialchars($tb['court_name']) ?></td>
                                    <td><?= htmlspecialchars($tb['start_time']) ?> - <?= htmlspecialchars($tb['end_time']) ?></td>
                                    <td><?= htmlspecialchars($tb['payment_method']) ?></td>
                                    <td>
                                        <span class="status-pill <?= strtolower($tb['booking_status']) ?> badge-<?= $tb['booking_status'] === 'Completed' ? 'success' : ($tb['booking_status'] === 'Cancelled' ? 'danger' : 'warning') ?>">
                                            <?= htmlspecialchars($tb['booking_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <form action="dashboard.php" method="POST" class="status-form">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                                                <input type="hidden" name="booking_id" value="<?= $tb['id'] ?>">
                                                <select name="status" class="form-control">
                                                    <option value="Confirmed" <?= $tb['booking_status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="Completed" <?= $tb['booking_status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                    <option value="Cancelled" <?= $tb['booking_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save</button>
                                            </form>
                                            <a href="booking_details.php?booking_id=<?= $tb['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>