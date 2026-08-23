<?php
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: /pickleball/auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/navbar.php';

$db = getDBConnection();

// Fetch System Analytics Metrics
$totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
$totalBookings  = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$revenueToday   = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE booking_date = CURDATE() AND booking_status != 'Cancelled'")->fetchColumn();
$monthlyRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE()) AND booking_status != 'Cancelled'")->fetchColumn();
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <h2 style="margin-bottom: 1.5rem;">System Analytics Dashboard</h2>

        <div class="grid-3">
            <div class="glass-card metric-card">
                <div>
                    <h4>Total Customers</h4>
                    <h2><?= number_format($totalCustomers) ?></h2>
                </div>
                <i class="fa-solid fa-users"></i>
            </div>

            <div class="glass-card metric-card">
                <div>
                    <h4>Total Bookings</h4>
                    <h2><?= number_format($totalBookings) ?></h2>
                </div>
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <div class="glass-card metric-card">
                <div>
                    <h4>Today's Revenue</h4>
                    <h2>$<?= number_format($revenueToday, 2) ?></h2>
                </div>
                <i class="fa-solid fa-dollar-sign"></i>
            </div>

            <div class="glass-card metric-card">
                <div>
                    <h4>Monthly Revenue</h4>
                    <h2>$<?= number_format($monthlyRevenue, 2) ?></h2>
                </div>
                <i class="fa-solid fa-chart-pie"></i>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>