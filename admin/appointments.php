<?php

session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";

requireLogin();


// =====================================
// SEARCH & FILTERS
// =====================================

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');


// =====================================
// UPDATE APPOINTMENT STATUS
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    $allowedStatuses = [
        'Pending',
        'Confirmed',
        'Completed',
        'Cancelled'
    ];

    if (
        $appointmentId > 0 &&
        in_array($newStatus, $allowedStatuses, true)
    ) {

        $stmt = $pdo->prepare("
            UPDATE appointments
            SET status = :status
            WHERE id = :id
        ");

        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $appointmentId
        ]);
    }

    // Preserve filters after updating
    $query = [];

    if ($search !== '') {
        $query['search'] = $search;
    }

    if ($statusFilter !== '') {
        $query['status'] = $statusFilter;
    }

    if ($dateFilter !== '') {
        $query['date'] = $dateFilter;
    }

    $redirectUrl = 'appointments.php';

    if (!empty($query)) {
        $redirectUrl .= '?' . http_build_query($query);
    }

    header('Location: ' . $redirectUrl);
    exit;
}


// =====================================
// BUILD APPOINTMENT QUERY
// =====================================

$sql = "
    SELECT
        a.id,
        a.owner_name,
        a.pet_name,
        a.service,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.created_at,
        c.full_name AS customer_name,
        c.email AS customer_email,
        c.contact_number AS customer_contact
    FROM appointments a

    LEFT JOIN customers c
        ON a.customer_id = c.id

    WHERE 1=1
";

$params = [];


// Search
if ($search !== '') {

    $sql .= "
        AND (
            a.owner_name LIKE :search
            OR a.pet_name LIKE :search
            OR a.service LIKE :search
            OR c.full_name LIKE :search
            OR c.email LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}


// Status filter
if ($statusFilter !== '') {

    $sql .= "
        AND a.status = :status
    ";

    $params[':status'] = $statusFilter;
}


// Date filter
if ($dateFilter !== '') {

    $sql .= "
        AND a.appointment_date = :appointment_date
    ";

    $params[':appointment_date'] = $dateFilter;
}


// Order
$sql .= "
    ORDER BY
        a.appointment_date ASC,
        a.appointment_time ASC,
        a.id ASC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$appointments = $stmt->fetchAll();


// =====================================
// COUNTS
// =====================================

$totalAppointments = (int) $pdo
    ->query("SELECT COUNT(*) FROM appointments")
    ->fetchColumn();

$pendingAppointments = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM appointments
        WHERE status = 'Pending'
    ")
    ->fetchColumn();

$confirmedAppointments = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM appointments
        WHERE status = 'Confirmed'
    ")
    ->fetchColumn();

$completedAppointments = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM appointments
        WHERE status = 'Completed'
    ")
    ->fetchColumn();

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
        Appointments | Minguito Veterinary Clinic
    </title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >


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

            --blue: #315a78;
        }


        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            min-height: 100vh;

            font-family:
                "DM Sans",
                Arial,
                sans-serif;

            color: var(--text);

            background:
                linear-gradient(
                    135deg,
                    #fbf4e9,
                    #f4e6d2
                );
        }


        /* =====================================
           HEADER
        ===================================== */

        .admin-header {
            background: var(--green);

            color: white;

            padding: 18px 0;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, .12);
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

            font-size: 20px;
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

            font:
                700 36px/1.1
                "Playfair Display",
                Georgia,
                serif;

            margin-bottom: 8px;
        }


        .page-title p {
            color: var(--muted);

            font-size: 14px;
        }


        /* =====================================
           STAT CARDS
        ===================================== */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;

            margin-bottom: 25px;
        }


        .stat {
            background: white;

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 19px;

            box-shadow:
                0 10px 28px
                rgba(7,59,42,.07);
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


        /* =====================================
           FILTER BOX
        ===================================== */

        .filter-card {
            background: white;

            border: 1px solid var(--border);

            border-radius: 18px;

            padding: 20px;

            box-shadow:
                0 10px 28px
                rgba(7,59,42,.07);

            margin-bottom: 22px;
        }


        .filter-form {
            display: grid;

            grid-template-columns:
                1.8fr
                1fr
                1fr
                auto
                auto;

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

            border:
                1px solid
                #dccab4;

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

            box-shadow:
                0 0 0 3px
                rgba(181,122,47,.1);
        }


        .filter-btn {
            height: 44px;

            padding: 0 17px;

            border: 0;

            border-radius: 10px;

            background: var(--green);

            color: white;

            font: inherit;

            font-size: 12px;

            font-weight: 800;

            cursor: pointer;
        }


        .filter-btn:hover {
            background: var(--gold);
        }


        .clear-btn {
            height: 44px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 0 15px;

            border:
                1px solid
                var(--border);

            border-radius: 10px;

            background: white;

            color: var(--green);

            text-decoration: none;

            font-size: 12px;

            font-weight: 800;
        }


        .clear-btn:hover {
            background: var(--cream);
        }


        /* =====================================
           TABLE
        ===================================== */

        .table-card {
            background: white;

            border:
                1px solid
                var(--border);

            border-radius: 18px;

            overflow: hidden;

            box-shadow:
                0 10px 28px
                rgba(7,59,42,.07);
        }


        .table-header {
            padding: 19px 22px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom:
                1px solid
                var(--border);
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

            min-width: 1000px;
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

            border-top:
                1px solid
                #eee3d4;

            font-size: 12px;

            vertical-align: middle;
        }


        tbody tr:hover {
            background: #fffbf5;
        }


        .pet {
            color: var(--green);

            font-size: 14px;

            font-weight: 800;
        }


        .owner {
            color: var(--text);

            font-weight: 700;
        }


        .customer {
            margin-top: 3px;

            color: var(--muted);

            font-size: 11px;
        }


        .service {
            color: var(--green);

            font-weight: 700;
        }


        .date {
            font-weight: 700;
        }


        .time {
            margin-top: 3px;

            color: var(--muted);

            font-size: 11px;
        }


        /* =====================================
           STATUS
        ===================================== */

        .status-form {
            display: flex;

            align-items: center;

            gap: 7px;
        }


        .status-select {
            height: 34px;

            padding: 0 8px;

            border-radius: 8px;

            border: 1px solid var(--border);

            background: #fffaf3;

            color: var(--green);

            font: inherit;

            font-size: 10px;

            font-weight: 800;

            cursor: pointer;
        }


        .status-update {
            height: 34px;

            padding: 0 9px;

            border: 0;

            border-radius: 8px;

            background: var(--green);

            color: white;

            font-size: 10px;

            font-weight: 800;

            cursor: pointer;
        }


        .status-update:hover {
            background: var(--gold);
        }


        /* =====================================
           EMPTY
        ===================================== */

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
           RESPONSIVE
        ===================================== */

        @media (max-width: 950px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .filter-form {
                grid-template-columns:
                    1fr 1fr;
            }

        }


        @media (max-width: 600px) {

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


            .page-title h1 {
                font-size: 31px;
            }


            .stats {
                grid-template-columns: 1fr;
            }


            .filter-form {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =====================================
     HEADER
===================================== -->

<header class="admin-header">

    <div class="header-inner">


        <div class="brand">

            <div class="brand-icon">
                🐾
            </div>

            <div class="brand-text">

                <strong>
                    Minguito Veterinary
                </strong>

                <span>
                    Administration Panel
                </span>

            </div>

        </div>


        <div class="header-links">

            <a
                href="dashboard.php"
                class="header-link"
            >
                Dashboard
            </a>


            <a
                href="../logout.php"
                class="header-link logout"
            >
                Logout
            </a>

        </div>


    </div>

</header>


<!-- =====================================
     MAIN
===================================== -->

<main class="main">

    <div class="container">


        <!-- PAGE TITLE -->

        <section class="page-title">

            <p class="section-label">
                Admin Panel
            </p>

            <h1>
                Appointments
            </h1>

            <p>
                Manage and monitor all veterinary appointments.
            </p>

        </section>


        <!-- =================================
             STATISTICS
        ================================== -->

        <section class="stats">


            <div class="stat">

                <div class="stat-label">
                    Total
                </div>

                <div class="stat-number">
                    <?= $totalAppointments ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Pending
                </div>

                <div class="stat-number">
                    <?= $pendingAppointments ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Confirmed
                </div>

                <div class="stat-number">
                    <?= $confirmedAppointments ?>
                </div>

            </div>


            <div class="stat">

                <div class="stat-label">
                    Completed
                </div>

                <div class="stat-number">
                    <?= $completedAppointments ?>
                </div>

            </div>


        </section>


        <!-- =================================
             FILTERS
        ================================== -->

        <section class="filter-card">

            <form
                method="GET"
                action="appointments.php"
                class="filter-form"
            >


                <div class="field">

                    <label for="search">
                        Search
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="<?= htmlspecialchars(
                            $search,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="Owner, pet, service..."
                    >

                </div>


                <div class="field">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="Pending"
                            <?= $statusFilter === 'Pending'
                                ? 'selected'
                                : '' ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Confirmed"
                            <?= $statusFilter === 'Confirmed'
                                ? 'selected'
                                : '' ?>
                        >
                            Confirmed
                        </option>

                        <option
                            value="Completed"
                            <?= $statusFilter === 'Completed'
                                ? 'selected'
                                : '' ?>
                        >
                            Completed
                        </option>

                        <option
                            value="Cancelled"
                            <?= $statusFilter === 'Cancelled'
                                ? 'selected'
                                : '' ?>
                        >
                            Cancelled
                        </option>

                    </select>

                </div>


                <div class="field">

                    <label for="date">
                        Date
                    </label>

                    <input
                        type="date"
                        id="date"
                        name="date"
                        value="<?= htmlspecialchars(
                            $dateFilter,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                </div>


                <button
                    type="submit"
                    class="filter-btn"
                >
                    Search
                </button>


                <a
                    href="appointments.php"
                    class="clear-btn"
                >
                    Clear
                </a>


            </form>

        </section>


        <!-- =================================
             APPOINTMENT TABLE
        ================================== -->

        <section class="table-card">


            <div class="table-header">

                <h2>
                    Appointment Records
                </h2>

                <span class="result-count">

                    <?= count($appointments) ?>

                    result<?= count($appointments) === 1
                        ? ''
                        : 's' ?>

                </span>

            </div>


            <?php if (!empty($appointments)): ?>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Pet
                                </th>

                                <th>
                                    Owner
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Appointment
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $appointments
                                as $appointment
                            ): ?>


                                <tr>


                                    <!-- PET -->

                                    <td>

                                        <div class="pet">

                                            <?= htmlspecialchars(
                                                $appointment['pet_name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- OWNER -->

                                    <td>

                                        <div class="owner">

                                            <?= htmlspecialchars(
                                                $appointment['owner_name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>


                                        <?php if (
                                            !empty(
                                                $appointment['customer_name']
                                            )
                                        ): ?>

                                            <div class="customer">

                                                Account:
                                                <?= htmlspecialchars(
                                                    $appointment[
                                                        'customer_name'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (
                                            !empty(
                                                $appointment['customer_email']
                                            )
                                        ): ?>

                                            <div class="customer">

                                                <?= htmlspecialchars(
                                                    $appointment[
                                                        'customer_email'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <!-- SERVICE -->

                                    <td>

                                        <div class="service">

                                            <?= htmlspecialchars(
                                                $appointment['service'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- DATE/TIME -->

                                    <td>

                                        <div class="date">

                                            <?= date(
                                                'M d, Y',
                                                strtotime(
                                                    $appointment[
                                                        'appointment_date'
                                                    ]
                                                )
                                            ) ?>

                                        </div>


                                        <div class="time">

                                            <?= date(
                                                'h:i A',
                                                strtotime(
                                                    $appointment[
                                                        'appointment_time'
                                                    ]
                                                )
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <form
                                            method="POST"
                                            class="status-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="appointment_id"
                                                value="<?= (int)
                                                    $appointment['id'] ?>"
                                            >


                                            <select
                                                name="status"
                                                class="status-select"
                                            >

                                                <option
                                                    value="Pending"
                                                    <?= $appointment[
                                                        'status'
                                                    ] === 'Pending'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Pending
                                                </option>


                                                <option
                                                    value="Confirmed"
                                                    <?= $appointment[
                                                        'status'
                                                    ] === 'Confirmed'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Confirmed
                                                </option>


                                                <option
                                                    value="Completed"
                                                    <?= $appointment[
                                                        'status'
                                                    ] === 'Completed'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Completed
                                                </option>


                                                <option
                                                    value="Cancelled"
                                                    <?= $appointment[
                                                        'status'
                                                    ] === 'Cancelled'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Cancelled
                                                </option>

                                            </select>


                                            <button
                                                type="submit"
                                                class="status-update"
                                            >
                                                Update
                                            </button>

                                        </form>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    <div class="empty-icon">
                        📅
                    </div>

                    No appointments found.

                    <?php if (
                        $search !== '' ||
                        $statusFilter !== '' ||
                        $dateFilter !== ''
                    ): ?>

                        <br>

                        Try changing your search or filters.

                    <?php endif; ?>

                </div>


            <?php endif; ?>


        </section>


    </div>

</main>


</body>

</html>