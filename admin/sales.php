<?php
session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";

requireLogin();

/*
|--------------------------------------------------------------------------
| SALES HISTORY
|--------------------------------------------------------------------------
| Sales are based ONLY on payments with payment_status = 'Paid'.
| Pending and Cancelled payments are not included.
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$method = trim($_GET['method'] ?? '');

$where = ["p.payment_status = 'Paid'"];
$params = [];

if ($search !== '') {
    $where[] = "(
        CAST(p.id AS CHAR) LIKE :search
        OR a.owner_name LIKE :search
        OR a.pet_name LIKE :search
        OR p.reference_number LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
}

if ($method !== '' && in_array($method, ['Cash', 'GCash'], true)) {
    $where[] = "p.payment_method = :method";
    $params[':method'] = $method;
}

$whereSql = implode(' AND ', $where);

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_transactions,
        COALESCE(SUM(p.amount), 0) AS total_sales,
        COALESCE(SUM(CASE WHEN p.payment_method = 'Cash' THEN p.amount ELSE 0 END), 0) AS cash_sales,
        COALESCE(SUM(CASE WHEN p.payment_method = 'GCash' THEN p.amount ELSE 0 END), 0) AS gcash_sales
    FROM payments p
    LEFT JOIN appointments a
        ON a.id = p.appointment_id
    WHERE {$whereSql}
");

$summaryStmt->execute($params);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_transactions' => 0,
    'total_sales' => 0,
    'cash_sales' => 0,
    'gcash_sales' => 0
];

$totalTransactions = (int)$summary['total_transactions'];
$totalSales = (float)$summary['total_sales'];
$cashSales = (float)$summary['cash_sales'];
$gcashSales = (float)$summary['gcash_sales'];

/*
|--------------------------------------------------------------------------
| REFUNDED PAYMENTS
|--------------------------------------------------------------------------
| Refunded payments are tracked separately and are NOT included in sales.
*/
$refundStmt = $pdo->query("
    SELECT
        COUNT(*) AS refunded_transactions,
        COALESCE(SUM(amount), 0) AS refunded_amount
    FROM payments
    WHERE payment_status = 'Refunded'
");

$refundSummary = $refundStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'refunded_transactions' => 0,
    'refunded_amount' => 0
];

$refundedTransactions = (int)$refundSummary['refunded_transactions'];
$refundedAmount = (float)$refundSummary['refunded_amount'];

/*
|--------------------------------------------------------------------------
| PAID SALES
|--------------------------------------------------------------------------
*/

$salesStmt = $pdo->prepare("
    SELECT
        p.id,
        p.appointment_id,
        p.amount,
        p.payment_method,
        p.reference_number,
        p.payment_status,
        p.paid_at,
        p.created_at,
        a.owner_name,
        a.pet_name,
        a.service,
        a.appointment_date,
        a.appointment_time
    FROM payments p
    LEFT JOIN appointments a
        ON a.id = p.appointment_id
    WHERE {$whereSql}
    ORDER BY COALESCE(p.paid_at, p.created_at) DESC, p.id DESC
");

$salesStmt->execute($params);
$sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| PAYMENT ITEMS
|--------------------------------------------------------------------------
| Loaded for the paid transactions so the admin can see what was sold.
|--------------------------------------------------------------------------
*/

$paymentIds = array_map(
    static fn($sale) => (int)$sale['id'],
    $sales
);

$itemsByPayment = [];

if (!empty($paymentIds)) {
    $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));

    $itemsStmt = $pdo->prepare("
        SELECT
            payment_id,
            item_type,
            item_name,
            quantity,
            unit_price,
            subtotal
        FROM payment_items
        WHERE payment_id IN ({$placeholders})
        ORDER BY payment_id DESC, id ASC
    ");

    $itemsStmt->execute($paymentIds);
    $paymentItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($paymentItems as $item) {
        $itemsByPayment[(int)$item['payment_id']][] = $item;
    }
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function formatSalesDate(?string $value): string
{
    if (!$value) {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('M d, Y h:i A', $timestamp) : '—';
}

function money(float $value): string
{
    return '₱' . number_format($value, 2);
}

function paymentMethodClass(string $method): string
{
    return strtolower($method) === 'gcash' ? 'gcash' : 'cash';
}

$adminUsername = $_SESSION['admin_username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sales | Minguito Veterinary Clinic</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/sidebar.css"
    >

    <style>
        :root {
            --green: #2f6b4f;
            --green-dark: #24543e;
            --green-soft: #e8f2eb;
            --gold: #c89b3c;
            --gold-soft: #fbf2df;
            --cream: #f8f1e5;
            --bg: #f5f6f2;
            --white: #ffffff;
            --text: #26352d;
            --muted: #7b877f;
            --border: #e4e8e3;
            --success: #246a45;
            --success-soft: #e2f1e8;
            --danger: #a13d32;
            --shadow: 0 12px 30px rgba(31, 53, 43, 0.07);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: "DM Sans", Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        .admin-main {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
            padding: 34px 38px 50px;
            box-sizing: border-box;
        }

        .sales-page {
            width: 100%;
            max-width: none;
            margin: 0;
        }

        .topbar {
            min-height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .page-heading h1 {
            margin: 0;
            color: var(--green);
            font-family: "Playfair Display", Georgia, serif;
            font-size: 36px;
            line-height: 1.1;
        }

        .page-heading p {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.75);
            color: var(--muted);
            font-size: 13px;
            white-space: nowrap;
        }

        .dashboard-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 15px;
            border-radius: 12px;
            background: var(--green);
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .dashboard-btn:hover {
            background: var(--green-dark);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .stat-card.total {
            border-top: 4px solid var(--green);
        }

        .stat-card.sales {
            border-top: 4px solid var(--gold);
        }

        .stat-card.cash {
            border-top: 4px solid #4f7d65;
        }

        .stat-card.gcash {
            border-top: 4px solid var(--gold);
        }

        .stat-card.refunded {
            border-top: 4px solid #6b7099;
        }

        .stat-card.refunded .stat-icon {
            background: #eef0ff;
            color: #4c568f;
        }

        .stat-card.refunded .stat-value {
            color: #4c568f;
        }

        .stat-note {
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-bottom: 13px;
            background: var(--green-soft);
            color: var(--green);
        }

        .sales .stat-icon {
            background: var(--gold-soft);
            color: var(--gold);
        }

        .gcash .stat-icon {
            background: #fbf2df;
            color: var(--gold);
        }

        .stat-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .stat-value {
            margin-top: 6px;
            color: var(--green);
            font-size: 25px;
            font-weight: 700;
        }

        .sales .stat-value {
            color: var(--gold);
        }

        .filter-card {
            margin-bottom: 22px;
            padding: 18px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        .filter-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 180px auto auto;
            align-items: end;
            gap: 12px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .field input,
        .field select {
            width: 100%;
            height: 43px;
            padding: 0 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font: inherit;
            outline: none;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(181, 122, 47, .10);
        }

        .filter-btn,
        .clear-btn {
            height: 43px;
            padding: 0 16px;
            border-radius: 10px;
            border: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .filter-btn {
            background: var(--green);
            color: #fff;
        }

        .filter-btn:hover {
            background: var(--green-dark);
        }

        .clear-btn {
            background: #f1eee8;
            color: var(--text);
        }

        .clear-btn:hover {
            background: #e7e1d7;
        }

        .sales-card {
            overflow: hidden;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .sales-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
        }

        .sales-card-header h2 {
            margin: 0;
            color: var(--green);
            font-family: "Playfair Display", Georgia, serif;
            font-size: 22px;
        }

        .sales-card-header p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .paid-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 11px;
            border-radius: 999px;
            background: var(--success-soft);
            color: var(--success);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }

        th {
            padding: 14px 18px;
            background: #fbf8f2;
            border-bottom: 1px solid var(--border);
            color: var(--muted);
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        td {
            padding: 17px 18px;
            border-bottom: 1px solid #eee9df;
            vertical-align: top;
            font-size: 13px;
        }

        tbody tr:hover {
            background: #fcfaf6;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .transaction-id {
            color: var(--green);
            font-weight: 700;
        }

        .customer-name {
            font-weight: 700;
            color: var(--text);
        }

        .pet-name {
            margin-top: 3px;
            color: var(--muted);
            font-size: 12px;
        }

        .payment-method {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
        }

        .payment-method.cash {
            background: var(--green-soft);
            color: var(--green);
        }

        .payment-method.gcash {
            background: #fbf2df;
            color: var(--gold);
        }

        .reference {
            color: var(--muted);
            font-size: 12px;
            word-break: break-word;
        }

        .amount {
            color: var(--green);
            font-weight: 700;
            font-size: 14px;
            white-space: nowrap;
        }

        .paid-time {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .item-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 180px;
        }

        .item-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
        }

        .item-name {
            color: var(--text);
        }

        .item-price {
            color: var(--muted);
            white-space: nowrap;
        }

        .item-type {
            margin-left: 5px;
            color: var(--gold);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .no-items {
            color: var(--muted);
            font-size: 12px;
        }

        .empty-state {
            padding: 65px 25px;
            text-align: center;
        }

        .empty-icon {
            width: 62px;
            height: 62px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            border-radius: 50%;
            background: var(--green-soft);
            color: var(--green);
            font-size: 25px;
        }

        .empty-state h3 {
            margin: 0 0 6px;
            color: var(--green);
            font-family: "Playfair Display", Georgia, serif;
            font-size: 21px;
        }

        .empty-state p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .sales-note {
            margin-top: 14px;
            color: var(--muted);
            font-size: 12px;
            text-align: right;
        }

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-form {
                grid-template-columns: 1fr 180px;
            }
        }

        @media (max-width: 980px) {
            .admin-main {
                margin-left: 0;
                width: 100%;
                padding: 85px 20px 40px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .topbar-actions {
                width: 100%;
            }

            .date-pill,
            .dashboard-btn {
                flex: 1;
                justify-content: center;
            }
        }

        @media (max-width: 700px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .page-heading h1 {
                font-size: 31px;
            }

            .sales-card-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .paid-badge {
                align-self: flex-start;
            }
        }

        @media (max-width: 450px) {
            .admin-main {
                padding-left: 14px;
                padding-right: 14px;
            }

            .topbar-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .date-pill,
            .dashboard-btn {
                width: 100%;
            }

            .stat-card {
                padding: 17px;
            }
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
</head>

<body>

<?php include 'sidebar.php'; ?>

<main class="admin-main">
    <div class="sales-page">

        <header class="topbar">
            <div class="page-heading">
                <h1>Sales</h1>
                <p>
                    View all successfully paid transactions from Minguito Veterinary Clinic.
                </p>
            </div>

            <div class="topbar-actions">
                <div class="date-pill">
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('F d, Y') ?>
                </div>

                <!-- Dashboard button removed because Dashboard is already available in the sidebar. -->
            </div>
        </header>

        <!-- SUMMARY -->
        <section class="stats-grid">

            <article class="stat-card total">
                <div class="stat-icon">
                    <i class="fa-solid fa-receipt"></i>
                </div>

                <div class="stat-label">Paid Transactions</div>

                <div class="stat-value">
                    <?= number_format($totalTransactions) ?>
                </div>
            </article>

            <article class="stat-card sales">
                <div class="stat-icon">
                    <i class="fa-solid fa-peso-sign"></i>
                </div>

                <div class="stat-label">Total Sales</div>

                <div class="stat-value">
                    <?= money($totalSales) ?>
                </div>
            </article>

            <article class="stat-card cash">
                <div class="stat-icon">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>

                <div class="stat-label">Cash Sales</div>

                <div class="stat-value">
                    <?= money($cashSales) ?>
                </div>
            </article>

            <article class="stat-card gcash">
                <div class="stat-icon">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                </div>

                <div class="stat-label">GCash Sales</div>

                <div class="stat-value">
                    <?= money($gcashSales) ?>
                </div>
            </article>

            <article class="stat-card refunded">
                <div class="stat-icon">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>

                <div class="stat-label">Refunded</div>

                <div class="stat-value">
                    <?= money($refundedAmount) ?>
                </div>

                <div class="stat-note">
                    <?= number_format($refundedTransactions) ?>
                    refunded transaction<?= $refundedTransactions === 1 ? '' : 's' ?>
                </div>
            </article>

        </section>

        <!-- FILTER -->
        <section class="filter-card">
            <form
                method="GET"
                action="sales.php"
                class="filter-form"
            >

                <div class="field">
                    <label for="search">Search Sales</label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Transaction ID, customer, pet, or reference number..."
                    >
                </div>

                <div class="field">
                    <label for="method">Payment Method</label>

                    <select
                        id="method"
                        name="method"
                    >
                        <option value="">All Methods</option>
                        <option
                            value="Cash"
                            <?= $method === 'Cash' ? 'selected' : '' ?>
                        >
                            Cash
                        </option>
                        <option
                            value="GCash"
                            <?= $method === 'GCash' ? 'selected' : '' ?>
                        >
                            GCash
                        </option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="filter-btn"
                >
                    <i class="fa-solid fa-filter"></i>
                    Filter
                </button>

                <a
                    href="sales.php"
                    class="clear-btn"
                >
                    <i class="fa-solid fa-rotate-left"></i>
                    Clear
                </a>

            </form>
        </section>

        <!-- SALES TABLE -->
        <section class="sales-card">

            <div class="sales-card-header">
                <div>
                    <h2>Sales History</h2>
                    <p>
                        Only payments marked as <strong>Paid</strong> are recorded as sales.
                    </p>
                </div>

                <div class="paid-badge">
                    <i class="fa-solid fa-circle-check"></i>
                    Paid Transactions
                </div>
            </div>

            <?php if (empty($sales)): ?>

                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-chart-column"></i>
                    </div>

                    <h3>No Sales Found</h3>

                    <p>
                        There are no paid transactions matching your current filter.
                    </p>
                </div>

            <?php else: ?>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th>Customer / Pet</th>
                                <th>Items</th>
                                <th>Payment</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Paid At</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($sales as $sale): ?>

                            <?php
                            $saleId = (int)$sale['id'];
                            $items = $itemsByPayment[$saleId] ?? [];
                            ?>

                            <tr>

                                <td>
                                    <div class="transaction-id">
                                        #<?= $saleId ?>
                                    </div>

                                    <?php if (!empty($sale['appointment_id'])): ?>
                                        <div class="pet-name">
                                            Appointment #<?= (int)$sale['appointment_id'] ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="customer-name">
                                        <?= htmlspecialchars(
                                            $sale['owner_name'] ?: 'Walk-in / Unnamed',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>

                                    <div class="pet-name">
                                        <i class="fa-solid fa-paw"></i>
                                        <?= htmlspecialchars(
                                            $sale['pet_name'] ?: 'No pet name',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </div>
                                </td>

                                <td>
                                    <?php if (!empty($items)): ?>

                                        <div class="item-list">

                                            <?php foreach ($items as $item): ?>

                                                <div class="item-line">
                                                    <span class="item-name">
                                                        <?= htmlspecialchars(
                                                            $item['item_name'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                        <span class="item-type">
                                                            <?= htmlspecialchars(
                                                                $item['item_type'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>
                                                        </span>

                                                        <?php if ((int)$item['quantity'] > 1): ?>
                                                            × <?= (int)$item['quantity'] ?>
                                                        <?php endif; ?>
                                                    </span>

                                                    <span class="item-price">
                                                        <?= money((float)$item['subtotal']) ?>
                                                    </span>
                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php else: ?>

                                        <span class="no-items">
                                            No payment items found
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span
                                        class="payment-method <?= paymentMethodClass((string)$sale['payment_method']) ?>"
                                    >
                                        <?php if (strtolower((string)$sale['payment_method']) === 'gcash'): ?>
                                            <i class="fa-solid fa-mobile-screen-button"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                        <?php endif; ?>

                                        <?= htmlspecialchars(
                                            $sale['payment_method'] ?: 'Unknown',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="reference">
                                        <?php if (!empty($sale['reference_number'])): ?>
                                            <?= htmlspecialchars(
                                                $sale['reference_number'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="amount">
                                        <?= money((float)$sale['amount']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="paid-time">
                                        <?= formatSalesDate($sale['paid_at'] ?: $sale['created_at']) ?>
                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>

        <div class="sales-note">
            Sales total is calculated from payments whose status is <strong>Paid</strong>.
        </div>

    </div>
</main>

</body>
</html>
