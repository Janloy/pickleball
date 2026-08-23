<?php
$role_id = $_SESSION['role_id'] ?? 3;
?>
<aside class="sidebar">
    <h3 style="margin-bottom: 1rem; padding-left: 0.5rem;">Navigation</h3>
    <?php if ($role_id == 1): ?>
        <a href="/pickleball/admin/dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="/pickleball/admin/courts.php"><i class="fa-solid fa-vector-square"></i> Courts</a>
        <a href="/pickleball/admin/staff.php"><i class="fa-solid fa-user-tie"></i> Staff Management</a>
        <a href="/pickleball/admin/customers.php"><i class="fa-solid fa-users"></i> Customer Management</a>
        <a href="/pickleball/admin/payment_qr.php"><i class="fa-solid fa-qrcode"></i> Payment QR Codes</a>
    <?php elseif ($role_id == 2): ?>
        <a href="/pickleball/staff/dashboard.php"><i class="fa-solid fa-calendar-check"></i> Today's Schedule</a>
    <?php else: ?>
        <a href="/pickleball/customer/dashboard.php"><i class="fa-solid fa-user"></i> My Dashboard</a>
        <a href="/pickleball/customer/book.php"><i class="fa-solid fa-calendar-plus"></i> Book Court</a>
    <?php endif; ?>
</aside>