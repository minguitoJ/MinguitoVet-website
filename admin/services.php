<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

/* =========================================================
   SERVICES CRUD
   Table: services
   Columns:
   id, name, description, price, status, created_at
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
        $price = trim($_POST['price'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if ($name === '') {
            $message = 'Please enter a service name.';
            $message_type = 'error';
        } elseif ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $message = 'Please enter a valid service price.';
            $message_type = 'error';
        } elseif (!in_array($status, ['Active', 'Inactive'], true)) {
            $message = 'Invalid service status.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO services (name, description, price, status)
                    VALUES (:name, :description, :price, :status)
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                    ':price' => number_format((float)$price, 2, '.', ''),
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
        $price = trim($_POST['price'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if ($id <= 0) {
            $message = 'Invalid service.';
            $message_type = 'error';
        } elseif ($name === '') {
            $message = 'Please enter a service name.';
            $message_type = 'error';
        } elseif ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $message = 'Please enter a valid service price.';
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
                        price = :price,
                        status = :status
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                    ':price' => number_format((float)$price, 2, '.', ''),
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
    SELECT id, name, description, price, status, created_at
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


    <style>
        :root {
            --green: #2f6b4f;
            --green-dark: #24543e;
            --green-soft: #e8f2eb;
            --cream: #f8f1e5;
            --gold: #c89b3c;
            --gold-light: #d4a15c;
            --white: #ffffff;
            --text: #26352d;
            --muted: #7b877f;
            --border: #e4e8e3;
            --bg: #f5f6f2;
            --success: #14633f;
            --warning: #a66a16;
            --danger: #a13d32;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { min-height: 100%; }

        body {
            min-height: 100vh;
            font-family: "DM Sans", Arial, sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        /* ===============================
           SHARED ADMIN HEADER
        =============================== */
        .admin-header {
            background: var(--green);
            color: white;
            padding: 18px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,.12);
        }

        .header-inner {
            width: min(1180px, 92%);
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            border-radius: 14px;
            background: #f8efe2;
            border: 1px solid rgba(212,161,92,.35);
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(0,0,0,.12);
        }

        .brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-text strong {
            display: block;
            font-size: 16px;
            font-weight: 800;
        }

        .brand-text span {
            display: block;
            margin-top: 2px;
            color: #d7e3dd;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .header-links {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-link {
            padding: 9px 14px;
            border-radius: 9px;
            color: white;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            transition: .2s ease;
        }

        .header-link:hover { background: rgba(255,255,255,.1); }
        .header-link.logout { background: var(--gold); }
        .header-link.logout:hover { background: var(--gold-light); }

        /* ===============================
           MAIN / PAGE TITLE
        =============================== */
        .main { padding: 40px 0 60px; }
        .container { width: min(1180px, 92%); margin: auto; }

        .page-title {
            margin-bottom: 28px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
        }

        .section-label {
            color: var(--gold);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .page-title h1 {
            color: var(--green);
            font: 700 36px/1.1 "Playfair Display", Georgia, serif;
            margin-bottom: 8px;
        }

        .page-title p { color: var(--muted); font-size: 14px; }

        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
            padding: 0 17px;
            border: 0;
            border-radius: 10px;
            background: var(--green);
            color: white;
            font: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(7,59,42,.14);
            white-space: nowrap;
        }

        .primary-btn:hover { background: var(--gold); }

        /* ===============================
           ALERTS
        =============================== */
        .alert {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert.success { background:#e7f3e9; color:#387346; border:1px solid #c8e1ce; }
        .alert.error { background:#f8e7e5; color:#a13c32; border:1px solid #ecc5c0; }

        /* ===============================
           STAT CARDS
        =============================== */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }

        .stat {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 19px;
            box-shadow: 0 10px 28px rgba(40, 65, 50, .045);
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }

        .stat-number { color: var(--green); font-size: 28px; font-weight: 800; }
        .stat-number.warning { color: var(--warning); }

        /* ===============================
           FILTER BOX
        =============================== */
        .filter-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 28px rgba(40, 65, 50, .045);
            margin-bottom: 22px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1.8fr 1fr auto auto;
            gap: 11px;
            align-items: end;
        }

        .field label {
            display: block;
            color: var(--green);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .field input, .field select {
            width: 100%;
            height: 44px;
            padding: 0 12px;
            border: 1px solid #dccab4;
            border-radius: 10px;
            background: #fffaf3;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            outline: none;
        }

        .field input:focus, .field select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(181,122,47,.1);
        }

        .filter-btn, .clear-btn {
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            border-radius: 10px;
            font: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
        }

        .filter-btn { border:0; background:var(--green); color:white; }
        .filter-btn:hover { background:var(--gold); }
        .clear-btn { border:1px solid var(--border); background:white; color:var(--green); }
        .clear-btn:hover { background:var(--cream); }

        /* ===============================
           TABLE
        =============================== */
        .table-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 28px rgba(40, 65, 50, .045);
        }

        .table-header {
            padding: 19px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }

        .table-header h2 { color:var(--green); font-size:18px; font-weight:800; }
        .result-count { color:var(--muted); font-size:12px; }
        .table-wrapper { overflow-x:auto; }

        table { width:100%; border-collapse:collapse; min-width:980px; }

        th {
            padding:14px 16px;
            background:#fcf7ef;
            color:var(--muted);
            text-align:left;
            font-size:10px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.6px;
            white-space:nowrap;
        }

        td {
            padding:15px 16px;
            border-top:1px solid #eee3d4;
            font-size:12px;
            vertical-align:middle;
        }

        tbody tr:hover { background:#fffbf5; }

        .item-id { color:var(--muted); font-weight:700; }
        .item-name { color:var(--green); font-size:14px; font-weight:800; }
        .description { max-width:390px; color:var(--muted); line-height:1.45; }
        .price { color:var(--green); font-weight:800; white-space:nowrap; }

        .status {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:6px 10px;
            border-radius:30px;
            font-size:10px;
            font-weight:800;
        }

        .status::before { content:''; width:7px; height:7px; border-radius:50%; background:currentColor; }
        .status-active { background:#e5f2e8; color:var(--green); }
        .status-inactive { background:#eeeae5; color:#77736d; }

        .stock { font-weight:800; white-space:nowrap; }
        .stock-ok { color:var(--success); }
        .stock-low { color:var(--warning); }
        .stock-out { color:var(--danger); }
        .stock-note { display:block; margin-top:3px; color:var(--muted); font-size:10px; font-weight:500; }

        .actions { display:flex; align-items:center; gap:7px; white-space:nowrap; }
        .action-btn {
            height:34px;
            padding:0 10px;
            border:0;
            border-radius:8px;
            font-family:inherit;
            font-size:10px;
            font-weight:800;
            cursor:pointer;
        }
        .edit-btn { background:#f2e7cf; color:#806025; }
        .delete-btn { background:#f8e3e0; color:var(--danger); }
        .edit-btn:hover, .delete-btn:hover { filter:brightness(.97); }

        .empty { padding:55px 20px; text-align:center; color:var(--muted); font-size:14px; }
        .empty-icon { font-size:35px; margin-bottom:10px; }

        /* ===============================
           MODALS
        =============================== */
        .modal {
            position:fixed;
            inset:0;
            background:rgba(7,59,42,.55);
            display:none;
            align-items:center;
            justify-content:center;
            padding:20px;
            z-index:1000;
        }
        .modal.show { display:flex; }
        .modal-box {
            width:min(560px,100%);
            max-height:92vh;
            overflow-y:auto;
            background:white;
            border:1px solid var(--border);
            border-radius:18px;
            padding:26px;
            box-shadow:0 18px 50px rgba(0,0,0,.2);
        }
        .modal-box h3 { color:var(--green); font:700 25px/1.1 "Playfair Display",Georgia,serif; margin-bottom:6px; }
        .modal-box > p { color:var(--muted); font-size:13px; margin-bottom:20px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .form-group { margin-bottom:15px; }
        .form-group label {
            display:block;
            color:var(--green);
            font-size:11px;
            font-weight:800;
            margin-bottom:7px;
            text-transform:uppercase;
            letter-spacing:.4px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width:100%;
            border:1px solid #dccab4;
            border-radius:10px;
            background:#fffaf3;
            color:var(--text);
            font:inherit;
            font-size:13px;
            padding:11px 12px;
            outline:none;
        }
        .form-group input, .form-group select { height:44px; }
        .form-group textarea { min-height:90px; resize:vertical; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color:var(--gold);
            box-shadow:0 0 0 3px rgba(181,122,47,.1);
        }
        .input-help { display:block; color:var(--muted); font-size:10px; margin-top:5px; line-height:1.4; }
        .modal-actions { display:flex; justify-content:flex-end; gap:9px; margin-top:18px; }
        .cancel-btn, .save-btn {
            height:42px;
            padding:0 17px;
            border:0;
            border-radius:10px;
            font-family:inherit;
            font-size:12px;
            font-weight:800;
            cursor:pointer;
        }
        .cancel-btn { background:#eee6da; color:#4d5751; }
        .save-btn { background:var(--green); color:white; }
        .save-btn:hover { background:var(--gold); }

        @media (max-width:950px) {
            .stats { grid-template-columns:repeat(2,1fr); }
            .filter-form { grid-template-columns:1fr 1fr; }
        }
        @media (max-width:600px) {
            .header-inner { align-items:flex-start; }
            .header-links { flex-direction:column; align-items:stretch; }
            .header-link { text-align:center; }
            .main { padding-top:28px; }
            .page-title { align-items:flex-start; flex-direction:column; }
            .page-title h1 { font-size:31px; }
            .stats { grid-template-columns:1fr; }
            .filter-form { grid-template-columns:1fr; }
            .form-row { grid-template-columns:1fr; }
        }
    
        /* =====================================================
           DASHBOARD COLOR PALETTE
           Keep this page visually consistent with admin/dashboard.php
        ====================================================== */
        :root {
            --green: #2f6b4f;
            --green-dark: #24543e;
            --green-soft: #e8f2eb;
            --gold: #c89b3c;
            --cream: #f8f1e5;
            --bg: #f5f6f2;
            --text: #26352d;
            --muted: #7b877f;
            --border: #e4e8e3;
            --white: #ffffff;
        }

        body {
            background: var(--bg);
            color: var(--text);
        }

        .admin-content,
        .admin-main,
        .patients-main {
            color: var(--text);
        }
</style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

    <style>
        /* Shared admin dashboard layout */
        .admin-content {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
            padding: 34px 38px 50px;
            box-sizing: border-box;
        }
        .admin-content > .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }
        .admin-content .container {
            width: 100%;
            max-width: none;
        }

        @media (max-width: 980px) {
            .admin-content {
                margin-left: 0;
                width: 100%;
                padding: 82px 20px 40px;
            }
        }
        @media (max-width: 600px) {
            .admin-content {
                padding: 78px 15px 35px;
            }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>


<main class="main admin-content">
    <div class="container">
        <section class="page-title">
            <div>
                <p class="section-label">Admin Panel</p>
                <h1>Services</h1>
                <p>Manage the veterinary services available to your customers.</p>
            </div>
            <button type="button" class="primary-btn" onclick="openAddModal()">+ Add Service</button>
        </section>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $message_type === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat"><div class="stat-label">Total Services</div><div class="stat-number"><?= $totalServices ?></div></div>
            <div class="stat"><div class="stat-label">Active Services</div><div class="stat-number"><?= $activeServices ?></div></div>
            <div class="stat"><div class="stat-label">Inactive Services</div><div class="stat-number"><?= $inactiveServices ?></div></div>
        </section>

        <section class="filter-card">
            <form method="GET" action="services.php" class="filter-form">
                <div class="field">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Service name or description...">
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="Active" <?= $status_filter === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $status_filter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="filter-btn">Search</button>
                <a href="services.php" class="clear-btn">Clear</a>
            </form>
        </section>

        <section class="table-card">
            <div class="table-header">
                <h2>Service Records</h2>
                <span class="result-count"><?= count($services) ?> result<?= count($services) === 1 ? '' : 's' ?></span>
            </div>

            <?php if (!empty($services)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr>
                            <th>ID</th><th>Service</th><th>Description</th><th>Price</th><th>Status</th><th>Created</th><th>Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><span class="item-id">#<?= (int)$service['id'] ?></span></td>
                                <td><div class="item-name"><?= htmlspecialchars($service['name']) ?></div></td>
                                <td><div class="description"><?= $service['description'] ? htmlspecialchars($service['description']) : 'No description.' ?></div></td>
                                <td><span class="price">₱<?= number_format((float)$service['price'], 2) ?></span></td>
                                <td><span class="status <?= $service['status'] === 'Active' ? 'status-active' : 'status-inactive' ?>"><?= htmlspecialchars($service['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($service['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <button type="button" class="action-btn edit-btn" onclick='openEditModal(
                                            <?= (int)$service["id"] ?>,
                                            <?= json_encode($service["name"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                                            <?= json_encode($service["description"] ?? "", JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                                            <?= json_encode((float)$service["price"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                                            <?= json_encode($service["status"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
                                        )'>Edit</button>
                                        <form method="POST" onsubmit="return confirmDelete('<?= htmlspecialchars(addslashes($service['name'])) ?>');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$service['id'] ?>">
                                            <button type="submit" class="action-btn delete-btn">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty"><div class="empty-icon">🐾</div><strong>No services found.</strong><br>Try another search or add a new service.</div>
            <?php endif; ?>
        </section>
    </div>
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
                <label for="servicePrice">Service Price (₱)</label>
                <input
                    type="number"
                    id="servicePrice"
                    name="price"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    required
                    placeholder="e.g. 500.00"
                >
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
    const servicePrice = document.getElementById('servicePrice');
    const serviceStatus = document.getElementById('serviceStatus');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');

    function openAddModal() {
        formAction.value = 'add';
        serviceId.value = '';
        serviceName.value = '';
        serviceDescription.value = '';
        servicePrice.value = '';
        serviceStatus.value = 'Active';

        modalTitle.textContent = 'Add Service';
        modalSubtitle.textContent = 'Create a new veterinary service.';

        modal.classList.add('show');
        serviceName.focus();
    }

    function openEditModal(id, name, description, price, status) {
        formAction.value = 'edit';
        serviceId.value = id;
        serviceName.value = name || '';
        serviceDescription.value = description || '';
        servicePrice.value = price ?? '';
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
