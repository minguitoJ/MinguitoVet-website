<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

/* =========================================================
   MONTH / YEAR NAVIGATION
   ========================================================= */
$currentMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$currentYear  = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

if ($currentMonth < 1) {
    $currentMonth = 12;
    $currentYear--;
}

if ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

$firstDay = new DateTime(sprintf('%04d-%02d-01', $currentYear, $currentMonth));
$lastDay  = (clone $firstDay)->modify('last day of this month');

$startDate = $firstDay->format('Y-m-d');
$endDate   = $lastDay->format('Y-m-d');
$today     = date('Y-m-d');

$monthName = $firstDay->format('F Y');

/* =========================================================
   LOAD APPOINTMENTS FOR SELECTED MONTH
   ========================================================= */
$stmt = $pdo->prepare("\n    SELECT\n        a.id,\n        a.customer_id,\n        a.owner_name,\n        a.pet_name,\n        a.service,\n        a.appointment_date,\n        a.appointment_time,\n        a.status,\n        c.full_name AS customer_name,\n        c.email AS customer_email\n    FROM appointments a\n    LEFT JOIN users c ON a.customer_id = c.id AND c.role = 'customer'\n    WHERE a.appointment_date BETWEEN :start_date AND :end_date\n    ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.id ASC\n");
$stmt->execute([
    ':start_date' => $startDate,
    ':end_date' => $endDate
]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$appointmentsByDate = [];

foreach ($appointments as $appointment) {
    $dateKey = $appointment['appointment_date'];
    if (!isset($appointmentsByDate[$dateKey])) {
        $appointmentsByDate[$dateKey] = [];
    }
    $appointmentsByDate[$dateKey][] = $appointment;
}

/* =========================================================
   MONTH STATISTICS
   ========================================================= */
$totalAppointments = count($appointments);
$pendingCount = 0;
$approvedCount = 0;
$completedCount = 0;
$cancelledCount = 0;

foreach ($appointments as $appointment) {
    switch ($appointment['status']) {
        case 'Pending':
            $pendingCount++;
            break;
        case 'Approved':
            $approvedCount++;
            break;
        case 'Completed':
            $completedCount++;
            break;
        case 'Cancelled':
            $cancelledCount++;
            break;
    }
}

/* =========================================================
   PREVIOUS / NEXT MONTH
   ========================================================= */
$previousMonth = (clone $firstDay)->modify('-1 month');
$nextMonth = (clone $firstDay)->modify('+1 month');

$prevMonth = (int) $previousMonth->format('n');
$prevYear = (int) $previousMonth->format('Y');
$nextMonthNumber = (int) $nextMonth->format('n');
$nextYear = (int) $nextMonth->format('Y');

/* Sunday = 0, Monday = 1 ... Saturday = 6 */
$startWeekday = (int) $firstDay->format('w');
$daysInMonth = (int) $lastDay->format('j');
$totalCells = (int) ceil(($startWeekday + $daysInMonth) / 7) * 7;

function calendarStatusClass(string $status): string
{
    return match ($status) {
        'Approved' => 'status-approved',
        'Completed' => 'status-completed',
        'Cancelled' => 'status-cancelled',
        default => 'status-pending',
    };
}

function formatAppointmentTime(?string $time): string
{
    if (!$time) {
        return '';
    }

    return date('g:i A', strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Calendar | Minguito Veterinary Clinic</title>

    <!-- Font Awesome: keeps the shared sidebar icons visible -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">

    <!-- Google Fonts: match the shared admin typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --green: #2f6b4f;
            --green-dark: #24543e;
            --green-light: #e8f2eb;
            --gold: #c89b3c;
            --cream: #f8f1e5;
            --bg: #f5f6f2;
            --text: #26352d;
            --muted: #7b877f;
            --border: #e4e8e3;
            --white: #fff;
            --danger: #c65c5c;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: "DM Sans", sans-serif;
            overflow-x: hidden;
        }

        /* =====================================================
           MAIN ADMIN CONTENT
           ===================================================== */
        .admin-content {
            min-height: 100vh;
            width: calc(100% - 270px);
            margin-left: 270px;
            padding: 38px 38px 50px;
            box-sizing: border-box;
        }

        .page-container {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 28px;
        }

        .page-title-wrap h1 {
            margin: 0;
            color: var(--green-dark);
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(30px, 3vw, 38px);
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -.7px;
        }

        .page-title-wrap p {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
        }

        /* =====================================================
           MONTH CONTROLS
           ===================================================== */
        .calendar-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px;
            padding: 18px 20px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(35, 67, 51, .05);
        }

        .month-heading {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .month-heading-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--green-light);
            color: var(--green);
            font-size: 18px;
        }

        .month-heading h2 {
            margin: 0;
            color: var(--green-dark);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 28px;
        }

        .month-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .calendar-btn {
            min-width: 42px;
            height: 42px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid var(--border);
            border-radius: 11px;
            background: #fff;
            color: var(--green-dark);
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: .2s ease;
        }

        .calendar-btn:hover {
            border-color: #cfd9d1;
            background: #f5f8f5;
            transform: translateY(-1px);
        }

        .calendar-btn.today-btn {
            background: var(--green);
            border-color: var(--green);
            color: #fff;
        }

        .calendar-btn.today-btn:hover {
            background: var(--green-dark);
            border-color: var(--green-dark);
        }

        /* =====================================================
           STAT CARDS
           ===================================================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .stat-card {
            min-width: 0;
            padding: 19px 20px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 7px 22px rgba(35, 67, 51, .045);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .stat-icon {
            width: 33px;
            height: 33px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: var(--green-light);
            color: var(--green);
            font-size: 13px;
        }

        .stat-number {
            margin-top: 10px;
            color: var(--green-dark);
            font-size: 28px;
            font-weight: 600;
            line-height: 1;
        }

        /* =====================================================
           CALENDAR CARD
           ===================================================== */
        .calendar-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 9px 28px rgba(35, 67, 51, .055);
            overflow: hidden;
        }

        .calendar-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .calendar-grid {
            min-width: 920px;
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .weekday {
            min-height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            background: #f1f4f1;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            color: #65736b;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        .weekday:nth-child(7) {
            border-right: 0;
        }

        .day-cell {
            position: relative;
            min-height: 145px;
            padding: 11px;
            background: #fff;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .day-cell:nth-child(7n) {
            border-right: 0;
        }

        .day-cell.empty-day {
            background: #fafbf9;
        }

        .day-cell.today {
            background: #fbf8ef;
        }

        .day-number {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            margin-bottom: 7px;
            border-radius: 9px;
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
        }

        .today .day-number {
            background: var(--gold);
            color: #fff;
            box-shadow: 0 5px 12px rgba(200, 155, 60, .2);
        }

        .day-appointments {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .appointment-item {
            display: block;
            padding: 7px 8px;
            border-radius: 9px;
            border-left: 3px solid var(--green);
            background: #f5f8f5;
            color: var(--text);
            text-decoration: none;
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .appointment-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 13px rgba(35, 67, 51, .1);
        }

        .appointment-item.status-pending {
            border-left-color: #d4a12c;
            background: #fbf5e7;
        }

        .appointment-item.status-approved {
            border-left-color: var(--green);
            background: #edf5ef;
        }

        .appointment-item.status-completed {
            border-left-color: #71879a;
            background: #eff3f6;
        }

        .appointment-item.status-cancelled {
            border-left-color: var(--danger);
            background: #fbefef;
        }

        .appointment-time {
            display: block;
            margin-bottom: 2px;
            color: #718078;
            font-size: 10px;
            font-weight: 600;
        }

        .appointment-pet {
            display: block;
            color: var(--green-dark);
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .appointment-service {
            display: block;
            margin-top: 2px;
            color: #748078;
            font-size: 9px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .more-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 1px;
            padding: 4px 5px;
            color: var(--green);
            font-size: 10px;
            font-weight: 600;
            text-decoration: none;
        }

        .more-link:hover {
            text-decoration: underline;
        }

        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 17px;
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            background: #fcfdfc;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #66736b;
            font-size: 11px;
            font-weight: 500;
        }

        .legend-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }

        .legend-pending { background: #d4a12c; }
        .legend-approved { background: var(--green); }
        .legend-completed { background: #71879a; }
        .legend-cancelled { background: var(--danger); }

        /* =====================================================
           RESPONSIVE
           ===================================================== */
        @media (max-width: 1200px) {
            .admin-content {
                padding: 32px 25px 45px;
            }

            .stats-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 980px) {
            .admin-content {
                width: 100%;
                margin-left: 0;
                padding: 82px 18px 40px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 680px) {
            .admin-content {
                padding-left: 14px;
                padding-right: 14px;
            }

            .page-title-wrap h1 {
                font-size: 30px;
            }

            .calendar-controls {
                align-items: flex-start;
                flex-direction: column;
            }

            .month-actions {
                width: 100%;
            }

            .calendar-btn {
                flex: 1;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 440px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* =====================================================
           TYPOGRAPHY REFINEMENT
           Keep the page elegant and readable without heavy bold text.
           ===================================================== */
        .page-title-wrap h1 {
            font-weight: 700 !important;
        }

        .page-title-wrap p {
            font-weight: 400 !important;
        }

        .month-heading h2 {
            font-weight: 400 !important;
        }

        .calendar-btn {
            font-weight: 500 !important;
        }

        .stat-label {
            font-weight: 600 !important;
        }

        .stat-number {
            font-weight: 500 !important;
        }

        .weekday {
            font-weight: 600 !important;
        }

        .day-number {
            font-weight: 500 !important;
        }

        .appointment-time {
            font-weight: 500 !important;
        }

        .appointment-pet {
            font-weight: 600 !important;
        }

        .appointment-service {
            font-weight: 400 !important;
        }

        .more-link {
            font-weight: 500 !important;
        }

        .legend-item {
            font-weight: 400 !important;
        }

        /* Shared sidebar profile/brand text */
        .sidebar-profile-text strong {
            font-weight: 500 !important;
        }

        .brand-copy strong {
            font-weight: 500 !important;
        }

        .sidebar-profile-text span,
        .brand-copy span {
            font-weight: 400 !important;
        }

    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="admin-content">
    <div class="page-container">

        <header class="page-header">
            <div class="page-title-wrap">
                <h1>Appointment Calendar</h1>
                <p>View and manage scheduled veterinary appointments by month.</p>
            </div>
        </header>

        <section class="calendar-controls">
            <div class="month-heading">
                <div class="month-heading-icon">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <h2><?= htmlspecialchars($monthName) ?></h2>
            </div>

            <div class="month-actions">
                <a
                    class="calendar-btn"
                    href="calendar.php?month=<?= $prevMonth ?>&year=<?= $prevYear ?>"
                    aria-label="Previous month"
                >
                    <i class="fa-solid fa-chevron-left"></i>
                </a>

                <a
                    class="calendar-btn today-btn"
                    href="calendar.php?month=<?= date('n') ?>&year=<?= date('Y') ?>"
                >
                    <i class="fa-solid fa-calendar-day"></i>
                    Today
                </a>

                <a
                    class="calendar-btn"
                    href="calendar.php?month=<?= $nextMonthNumber ?>&year=<?= $nextYear ?>"
                    aria-label="Next month"
                >
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total</span>
                    <span class="stat-icon"><i class="fa-solid fa-calendar-check"></i></span>
                </div>
                <div class="stat-number"><?= $totalAppointments ?></div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Pending</span>
                    <span class="stat-icon"><i class="fa-solid fa-clock"></i></span>
                </div>
                <div class="stat-number"><?= $pendingCount ?></div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Approved</span>
                    <span class="stat-icon"><i class="fa-solid fa-circle-check"></i></span>
                </div>
                <div class="stat-number"><?= $approvedCount ?></div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Completed</span>
                    <span class="stat-icon"><i class="fa-solid fa-check-double"></i></span>
                </div>
                <div class="stat-number"><?= $completedCount ?></div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Cancelled</span>
                    <span class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></span>
                </div>
                <div class="stat-number"><?= $cancelledCount ?></div>
            </article>
        </section>

        <section class="calendar-card">
            <div class="calendar-scroll">
                <div class="calendar-grid">
                    <?php
                    $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    foreach ($weekdays as $weekday):
                    ?>
                        <div class="weekday"><?= $weekday ?></div>
                    <?php endforeach; ?>

                    <?php for ($cell = 0; $cell < $totalCells; $cell++): ?>
                        <?php
                        $dayNumber = $cell - $startWeekday + 1;

                        if ($dayNumber < 1 || $dayNumber > $daysInMonth):
                            echo '<div class="day-cell empty-day"></div>';
                            continue;
                        endif;

                        $cellDate = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $dayNumber);
                        $isToday = ($cellDate === $today);
                        $dayAppointments = $appointmentsByDate[$cellDate] ?? [];
                        ?>

                        <div class="day-cell<?= $isToday ? ' today' : '' ?>">
                            <div class="day-number"><?= $dayNumber ?></div>

                            <?php if ($dayAppointments): ?>
                                <div class="day-appointments">
                                    <?php foreach (array_slice($dayAppointments, 0, 3) as $appointment): ?>
                                        <a
                                            class="appointment-item <?= calendarStatusClass($appointment['status']) ?>"
                                            href="appointments.php"
                                            title="<?= htmlspecialchars(($appointment['pet_name'] ?: 'Pet') . ' — ' . ($appointment['service'] ?: 'Appointment')) ?>"
                                        >
                                            <span class="appointment-time">
                                                <?= htmlspecialchars(formatAppointmentTime($appointment['appointment_time'])) ?>
                                            </span>
                                            <span class="appointment-pet">
                                                <?= htmlspecialchars($appointment['pet_name'] ?: 'Unnamed Pet') ?>
                                            </span>
                                            <span class="appointment-service">
                                                <?= htmlspecialchars($appointment['service'] ?: 'Veterinary appointment') ?>
                                            </span>
                                        </a>
                                    <?php endforeach; ?>

                                    <?php if (count($dayAppointments) > 3): ?>
                                        <a class="more-link" href="appointments.php">
                                            +<?= count($dayAppointments) - 3 ?> more
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="calendar-legend">
                <span class="legend-item">
                    <span class="legend-dot legend-pending"></span>
                    Pending
                </span>
                <span class="legend-item">
                    <span class="legend-dot legend-approved"></span>
                    Approved
                </span>
                <span class="legend-item">
                    <span class="legend-dot legend-completed"></span>
                    Completed
                </span>
                <span class="legend-item">
                    <span class="legend-dot legend-cancelled"></span>
                    Cancelled
                </span>
            </div>
        </section>

    </div>
</main>

</body>
</html>
