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

function uploadCourtImage(&$error)
{
    if (empty($_FILES['court_image']) || $_FILES['court_image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $fileTmp = $_FILES['court_image']['tmp_name'];
    $fileName = $_FILES['court_image']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed, true)) {
        $error = 'Invalid image format. Only JPG, JPEG, PNG, and WEBP files are allowed.';
        return null;
    }

    $imageName = 'court_' . time() . '_' . uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/courts/';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        $error = 'Unable to create court upload directory.';
        return null;
    }

    if (!move_uploaded_file($fileTmp, $uploadDir . $imageName)) {
        $error = 'Failed to upload court image.';
        return null;
    }

    return $imageName;
}

// Delete Court Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_court'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $courtId = (int)($_POST['court_id'] ?? 0);
        $deleteStmt = $db->prepare('DELETE FROM courts WHERE id = ?');
        if ($deleteStmt->execute([$courtId])) {
            $msg = 'Court removed successfully.';
        } else {
            $error = 'Unable to delete the selected court.';
        }
    } else {
        $error = 'Invalid security token.';
    }
}

// Update Court Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_court'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $courtId = (int)($_POST['court_id'] ?? 0);
        $name = sanitizeInput($_POST['court_name'] ?? '');
        $number = sanitizeInput($_POST['court_number'] ?? '');
        $type = sanitizeInput($_POST['type'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = (float)($_POST['price_per_hour'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'Available');

        $image_name = uploadCourtImage($error);

        if (empty($error)) {
            if ($image_name) {
                $stmt = $db->prepare('UPDATE courts SET court_name = ?, court_number = ?, type = ?, description = ?, price_per_hour = ?, status = ?, image = ? WHERE id = ?');
                $result = $stmt->execute([$name, $number, $type, $description, $price, $status, $image_name, $courtId]);
            } else {
                $stmt = $db->prepare('UPDATE courts SET court_name = ?, court_number = ?, type = ?, description = ?, price_per_hour = ?, status = ? WHERE id = ?');
                $result = $stmt->execute([$name, $number, $type, $description, $price, $status, $courtId]);
            }

            if ($result) {
                $msg = 'Court updated successfully.';
            } else {
                $error = 'Failed to update court record.';
            }
        }
    } else {
        $error = 'Invalid security token.';
    }
}

// Add Court Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_court'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = sanitizeInput($_POST['court_name'] ?? '');
        $number = sanitizeInput($_POST['court_number'] ?? '');
        $type = sanitizeInput($_POST['type'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = (float)($_POST['price_per_hour'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'Available');
        $image_name = 'default_court.jpg';

        $uploadedImage = uploadCourtImage($error);
        if ($uploadedImage) {
            $image_name = $uploadedImage;
        }

        if (empty($error)) {
            $stmt = $db->prepare("INSERT INTO courts (court_name, court_number, type, description, price_per_hour, status, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $number, $type, $description, $price, $status, $image_name])) {
                $msg = 'Court successfully added!';
            } else {
                $error = 'Failed to insert court record into the database.';
            }
        }
    } else {
        $error = 'Invalid security token.';
    }
}

$courts = $db->query("SELECT * FROM courts ORDER BY id DESC")->fetchAll();
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <h2 class="page-title">Court Facility Management</h2>
        

        <div class="split-grid">
            <div class="glass-card panel-column">
                <div class="dashboard-card-header">
                    <div>
                        <h3>Add New Court</h3>
                        <p class="dashboard-subtitle">Create a new court listing with hourly price, type, and availability.</p>
                    </div>
                </div>
                <form action="courts.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

                    <div class="form-group">
                        <label>Court Image</label>
                        <input type="file" name="court_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <div class="form-group">
                        <label>Court Name</label>
                        <input type="text" name="court_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Court Number/ID</label>
                        <input type="text" name="court_number" class="form-control" placeholder="e.g. CRT-04" required>
                    </div>

                    <div class="form-group">
                        <label>Environment</label>
                        <select name="type" class="form-control">
                            <option value="Indoor">Indoor</option>
                            <option value="Outdoor">Outdoor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Add a short description of the court"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Hourly Rate (₱)</label>
                        <input type="number" step="0.01" name="price_per_hour" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Available">Available</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Disabled">Disabled</option>
                        </select>
                    </div>

                    <button type="submit" name="add_court" class="btn btn-primary btn-block">Create Court</button>
                </form>
            </div>

            <div class="glass-card panel-column">
                <div class="dashboard-card-header">
                    <div>
                        <h3>Court Inventory</h3>
                        <p class="dashboard-subtitle">See all courts at a glance with actions for edit and delete.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Rate</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courts as $c): ?>
                                <tr>
                                    <td>
                                        <?php $img = !empty($c['image']) ? $c['image'] : 'default_court.jpg'; ?>
                                        <img src="/pickleball/uploads/courts/<?= htmlspecialchars($img) ?>" alt="Court Image" class="court-thumb" onerror="this.onerror=null; this.src='/pickleball/uploads/courts/default_court.jpg';">
                                    </td>
                                    <td><?= htmlspecialchars($c['court_name']) ?></td>
                                    <td><?= htmlspecialchars($c['court_number']) ?></td>
                                    <td><?= htmlspecialchars($c['type']) ?></td>
                                    <td>₱<?= number_format($c['price_per_hour'], 2) ?></td>
                                    <td><span class="status-pill <?= strtolower(htmlspecialchars($c['status'])) ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                                    <td>
                                        <div class="action-group">
                                            <button type="button" class="btn btn-secondary btn-sm btn-edit-court"
                                                data-court-id="<?= $c['id'] ?>"
                                                data-court-name="<?= htmlspecialchars($c['court_name'], ENT_QUOTES) ?>"
                                                data-court-number="<?= htmlspecialchars($c['court_number'], ENT_QUOTES) ?>"
                                                data-type="<?= htmlspecialchars($c['type'], ENT_QUOTES) ?>"
                                                data-description="<?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES) ?>"
                                                data-price="<?= htmlspecialchars($c['price_per_hour'], ENT_QUOTES) ?>"
                                                data-status="<?= htmlspecialchars($c['status'], ENT_QUOTES) ?>"
                                            >Edit</button>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-court"
                                                data-court-id="<?= $c['id'] ?>"
                                                data-court-name="<?= htmlspecialchars($c['court_name'], ENT_QUOTES) ?>"
                                            >Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="editCourtModal" class="edit-modal" aria-hidden="true">
            <div class="edit-modal-panel">
                <button type="button" class="modal-close" aria-label="Close edit court modal">&times;</button>
                <div class="dashboard-card-header">
                    <div>
                        <h3>Edit Court</h3>
                        <p class="dashboard-subtitle">Update court details and save changes.</p>
                    </div>
                </div>
                <form action="courts.php" method="POST" enctype="multipart/form-data" id="editCourtForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <input type="hidden" name="court_id" id="edit_court_id">

                    <div class="form-group">
                        <label>New Court Image</label>
                        <input type="file" name="court_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <div class="form-group">
                        <label>Court Name</label>
                        <input type="text" name="court_name" class="form-control" id="edit_court_name" required>
                    </div>

                    <div class="form-group">
                        <label>Court Number/ID</label>
                        <input type="text" name="court_number" class="form-control" id="edit_court_number" placeholder="e.g. CRT-04" required>
                    </div>

                    <div class="form-group">
                        <label>Environment</label>
                        <select name="type" class="form-control" id="edit_type">
                            <option value="Indoor">Indoor</option>
                            <option value="Outdoor">Outdoor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" id="edit_description" rows="3" placeholder="Update court description"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Hourly Rate (₱)</label>
                        <input type="number" step="0.01" name="price_per_hour" class="form-control" id="edit_price_per_hour" required>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" id="edit_status">
                            <option value="Available">Available</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Disabled">Disabled</option>
                        </select>
                    </div>

                    <button type="submit" name="update_court" class="btn btn-primary btn-block">Save Changes</button>
                </form>
            </div>
        </div>

        <div id="deleteConfirmModal" class="status-modal delete-modal" aria-hidden="true">
            <div class="status-panel">
                <button type="button" class="modal-close" aria-label="Close delete confirmation">&times;</button>
                <div class="status-icon error">!</div>
                <h3>Confirm Delete</h3>
                <p class="modal-copy" id="deleteConfirmText">Are you sure you want to delete this court?</p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary btn-block btn-cancel-delete">Cancel</button>
                    <button type="button" class="btn btn-danger btn-block btn-confirm-delete">Delete Court</button>
                </div>
                <form id="deleteCourtForm" method="POST" action="courts.php" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <input type="hidden" name="court_id" id="delete_court_id">
                    <input type="hidden" name="delete_court" value="1">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>