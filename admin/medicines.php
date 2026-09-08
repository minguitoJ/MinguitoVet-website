<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| MEDICINES CRUD
|--------------------------------------------------------------------------
| Table: medicines
| Columns:
| id, name, description, price, stock, status, created_at
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';

/* ---------------------------------------------------------
   HANDLE ADD / EDIT / DELETE
   --------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ADD MEDICINE */
    if ($action === 'add') {

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $stock = trim($_POST['stock'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if ($name === '') {
            $message = 'Please enter a medicine name.';
            $message_type = 'error';

        } elseif ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $message = 'Please enter a valid medicine price.';
            $message_type = 'error';

        } elseif (
            $stock === '' ||
            filter_var($stock, FILTER_VALIDATE_INT) === false ||
            (int)$stock < 0
        ) {
            $message = 'Please enter a valid stock quantity.';
            $message_type = 'error';

        } elseif (!in_array($status, ['Active', 'Inactive'], true)) {
            $message = 'Invalid medicine status.';
            $message_type = 'error';

        } else {

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO medicines
                        (name, description, price, stock, status)
                    VALUES
                        (:name, :description, :price, :stock, :status)
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                    ':price' => number_format((float)$price, 2, '.', ''),
                    ':stock' => (int)$stock,
                    ':status' => $status
                ]);

                header('Location: medicines.php?success=added');
                exit;

            } catch (PDOException $e) {
                $message = 'Unable to add the medicine.';
                $message_type = 'error';
            }
        }
    }

    /* EDIT MEDICINE */
    elseif ($action === 'edit') {

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $stock = trim($_POST['stock'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if ($id <= 0) {
            $message = 'Invalid medicine.';
            $message_type = 'error';

        } elseif ($name === '') {
            $message = 'Please enter a medicine name.';
            $message_type = 'error';

        } elseif ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $message = 'Please enter a valid medicine price.';
            $message_type = 'error';

        } elseif (
            $stock === '' ||
            filter_var($stock, FILTER_VALIDATE_INT) === false ||
            (int)$stock < 0
        ) {
            $message = 'Please enter a valid stock quantity.';
            $message_type = 'error';

        } elseif (!in_array($status, ['Active', 'Inactive'], true)) {
            $message = 'Invalid medicine status.';
            $message_type = 'error';

        } else {

            try {
                $stmt = $pdo->prepare("
                    UPDATE medicines
                    SET
                        name = :name,
                        description = :description,
                        price = :price,
                        stock = :stock,
                        status = :status
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                    ':price' => number_format((float)$price, 2, '.', ''),
                    ':stock' => (int)$stock,
                    ':status' => $status,
                    ':id' => $id
                ]);

                header('Location: medicines.php?success=updated');
                exit;

            } catch (PDOException $e) {
                $message = 'Unable to update the medicine.';
                $message_type = 'error';
            }
        }
    }

    /* DELETE MEDICINE */
    elseif ($action === 'delete') {

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $message = 'Invalid medicine.';
            $message_type = 'error';

        } else {

            try {
                $stmt = $pdo->prepare("
                    DELETE FROM medicines
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':id' => $id
                ]);

                header('Location: medicines.php?success=deleted');
                exit;

            } catch (PDOException $e) {
                $message = 'Unable to delete the medicine.';
                $message_type = 'error';
            }
        }
    }
}

/* ---------------------------------------------------------
   SUCCESS MESSAGES
   --------------------------------------------------------- */
if (isset($_GET['success'])) {

    switch ($_GET['success']) {

        case 'added':
            $message = 'Medicine added successfully.';
            $message_type = 'success';
            break;

        case 'updated':
            $message = 'Medicine updated successfully.';
            $message_type = 'success';
            break;

        case 'deleted':
            $message = 'Medicine deleted successfully.';
            $message_type = 'success';
            break;
    }
}

/* ---------------------------------------------------------
   SEARCH / FILTER
   --------------------------------------------------------- */
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$sql = "
    SELECT
        id,
        name,
        description,
        price,
        stock,
        status,
        created_at
    FROM medicines
    WHERE 1 = 1
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

$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------
   COUNTS
   --------------------------------------------------------- */
$totalMedicines = (int)$pdo
    ->query("SELECT COUNT(*) FROM medicines")
    ->fetchColumn();

$activeMedicines = (int)$pdo
    ->query("SELECT COUNT(*) FROM medicines WHERE status = 'Active'")
    ->fetchColumn();

$inactiveMedicines = (int)$pdo
    ->query("SELECT COUNT(*) FROM medicines WHERE status = 'Inactive'")
    ->fetchColumn();

$lowStockMedicines = (int)$pdo
    ->query("
        SELECT COUNT(*)
        FROM medicines
        WHERE status = 'Active'
        AND stock <= 5
    ")
    ->fetchColumn();

$adminUsername = $_SESSION['admin_username'] ?? 'Administrator';

function medicine_json($value): string
{
    return json_encode(
        $value,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Medicines | Minguito Veterinary Clinic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/sidebar.css">

    <style>
        :root {
            --green: #073b2a;
            --green-light: #174c3b;
            --cream: #f8efe2;
            --gold: #b57a2f;
            --gold-light: #d4a15c;
            --white: #ffffff;
            --text: #17231e;
            --muted: #66756e;
            --border: #e6d7c2;
            --success: #14633f;
            --warning: #a66a16;
            --danger: #a13d32;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            font-family: "DM Sans", Arial, sans-serif;
            color: var(--text);
            background: linear-gradient(135deg, #fbf4e9, #f4e6d2);
        }

        .admin-main {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
        }

        /* =====================================
           HEADER
        ===================================== */

        .admin-header {
            background: var(--green);
            color: white;
            padding: 18px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .12);
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
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3px;
            overflow: hidden;
        }

        .brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 10px;
            display: block;
        }

        .brand-text strong {
            display: block;
            font-family: "Playfair Display", Georgia, serif;
            font-size: 16px;
            font-weight: 700;
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

        .header-link:hover {
            background: rgba(255,255,255,.1);
        }

        .header-link.logout {
            background: var(--gold);
        }

        .header-link.logout:hover {
            background: var(--gold-light);
        }

        /* =====================================
           MAIN
        ===================================== */

        .container {
            width: min(1180px, 92%);
            margin: auto;
        }

        .main {
            padding: 40px 0 60px;
        }

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

        .page-title p {
            color: var(--muted);
            font-size: 14px;
        }

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
        }

        .primary-btn:hover {
            background: var(--gold);
        }

        /* =====================================
           ALERT
        ===================================== */

        .alert {
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert.success {
            background: #e7f3e9;
            color: #387346;
            border: 1px solid #c8e1ce;
        }

        .alert.error {
            background: #f8e7e5;
            color: #a13c32;
            border: 1px solid #ecc5c0;
        }

        /* =====================================
           STAT CARDS
        ===================================== */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }

        .stat {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 19px;
            box-shadow: 0 10px 28px rgba(7,59,42,.07);
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }

        .stat-number {
            color: var(--green);
            font-size: 28px;
            font-weight: 800;
        }

        .stat-number.warning {
            color: var(--warning);
        }

        /* =====================================
           FILTER BOX
        ===================================== */

        .filter-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 28px rgba(7,59,42,.07);
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

        .field input,
        .field select {
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

        .field input:focus,
        .field select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(181,122,47,.1);
        }

        .filter-btn,
        .clear-btn {
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

        .filter-btn {
            border: 0;
            background: var(--green);
            color: white;
        }

        .filter-btn:hover {
            background: var(--gold);
        }

        .clear-btn {
            border: 1px solid var(--border);
            background: white;
            color: var(--green);
        }

        .clear-btn:hover {
            background: var(--cream);
        }

        /* =====================================
           TABLE
        ===================================== */

        .table-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 28px rgba(7,59,42,.07);
        }

        .table-header {
            padding: 19px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }

        .table-header h2 {
            color: var(--green);
            font-size: 18px;
            font-weight: 800;
        }

        .result-count {
            color: var(--muted);
            font-size: 12px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        th {
            padding: 14px 16px;
            background: #fcf7ef;
            color: var(--muted);
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        td {
            padding: 15px 16px;
            border-top: 1px solid #eee3d4;
            font-size: 12px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #fffbf5;
        }

        .medicine-id {
            color: var(--muted);
            font-weight: 700;
        }

        .medicine-name {
            color: var(--green);
            font-size: 14px;
            font-weight: 800;
        }

        .description {
            max-width: 360px;
            color: var(--muted);
            line-height: 1.45;
        }

        .price {
            color: var(--green);
            font-weight: 800;
            white-space: nowrap;
        }

        .stock {
            font-weight: 800;
            white-space: nowrap;
        }

        .stock-ok {
            color: var(--success);
        }

        .stock-low {
            color: var(--warning);
        }

        .stock-out {
            color: var(--danger);
        }

        .stock-note {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 500;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 30px;
            font-size: 10px;
            font-weight: 800;
        }

        .status::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-active {
            background: #e5f2e8;
            color: #2b7748;
        }

        .status-inactive {
            background: #eeeae5;
            color: #77736d;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-btn {
            height: 34px;
            padding: 0 10px;
            border: 0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 10px;
            font-weight: 800;
            cursor: pointer;
        }

        .edit-btn {
            background: #f2e7cf;
            color: #806025;
        }

        .delete-btn {
            background: #f8e3e0;
            color: #a13d32;
        }

        .edit-btn:hover,
        .delete-btn:hover {
            filter: brightness(.97);
        }

        .empty {
            padding: 55px 20px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .empty-icon {
            font-size: 35px;
            margin-bottom: 10px;
        }

        /* =====================================
           MODAL
        ===================================== */

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(7, 59, 42, .55);
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
            width: min(560px, 100%);
            max-height: 92vh;
            overflow-y: auto;
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 18px 50px rgba(0,0,0,.2);
        }

        .modal-box h3 {
            color: var(--green);
            font: 700 25px/1.1 "Playfair Display", Georgia, serif;
            margin-bottom: 6px;
        }

        .modal-box > p {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: var(--green);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            border: 1px solid #dccab4;
            border-radius: 10px;
            background: #fffaf3;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            padding: 11px 12px;
            outline: none;
        }

        .form-group input,
        .form-group select {
            height: 44px;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(181,122,47,.1);
        }

        .input-help {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            margin-top: 18px;
        }

        .cancel-btn,
        .save-btn {
            height: 42px;
            padding: 0 17px;
            border: 0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .cancel-btn {
            background: #eee6da;
            color: #4d5751;
        }

        .save-btn {
            background: var(--green);
            color: white;
        }

        .save-btn:hover {
            background: var(--gold);
        }

        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (max-width: 1200px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 950px) {
            .admin-main {
                margin-left: 0;
                width: 100%;
            }

            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 700px) {
            .header-inner {
                align-items: flex-start;
            }

            .header-links {
                flex-direction: column;
                align-items: stretch;
            }

            .header-link {
                text-align: center;
            }

            .main {
                padding-top: 28px;
            }

            .page-title {
                align-items: flex-start;
                flex-direction: column;
            }

            .page-title h1 {
                font-size: 31px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>

<?php include 'sidebar.php'; ?>

<main class="admin-main">

    <header class="admin-header">
        <div class="header-inner">

            <div class="brand">
                <div class="brand-icon">
                    <img
                        src="../assets/images/logo2.png"
                        alt="Minguito Veterinary Clinic Logo"
                    >
                </div>

                <div class="brand-text">
                    <strong>Minguito Veterinary</strong>
                    <span>Administration Panel</span>
                </div>
            </div>

            <div class="header-links">
                <a href="dashboard.php" class="header-link">
                    Dashboard
                </a>

                <a href="../logout.php" class="header-link logout">
                    Logout
                </a>
            </div>

        </div>
    </header>

    <main class="main">

        <div class="container">

            <section class="page-title">

                <div>
                    <div class="section-label">
                        Inventory Management
                    </div>

                    <h1>Medicines</h1>

                    <p>
                        Manage medicines, prices, and available stock for clinic payments.
                    </p>
                </div>

                <button
                    type="button"
                    class="primary-btn"
                    onclick="openAddModal()"
                >
                    + Add Medicine
                </button>

            </section>

            <?php if ($message !== ''): ?>

                <div class="alert <?= $message_type === 'success' ? 'success' : 'error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>

            <?php endif; ?>

            <section class="stats">

                <article class="stat">
                    <div class="stat-label">
                        Total Medicines
                    </div>

                    <div class="stat-number">
                        <?= $totalMedicines ?>
                    </div>
                </article>

                <article class="stat">
                    <div class="stat-label">
                        Active
                    </div>

                    <div class="stat-number">
                        <?= $activeMedicines ?>
                    </div>
                </article>

                <article class="stat">
                    <div class="stat-label">
                        Inactive
                    </div>

                    <div class="stat-number">
                        <?= $inactiveMedicines ?>
                    </div>
                </article>

                <article class="stat">
                    <div class="stat-label">
                        Low Stock ≤ 5
                    </div>

                    <div class="stat-number <?= $lowStockMedicines > 0 ? 'warning' : '' ?>">
                        <?= $lowStockMedicines ?>
                    </div>
                </article>

            </section>

            <section class="filter-card">

                <form method="GET" action="medicines.php" class="filter-form">

                    <div class="field">

                        <label for="search">
                            Search
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Medicine name or description..."
                        >

                    </div>

                    <div class="field">

                        <label for="status">
                            Status
                        </label>

                        <select id="status" name="status">

                            <option value="">
                                All Statuses
                            </option>

                            <option
                                value="Active"
                                <?= $status_filter === 'Active' ? 'selected' : '' ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?= $status_filter === 'Inactive' ? 'selected' : '' ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>

                    <button type="submit" class="filter-btn">
                        Search
                    </button>

                    <a href="medicines.php" class="clear-btn">
                        Clear
                    </a>

                </form>

            </section>

            <section class="table-card">

                <div class="table-header">

                    <h2>
                        Medicine Records
                    </h2>

                    <span class="result-count">
                        <?= count($medicines) ?>
                        result<?= count($medicines) === 1 ? '' : 's' ?>
                    </span>

                </div>

                <?php if (!empty($medicines)): ?>

                    <div class="table-wrapper">

                        <table>

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Medicine</th>
                                    <th>Description</th>
                                    <th>Unit Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($medicines as $medicine): ?>

                                    <?php
                                    $stock = (int)$medicine['stock'];

                                    if ($stock <= 0) {
                                        $stockClass = 'stock-out';
                                        $stockText = 'Out of stock';
                                        $stockNote = 'Needs restocking';
                                    } elseif ($stock <= 5) {
                                        $stockClass = 'stock-low';
                                        $stockText = $stock;
                                        $stockNote = 'Low stock';
                                    } else {
                                        $stockClass = 'stock-ok';
                                        $stockText = $stock;
                                        $stockNote = 'Available';
                                    }
                                    ?>

                                    <tr>

                                        <td>
                                            <span class="medicine-id">
                                                #<?= (int)$medicine['id'] ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="medicine-name">
                                                <?= htmlspecialchars($medicine['name']) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="description">
                                                <?= htmlspecialchars(
                                                    $medicine['description'] ?: 'No description.'
                                                ) ?>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="price">
                                                ₱<?= number_format((float)$medicine['price'], 2) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="stock <?= $stockClass ?>">
                                                <?= htmlspecialchars($stockText) ?>
                                            </span>

                                            <span class="stock-note">
                                                <?= htmlspecialchars($stockNote) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="status <?= $medicine['status'] === 'Active' ? 'status-active' : 'status-inactive' ?>">
                                                <?= htmlspecialchars($medicine['status']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= date('M d, Y', strtotime($medicine['created_at'])) ?>
                                        </td>

                                        <td>

                                            <div class="actions">

                                                <button
                                                    type="button"
                                                    class="action-btn edit-btn"
                                                    onclick='openEditModal(
                                                        <?= (int)$medicine["id"] ?>,
                                                        <?= medicine_json($medicine["name"]) ?>,
                                                        <?= medicine_json($medicine["description"] ?? "") ?>,
                                                        <?= medicine_json((float)$medicine["price"]) ?>,
                                                        <?= (int)$medicine["stock"] ?>,
                                                        <?= medicine_json($medicine["status"]) ?>
                                                    )'
                                                >
                                                    Edit
                                                </button>

                                                <form
                                                    method="POST"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('Delete this medicine? This action cannot be undone.');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="delete"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int)$medicine['id'] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="action-btn delete-btn"
                                                    >
                                                        Delete
                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty">

                        <div class="empty-icon">
                            💊
                        </div>

                        <div>
                            No medicines found.
                        </div>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</main>

<!-- ADD / EDIT MODAL -->
<div
    class="modal"
    id="medicineModal"
    aria-hidden="true"
>
    <div class="modal-box">

        <h3 id="modalTitle">
            Add Medicine
        </h3>

        <p id="modalDescription">
            Add a medicine with its price and current stock.
        </p>

        <form method="POST" id="medicineForm">

            <input
                type="hidden"
                name="action"
                id="formAction"
                value="add"
            >

            <input
                type="hidden"
                name="id"
                id="medicineId"
                value=""
            >

            <div class="form-group">

                <label for="medicineName">
                    Medicine Name
                </label>

                <input
                    type="text"
                    id="medicineName"
                    name="name"
                    maxlength="150"
                    required
                    placeholder="e.g. Deworming Medicine"
                >

            </div>

            <div class="form-group">

                <label for="medicineDescription">
                    Description
                </label>

                <textarea
                    id="medicineDescription"
                    name="description"
                    maxlength="1000"
                    placeholder="Enter a short description..."
                ></textarea>

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label for="medicinePrice">
                        Unit Price (₱)
                    </label>

                    <input
                        type="number"
                        id="medicinePrice"
                        name="price"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        required
                        placeholder="e.g. 250.00"
                    >

                </div>

                <div class="form-group">

                    <label for="medicineStock">
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        id="medicineStock"
                        name="stock"
                        min="0"
                        step="1"
                        required
                        placeholder="e.g. 20"
                    >

                    <span class="input-help">
                        Enter the current available quantity.
                    </span>

                </div>

            </div>

            <div class="form-group">

                <label for="medicineStatus">
                    Status
                </label>

                <select
                    id="medicineStatus"
                    name="status"
                    required
                >
                    <option value="Active">
                        Active
                    </option>

                    <option value="Inactive">
                        Inactive
                    </option>
                </select>

            </div>

            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-btn"
                    onclick="closeMedicineModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="save-btn"
                >
                    Save Medicine
                </button>

            </div>

        </form>

    </div>
</div>

<script>
    const medicineModal = document.getElementById('medicineModal');

    const medicineForm = document.getElementById('medicineForm');
    const formAction = document.getElementById('formAction');
    const medicineId = document.getElementById('medicineId');

    const medicineName = document.getElementById('medicineName');
    const medicineDescription = document.getElementById('medicineDescription');
    const medicinePrice = document.getElementById('medicinePrice');
    const medicineStock = document.getElementById('medicineStock');
    const medicineStatus = document.getElementById('medicineStatus');

    const modalTitle = document.getElementById('modalTitle');
    const modalDescription = document.getElementById('modalDescription');

    function openAddModal() {

        medicineForm.reset();

        formAction.value = 'add';
        medicineId.value = '';

        medicineName.value = '';
        medicineDescription.value = '';
        medicinePrice.value = '';
        medicineStock.value = '0';
        medicineStatus.value = 'Active';

        modalTitle.textContent = 'Add Medicine';
        modalDescription.textContent =
            'Add a medicine with its price and current stock.';

        medicineModal.classList.add('show');
        medicineModal.setAttribute('aria-hidden', 'false');

        setTimeout(() => medicineName.focus(), 50);
    }

    function openEditModal(
        id,
        name,
        description,
        price,
        stock,
        status
    ) {

        formAction.value = 'edit';
        medicineId.value = id;

        medicineName.value = name || '';
        medicineDescription.value = description || '';
        medicinePrice.value = price ?? '';
        medicineStock.value = stock ?? 0;
        medicineStatus.value = status || 'Active';

        modalTitle.textContent = 'Edit Medicine';
        modalDescription.textContent =
            'Update the medicine information, price, or stock.';

        medicineModal.classList.add('show');
        medicineModal.setAttribute('aria-hidden', 'false');

        setTimeout(() => medicineName.focus(), 50);
    }

    function closeMedicineModal() {

        medicineModal.classList.remove('show');
        medicineModal.setAttribute('aria-hidden', 'true');
    }

    medicineModal.addEventListener('click', function (event) {

        if (event.target === medicineModal) {
            closeMedicineModal();
        }

    });

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeMedicineModal();
        }

    });
</script>

</body>
</html>
