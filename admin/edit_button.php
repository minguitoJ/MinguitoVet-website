<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

/* =========================================================
   SERVICES CRUD
   Table: services
   Columns:
   id, name, description, status, created_at
   ========================================================= */

$message = '';
$message_type = '';

// ---------------------------------------------------------
// HANDLE ADD / EDIT / DELETE / STATUS
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ADD SERVICE
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if ($name === '') {
            $message = 'Please enter a service name.';
            $message_type = 'error';
        } elseif (!in_array($status, ['Active', 'Inactive'], true)) {
            $message = 'Invalid service status.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO services (name, description, status)
                    VALUES (:name, :description, :status)
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                    ':status' => $status
                ]);

                header('Location: services.php?success=added');
                exit;
            } catch (PDOException $e) {
                $message = 'Unable to add the service.';
                $message_type = 'error';
            }
        }
    }

    // EDIT SERVICE
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if ($id <= 0) {
            $message = 'Invalid service.';
            $message_type = 'error';
        } elseif ($name === '') {
            $message = 'Please enter a service name.';
            $message_type = 'error';
        } elseif (!in_array($status, ['Active', 'Inactive'], true)) {
            $message = 'Invalid service status.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE services
                    SET name = :name,
                        description = :description,
                        status = :status
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                    ':status' => $status,
                    ':id' => $id
                ]);

                header('Location: services.php?success=updated');
                exit;
            } catch (PDOException $e) {
                $message = 'Unable to update the service.';
                $message_type = 'error';
            }
        }
    }

    // DELETE SERVICE
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $message = 'Invalid service.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM services WHERE id = :id");
                $stmt->execute([':id' => $id]);

                header('Location: services.php?success=deleted');
                exit;
            } catch (PDOException $e) {
                $message = 'Unable to delete the service.';
                $message_type = 'error';
            }
        }
    }
}

// ---------------------------------------------------------
// SUCCESS MESSAGES
// ---------------------------------------------------------
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $message = 'Service added successfully.';
            $message_type = 'success';
            break;

        case 'updated':
            $message = 'Service updated successfully.';
            $message_type = 'success';
            break;

        case 'deleted':
            $message = 'Service deleted successfully.';
            $message_type = 'success';
            break;
    }
}

// ---------------------------------------------------------
// SEARCH
// ---------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$sql = "
    SELECT id, name, description, status, created_at
    FROM services
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            name LIKE :search
            OR description LIKE :search
        )
    ";
    $params[':search'] = '%' . $search . '%';
}

if (in_array($status_filter, ['Active', 'Inactive'], true)) {
    $sql .= " AND status = :status_filter";
    $params[':status_filter'] = $status_filter;
}

$sql .= " ORDER BY status ASC, name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------
// SERVICE COUNTS
// ---------------------------------------------------------
$totalServices = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$activeServices = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE status = 'Active'")->fetchColumn();
$inactiveServices = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE status = 'Inactive'")->fetchColumn();

$adminUsername = $_SESSION['admin_username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services | Minguito Veterinary Clinic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f6f1e8;
            color: #263b32;
            min-height: 100vh;
        }

        .admin-header {
            background: #173d32;
            color: #fff;
            padding: 18px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 18px rgba(23, 61, 50, .15);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            border-radius: 50%;
            background: #d5aa5c;
            color: #173d32;
            display: grid;
            place-items: center;
            font-size: 21px;
            font-weight: 700;
        }

        .brand-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            line-height: 1.1;
        }

        .brand-text span {
            display: block;
            font-size: 11px;
            opacity: .75;
            margin-top: 3px;
            letter-spacing: .5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-name {
            font-size: 14px;
            color: #f4ead8;
        }

        .logout-btn {
            text-decoration: none;
            color: #173d32;
            background: #d5aa5c;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .page {
            width: min(1250px, 92%);
            margin: 38px auto 60px;
        }

        .page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .eyebrow {
            color: #a47b35;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-heading h2 {
            font-family: 'Playfair Display', serif;
            color: #173d32;
            font-size: 34px;
        }

        .page-heading p {
            color: #6c756f;
            margin-top: 6px;
            font-size: 14px;
        }

        .add-btn {
            border: 0;
            cursor: pointer;
            background: #173d32;
            color: #fff;
            padding: 12px 18px;
            border-radius: 9px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 5px 15px rgba(23, 61, 50, .18);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 17px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e9dfcf;
            border-radius: 14px;
            padding: 19px 21px;
            box-shadow: 0 5px 18px rgba(70, 53, 30, .05);
        }

        .stat-label {
            color: #7b817c;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
            font-weight: 700;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #173d32;
            margin-top: 5px;
        }

        .success,
        .error {
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 600;
        }

        .success {
            background: #e7f3eb;
            color: #22613d;
            border: 1px solid #bcdcc8;
        }

        .error {
            background: #fbe9e7;
            color: #a33a30;
            border: 1px solid #efc1bc;
        }

        .filters {
            background: #fff;
            border: 1px solid #e9dfcf;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
            box-shadow: 0 5px 18px rgba(70, 53, 30, .05);
        }

        .filters input,
        .filters select {
            border: 1px solid #dcd2c2;
            background: #fcfaf6;
            color: #263b32;
            padding: 11px 12px;
            border-radius: 8px;
            font-family: inherit;
            outline: none;
        }

        .filters input {
            flex: 1;
            min-width: 180px;
        }

        .filters input:focus,
        .filters select:focus {
            border-color: #a47b35;
        }

        .filter-btn,
        .clear-btn {
            border: 0;
            padding: 11px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            text-decoration: none;
            font-size: 13px;
        }

        .filter-btn {
            background: #173d32;
            color: #fff;
        }

        .clear-btn {
            background: #eee7dc;
            color: #46544d;
        }

        .table-card {
            background: #fff;
            border: 1px solid #e9dfcf;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(70, 53, 30, .05);
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #f1e8d9;
            color: #496055;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .8px;
            text-align: left;
            padding: 14px 16px;
            white-space: nowrap;
        }

        td {
            padding: 15px 16px;
            border-top: 1px solid #eee8de;
            vertical-align: middle;
            font-size: 14px;
        }

        tr:hover td {
            background: #fcfaf6;
        }

        .service-name {
            font-weight: 700;
            color: #173d32;
        }

        .description {
            max-width: 390px;
            color: #6f7772;
            line-height: 1.5;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
        }

        .status::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-active {
            background: #e6f3e9;
            color: #2b7748;
        }

        .status-inactive {
            background: #eeeae5;
            color: #77736d;
        }

        .actions {
            display: flex;
            gap: 7px;
            white-space: nowrap;
        }

        .action-btn {
            border: 0;
            cursor: pointer;
            border-radius: 7px;
            padding: 8px 11px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
        }

        .edit-btn {
            background: #f2e7cf;
            color: #806025;
        }

        .delete-btn {
            background: #f8e3e0;
            color: #a23b32;
        }

        .empty {
            padding: 50px 20px;
            text-align: center;
            color: #7b817c;
        }

        .empty-icon {
            font-size: 35px;
            margin-bottom: 10px;
        }

        /* MODAL */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 35, 28, .55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-box {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .2);
        }

        .modal-box h3 {
            font-family: 'Playfair Display', serif;
            color: #173d32;
            font-size: 25px;
            margin-bottom: 5px;
        }

        .modal-box > p {
            color: #777;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            color: #41544b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            border: 1px solid #dcd2c2;
            background: #fcfaf6;
            border-radius: 8px;
            padding: 11px 12px;
            font-family: inherit;
            color: #263b32;
            outline: none;
        }

        .form-group textarea {
            min-height: 105px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #a47b35;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            margin-top: 20px;
        }

        .cancel-btn,
        .save-btn {
            border: 0;
            border-radius: 8px;
            padding: 11px 17px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
        }

        .cancel-btn {
            background: #eee7dc;
            color: #4d5751;
        }

        .save-btn {
            background: #173d32;
            color: #fff;
        }

        @media (max-width: 760px) {
            .admin-header {
                padding: 14px 4%;
            }

            .admin-name {
                display: none;
            }

            .page {
                width: 94%;
                margin-top: 25px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .page-heading h2 {
                font-size: 29px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .filters {
                flex-direction: column;
            }

            .filters input,
            .filters select {
                width: 100%;
            }
        }
    </style>
</head>
<body class="admin-with-sidebar">
<?php include 'sidebar.php'; ?>

<header class="admin-header">
    <div class="brand">
        <div class="brand-icon">🐾</div>
        <div class="brand-text">
            <h1>Minguito Veterinary</h1>
            <span>ADMINISTRATION PANEL</span>
        </div>
    </div>

    <div class="header-right">
        <span class="admin-name">
            Welcome, <?= htmlspecialchars($adminUsername) ?>
        </span>

        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</header>

<main class="page admin-content">

    <div class="page-heading">
        <div>
            <div class="eyebrow">Clinic Management</div>
            <h2>Services</h2>
            <p>Manage the veterinary services available to your customers.</p>
        </div>

        <button type="button" class="add-btn" onclick="openAddModal()">
            + Add Service
        </button>
    </div>

    <?php if ($message !== ''): ?>
        <div class="<?= $message_type === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-label">Total Services</div>
            <div class="stat-number"><?= $totalServices ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Active Services</div>
            <div class="stat-number"><?= $activeServices ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Inactive Services</div>
            <div class="stat-number"><?= $inactiveServices ?></div>
        </div>
    </section>

    <form class="filters" method="GET">
        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Search service name or description..."
        >

        <select name="status">
            <option value="">All Status</option>
            <option value="Active" <?= $status_filter === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $status_filter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>

        <button type="submit" class="filter-btn">Search</button>

        <?php if ($search !== '' || $status_filter !== ''): ?>
            <a href="services.php" class="clear-btn">Clear</a>
        <?php endif; ?>
    </form>

    <section class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty">
                                <div class="empty-icon">🐾</div>
                                <strong>No services found.</strong>
                                <p>Try another search or add a new service.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td>#<?= (int)$service['id'] ?></td>

                            <td>
                                <div class="service-name">
                                    <?= htmlspecialchars($service['name']) ?>
                                </div>
                            </td>

                            <td>
                                <div class="description">
                                    <?= $service['description']
                                        ? htmlspecialchars($service['description'])
                                        : '<span style="color:#aaa;">No description</span>' ?>
                                </div>
                            </td>

                            <td>
                                <span class="status <?= $service['status'] === 'Active' ? 'status-active' : 'status-inactive' ?>">
                                    <?= htmlspecialchars($service['status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= date('M d, Y', strtotime($service['created_at'])) ?>
                            </td>

                            <td>
                                <div class="actions">
                                    <button
                                        type="button"
                                        class="action-btn edit-btn js-edit-service"
                                        data-id="<?= (int)$service['id'] ?>"
                                        data-name="<?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-description="<?= htmlspecialchars($service['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-status="<?= htmlspecialchars($service['status'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        Edit
                                    </button>

                                    <form method="POST" onsubmit="return confirmDelete('<?= htmlspecialchars(addslashes($service['name'])) ?>');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$service['id'] ?>">
                                        <button type="submit" class="action-btn delete-btn">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<!-- ADD / EDIT MODAL -->
<div class="modal" id="serviceModal">
    <div class="modal-box">
        <h3 id="modalTitle">Add Service</h3>
        <p id="modalSubtitle">Create a new veterinary service.</p>

        <form method="POST" id="serviceForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="serviceId" value="">

            <div class="form-group">
                <label for="serviceName">Service Name</label>
                <input
                    type="text"
                    id="serviceName"
                    name="name"
                    maxlength="150"
                    required
                    placeholder="e.g. Consultation & Check-up"
                >
            </div>

            <div class="form-group">
                <label for="serviceDescription">Description</label>
                <textarea
                    id="serviceDescription"
                    name="description"
                    placeholder="Enter a short description of the service..."
                ></textarea>
            </div>

            <div class="form-group">
                <label for="serviceStatus">Status</label>
                <select id="serviceStatus" name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeModal()">
                    Cancel
                </button>

                <button type="submit" class="save-btn">
                    Save Service
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('serviceModal');
    const formAction = document.getElementById('formAction');
    const serviceId = document.getElementById('serviceId');
    const serviceName = document.getElementById('serviceName');
    const serviceDescription = document.getElementById('serviceDescription');
    const serviceStatus = document.getElementById('serviceStatus');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');

    function openAddModal() {
        formAction.value = 'add';
        serviceId.value = '';
        serviceName.value = '';
        serviceDescription.value = '';
        serviceStatus.value = 'Active';

        modalTitle.textContent = 'Add Service';
        modalSubtitle.textContent = 'Create a new veterinary service.';

        modal.classList.add('show');
        serviceName.focus();
    }

    function openEditModal(id, name, description, status) {
        formAction.value = 'edit';
        serviceId.value = id;
        serviceName.value = name || '';
        serviceDescription.value = description || '';
        serviceStatus.value = status || 'Active';

        modalTitle.textContent = 'Edit Service';
        modalSubtitle.textContent = 'Update the information for this service.';

        modal.classList.add('show');
        serviceName.focus();
    }

    function closeModal() {
        modal.classList.remove('show');
    }

    function confirmDelete(name) {
        return confirm(
            'Delete "' + name + '"?\n\n' +
            'This service will be permanently removed from the services table.'
        );
    }

    // EDIT BUTTONS
    document.querySelectorAll('.js-edit-service').forEach(function (button) {
        button.addEventListener('click', function () {
            openEditModal(
                this.dataset.id,
                this.dataset.name,
                this.dataset.description,
                this.dataset.status
            );
        });
    });

    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
</script>

</body>
</html>
