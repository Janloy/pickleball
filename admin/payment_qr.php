<?php
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: /pickleball/auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/navbar.php';

$db = getDBConnection();
$error = '';
$msg = '';
$providers = ['GCash', 'Maya'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_qr'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $provider = sanitizeInput($_POST['provider'] ?? '');
        if (!in_array($provider, $providers, true)) {
            $error = 'Invalid payment provider selected.';
        } elseif (!isset($_FILES['qr_image']) || $_FILES['qr_image']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload a QR code image.';
        } else {
            $uploadedFile = $_FILES['qr_image'];
            $fileExt = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($fileExt, $allowedExt, true)) {
                $error = 'Only JPG, JPEG, PNG, or WEBP images are allowed.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/payment_qr/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = strtolower($provider) . '_qr_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $fileExt;
                $destination = $uploadDir . $fileName;

                if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
                    $existingStmt = $db->prepare("SELECT image FROM payment_qr_codes WHERE provider = ?");
                    $existingStmt->execute([$provider]);
                    $existing = $existingStmt->fetch();

                    if ($existing) {
                        if (!empty($existing['image']) && file_exists($uploadDir . $existing['image'])) {
                            @unlink($uploadDir . $existing['image']);
                        }
                        $updateStmt = $db->prepare("UPDATE payment_qr_codes SET image = ?, updated_at = NOW() WHERE provider = ?");
                        $updateStmt->execute([$fileName, $provider]);
                    } else {
                        $insertStmt = $db->prepare("INSERT INTO payment_qr_codes (provider, image) VALUES (?, ?)");
                        $insertStmt->execute([$provider, $fileName]);
                    }

                    $msg = "$provider QR code uploaded successfully.";
                } else {
                    $error = 'Unable to save the uploaded QR code. Please try again.';
                }
            }
        }
    }
}

$qrCodes = [];
try {
    $qrStmt = $db->query("SELECT provider, image FROM payment_qr_codes WHERE provider IN ('GCash','Maya')");
    foreach ($qrStmt->fetchAll() as $row) {
        $qrCodes[$row['provider']] = $row['image'];
    }
} catch (PDOException $e) {
    $qrCodes = [];
}
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <h2 style="margin-bottom: 1.5rem;">Payment QR Code Manager</h2>

        <?php if ($msg): ?>
            <div style="background: #DCFCE7; color: #166534; padding: 0.9rem 1rem; border-radius: 12px; margin-bottom: 1rem;"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #FEE2E2; color: #B91C1C; padding: 0.9rem 1rem; border-radius: 12px; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <div class="glass-card">
                <h3>Upload QR Code</h3>
                <form action="payment_qr.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <div class="form-group">
                        <label>Provider</label>
                        <select name="provider" class="form-control" required>
                            <option value="">Select Provider</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>QR Code Image</label>
                        <input type="file" name="qr_image" accept="image/jpeg,image/png,image/webp" class="form-control" required>
                    </div>
                    <button type="submit" name="save_qr" class="btn btn-primary" style="width:100%;">Save QR Code</button>
                </form>
            </div>

            <div class="glass-card">
                <h3>Current QR Codes</h3>
                <?php foreach ($providers as $provider): ?>
                    <div style="margin-bottom: 1.25rem; padding: 1rem; border-radius: 18px; background: #f8fafc; border: 1px solid #e2e8f0;">
                        <h4 style="margin: 0 0 0.75rem;"><?= htmlspecialchars($provider) ?></h4>
                        <?php if (!empty($qrCodes[$provider])): ?>
                            <img src="/pickleball/uploads/payment_qr/<?= htmlspecialchars($qrCodes[$provider]) ?>" alt="<?= htmlspecialchars($provider) ?> QR" style="width:100%; max-width:320px; border-radius: 16px; display:block; margin-bottom:0.75rem;">
                            <p style="margin:0; color:#0f172a;">Uploaded at <?= date('F j, Y, g:i A', file_exists(__DIR__ . '/../uploads/payment_qr/' . $qrCodes[$provider]) ? filemtime(__DIR__ . '/../uploads/payment_qr/' . $qrCodes[$provider]) : time()) ?></p>
                        <?php else: ?>
                            <p style="margin:0; color:#475569;">No QR code uploaded yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
