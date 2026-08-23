<?php
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: /pickleball/auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/navbar.php';

$db = getDBConnection();
$msg = '';
$error = '';

// Add new staff user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            $error = 'Please fill in all required staff fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $existingStmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $existingStmt->execute([$email]);
            if ($existingStmt->fetch()) {
                $error = 'A user with that email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $db->prepare('INSERT INTO users (role_id, first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?, ?)');
                if ($insertStmt->execute([2, $firstName, $lastName, $email, $hashedPassword, $phone])) {
                    $msg = 'Staff user successfully added.';
                } else {
                    $error = 'Unable to create the staff user. Please try again.';
                }
            }
        }
    }
}

// Update existing staff user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($staffId <= 0 || empty($firstName) || empty($lastName) || empty($email)) {
            $error = 'Please fill in all required staff fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $existingStmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $existingStmt->execute([$email, $staffId]);
            if ($existingStmt->fetch()) {
                $error = 'Another user already uses that email address.';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = 'Password must be at least 6 characters long.';
                    }
                }

                if (empty($error)) {
                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, password = ? WHERE id = ? AND role_id = 2');
                        $updated = $updateStmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword, $staffId]);
                    } else {
                        $updateStmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ? AND role_id = 2');
                        $updated = $updateStmt->execute([$firstName, $lastName, $email, $phone, $staffId]);
                    }

                    if ($updated) {
                        $msg = 'Staff user updated successfully.';
                    } else {
                        $error = 'Unable to update staff user. Please try again.';
                    }
                }
            }
        }
    }
}

// Delete existing staff user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        if ($staffId <= 0) {
            $error = 'Staff user not found.';
        } else {
            $deleteStmt = $db->prepare('DELETE FROM users WHERE id = ? AND role_id = 2');
            if ($deleteStmt->execute([$staffId])) {
                $msg = 'Staff user deleted successfully.';
            } else {
                $error = 'Unable to delete staff user. Please try again.';
            }
        }
    }
}

$staffUsers = $db->query('SELECT id, first_name, last_name, email, phone, created_at FROM users WHERE role_id = 2 ORDER BY id DESC')->fetchAll();
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <h2 class="page-title">Staff User Management</h2>

        <div class="split-grid">
            <div class="glass-card panel-column">
                <div class="dashboard-card-header">
                    <div>
                        <h3>Add New Staff</h3>
                        <p class="dashboard-subtitle">Create a staff login with admin-managed user details.</p>
                    </div>
                </div>

                <form action="staff.php" method="POST" class="form-card">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" name="add_staff" class="btn btn-primary btn-block">Add Staff User</button>
                </form>
            </div>

            <div class="glass-card panel-column">
                <div class="dashboard-card-header">
                    <div>
                        <h3>Staff Accounts</h3>
                        <p class="dashboard-subtitle">View and edit all staff users from one place.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffUsers as $staff): ?>
                                <tr>
                                    <td><?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?></td>
                                    <td><?= htmlspecialchars($staff['email']) ?></td>
                                    <td><?= htmlspecialchars($staff['phone'] ?? '-') ?></td>
                                    <td><?= date('M j, Y', strtotime($staff['created_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-secondary btn-sm btn-edit-staff"
                                            data-staff-id="<?= $staff['id'] ?>"
                                            data-first-name="<?= htmlspecialchars($staff['first_name'], ENT_QUOTES) ?>"
                                            data-last-name="<?= htmlspecialchars($staff['last_name'], ENT_QUOTES) ?>"
                                            data-email="<?= htmlspecialchars($staff['email'], ENT_QUOTES) ?>"
                                            data-phone="<?= htmlspecialchars($staff['phone'] ?? '', ENT_QUOTES) ?>"
                                        >Edit</button>
                                        <button type="button" class="btn btn-danger btn-sm btn-delete-staff"
                                            data-staff-id="<?= $staff['id'] ?>"
                                            data-staff-name="<?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name'], ENT_QUOTES) ?>"
                                        >Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="editStaffModal" class="edit-modal" aria-hidden="true">
            <div class="edit-modal-panel">
                <button type="button" class="modal-close" aria-label="Close edit staff modal">&times;</button>
                <div class="dashboard-card-header">
                    <div>
                        <h3>Edit Staff User</h3>
                        <p class="dashboard-subtitle">Update staff details or reset password.</p>
                    </div>
                </div>
                <form action="staff.php" method="POST" id="editStaffForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <input type="hidden" name="staff_id" id="edit_staff_id">

                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" id="edit_first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" id="edit_last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" id="edit_phone">
                    </div>
                    <div class="form-group">
                        <label>New Password <small>(leave blank to keep current password)</small></label>
                        <input type="password" name="password" class="form-control" id="edit_password" placeholder="Enter new password if changing">
                    </div>
                    <button type="submit" name="update_staff" class="btn btn-primary btn-block">Save Changes</button>
                </form>
            </div>
        </div>

        <div id="deleteConfirmModal" class="status-modal delete-modal" aria-hidden="true">
            <div class="status-panel">
                <button type="button" class="modal-close" aria-label="Close delete confirmation">&times;</button>
                <div class="status-icon error">!</div>
                <h3>Confirm Delete</h3>
                <p class="modal-copy" id="deleteConfirmText">Are you sure you want to delete this staff user?</p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary btn-block btn-cancel-delete">Cancel</button>
                    <button type="button" class="btn btn-danger btn-block btn-confirm-delete">Delete User</button>
                </div>
                <form id="deleteStaffForm" method="POST" action="staff.php" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <input type="hidden" name="staff_id" id="delete_staff_id">
                    <input type="hidden" name="delete_staff" value="1">
                </form>
            </div>
        </div>

        <div id="statusModal" class="status-modal <?= $msg || $error ? 'is-open' : '' ?>" aria-hidden="<?= $msg || $error ? 'false' : 'true' ?>">
            <div class="status-panel">
                <button type="button" class="modal-close" aria-label="Close status notification">&times;</button>
                <div class="status-icon <?= $msg ? 'success' : 'error' ?>"><?= $msg ? '✓' : '&times;' ?></div>
                <h3><?= $msg ? 'Success' : 'Oops!' ?></h3>
                <p class="modal-copy"><?= htmlspecialchars($msg ?: $error) ?></p>
                <button type="button" class="btn btn-primary btn-block btn-status-close">Okay</button>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editStaffModal');
    const editButtons = document.querySelectorAll('.btn-edit-staff');
    const editClose = editModal && editModal.querySelector('.modal-close');
    const editForm = document.getElementById('editStaffForm');

    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteClose = deleteModal && deleteModal.querySelector('.modal-close');
    const deleteButtons = document.querySelectorAll('.btn-delete-staff');
    const cancelDelete = document.querySelector('.btn-cancel-delete');
    const confirmDelete = document.querySelector('.btn-confirm-delete');
    const deleteStaffForm = document.getElementById('deleteStaffForm');
    const deleteStaffIdInput = document.getElementById('delete_staff_id');

    const statusModal = document.getElementById('statusModal');
    const statusClose = statusModal && statusModal.querySelector('.modal-close');
    const statusOkay = document.querySelector('.btn-status-close');

    function openEditModal(data) {
        if (!editModal) return;
        editModal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        document.getElementById('edit_staff_id').value = data.id;
        document.getElementById('edit_first_name').value = data.firstName;
        document.getElementById('edit_last_name').value = data.lastName;
        document.getElementById('edit_email').value = data.email;
        document.getElementById('edit_phone').value = data.phone;
        document.getElementById('edit_password').value = '';
    }

    function closeEditModal() {
        if (!editModal) return;
        editModal.classList.remove('is-open');
        document.body.style.overflow = '';
        if (editForm) editForm.reset();
    }

    function openDeleteModal(data) {
        if (!deleteModal) return;
        deleteModal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        deleteStaffIdInput.value = data.id;
        document.getElementById('deleteConfirmText').textContent = `Delete staff user “${data.name}”? This action cannot be undone.`;
    }

    function closeDeleteModal() {
        if (!deleteModal) return;
        deleteModal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function closeStatusModal() {
        if (!statusModal) return;
        statusModal.classList.remove('is-open');
        statusModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (editButtons.length) {
        editButtons.forEach((button) => {
            button.addEventListener('click', () => {
                openEditModal({
                    id: button.dataset.staffId,
                    firstName: button.dataset.firstName,
                    lastName: button.dataset.lastName,
                    email: button.dataset.email,
                    phone: button.dataset.phone,
                });
            });
        });
    }

    if (editClose) {
        editClose.addEventListener('click', closeEditModal);
    }

    if (editModal) {
        editModal.addEventListener('click', (event) => {
            if (event.target === editModal) {
                closeEditModal();
            }
        });
    }

    if (deleteButtons.length) {
        deleteButtons.forEach((button) => {
            button.addEventListener('click', () => {
                openDeleteModal({
                    id: button.dataset.staffId,
                    name: button.dataset.staffName,
                });
            });
        });
    }

    if (deleteClose) {
        deleteClose.addEventListener('click', closeDeleteModal);
    }

    if (cancelDelete) {
        cancelDelete.addEventListener('click', closeDeleteModal);
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });
    }

    if (confirmDelete && deleteStaffForm) {
        confirmDelete.addEventListener('click', () => {
            deleteStaffForm.submit();
        });
    }

    if (statusModal && statusModal.classList.contains('is-open')) {
        statusModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    if (statusClose) {
        statusClose.addEventListener('click', closeStatusModal);
    }

    if (statusOkay) {
        statusOkay.addEventListener('click', closeStatusModal);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>