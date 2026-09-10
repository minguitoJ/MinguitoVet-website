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

/*
 * Appointment display choices
 *
 * teacher_latest  = newest booking/record first (teacher's requirement)
 * upcoming        = original appointment view: nearest date/time first
 */
$sort = trim($_GET['sort'] ?? 'teacher_latest');

$allowedSorts = [
    'teacher_latest',
    'upcoming'
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'teacher_latest';
}


// =====================================
// UPDATE APPOINTMENT STATUS
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    $allowedStatuses = [
        'Pending',
        'Approved',
        'Completed',
        'Cancelled'
    ];

    if (
        $appointmentId > 0 &&
        in_array($newStatus, $allowedStatuses, true)
    ) {

        /*
         * Completed and Cancelled appointments are permanently locked.
         * They cannot be changed again after completion/cancellation.
         */
        $currentStmt = $pdo->prepare("
            SELECT status
            FROM appointments
            WHERE id = :id
            LIMIT 1
        ");

        $currentStmt->execute([
            ':id' => $appointmentId
        ]);

        $currentStatus = $currentStmt->fetchColumn();

        if ($currentStatus !== 'Completed' && $currentStatus !== 'Cancelled') {

            /*
             * An appointment can only become Completed
             * after its payment has been marked Paid.
             */
            $canComplete = true;

            if ($newStatus === 'Completed') {

                $paymentStmt = $pdo->prepare("
                    SELECT payment_status
                    FROM payments
                    WHERE appointment_id = :appointment_id
                    ORDER BY id DESC
                    LIMIT 1
                ");

                $paymentStmt->execute([
                    ':appointment_id' => $appointmentId
                ]);

                $paymentStatus = $paymentStmt->fetchColumn();

                if ($paymentStatus !== 'Paid') {
                    $canComplete = false;
                }
            }

            if ($canComplete) {

                /*
                 * Cancellation + refund:
                 * - If the latest payment is Paid, mark it Refunded.
                 * - Restore medicine quantities that were deducted during payment.
                 * - If the latest payment is Pending, simply cancel the payment.
                 * - Everything is committed together with the appointment cancellation.
                 */
                if ($newStatus === 'Cancelled') {

                    $pdo->beginTransaction();

                    try {

                        $paymentLockStmt = $pdo->prepare("
                            SELECT id, payment_status
                            FROM payments
                            WHERE appointment_id = :appointment_id
                            ORDER BY id DESC
                            LIMIT 1
                            FOR UPDATE
                        ");

                        $paymentLockStmt->execute([
                            ':appointment_id' => $appointmentId
                        ]);

                        $lockedPayment = $paymentLockStmt->fetch(PDO::FETCH_ASSOC);

                        if (
                            $lockedPayment &&
                            $lockedPayment['payment_status'] === 'Paid'
                        ) {

                            /*
                             * Restore medicine stock from the paid transaction.
                             * Maximum medicine stock remains 5.
                             */
                            $itemsLockStmt = $pdo->prepare("
                                SELECT item_type, item_name, quantity
                                FROM payment_items
                                WHERE payment_id = :payment_id
                                FOR UPDATE
                            ");

                            $itemsLockStmt->execute([
                                ':payment_id' => $lockedPayment['id']
                            ]);

                            $refundItems = $itemsLockStmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($refundItems as $refundItem) {

                                if (
                                    strtolower(trim((string)$refundItem['item_type'])) !==
                                    'medicine'
                                ) {
                                    continue;
                                }

                                $quantity = (int)$refundItem['quantity'];

                                if ($quantity <= 0) {
                                    continue;
                                }

                                $medicineStmt = $pdo->prepare("
                                    UPDATE medicines
                                    SET stock = LEAST(stock + :quantity, 5)
                                    WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))
                                ");

                                $medicineStmt->execute([
                                    ':quantity' => $quantity,
                                    ':name' => $refundItem['item_name']
                                ]);
                            }

                            /*
                             * A refunded payment is no longer a sale.
                             */
                            $refundStmt = $pdo->prepare("
                                UPDATE payments
                                SET payment_status = 'Refunded',
                                    refunded_at = NOW()
                                WHERE id = :id
                                  AND payment_status = 'Paid'
                            ");

                            $refundStmt->execute([
                                ':id' => $lockedPayment['id']
                            ]);

                        } elseif (
                            $lockedPayment &&
                            $lockedPayment['payment_status'] === 'Pending'
                        ) {

                            $cancelPaymentStmt = $pdo->prepare("
                                UPDATE payments
                                SET payment_status = 'Cancelled',
                                    paid_at = NULL
                                WHERE id = :id
                                  AND payment_status = 'Pending'
                            ");

                            $cancelPaymentStmt->execute([
                                ':id' => $lockedPayment['id']
                            ]);
                        }

                        $stmt = $pdo->prepare("
                            UPDATE appointments
                            SET status = 'Cancelled'
                            WHERE id = :id
                              AND status NOT IN ('Completed', 'Cancelled')
                        ");

                        $stmt->execute([
                            ':id' => $appointmentId
                        ]);

                        $pdo->commit();

                    } catch (Throwable $e) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        error_log(
                            'Minguito appointment cancellation/refund error: ' .
                            $e->getMessage()
                        );

                        header('Location: appointments.php?error=refund_failed');
                        exit;
                    }

                } else {

                    $stmt = $pdo->prepare("
                        UPDATE appointments
                        SET status = :status
                        WHERE id = :id
                        AND status NOT IN ('Completed', 'Cancelled')
                    ");

                    $stmt->execute([
                        ':status' => $newStatus,
                        ':id' => $appointmentId
                    ]);
                }

            } else {

                /*
                 * Preserve filters and show a payment-required message.
                 */
                $query = [
                    'error' => 'payment_required'
                ];

                if ($search !== '') {
                    $query['search'] = $search;
                }

                if ($statusFilter !== '') {
                    $query['status'] = $statusFilter;
                }

                if ($dateFilter !== '') {
                    $query['date'] = $dateFilter;
                }

                header(
                    'Location: appointments.php?' .
                    http_build_query($query)
                );
                exit;
            }
        }
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

    if ($sort !== 'teacher_latest') {
        $query['sort'] = $sort;
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
        c.contact_number AS customer_contact,
        p.id AS payment_id,
        COALESCE(p.payment_status, 'Unpaid') AS payment_status,
        COALESCE(p.payment_method, '') AS payment_method
    FROM appointments a

    LEFT JOIN users c
        ON a.customer_id = c.id
       AND c.role = 'customer'

    LEFT JOIN payments p
        ON p.id = (
            SELECT MAX(p2.id)
            FROM payments p2
            WHERE p2.appointment_id = a.id
        )

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
switch ($sort) {
    case 'upcoming':
        // Nearest scheduled appointment first.
        $sql .= "
            ORDER BY
                a.appointment_date ASC,
                a.appointment_time ASC,
                a.id DESC
        ";
        break;



    case 'teacher_latest':
    default:
        // Teacher's requirement: latest booking/record at the top.
        $sql .= "
            ORDER BY
                a.created_at DESC,
                a.id DESC
        ";
        break;
}


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

$approvedAppointments = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM appointments
        WHERE status = 'Approved'
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
            --green: #2f6b4f;
            --green-light: #24543e;
            --cream: #f8f1e5;
            --gold: #c89b3c;
            --gold-light: #c89b3c;
            --white: #ffffff;
            --text: #26352d;
            --muted: #7b877f;
            --border: #e4e8e3;

            --success: #2f6b4f;
            --warning: #9a7425;
            --danger: #c65c5c;

            --blue: #5d8497;
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
                    #f5f6f2,
                    #f8f1e5
                );
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
                1.6fr
                1fr
                1fr
                1.35fr
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
                #e4e8e3;

            border-radius: 10px;

            background: #fcfdfb;

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

            min-width: 1140px;
        }


        th {
            padding: 14px 16px;

            background: #f8f1e5;

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
                #eef0ed;

            font-size: 12px;

            vertical-align: middle;
        }


        tbody tr:hover {
            background: #fcfdfb;
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

            background: #fcfdfb;

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
           ALERT
        ===================================== */

        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 12px;
            line-height: 1.5;
        }

        .alert-warning {
            color: #79520f;
            background: #fff5dc;
            border: 1px solid #ead09b;
        }

        .alert-warning strong {
            color: #6c4707;
            font-weight: 800;
        }


        /* =====================================
           ACTIONS
        ===================================== */

        .action-cell {
            white-space: nowrap;
        }

        .payment-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 11px;
            border-radius: 8px;
            background: var(--gold);
            color: white;
            text-decoration: none;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .2px;
            transition: .2s ease;
        }

        .payment-action:hover {
            background: var(--green);
        }

        .payment-paid {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 34px;
            padding: 0 10px;
            border-radius: 8px;
            background: #e9f5ed;
            color: var(--success);
            font-size: 10px;
            font-weight: 800;
        }

        .payment-waiting {
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
        }

        .locked-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--success);
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
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


    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .admin-content {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
            padding: 34px 38px 50px;
            box-sizing: border-box;
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


<!-- =====================================
     HEADER
===================================== -->




<!-- =====================================
     MAIN
===================================== -->

<main class="main admin-content">

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


        <?php if (($_GET['error'] ?? '') === 'payment_required'): ?>

            <div class="alert alert-warning">
                <strong>Payment Required</strong>
                <span>
                    This appointment cannot be marked as Completed
                    until its payment has been recorded as Paid.
                </span>
            </div>

        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === 'refund_failed'): ?>
            <div class="alert alert-warning">
                <strong>Cancellation Failed</strong>
                <span>
                    The appointment could not be cancelled and the refund was not completed.
                    Please try again.
                </span>
            </div>
        <?php endif; ?>


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
                    Approved
                </div>

                <div class="stat-number">
                    <?= $approvedAppointments ?>
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
                            value="Approved"
                            <?= $statusFilter === 'Approved'
                                ? 'selected'
                                : '' ?>
                        >
                            Approved
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


                <div class="field">

                    <label for="sort">
                        Show / Sort By
                    </label>

                    <select
                        id="sort"
                        name="sort"
                        onchange="this.form.submit()"
                        title="Choose how appointments should be displayed"
                    >
                        <option
                            value="teacher_latest"
                            <?= $sort === 'teacher_latest'
                                ? 'selected'
                                : '' ?>
                        >
                            Latest Added
                        </option>

                        <option
                            value="upcoming"
                            <?= $sort === 'upcoming'
                                ? 'selected'
                                : '' ?>
                        >
                            Nearest Date &amp; Time
                        </option>
                    </select>

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

                                <th>
                                    Payment
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

                                        <?php if ($appointment['status'] === 'Completed'): ?>

                                            <div class="locked-status">
                                                🔒 Completed
                                            </div>

                                        <?php elseif ($appointment['status'] === 'Cancelled'): ?>

                                            <div class="locked-status">
                                                🔒 Cancelled
                                            </div>

                                            <?php if ($appointment['payment_status'] === 'Refunded'): ?>
                                                <div style="
                                                    margin-top: 6px;
                                                    color: #4c568f;
                                                    font-size: 10px;
                                                    font-weight: 800;
                                                ">
                                                    ↩ Refunded
                                                </div>
                                            <?php endif; ?>

                                        <?php else: ?>

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
                                                        value="Approved"
                                                        <?= $appointment[
                                                            'status'
                                                        ] === 'Approved'
                                                            ? 'selected'
                                                            : '' ?>
                                                    >
                                                        Approved
                                                    </option>


                                                    <option
                                                        value="Completed"
                                                        <?= $appointment[
                                                            'status'
                                                        ] === 'Completed'
                                                            ? 'selected'
                                                            : '' ?>
                                                        <?= $appointment[
                                                            'payment_status'
                                                        ] !== 'Paid'
                                                            ? 'disabled'
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

                                        <?php endif; ?>

                                    </td>


                                    <!-- PAYMENT -->

                                    <td class="action-cell">

                                        <?php if (
                                            $appointment['status'] === 'Approved' &&
                                            $appointment['payment_status'] !== 'Paid'
                                        ): ?>

                                            <a
                                                href="payment.php?appointment_id=<?= (int)
                                                    $appointment['id'] ?>"
                                                class="payment-action"
                                            >
                                                <?php if (
                                                    ($appointment['payment_method'] ?? '') === 'Cash'
                                                    && ($appointment['payment_status'] ?? '') === 'Pending'
                                                ): ?>
                                                    Review Payment
                                                <?php elseif (
                                                    ($appointment['payment_method'] ?? '') === 'GCash'
                                                    && ($appointment['payment_status'] ?? '') === 'Pending'
                                                ): ?>
                                                    Review Payment
                                                <?php else: ?>
                                                    Prepare Payment
                                                <?php endif; ?>
                                            </a>

                                        <?php elseif (
                                            $appointment['payment_status'] === 'Paid'
                                        ): ?>

                                            <span class="payment-paid">
                                                ✓ Paid
                                            </span>

                                        <?php elseif (
                                            $appointment['status'] === 'Pending'
                                        ): ?>

                                            <span class="payment-waiting">
                                                Awaiting approval
                                            </span>

                                        <?php elseif (
                                            $appointment['status'] === 'Completed'
                                        ): ?>

                                            <span class="payment-paid">
                                                ✓ Paid
                                            </span>

                                        <?php else: ?>

                                            <span class="payment-waiting">
                                                —
                                            </span>

                                        <?php endif; ?>

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