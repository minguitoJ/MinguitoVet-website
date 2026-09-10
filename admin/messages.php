<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$message = '';
$messageType = '';

// ---------------------------------------------------------
// DELETE MESSAGE
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        $message = 'Invalid message.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
            $stmt->execute([':id' => $id]);

            header('Location: messages.php?success=deleted');
            exit;
        } catch (PDOException $e) {
            $message = 'Unable to delete the message.';
            $messageType = 'error';
        }
    }
}

// ---------------------------------------------------------
// SUCCESS MESSAGE
// ---------------------------------------------------------
if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $message = 'Message deleted successfully.';
    $messageType = 'success';
}

// ---------------------------------------------------------
// SEARCH
// ---------------------------------------------------------
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT id, name, email, subject, message, created_at
    FROM contact_messages
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            name LIKE :search
            OR email LIKE :search
            OR subject LIKE :search
            OR message LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------
// COUNTS
// ---------------------------------------------------------
$totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();

$todayMessagesStmt = $pdo->query("
    SELECT COUNT(*)
    FROM contact_messages
    WHERE DATE(created_at) = CURDATE()
");
$todayMessages = (int)$todayMessagesStmt->fetchColumn();

$adminUsername = $_SESSION['admin_username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Minguito Veterinary Clinic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f6f2;
            color: #26352d;
            min-height: 100vh;
        }

        .admin-header {
            background: #24543e;
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
            background: #c89b3c;
            color: #24543e;
            display: grid;
            place-items: center;
            font-size: 21px;
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
            color: #24543e;
            background: #c89b3c;
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .page {
            width: min(1200px, 92%);
            margin: 38px auto 60px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .eyebrow {
            color: #c89b3c;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.8px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-heading h2 {
            font-family: 'Playfair Display', serif;
            color: #24543e;
            font-size: 34px;
        }

        .page-heading p {
            color: #7b877f;
            margin-top: 6px;
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 17px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e4e8e3;
            border-radius: 14px;
            padding: 19px 21px;
            box-shadow: 0 5px 18px rgba(70, 53, 30, .05);
        }

        .stat-label {
            color: #7b877f;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .8px;
            font-weight: 700;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #24543e;
            margin-top: 5px;
        }

        .message-alert {
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 600;
        }

        .message-alert.success {
            background: #e7f3eb;
            color: #22613d;
            border: 1px solid #bcdcc8;
        }

        .message-alert.error {
            background: #fbe9e7;
            color: #a33a30;
            border: 1px solid #efc1bc;
        }

        .filters {
            background: #fff;
            border: 1px solid #e4e8e3;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
            box-shadow: 0 5px 18px rgba(70, 53, 30, .05);
        }

        .filters input {
            flex: 1;
            min-width: 200px;
            border: 1px solid #dcd2c2;
            background: #fcfdfb;
            color: #26352d;
            padding: 11px 12px;
            border-radius: 8px;
            font-family: inherit;
            outline: none;
        }

        .filters input:focus {
            border-color: #c89b3c;
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
            background: #24543e;
            color: #fff;
        }

        .clear-btn {
            background: #f8f1e5;
            color: #496055;
        }

        .table-card {
            background: #fff;
            border: 1px solid #e4e8e3;
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
            min-width: 980px;
        }

        th {
            background: #f8f1e5;
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
            border-top: 1px solid #eef0ed;
            vertical-align: middle;
            font-size: 14px;
        }

        tr:hover td {
            background: #fcfdfb;
        }

        .sender-name {
            font-weight: 700;
            color: #24543e;
        }

        .sender-email {
            display: block;
            color: #7b877f;
            font-size: 12px;
            margin-top: 3px;
        }

        .subject {
            font-weight: 700;
            color: #26352d;
            max-width: 230px;
        }

        .preview {
            max-width: 350px;
            color: #7b877f;
            line-height: 1.45;
        }

        .date {
            color: #7b877f;
            white-space: nowrap;
            font-size: 13px;
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

        .view-btn {
            background: #e4efe9;
            color: #286047;
        }

        .delete-btn {
            background: #f8e3e0;
            color: #a23b32;
        }

        .empty {
            padding: 55px 20px;
            text-align: center;
            color: #7b877f;
        }

        .empty-icon {
            font-size: 38px;
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
            width: min(650px, 100%);
            max-height: 90vh;
            overflow-y: auto;
            background: #fff;
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .2);
        }

        .modal-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .modal-box h3 {
            font-family: 'Playfair Display', serif;
            color: #24543e;
            font-size: 25px;
            line-height: 1.2;
        }

        .close-btn {
            border: 0;
            background: #f8f1e5;
            color: #496055;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            flex-shrink: 0;
        }

        .message-meta {
            background: #f8f1e5;
            border-radius: 11px;
            padding: 15px;
            margin-bottom: 18px;
        }

        .meta-row {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-label {
            width: 75px;
            flex-shrink: 0;
            color: #7b877f;
            font-weight: 700;
        }

        .meta-value {
            color: #26352d;
            word-break: break-word;
        }

        .full-message {
            border: 1px solid #e4e8e3;
            border-radius: 11px;
            padding: 18px;
            color: #26352d;
            line-height: 1.7;
            white-space: pre-wrap;
            word-break: break-word;
            background: #fff;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .modal-delete {
            border: 0;
            background: #f8e3e0;
            color: #a23b32;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
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

            .page-heading h2 {
                font-size: 29px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .filters {
                flex-direction: column;
            }

            .filters input {
                width: 100%;
            }

            .modal-box {
                padding: 21px;
            }
        }

        /* =========================================================
           SHARED SIDEBAR LAYOUT — KEEP CONTENT BESIDE SIDEBAR
           ========================================================= */
        html, body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .page.admin-content {
            display: block;
            width: calc(100% - 270px) !important;
            max-width: none !important;
            margin-left: 270px !important;
            margin-right: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding: 30px 34px 45px !important;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .page.admin-content > * {
            max-width: none;
        }

        @media (max-width: 980px) {
            .page.admin-content {
                width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding: 82px 20px 45px !important;
            }
        }

        @media (max-width: 760px) {
            .page.admin-content {
                width: 100% !important;
                padding: 82px 15px 40px !important;
            }
        }
    </style>
</head>
<body class="admin-with-sidebar">

<?php include 'sidebar.php'; ?>



<main class="page admin-content">

    <div class="page-heading">
        <div class="eyebrow">Communication</div>
        <h2>Contact Messages</h2>
        <p>View and manage messages submitted through the clinic contact form.</p>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message-alert <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-label">Total Messages</div>
            <div class="stat-number"><?= $totalMessages ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Received Today</div>
            <div class="stat-number"><?= $todayMessages ?></div>
        </div>
    </section>

    <form class="filters" method="GET">
        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Search name, email, subject, or message..."
        >

        <button type="submit" class="filter-btn">Search</button>

        <?php if ($search !== ''): ?>
            <a href="messages.php" class="clear-btn">Clear</a>
        <?php endif; ?>
    </form>

    <section class="table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Received</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty">
                                <div class="empty-icon">📩</div>
                                <strong>No messages found.</strong>
                                <p>
                                    <?= $search !== ''
                                        ? 'Try a different search.'
                                        : 'Messages submitted through the contact form will appear here.' ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($messages as $item): ?>
                        <?php
                            $preview = trim(preg_replace('/\s+/', ' ', $item['message'] ?? ''));
                            if (strlen($preview) > 105) {
                                $preview = substr($preview, 0, 105) . '...';
                            }
                        ?>

                        <tr>
                            <td>#<?= (int)$item['id'] ?></td>

                            <td>
                                <div class="sender-name">
                                    <?= htmlspecialchars($item['name']) ?>
                                </div>

                                <span class="sender-email">
                                    <?= htmlspecialchars($item['email']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="subject">
                                    <?= htmlspecialchars($item['subject']) ?>
                                </div>
                            </td>

                            <td>
                                <div class="preview">
                                    <?= htmlspecialchars($preview) ?>
                                </div>
                            </td>

                            <td>
                                <div class="date">
                                    <?= date('M d, Y', strtotime($item['created_at'])) ?><br>
                                    <?= date('h:i A', strtotime($item['created_at'])) ?>
                                </div>
                            </td>

                            <td>
                                <div class="actions">

                                    <button
                                        type="button"
                                        class="action-btn view-btn"
                                        onclick='openMessageModal(
                                            <?= json_encode($item["name"]) ?>,
                                            <?= json_encode($item["email"]) ?>,
                                            <?= json_encode($item["subject"]) ?>,
                                            <?= json_encode($item["message"]) ?>,
                                            <?= json_encode(date("M d, Y h:i A", strtotime($item["created_at"]))) ?>
                                        )'
                                    >
                                        View
                                    </button>

                                    <form
                                        method="POST"
                                        onsubmit="return confirmDelete('<?= htmlspecialchars(addslashes($item['subject'])) ?>');"
                                    >
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

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

<!-- VIEW MESSAGE MODAL -->
<div class="modal" id="messageModal">
    <div class="modal-box">

        <div class="modal-top">
            <h3 id="modalSubject">Message</h3>

            <button type="button" class="close-btn" onclick="closeMessageModal()">
                &times;
            </button>
        </div>

        <div class="message-meta">
            <div class="meta-row">
                <span class="meta-label">From</span>
                <span class="meta-value" id="modalName"></span>
            </div>

            <div class="meta-row">
                <span class="meta-label">Email</span>
                <span class="meta-value" id="modalEmail"></span>
            </div>

            <div class="meta-row">
                <span class="meta-label">Received</span>
                <span class="meta-value" id="modalDate"></span>
            </div>
        </div>

        <div class="full-message" id="modalMessage"></div>

        <div class="modal-footer">
            <button type="button" class="close-btn" style="width:auto;height:auto;border-radius:8px;padding:9px 15px;" onclick="closeMessageModal()">
                Close
            </button>
        </div>

    </div>
</div>

<script>
const messageModal = document.getElementById('messageModal');

function openMessageModal(name, email, subject, message, date) {
    document.getElementById('modalName').textContent = name || '';
    document.getElementById('modalEmail').textContent = email || '';
    document.getElementById('modalSubject').textContent = subject || 'Message';
    document.getElementById('modalMessage').textContent = message || '';
    document.getElementById('modalDate').textContent = date || '';

    messageModal.classList.add('show');
}

function closeMessageModal() {
    messageModal.classList.remove('show');
}

function confirmDelete(subject) {
    return confirm(
        'Delete this message?\n\n' +
        'Subject: "' + subject + '"\n\n' +
        'This message will be permanently removed.'
    );
}

messageModal.addEventListener('click', function(event) {
    if (event.target === messageModal) {
        closeMessageModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeMessageModal();
    }
});
</script>

</body>
</html>
