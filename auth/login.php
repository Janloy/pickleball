<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$message = '';
if (isset($_GET['registered'])) {
    $message = 'Registration successful! Please login below.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
    } else {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];

        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

            if ($user['role_id'] == 1) {
                header("Location: /pickleball/admin/dashboard.php");
            } elseif ($user['role_id'] == 2) {
                header("Location: /pickleball/staff/dashboard.php");
            } else {
                header("Location: /pickleball/customer/dashboard.php");
            }
            exit;
        } else {
            $message = 'Invalid email or password.';
        }
    }
}
?>

<div style="max-width: 400px; margin: 4rem auto;" class="glass-card">
    <h2 style="margin-bottom: 1.5rem; text-align: center; color: var(--dark-green);">Welcome Back</h2>
    <?php if ($message): ?>
        <div style="background: #F0FDF4; color: #15803D; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>