<?php
session_start();

require_once "../config/database.php";
require_once "../includes/functions.php";

requireLogin();

/* ---------------------------------------------------------
   SEARCH
--------------------------------------------------------- */
$search = trim($_GET['search'] ?? '');

/* ---------------------------------------------------------
   PATIENT LIST
   A patient is represented by the unique pet + owner pair,
   matching the original patients.php behavior.
--------------------------------------------------------- */
$sql = "
    SELECT
        a.pet_name,
        a.owner_name,
        COUNT(*) AS visits,
        MAX(a.appointment_date) AS last_visit,
        MAX(a.created_at) AS last_record
    FROM appointments a
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE a.pet_name LIKE :search
           OR a.owner_name LIKE :search
    ";
    $params[':search'] = '%' . $search . '%';
}

$sql .= "
    GROUP BY a.pet_name, a.owner_name
    ORDER BY a.pet_name ASC, a.owner_name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

/* ---------------------------------------------------------
   DASHBOARD STATS
--------------------------------------------------------- */
$totalPatientsStmt = $pdo->query(" 
    SELECT COUNT(*)
    FROM (
        SELECT pet_name, owner_name
        FROM appointments
        GROUP BY pet_name, owner_name
    ) AS patient_groups
");
$totalPatients = (int) $totalPatientsStmt->fetchColumn();

$totalVisitsStmt = $pdo->query("SELECT COUNT(*) FROM appointments");
$totalVisits = (int) $totalVisitsStmt->fetchColumn();

$todayVisitsStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM appointments WHERE appointment_date = :today"
);
$todayVisitsStmt->execute([':today' => date('Y-m-d')]);
$todayVisits = (int) $todayVisitsStmt->fetchColumn();

$adminUsername = $_SESSION['admin_username'] ?? 'Administrator';

function patientDate($date) {
    if (!$date) {
        return '—';
    }

    $time = strtotime($date);
    return $time ? date('M d, Y', $time) : htmlspecialchars($date);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients | Minguito Veterinary Clinic</title>

    <!-- Font Awesome: required by shared admin/sidebar.php icons -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Shared admin sidebar -->
    <link rel="stylesheet" href="../assets/css/sidebar.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

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
            --shadow: 0 12px 32px rgba(35, 67, 51, .07);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: "DM Sans", sans-serif;
        }

        .patients-main {
            margin-left: var(--sidebar-width, 270px);
            width: calc(100% - var(--sidebar-width, 270px));
            min-height: 100vh;
            padding: 38px 38px 50px;
            overflow-x: hidden;
        }

        .patients-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* -------------------------------------------------
           PAGE HEADER
        ------------------------------------------------- */
        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 30px;
        }

        .page-eyebrow {
            margin: 0 0 7px;
            color: var(--green);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .page-header h1 {
            margin: 0;
            color: var(--green-dark);
            font-family: "Playfair Display", serif;
            font-size: clamp(34px, 4vw, 48px);
            line-height: 1.05;
            letter-spacing: -.7px;
        }

        .page-header p {
            max-width: 680px;
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .view-site-btn {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            min-height: 45px;
            padding: 0 17px;
            border-radius: 11px;
            background: var(--green);
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(47, 107, 79, .16);
            transition: transform .2s ease, background .2s ease;
        }

        .view-site-btn:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
        }

        /* -------------------------------------------------
           STATISTICS
        ------------------------------------------------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            min-height: 128px;
            padding: 22px 24px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .stat-icon {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--green-soft);
            color: var(--green);
            font-size: 16px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .stat-number {
            margin-top: 3px;
            color: var(--green-dark);
            font-size: 28px;
            font-weight: 800;
            line-height: 1.1;
        }

        /* -------------------------------------------------
           CONTENT CARD
        ------------------------------------------------- */
        .patients-card {
            overflow: hidden;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .card-header {
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, #fff, #fbfaf7);
        }

        .card-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        .card-title-row h2 {
            margin: 0;
            color: var(--green-dark);
            font-family: "Playfair Display", serif;
            font-size: 25px;
        }

        .record-count {
            padding: 7px 11px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green);
            font-size: 11px;
            font-weight: 800;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-wrap {
            position: relative;
            flex: 1;
        }

        .search-wrap i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 13px;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            height: 46px;
            padding: 0 15px 0 42px;
            border: 1px solid #d9dfda;
            border-radius: 11px;
            outline: none;
            background: #fff;
            color: var(--text);
            font: inherit;
            font-size: 13px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .search-input::placeholder {
            color: #9aa39c;
        }

        .search-input:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 4px rgba(47, 107, 79, .08);
        }

        .search-btn,
        .clear-btn {
            height: 46px;
            padding: 0 17px;
            border: 0;
            border-radius: 11px;
            font: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .search-btn {
            background: var(--green);
            color: #fff;
        }

        .search-btn:hover {
            background: var(--green-dark);
        }

        .clear-btn {
            background: var(--cream);
            color: #72552c;
        }

        .clear-btn:hover {
            background: #efe2cc;
        }

        /* -------------------------------------------------
           TABLE
        ------------------------------------------------- */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .patients-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        .patients-table th {
            padding: 15px 20px;
            background: var(--cream);
            color: #647068;
            border-bottom: 1px solid #e8dfd0;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .patients-table td {
            padding: 17px 20px;
            border-bottom: 1px solid #edf0ed;
            color: #536158;
            font-size: 13px;
            vertical-align: middle;
        }

        .patients-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .patients-table tbody tr:hover {
            background: #fbfcfa;
        }

        .pet-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 190px;
        }

        .pet-avatar {
            width: 39px;
            height: 39px;
            flex: 0 0 39px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--green-soft);
            color: var(--green);
            font-size: 15px;
        }

        .pet-name {
            color: var(--green-dark);
            font-weight: 800;
        }

        .owner-name {
            color: #647068;
            font-weight: 600;
        }

        .visit-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green);
            font-size: 11px;
            font-weight: 800;
        }

        .date-text {
            color: #59675e;
            white-space: nowrap;
        }

        .empty-state {
            padding: 64px 20px;
            text-align: center;
        }

        .empty-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 14px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: var(--green-soft);
            color: var(--green);
            font-size: 22px;
        }

        .empty-state h3 {
            margin: 0 0 7px;
            color: var(--green-dark);
            font-family: "Playfair Display", serif;
            font-size: 22px;
        }

        .empty-state p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* -------------------------------------------------
           RESPONSIVE
        ------------------------------------------------- */
        @media (max-width: 980px) {
            .patients-main {
                margin-left: 0;
                width: 100%;
                padding: 82px 20px 40px;
            }
        }

        @media (max-width: 760px) {
            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .view-site-btn {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-form {
                align-items: stretch;
                flex-direction: column;
            }

            .search-btn,
            .clear-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="patients-main">
    <div class="patients-container">

        <!-- PAGE HEADER -->
        <header class="page-header">
            <div>
                <p class="page-eyebrow">Minguito Veterinary Clinic</p>
                <h1>Patients</h1>
                <p>
                    View the pets registered through your clinic appointments,
                    their owners, visit counts, and latest recorded visit.
                </p>
            </div>

            <a href="../index.php" class="view-site-btn">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                View Website
            </a>
        </header>

        <!-- STATISTICS -->
        <section class="stats-grid" aria-label="Patient statistics">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Patients</span>
                    <span class="stat-icon">
                        <i class="fa-solid fa-paw"></i>
                    </span>
                </div>
                <div class="stat-number"><?= $totalPatients ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Total Visits</span>
                    <span class="stat-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </span>
                </div>
                <div class="stat-number"><?= $totalVisits ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Today's Visits</span>
                    <span class="stat-icon">
                        <i class="fa-solid fa-calendar-day"></i>
                    </span>
                </div>
                <div class="stat-number"><?= $todayVisits ?></div>
            </div>
        </section>

        <!-- PATIENT LIST -->
        <section class="patients-card">
            <div class="card-header">
                <div class="card-title-row">
                    <h2>Patient Records</h2>
                    <span class="record-count">
                        <?= count($patients) ?> shown
                    </span>
                </div>

                <form method="GET" class="filter-form">
                    <div class="search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="search"
                            name="search"
                            class="search-input"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search by pet name or owner name..."
                            autocomplete="off">
                    </div>

                    <button type="submit" class="search-btn">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Search
                    </button>

                    <?php if ($search !== ''): ?>
                        <a href="patients.php" class="clear-btn">
                            <i class="fa-solid fa-xmark"></i>
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($patients)): ?>
                <div class="table-wrap">
                    <table class="patients-table">
                        <thead>
                            <tr>
                                <th>Pet</th>
                                <th>Owner</th>
                                <th>Visits</th>
                                <th>Last Visit</th>
                                <th>Latest Record</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td>
                                        <div class="pet-cell">
                                            <div class="pet-avatar">
                                                <i class="fa-solid fa-paw"></i>
                                            </div>
                                            <span class="pet-name">
                                                <?= htmlspecialchars($patient['pet_name']) ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="owner-name">
                                            <?= htmlspecialchars($patient['owner_name']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="visit-badge">
                                            <i class="fa-solid fa-stethoscope"></i>
                                            <?= (int) $patient['visits'] ?>
                                            <?= ((int) $patient['visits'] === 1) ? 'visit' : 'visits' ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="date-text">
                                            <?= patientDate($patient['last_visit']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="date-text">
                                            <?= patientDate($patient['last_record']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-paw"></i>
                    </div>

                    <?php if ($search !== ''): ?>
                        <h3>No patients found</h3>
                        <p>
                            No patient matched
                            “<?= htmlspecialchars($search) ?>”.
                            Try another search.
                        </p>
                    <?php else: ?>
                        <h3>No patient records yet</h3>
                        <p>
                            Patients will appear here after appointments are recorded.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

</body>
</html>
