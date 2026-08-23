<?php
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: /pickleball/auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/navbar.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF security token.';
    } else {
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Fetch User Info
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch();

        // Basic name validation
        if (empty($firstName) || empty($lastName)) {
            $error = 'Please enter both first name and last name.';
        }

        // Handle Avatar Upload
        if (empty($error) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['profile_pic']['tmp_name'];
            $fileName = $_FILES['profile_pic']['name'];
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $avatarName = 'user_' . $userId . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/profiles/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                if (move_uploaded_file($fileTmp, $uploadDir . $avatarName)) {
                    $updatePic = $db->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                    $updatePic->execute([$avatarName, $userId]);
                } else {
                    $error = 'Failed to upload profile picture.';
                }
            } else {
                $error = 'Invalid image type. Only JPG, PNG, and WEBP formats are allowed.';
            }
        }

        $requiresPassword = (
            $firstName !== $currentUser['first_name'] ||
            $lastName !== $currentUser['last_name'] ||
            $email !== $currentUser['email'] ||
            $phone !== $currentUser['phone']
        );

        if (empty($error) && $requiresPassword) {
            if (empty($currentPassword)) {
                $error = 'Current password is required to save profile changes.';
            } elseif (!password_verify($currentPassword, $currentUser['password'])) {
                $error = 'Current password is incorrect.';
            }
        }

        if (empty($error) && !empty($newPassword)) {
            if (empty($currentPassword)) {
                $error = 'Current password is required to change your password.';
            } elseif (!password_verify($currentPassword, $currentUser['password'])) {
                $error = 'Current password is incorrect.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters long.';
            }
        }

        if (empty($error) && empty($newPassword) && !$requiresPassword) {
            $success = 'No changes were made.';
        }

        if (empty($error)) {
            if ($requiresPassword) {
                $updateUser = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
                $updateUser->execute([$firstName, $lastName, $email, $phone, $userId]);
            }

            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePass = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updatePass->execute([$hashedPassword, $userId]);
            }

            if (empty($error)) {
                $_SESSION['user_name'] = trim($firstName . ' ' . $lastName);
                $success = 'Profile successfully updated!';
            }
        }
    }
}

// Reload user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$profilePic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default_avatar.jpg';
?>

<div class="dashboard-container">
    <main class="main-content fade-in">
        <div class="profile-shell">
            <div class="profile-header">
                <div>
                    <span class="eyebrow">Account</span>
                    <h2>Personal Profile</h2>
                    <p class="subtext">Your account hub for avatar updates, contact information, and secure login credentials. Everything is designed to feel modern, calm, and easy to manage.</p>
                </div>
                <div class="profile-chip">Customer Portal</div>
            </div>

            <section class="profile-highlight-card glass-card">
                <div class="highlight-left">
                    <div class="avatar-frame">
                        <img src="/pickleball/uploads/profiles/<?= htmlspecialchars($profilePic) ?>" 
                             alt="Profile Avatar" 
                             id="avatar_preview" 
                             class="profile-avatar"
                             onerror="this.onerror=null; this.src='/pickleball/uploads/profiles/default_avatar.jpg';">
                    </div>
                    <div class="avatar-details">
                        <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                        <p>Update your photo and contact details to keep your booking profile fresh and memorable.</p>
                    </div>
                    <label for="profile_pic" class="avatar-action">Upload New Avatar</label>
                    <input type="file" name="profile_pic" id="profile_pic" accept="image/jpeg,image/png,image/webp" style="display: none;">
                </div>

                <div class="highlight-right">
                    <div class="profile-stat-grid">
                        <div class="profile-stat">
                            <span>Phone</span>
                            <strong><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></strong>
                        </div>
                        <div class="profile-stat">
                            <span>Member since</span>
                            <strong><?= date('F Y', strtotime($user['created_at'] ?? 'now')) ?></strong>
                        </div>
                        <div class="profile-completion">
                            <span>Profile completion</span>
                            <div class="completion-score">
                                <strong>82%</strong>
                                <span>Strong</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>
                    </div>
                    <div class="profile-note">Tip: Keep your email and phone number up to date so we can send booking confirmations and reminders directly to you.</div>
                </div>
            </section>

            <form action="profile.php" method="POST" enctype="multipart/form-data" class="profile-form-card">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

                <section class="profile-section">
                    <div class="section-header">
                        <span>01</span>
                        <h3>Basic details</h3>
                    </div>
                    <div class="profile-field-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="profile-field-grid">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                    </div>
                </section>

                <section class="profile-section">
                    <div class="section-header">
                        <span>02</span>
                        <h3>Security settings</h3>
                    </div>
                    <p>Enter your current password to confirm profile updates. Only fill in the new password fields if you want to change your login.</p>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control">
                    </div>
                    <button type="button" id="toggle_password_fields" class="btn btn-secondary" style="margin-bottom: 1rem;">Show password fields</button>
                    <div id="password_fields" style="display: none;">
                        <div class="profile-field-grid">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                            </div>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>

            <div id="profile_toast_backdrop" class="toast-backdrop" style="display:none;"></div>
            <div id="profile_toast" class="success-toast" style="display:none;">
                <div class="toast-checkmark" id="toast_icon">✓</div>
                <h3 id="toast_title"></h3>
                <p class="toast-description" id="toast_message"></p>
                <button id="toast_ok_button" class="toast-ok-button" type="button">OK</button>
            </div>
        </div>
    </main>
</div>

<script>
function showProfileToast(type, title, message) {
    const backdrop = document.getElementById('profile_toast_backdrop');
    const toast = document.getElementById('profile_toast');
    const icon = document.getElementById('toast_icon');
    const titleEl = document.getElementById('toast_title');
    const messageEl = document.getElementById('toast_message');
    const okButton = document.getElementById('toast_ok_button');

    toast.classList.toggle('error', type === 'error');
    icon.textContent = type === 'error' ? '✕' : '✓';
    titleEl.textContent = title;
    messageEl.textContent = message;

    backdrop.style.display = 'block';
    toast.style.display = 'flex';
    requestAnimationFrame(() => {
        backdrop.classList.add('visible');
        toast.classList.add('toast-show');
    });

    function hideToast() {
        backdrop.classList.remove('visible');
        toast.classList.remove('toast-show');
        toast.addEventListener('transitionend', () => {
            toast.style.display = 'none';
            backdrop.style.display = 'none';
        }, { once: true });
    }

    okButton.onclick = hideToast;
    backdrop.onclick = hideToast;
}

document.getElementById('profile_pic').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('avatar_preview').src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});

const togglePasswordFieldsButton = document.getElementById('toggle_password_fields');
const passwordFields = document.getElementById('password_fields');
if (togglePasswordFieldsButton && passwordFields) {
    togglePasswordFieldsButton.addEventListener('click', () => {
        const isHidden = passwordFields.style.display === 'none' || passwordFields.style.display === '';
        passwordFields.style.display = isHidden ? 'block' : 'none';
        togglePasswordFieldsButton.textContent = isHidden ? 'Hide Password Fields' : 'Change Password';
    });
}

<?php if (!empty($success)): ?>
showProfileToast('success', 'Saved Successfully', <?= json_encode($success) ?>);
<?php elseif (!empty($error)): ?>
showProfileToast('error', 'Update Failed', <?= json_encode($error) ?>);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>