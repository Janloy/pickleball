<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request security token.';
    } else {
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $password = $_POST['password'];

        if (empty($firstName) || empty($lastName)) {
            $message = 'Please enter both first name and last name.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email address format.';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
        } else {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = 'Email address is already registered.';
            } else {
                $hashedPass = password_hash($password, PASSWORD_BCRYPT);
                $insertStmt = $db->prepare("INSERT INTO users (role_id, first_name, last_name, email, password, phone) VALUES (3, ?, ?, ?, ?, ?)");
                if ($insertStmt->execute([$firstName, $lastName, $email, $hashedPass, $phone])) {
                    header("Location: login.php?registered=success");
                    exit;
                } else {
                    $message = 'An error occurred during registration.';
                }
            }
        }
    }
}
?>

<div style="max-width: 450px; margin: 3rem auto;" class="glass-card">
    <h2 style="margin-bottom: 1.5rem; text-align: center; color: var(--dark-green);">Create Account</h2>
    <?php if ($message): ?>
        <div style="background: #FEE2E2; color: #B91C1C; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form action="register.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
            <div class="form-group" style="flex:1; min-width:180px;">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="form-group" style="flex:1; min-width:180px;">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>