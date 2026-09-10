<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$pageTitle = 'Admin Dashboard';

// ---------------------------------------------------------
// DASHBOARD DATA
// ---------------------------------------------------------

function getCount(PDO $pdo, string $sql): int
{
    return (int) $pdo->query($sql)->fetchColumn();
}

// Appointment counts
$totalAppointments = getCount(
    $pdo,
    "SELECT COUNT(*) FROM appointments"
);

$pendingAppointments = getCount(
    $pdo,
    "SELECT COUNT(*) FROM appointments WHERE status = 'Pending'"
);

$approvedAppointments = getCount(
    $pdo,
    "SELECT COUNT(*) FROM appointments WHERE status = 'Approved'"
);

$completedAppointments = getCount(
    $pdo,
    "SELECT COUNT(*) FROM appointments WHERE status = 'Completed'"
);

$cancelledAppointments = getCount(
    $pdo,
    "SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled'"
);

// Other totals
$totalPatients = getCount(
    $pdo,
    "SELECT COUNT(DISTINCT CONCAT(pet_name, '|', owner_name)) FROM appointments"
);

$totalCustomers = getCount(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE role = 'customer'"
);

$totalServices = getCount(
    $pdo,
    "SELECT COUNT(*) FROM services"
);

$activeServices = getCount(
    $pdo,
    "SELECT COUNT(*) FROM services WHERE status = 'Active'"
);

$totalMessages = getCount(
    $pdo,
    "SELECT COUNT(*) FROM contact_messages"
);

// Total sales — only paid transactions count as sales.
$totalSalesStmt = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'Paid'"
);
$totalSales = (float) $totalSalesStmt->fetchColumn();

// Medicine stock / restock monitoring
// Maximum stock per medicine: 5
$maxMedicineStock = 5;

$restockStmt = $pdo->query("
    SELECT id, name, stock, status
    FROM medicines
    WHERE status = 'Active' AND stock <= 5
    ORDER BY stock ASC, name ASC
");
$restockMedicines = $restockStmt->fetchAll(PDO::FETCH_ASSOC);

$restockCount = count($restockMedicines);
$outOfStockCount = 0;
$lowStockCount = 0;

foreach ($restockMedicines as $medicine) {
    if ((int) $medicine['stock'] <= 0) {
        $outOfStockCount++;
    } else {
        $lowStockCount++;
    }
}

// Today's appointments
$today = date('Y-m-d');

$todayAppointments = getCount(
    $pdo,
    "SELECT COUNT(*) FROM appointments WHERE appointment_date = " . $pdo->quote($today)
);

// ---------------------------------------------------------
// TODAY'S APPOINTMENTS
// ---------------------------------------------------------

$todayStmt = $pdo->prepare("
    SELECT
        a.id,
        a.owner_name,
        a.pet_name,
        a.service,
        a.appointment_date,
        a.appointment_time,
        a.status,
        c.email AS customer_email
    FROM appointments a
    LEFT JOIN users c
        ON c.id = a.customer_id
       AND c.role = 'customer'
    WHERE a.appointment_date = :today
    ORDER BY a.appointment_time ASC, a.id ASC
    LIMIT 8
");

$todayStmt->execute([
    'today' => $today
]);

$todayRows = $todayStmt->fetchAll();

// ---------------------------------------------------------
// RECENT APPOINTMENTS
// ---------------------------------------------------------

$recentStmt = $pdo->query("
    SELECT
        a.id,
        a.owner_name,
        a.pet_name,
        a.service,
        a.appointment_date,
        a.appointment_time,
        a.status,
        c.full_name AS customer_name
    FROM appointments a
    LEFT JOIN users c
        ON c.id = a.customer_id
       AND c.role = 'customer'
    ORDER BY a.created_at DESC, a.id DESC
    LIMIT 7
");

$recentRows = $recentStmt->fetchAll();

// ---------------------------------------------------------
// RECENT MESSAGES
// ---------------------------------------------------------

$messageStmt = $pdo->query("
    SELECT
        id,
        name,
        email,
        subject,
        message,
        created_at
    FROM contact_messages
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");

$messageRows = $messageStmt->fetchAll();

// ---------------------------------------------------------
// HELPER FUNCTIONS
// ---------------------------------------------------------

function statusClass(string $status): string
{
    return strtolower($status);
}

function formatDateValue(?string $date): string
{
    if (!$date) {
        return '—';
    }

    return date('M d, Y', strtotime($date));
}

function formatTimeValue(?string $time): string
{
    if (!$time) {
        return '—';
    }

    return date('h:i A', strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($pageTitle) ?> | Minguito Veterinary Clinic
    </title>

    <!-- Google Fonts -->
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

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- SHARED ADMIN SIDEBAR CSS -->
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

            --cream: #f8f1e5;

            --bg: #f5f6f2;

            --text: #26352d;

            --muted: #7b877f;

            --border: #e4e8e3;

            --white: #ffffff;

            --danger: #c65c5c;
        }


        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;

            background: var(--bg);

            color: var(--text);

            font-family: 'DM Sans', sans-serif;
        }


        a {
            color: inherit;
        }


        /* =====================================================
           MAIN ADMIN CONTENT
        ===================================================== */

        .admin-main {
            margin-left: 270px;

            min-height: 100vh;

            padding: 30px 34px 45px;
        }


        /* =====================================================
           TOP BAR
        ===================================================== */

        .topbar {
            min-height: 62px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .page-heading h1 {
            margin: 0;

            font-family: 'Playfair Display', serif;

            font-size: clamp(27px, 3vw, 38px);

            color: var(--green-dark);
        }


        .page-heading p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 13px;
        }


        .topbar-actions {
            display: flex;

            align-items: center;

            gap: 10px;
        }


        .date-pill {
            display: flex;

            align-items: center;

            gap: 8px;

            padding: 10px 14px;

            border-radius: 12px;

            background: var(--white);

            border: 1px solid var(--border);

            color: var(--muted);

            font-size: 12px;

            font-weight: 700;
        }


        .date-pill i {
            color: var(--gold);
        }


        .primary-btn {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            min-height: 40px;

            padding: 0 15px;

            border: 0;

            border-radius: 11px;

            background: var(--green);

            color: #ffffff;

            text-decoration: none;

            font-size: 12px;

            font-weight: 800;

            box-shadow:
                0 7px 18px rgba(47, 107, 79, .16);

            transition:
                background .2s ease,
                transform .2s ease;
        }


        .primary-btn:hover {
            background: var(--green-dark);

            transform: translateY(-1px);
        }


        /* =====================================================
           STATISTICS
        ===================================================== */

        .stats-grid {
            display: grid;

            grid-template-columns:
                repeat(5, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 23px;
        }


        .stat-card {
            position: relative;

            overflow: hidden;

            padding: 20px;

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 17px;

            box-shadow:
                0 8px 25px rgba(40, 65, 50, .045);
        }


        .stat-card::after {
            content: "";

            position: absolute;

            right: -24px;

            bottom: -30px;

            width: 90px;

            height: 90px;

            border-radius: 50%;

            background: var(--green-soft);
        }


        .stat-top {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;
        }


        .stat-icon {
            width: 42px;

            height: 42px;

            display: grid;

            place-items: center;

            border-radius: 12px;

            background: var(--green-soft);

            color: var(--green);

            font-size: 16px;
        }


        .stat-label {
            margin-top: 14px;

            color: var(--muted);

            font-size: 11px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .65px;
        }


        .stat-value {
            margin-top: 4px;

            color: var(--text);

            font-size: 27px;

            font-weight: 800;

            line-height: 1;
        }


        .stat-note {
            margin-top: 9px;

            color: var(--muted);

            font-size: 10px;
        }


        .stat-card.gold .stat-icon {
            background: #fbf2df;

            color: var(--gold);
        }


        .stat-card.blue .stat-icon {
            background: #e9f1f5;

            color: #5d8497;
        }


        .stat-card.red .stat-icon {
            background: #faeeee;

            color: var(--danger);
        }


        /* =====================================================
           DASHBOARD GRID
        ===================================================== */

        .dashboard-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1.55fr)
                minmax(300px, .85fr);

            gap: 20px;

            align-items: start;
        }


        /* =====================================================
           PANELS
        ===================================================== */

        .panel {
            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 17px;

            box-shadow:
                0 8px 25px rgba(40, 65, 50, .045);

            overflow: hidden;
        }


        .panel + .panel {
            margin-top: 20px;
        }


        .panel-header {
            padding: 19px 21px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            border-bottom: 1px solid var(--border);
        }


        .panel-title {
            margin: 0;

            color: var(--green-dark);

            font-size: 15px;

            font-weight: 800;
        }


        .panel-subtitle {
            margin: 4px 0 0;

            color: var(--muted);

            font-size: 11px;
        }


        .panel-link {
            color: var(--green);

            text-decoration: none;

            font-size: 11px;

            font-weight: 800;
        }


        .panel-link:hover {
            color: var(--green-dark);
        }


        .panel-body {
            padding: 0;
        }


        /* =====================================================
           APPOINTMENT ROW
        ===================================================== */

        .appointment-row {
            display: grid;

            grid-template-columns:
                58px
                minmax(150px, 1fr)
                minmax(125px, .8fr)
                105px;

            align-items: center;

            gap: 14px;

            padding: 15px 21px;

            border-bottom: 1px solid #eef0ed;
        }


        .appointment-row:last-child {
            border-bottom: 0;
        }


        .appointment-time {
            color: var(--green-dark);

            font-size: 12px;

            font-weight: 800;
        }


        .pet-info strong,
        .service-info strong {
            display: block;

            color: var(--text);

            font-size: 12px;
        }


        .pet-info span,
        .service-info span {
            display: block;

            margin-top: 3px;

            color: var(--muted);

            font-size: 10px;
        }


        /* =====================================================
           STATUS BADGES
        ===================================================== */

        .status {
            width: max-content;

            padding: 6px 9px;

            border-radius: 30px;

            font-size: 9px;

            font-weight: 800;
        }


        .status.pending {
            background: #fbf2df;

            color: #9a7425;
        }


        .status.approved {
            background: var(--green-soft);

            color: var(--green);
        }


        .status.completed {
            background: #e8eff1;

            color: #587785;
        }


        .status.cancelled {
            background: #faeeee;

            color: var(--danger);
        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {
            padding: 35px 20px;

            text-align: center;

            color: var(--muted);

            font-size: 12px;
        }


        .empty-state i {
            display: block;

            margin-bottom: 9px;

            color: #b6c0b9;

            font-size: 25px;
        }


        /* =====================================================
           QUICK ACTIONS
        ===================================================== */

        .quick-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 11px;

            padding: 17px;
        }


        .quick-card {
            padding: 15px;

            border: 1px solid var(--border);

            border-radius: 13px;

            text-decoration: none;

            background: #fcfdfb;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }


        .quick-card:hover {
            transform: translateY(-2px);

            border-color: #cddbd2;

            box-shadow:
                0 8px 18px rgba(40, 65, 50, .07);
        }


        .quick-card i {
            color: var(--green);

            font-size: 17px;
        }


        .quick-card strong {
            display: block;

            margin-top: 10px;

            font-size: 11px;
        }


        .quick-card span {
            display: block;

            margin-top: 4px;

            color: var(--muted);

            font-size: 9px;
        }


        /* =====================================================
           MINI STATISTICS
        ===================================================== */

        .mini-stats {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            border-top: 1px solid var(--border);
        }


        .mini-stat {
            padding: 15px 12px;

            text-align: center;

            border-right: 1px solid var(--border);
        }


        .mini-stat:last-child {
            border-right: 0;
        }


        .mini-stat strong {
            display: block;

            color: var(--green-dark);

            font-size: 19px;
        }


        .mini-stat span {
            display: block;

            margin-top: 3px;

            color: var(--muted);

            font-size: 9px;
        }


        /* =====================================================
           RECENT MESSAGES
        ===================================================== */

        .message-item {
            padding: 15px 19px;

            border-bottom: 1px solid #eef0ed;
        }


        .message-item:last-child {
            border-bottom: 0;
        }


        .message-top {
            display: flex;

            justify-content: space-between;

            gap: 10px;
        }


        .message-name {
            color: var(--text);

            font-size: 12px;

            font-weight: 800;
        }


        .message-date {
            color: var(--muted);

            font-size: 9px;
        }


        .message-subject {
            margin-top: 5px;

            color: var(--green);

            font-size: 10px;

            font-weight: 700;
        }


        .message-preview {
            margin-top: 4px;

            color: var(--muted);

            font-size: 10px;

            line-height: 1.45;

            display: -webkit-box;

            -webkit-line-clamp: 2;

            -webkit-box-orient: vertical;

            overflow: hidden;
        }


        /* =====================================================
           CLINIC OVERVIEW
        ===================================================== */

        .overview-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 12px;

            padding: 17px;
        }


        .overview-box {
            padding: 14px;

            border-radius: 13px;

            background: var(--cream);
        }


        .overview-box i {
            color: var(--gold);

            font-size: 15px;
        }


        .overview-box strong {
            display: block;

            margin-top: 8px;

            font-size: 19px;

            color: var(--green-dark);
        }


        .overview-box span {
            display: block;

            margin-top: 3px;

            color: var(--muted);

            font-size: 9px;
        }


        /* =====================================================
           MEDICINE RESTOCK ALERT
        ===================================================== */
        .restock-alert {
            margin-bottom: 23px;
            border: 1px solid #eadfc9;
            border-radius: 17px;
            background: var(--white);
            box-shadow: 0 8px 25px rgba(40, 65, 50, .045);
            overflow: hidden;
        }
        .restock-alert.has-alert { border-color: #ead7ad; }
        .restock-alert-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px 21px;
            background: #fffaf0;
            border-bottom: 1px solid #f0e6d1;
        }
        .restock-heading {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .restock-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #fbf2df;
            color: var(--gold);
            font-size: 16px;
            flex: 0 0 auto;
        }
        .restock-title {
            margin: 0;
            color: var(--green-dark);
            font-size: 15px;
            font-weight: 800;
        }
        .restock-subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 11px;
        }
        .restock-summary {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .restock-count {
            padding: 6px 10px;
            border-radius: 30px;
            background: #fbf2df;
            color: #9a7425;
            font-size: 9px;
            font-weight: 800;
        }
        .restock-count.out {
            background: #faeeee;
            color: var(--danger);
        }
        .restock-body { padding: 0; }
        .restock-row {
            display: grid;
            grid-template-columns: minmax(160px, 1fr) 110px 190px;
            align-items: center;
            gap: 14px;
            padding: 13px 21px;
            border-bottom: 1px solid #eef0ed;
        }
        .restock-row:last-child { border-bottom: 0; }
        .restock-name strong {
            display: block;
            color: var(--text);
            font-size: 12px;
        }
        .restock-name span {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 10px;
        }
        .stock-meter {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .stock-bar {
            width: 58px;
            height: 7px;
            border-radius: 20px;
            background: #edf0ed;
            overflow: hidden;
        }
        .stock-fill {
            height: 100%;
            border-radius: inherit;
            background: var(--gold);
        }
        .stock-number {
            color: var(--text);
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }
        .restock-status {
            justify-self: end;
            width: max-content;
            padding: 6px 9px;
            border-radius: 30px;
            font-size: 9px;
            font-weight: 800;
        }
        .restock-status.low {
            background: #fbf2df;
            color: #9a7425;
        }
        .restock-status.out {
            background: #faeeee;
            color: var(--danger);
        }
        .restock-empty {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 17px 21px;
            color: var(--green);
            font-size: 11px;
            font-weight: 700;
        }
        .restock-empty i { font-size: 17px; }
        .restock-footer {
            display: flex;
            justify-content: flex-end;
            padding: 12px 21px;
            border-top: 1px solid var(--border);
            background: #fcfdfb;
        }

        @media (max-width: 700px) {
            .restock-alert-header {
                align-items: flex-start;
                flex-direction: column;
            }
            .restock-summary { justify-content: flex-start; }
            .restock-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            .restock-status { justify-self: start; }
            .stock-meter { width: max-content; }
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1200px) {

            .stats-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .dashboard-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 980px) {

            .admin-main {
                margin-left: 0;

                padding: 85px 20px 35px;
            }

        }


        @media (max-width: 700px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }


            .topbar {
                align-items: flex-start;

                flex-direction: column;
            }


            .topbar-actions {
                width: 100%;
            }


            .date-pill {
                flex: 1;
            }


            .primary-btn {
                flex: 1;
            }


            .appointment-row {
                grid-template-columns: 1fr;

                gap: 7px;
            }


            .appointment-time {
                margin-bottom: 2px;
            }


            .status {
                justify-self: start;
            }


            .overview-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 450px) {

            .admin-main {
                padding-left: 14px;

                padding-right: 14px;
            }


            .quick-grid {
                grid-template-columns: 1fr;
            }


            .panel-header {
                padding-left: 16px;

                padding-right: 16px;
            }

        }

    </style>

</head>


<body>

    <!-- =====================================================
         SHARED ADMIN SIDEBAR
    ====================================================== -->

    <?php include 'sidebar.php'; ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="admin-main">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="topbar">

            <div class="page-heading">

                <h1>
                    Good day, Admin! 🐾
                </h1>

                <p>
                    Here's what's happening at Minguito Veterinary Clinic today.
                </p>

            </div>


            <div class="topbar-actions">

                <div class="date-pill">

                    <i class="fa-regular fa-calendar"></i>

                    <?= date('F d, Y') ?>

                </div>


                <a
                    href="appointments.php"
                    class="primary-btn"
                >

                    <i class="fa-solid fa-calendar-plus"></i>

                    Manage Appointments

                </a>

            </div>

        </header>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <!-- TOTAL APPOINTMENTS -->

            <article class="stat-card">

                <div class="stat-top">

                    <div class="stat-icon">

                        <i class="fa-solid fa-calendar-check"></i>

                    </div>

                </div>


                <div class="stat-label">
                    Total Appointments
                </div>


                <div class="stat-value">
                    <?= $totalAppointments ?>
                </div>


                <div class="stat-note">
                    <?= $todayAppointments ?> scheduled for today
                </div>

            </article>


            <!-- PENDING -->

            <article class="stat-card gold">

                <div class="stat-top">

                    <div class="stat-icon">

                        <i class="fa-solid fa-clock"></i>

                    </div>

                </div>


                <div class="stat-label">
                    Pending
                </div>


                <div class="stat-value">
                    <?= $pendingAppointments ?>
                </div>


                <div class="stat-note">
                    Waiting for approval
                </div>

            </article>


            <!-- APPROVED -->

            <article class="stat-card">

                <div class="stat-top">

                    <div class="stat-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                </div>


                <div class="stat-label">
                    Approved
                </div>


                <div class="stat-value">
                    <?= $approvedAppointments ?>
                </div>


                <div class="stat-note">
                    Upcoming approved visits
                </div>

            </article>


            <!-- CANCELLED -->

            <article class="stat-card red">

                <div class="stat-top">

                    <div class="stat-icon">

                        <i class="fa-solid fa-circle-xmark"></i>

                    </div>

                </div>


                <div class="stat-label">
                    Cancelled
                </div>


                <div class="stat-value">
                    <?= $cancelledAppointments ?>
                </div>


                <div class="stat-note">
                    Cancelled appointments
                </div>

            </article>


            <!-- TOTAL SALES -->

            <article class="stat-card gold">

                <div class="stat-top">

                    <div class="stat-icon">

                        <i class="fa-solid fa-peso-sign"></i>

                    </div>

                </div>


                <div class="stat-label">
                    Total Sales
                </div>


                <div class="stat-value">
                    ₱<?= number_format($totalSales, 2) ?>
                </div>


                <div class="stat-note">
                    From paid transactions
                </div>

            </article>


        </section>


        <!-- =================================================
             MEDICINE RESTOCK ALERT
        ================================================== -->
        <section class="restock-alert <?= $restockCount > 0 ? 'has-alert' : '' ?>">
            <div class="restock-alert-header">
                <div class="restock-heading">
                    <div class="restock-icon">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <h2 class="restock-title">Medicine Stock Monitoring</h2>
                        <p class="restock-subtitle">
                            Maximum stock per medicine is <?= $maxMedicineStock ?>.
                            Low stock starts at 3 or below.
                        </p>
                    </div>
                </div>

                <div class="restock-summary">
                    <?php if ($outOfStockCount > 0): ?>
                        <span class="restock-count out">
                            <?= $outOfStockCount ?> Out of Stock
                        </span>
                    <?php endif; ?>
                    <?php if ($lowStockCount > 0): ?>
                        <span class="restock-count">
                            <?= $lowStockCount ?> Restock Suggested
                        </span>
                    <?php endif; ?>
                    <?php if ($restockCount === 0): ?>
                        <span class="restock-count">All Stock OK</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="restock-body">
                <?php if ($restockMedicines): ?>
                    <?php foreach ($restockMedicines as $medicine): ?>
                        <?php
                            $stock = max(0, min($maxMedicineStock, (int) $medicine['stock']));
                            $percentage = ($stock / $maxMedicineStock) * 100;
                            $isOut = $stock <= 0;
                        ?>
                        <div class="restock-row">
                            <div class="restock-name">
                                <strong><?= htmlspecialchars($medicine['name']) ?></strong>
                                <span>
                                    <?= $isOut ? 'Needs immediate restocking.' : 'Consider adding stock.' ?>
                                </span>
                            </div>
                            <div class="stock-meter">
                                <div class="stock-bar">
                                    <div class="stock-fill" style="width: <?= $percentage ?>%;"></div>
                                </div>
                                <span class="stock-number"><?= $stock ?> / <?= $maxMedicineStock ?></span>
                            </div>
                            <span class="restock-status <?= $isOut ? 'out' : 'low' ?>">
                                <?= $isOut ? 'Restock Required' : 'Restock Suggested' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="restock-empty">
                        <i class="fa-solid fa-circle-check"></i>
                        All active medicines are currently stocked above the restock level.
                    </div>
                <?php endif; ?>
            </div>

            <div class="restock-footer">
                <a href="medicines.php" class="panel-link">Manage Medicines →</a>
            </div>
        </section>


        <!-- =================================================
             DASHBOARD CONTENT
        ================================================== -->

        <div class="dashboard-grid">


            <!-- =================================================
                 LEFT COLUMN
            ================================================== -->

            <div>


                <!-- TODAY'S APPOINTMENTS -->

                <section class="panel">

                    <div class="panel-header">

                        <div>

                            <h2 class="panel-title">
                                Today's Appointments
                            </h2>

                            <p class="panel-subtitle">
                                Appointments scheduled for <?= date('F d, Y') ?>.
                            </p>

                        </div>


                        <a
                            href="appointments.php"
                            class="panel-link"
                        >
                            View all →
                        </a>

                    </div>


                    <div class="panel-body">

                        <?php if ($todayRows): ?>

                            <?php foreach ($todayRows as $row): ?>

                                <div class="appointment-row">


                                    <div class="appointment-time">

                                        <?= htmlspecialchars(
                                            formatTimeValue(
                                                $row['appointment_time']
                                            )
                                        ) ?>

                                    </div>


                                    <div class="pet-info">

                                        <strong>
                                            <?= htmlspecialchars(
                                                $row['pet_name']
                                            ) ?>
                                        </strong>

                                        <span>
                                            Owner:
                                            <?= htmlspecialchars(
                                                $row['owner_name']
                                            ) ?>
                                        </span>

                                    </div>


                                    <div class="service-info">

                                        <strong>
                                            <?= htmlspecialchars(
                                                $row['service']
                                            ) ?>
                                        </strong>

                                        <span>
                                            Appointment #
                                            <?= (int) $row['id'] ?>
                                        </span>

                                    </div>


                                    <span
                                        class="status <?= htmlspecialchars(
                                            statusClass(
                                                $row['status']
                                            )
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $row['status']
                                        ) ?>

                                    </span>


                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="empty-state">

                                <i class="fa-regular fa-calendar"></i>

                                No appointments scheduled for today.

                            </div>

                        <?php endif; ?>

                    </div>

                </section>


                <!-- RECENT APPOINTMENTS -->

                <section class="panel">

                    <div class="panel-header">

                        <div>

                            <h2 class="panel-title">
                                Recent Appointments
                            </h2>

                            <p class="panel-subtitle">
                                Latest bookings received by the clinic.
                            </p>

                        </div>


                        <a
                            href="appointments.php"
                            class="panel-link"
                        >
                            Manage →
                        </a>

                    </div>


                    <div class="panel-body">

                        <?php if ($recentRows): ?>

                            <?php foreach ($recentRows as $row): ?>

                                <div class="appointment-row">


                                    <div class="appointment-time">

                                        <?= htmlspecialchars(
                                            formatDateValue(
                                                $row['appointment_date']
                                            )
                                        ) ?>

                                    </div>


                                    <div class="pet-info">

                                        <strong>
                                            <?= htmlspecialchars(
                                                $row['pet_name']
                                            ) ?>
                                        </strong>

                                        <span>
                                            <?= htmlspecialchars(
                                                $row['owner_name']
                                            ) ?>
                                        </span>

                                    </div>


                                    <div class="service-info">

                                        <strong>
                                            <?= htmlspecialchars(
                                                $row['service']
                                            ) ?>
                                        </strong>

                                        <span>
                                            <?= htmlspecialchars(
                                                $row['customer_name']
                                                    ?: 'Guest record'
                                            ) ?>
                                        </span>

                                    </div>


                                    <span
                                        class="status <?= htmlspecialchars(
                                            statusClass(
                                                $row['status']
                                            )
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $row['status']
                                        ) ?>

                                    </span>


                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="empty-state">

                                <i class="fa-solid fa-paw"></i>

                                No appointments found yet.

                            </div>

                        <?php endif; ?>

                    </div>

                </section>


            </div>


            <!-- =================================================
                 RIGHT COLUMN
            ================================================== -->

            <div>


                <!-- QUICK ACTIONS -->

                <section class="panel">

                    <div class="panel-header">

                        <div>

                            <h2 class="panel-title">
                                Quick Actions
                            </h2>

                            <p class="panel-subtitle">
                                Jump directly to an admin section.
                            </p>

                        </div>

                    </div>


                    <div class="quick-grid">


                        <!-- APPOINTMENTS -->

                        <a
                            href="appointments.php"
                            class="quick-card"
                        >

                            <i class="fa-solid fa-calendar-check"></i>

                            <strong>
                                Appointments
                            </strong>

                            <span>
                                Review and update bookings
                            </span>

                        </a>


                        <!-- CALENDAR -->

                        <a
                            href="calendar.php"
                            class="quick-card"
                        >

                            <i class="fa-solid fa-calendar-days"></i>

                            <strong>
                                Calendar
                            </strong>

                            <span>
                                View the appointment schedule
                            </span>

                        </a>


                        <!-- PATIENTS -->

                        <a
                            href="patients.php"
                            class="quick-card"
                        >

                            <i class="fa-solid fa-paw"></i>

                            <strong>
                                Patients
                            </strong>

                            <span>
                                Check pet visit records
                            </span>

                        </a>


                        <!-- SERVICES -->

                        <a
                            href="services.php"
                            class="quick-card"
                        >

                            <i class="fa-solid fa-stethoscope"></i>

                            <strong>
                                Services
                            </strong>

                            <span>
                                Manage clinic services
                            </span>

                        </a>


                        <!-- MESSAGES -->

                        <a
                            href="messages.php"
                            class="quick-card"
                        >

                            <i class="fa-solid fa-envelope"></i>

                            <strong>
                                Messages
                            </strong>

                            <span>
                                Read customer inquiries
                            </span>

                        </a>


                        <!-- SETTINGS -->

                        <a
                            href="settings.php"
                            class="quick-card"
                        >

                            <i class="fa-solid fa-gear"></i>

                            <strong>
                                Settings
                            </strong>

                            <span>
                                Manage admin account
                            </span>

                        </a>


                    </div>


                    <!-- MINI STATISTICS -->

                    <div class="mini-stats">


                        <div class="mini-stat">

                            <strong>
                                <?= $totalPatients ?>
                            </strong>

                            <span>
                                Patients
                            </span>

                        </div>


                        <div class="mini-stat">

                            <strong>
                                <?= $totalCustomers ?>
                            </strong>

                            <span>
                                Customers
                            </span>

                        </div>


                        <div class="mini-stat">

                            <strong>
                                <?= $activeServices ?>
                            </strong>

                            <span>
                                Active Services
                            </span>

                        </div>


                    </div>

                </section>


                <!-- CLINIC OVERVIEW -->

                <section class="panel">

                    <div class="panel-header">

                        <div>

                            <h2 class="panel-title">
                                Clinic Overview
                            </h2>

                            <p class="panel-subtitle">
                                Current database totals.
                            </p>

                        </div>

                    </div>


                    <div class="overview-grid">


                        <!-- COMPLETED -->

                        <div class="overview-box">

                            <i class="fa-solid fa-check-double"></i>

                            <strong>
                                <?= $completedAppointments ?>
                            </strong>

                            <span>
                                Completed appointments
                            </span>

                        </div>


                        <!-- SERVICES -->

                        <div class="overview-box">

                            <i class="fa-solid fa-list-check"></i>

                            <strong>
                                <?= $totalServices ?>
                            </strong>

                            <span>
                                Total services
                            </span>

                        </div>


                        <!-- CUSTOMERS -->

                        <div class="overview-box">

                            <i class="fa-solid fa-user-group"></i>

                            <strong>
                                <?= $totalCustomers ?>
                            </strong>

                            <span>
                                Registered customers
                            </span>

                        </div>


                        <!-- MESSAGES -->

                        <div class="overview-box">

                            <i class="fa-solid fa-message"></i>

                            <strong>
                                <?= $totalMessages ?>
                            </strong>

                            <span>
                                Contact messages
                            </span>

                        </div>


                    </div>

                </section>


                <!-- RECENT MESSAGES -->

                <section class="panel">

                    <div class="panel-header">

                        <div>

                            <h2 class="panel-title">
                                Recent Messages
                            </h2>

                            <p class="panel-subtitle">
                                Latest messages from the contact form.
                            </p>

                        </div>


                        <a
                            href="messages.php"
                            class="panel-link"
                        >
                            View all →
                        </a>

                    </div>


                    <div class="panel-body">

                        <?php if ($messageRows): ?>

                            <?php foreach ($messageRows as $message): ?>

                                <div class="message-item">


                                    <div class="message-top">

                                        <span class="message-name">

                                            <?= htmlspecialchars(
                                                $message['name']
                                            ) ?>

                                        </span>


                                        <span class="message-date">

                                            <?= htmlspecialchars(
                                                formatDateValue(
                                                    substr(
                                                        $message['created_at'],
                                                        0,
                                                        10
                                                    )
                                                )
                                            ) ?>

                                        </span>

                                    </div>


                                    <div class="message-subject">

                                        <?= htmlspecialchars(
                                            $message['subject']
                                        ) ?>

                                    </div>


                                    <div class="message-preview">

                                        <?= htmlspecialchars(
                                            $message['message']
                                        ) ?>

                                    </div>


                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="empty-state">

                                <i class="fa-regular fa-envelope"></i>

                                No messages received yet.

                            </div>

                        <?php endif; ?>

                    </div>

                </section>


            </div>


        </div>


    </main>


</body>

</html>