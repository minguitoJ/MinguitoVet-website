<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$adminId = (int)($_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0);
$adminRole = $_SESSION['user_role'] ?? '';

if ($adminId <= 0 || ($adminRole !== '' && $adminRole !== 'admin')) {
    header('Location: ../login.php?type=admin');
    exit;
}

// Get current admin from the unified users table.
$stmt = $pdo->prepare("
    SELECT id, full_name, username, email, contact_number, password, role, created_at
    FROM users
    WHERE id = :id
      AND role = 'admin'
    LIMIT 1
");
$stmt->execute([':id' => $adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?type=admin');
    exit;
}

$message = '';
$messageType = '';

// ---------------------------------------------------------
// UPDATE USERNAME
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_username') {

    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $message = 'Please enter a username.';
        $messageType = 'error';
    } elseif (strlen($username) < 3) {
        $message = 'Username must be at least 3 characters.';
        $messageType = 'error';
    } elseif (strlen($username) > 100) {
        $message = 'Username must not exceed 100 characters.';
        $messageType = 'error';
    } else {
        try {
            // Check if another admin already uses this username
            $check = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = :username
                  AND id != :id
                  AND role = 'admin'
                LIMIT 1
            ");
            $check->execute([
                ':username' => $username,
                ':id' => $adminId
            ]);

            if ($check->fetch()) {
                $message = 'That username is already being used by another admin.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET username = :username
                    WHERE id = :id
                      AND role = 'admin'
                ");

                $stmt->execute([
                    ':username' => $username,
                    ':id' => $adminId
                ]);

                $_SESSION['admin_username'] = $username;
                $_SESSION['user_id'] = $adminId;
                $_SESSION['user_role'] = 'admin';
                $admin['username'] = $username;

                $message = 'Username updated successfully.';
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Unable to update the username.';
            $messageType = 'error';
        }
    }
}

// ---------------------------------------------------------
// CHANGE PASSWORD
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $message = 'Please complete all password fields.';
        $messageType = 'error';
    } elseif (!password_verify($currentPassword, $admin['password'])) {
        $message = 'Your current password is incorrect.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'New password must be at least 6 characters.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New password and confirmation password do not match.';
        $messageType = 'error';
    } elseif (password_verify($newPassword, $admin['password'])) {
        $message = 'New password must be different from your current password.';
        $messageType = 'error';
    }

    if ($message === '') {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE users
                SET password = :password
                WHERE id = :id
                  AND role = 'admin'
            ");

            $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $adminId
            ]);

            // Keep the current admin session valid after changing the password.
            $_SESSION['user_id'] = $adminId;
            $_SESSION['user_role'] = 'admin';

            $admin['password'] = $hashedPassword;

            $message = 'Password changed successfully.';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Unable to change the password.';
            $messageType = 'error';
        }
    }
}

// ---------------------------------------------------------
// RELOAD ADMIN DATA
// ---------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT id, full_name, username, email, contact_number, password, role, created_at
    FROM users
    WHERE id = :id
      AND role = 'admin'
    LIMIT 1
");
$stmt->execute([':id' => $adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?type=admin');
    exit;
}

$adminUsername = $admin['username'] ?? 'Administrator';
$createdDate = !empty($admin['created_at'])
    ? date('F d, Y', strtotime($admin['created_at']))
    : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Minguito Veterinary Clinic</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

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


        .page {
            width: 100%;
            margin: 0;
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

        .message {
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .message.success {
            background: #e7f3eb;
            color: #22613d;
            border: 1px solid #bcdcc8;
        }

        .message.error {
            background: #fbe9e7;
            color: #a33a30;
            border: 1px solid #efc1bc;
        }

        .profile-card {
            background: #24543e;
            color: #fff;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(23, 61, 50, .14);
        }

        .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #c89b3c;
            color: #24543e;
            display: grid;
            place-items: center;
            font-size: 27px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-info h3 {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
        }

        .profile-info p {
            color: #dce6df;
            font-size: 13px;
            margin-top: 4px;
        }

        .profile-date {
            margin-left: auto;
            text-align: right;
            color: #e4e8e3;
            font-size: 12px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .settings-card {
            background: #fff;
            border: 1px solid #e4e8e3;
            border-radius: 15px;
            padding: 24px;
            box-shadow: 0 5px 18px rgba(70, 53, 30, .05);
        }

        .settings-card.full {
            grid-column: 1 / -1;
        }

        .card-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #f8f1e5;
            display: grid;
            place-items: center;
            font-size: 19px;
            margin-bottom: 13px;
        }

        .settings-card h3 {
            color: #24543e;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .settings-card .description {
            color: #7b877ff79;
            font-size: 13px;
            line-height: 1.5;
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

        .input-wrap {
            position: relative;
        }

        .form-group input {
            width: 100%;
            border: 1px solid #dcd2c2;
            background: #fcfdfb;
            border-radius: 8px;
            padding: 11px 12px;
            font-family: inherit;
            color: #26352d;
            outline: none;
        }

        .form-group input:focus {
            border-color: #c89b3c;
        }

        .toggle-password {
            position: absolute;
            right: 9px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            cursor: pointer;
            color: #7b877f;
            font-size: 12px;
            font-weight: 700;
        }

        .save-btn {
            border: 0;
            background: #24543e;
            color: #fff;
            padding: 11px 17px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
        }

        .info-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .info-item {
            background: #f8f1e5;
            border-radius: 10px;
            padding: 14px;
        }

        .info-item span {
            display: block;
            color: #7b877f;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .info-item strong {
            color: #24543e;
            font-size: 14px;
            word-break: break-word;
        }

        .note {
            margin-top: 15px;
            color: #7b877f;
            font-size: 12px;
            line-height: 1.5;
        }

        @media (max-width: 760px) {
            .page-heading h2 {
                font-size: 29px;
            }

            .settings-grid {
                grid-template-columns: 1fr;
            }

            .settings-card.full {
                grid-column: auto;
            }

            .profile-card {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .profile-date {
                width: 100%;
                text-align: left;
                margin-left: 0;
            }

            .info-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .admin-content {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
            padding: 30px 34px 45px;
            box-sizing: border-box;
        }

        .admin-content .page-heading,
        .admin-content .profile-card,
        .admin-content .settings-grid {
            width: 100%;
            max-width: none;
        }

        .settings-grid {
            align-items: stretch;
        }

        .settings-card {
            min-width: 0;
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



<main class="page admin-content">

    <div class="page-heading">
        <div class="eyebrow">Administration</div>
        <h2>Settings</h2>
        <p>Manage your administrator account and security settings.</p>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="profile-card">
        <div class="avatar">A</div>

        <div class="profile-info">
            <h3><?= htmlspecialchars($adminUsername) ?></h3>
            <p>Clinic Administrator</p>
        </div>

        <div class="profile-date">
            Admin since<br>
            <strong><?= htmlspecialchars($createdDate) ?></strong>
        </div>
    </section>

    <section class="settings-grid">

        <!-- USERNAME -->
        <div class="settings-card">
            <div class="card-icon">👤</div>

            <h3>Admin Account</h3>
            <p class="description">
                Update the username used when signing in to the admin panel.
            </p>

            <form method="POST">
                <input type="hidden" name="action" value="update_username">

                <div class="form-group">
                    <label for="username">USERNAME</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($adminUsername) ?>"
                        maxlength="100"
                        required
                    >
                </div>

                <button type="submit" class="save-btn">
                    Save Username
                </button>
            </form>
        </div>

        <!-- PASSWORD -->
        <div class="settings-card">
            <div class="card-icon">🔐</div>

            <h3>Change Password</h3>
            <p class="description">
                Keep your administrator account secure by using a strong password.
            </p>

            <form method="POST">
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label for="current_password">CURRENT PASSWORD</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">
                            Show
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">NEW PASSWORD</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            minlength="6"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">
                            Show
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">CONFIRM NEW PASSWORD</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="6"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                            Show
                        </button>
                    </div>
                </div>

                <button type="submit" class="save-btn">
                    Change Password
                </button>
            </form>
        </div>

        <!-- ACCOUNT INFORMATION -->
        <div class="settings-card full">
            <div class="card-icon">ℹ️</div>

            <h3>Account Information</h3>
            <p class="description">
                Current information stored for your administrator account.
            </p>

            <div class="info-list">
                <div class="info-item">
                    <span>Admin ID</span>
                    <strong>#<?= (int)$adminId ?></strong>
                </div>

                <div class="info-item">
                    <span>Username</span>
                    <strong><?= htmlspecialchars($adminUsername) ?></strong>
                </div>

                <div class="info-item">
                    <span>Created</span>
                    <strong><?= htmlspecialchars($createdDate) ?></strong>
                </div>
            </div>

            <p class="note">
                Your administrator password is stored securely as a password hash and is never displayed here.
            </p>
        </div>

    </section>

</main>

<script>
function togglePassword(id, button) {
    const input = document.getElementById(id);

    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
    } else {
        input.type = 'password';
        button.textContent = 'Show';
    }
}
</script>

</body>
</html>
