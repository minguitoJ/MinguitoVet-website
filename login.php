<?php
session_start();

require_once __DIR__ . '/config/database.php';

/*
 * ONE LOGIN SYSTEM
 * Both Admin and Customer accounts now use the `users` table.
 *
 * users.role:
 *   admin     -> admin/dashboard.php
 *   customer  -> normal website
 *
 * Existing admin/customer session names are preserved so the
 * current pages remain compatible.
 */

if (isset($_SESSION['admin_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}

if (isset($_SESSION['customer_id']) && ($_SESSION['user_role'] ?? '') === 'customer') {
    header('Location: index.php');
    exit;
}

$error = '';
$registered = isset($_GET['registered']) && $_GET['registered'] === '1';
$redirect = $_POST['redirect'] ?? ($_GET['redirect'] ?? '');

$allowedRedirects = [
    'appointment.php',
    'appointments.php',
    'profile.php',
    'index.php'
];

if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {

        $error = 'Please enter your username/email and password.';

    } else {

        try {

            /* Search both username and email in the single users table. */
            $userStmt = $pdo->prepare(
                'SELECT
                    id,
                    full_name,
                    username,
                    email,
                    contact_number,
                    password,
                    role
                 FROM users
                 WHERE username = :identifier
                    OR email = :identifier
                 LIMIT 1'
            );

            $userStmt->execute([
                ':identifier' => $identifier
            ]);

            $account = $userStmt->fetch();

            if (
                $account &&
                password_verify($password, $account['password'])
            ) {

                session_regenerate_id(true);

                /* Clear any previous account session. */
                unset(
                    $_SESSION['admin_id'],
                    $_SESSION['admin_username'],
                    $_SESSION['customer_id'],
                    $_SESSION['customer_name'],
                    $_SESSION['customer_email'],
                    $_SESSION['customer_contact'],
                    $_SESSION['user_id'],
                    $_SESSION['user_role']
                );

                $role = strtolower(trim((string) $account['role']));

                /* ADMIN */
                if ($role === 'admin') {

                    $_SESSION['user_id'] = (int) $account['id'];
                    $_SESSION['user_role'] = 'admin';

                    /* Keep existing admin session names for compatibility. */
                    $_SESSION['admin_id'] = (int) $account['id'];
                    $_SESSION['admin_username'] =
                        $account['username'] ?: $account['email'];

                    /* Admin always goes directly to the Dashboard. */
                    header('Location: admin/dashboard.php');
                    exit;
                }

                /* CUSTOMER */
                if ($role === 'customer') {

                    $_SESSION['user_id'] = (int) $account['id'];
                    $_SESSION['user_role'] = 'customer';

                    /* Keep existing customer session names for compatibility. */
                    $_SESSION['customer_id'] = (int) $account['id'];
                    $_SESSION['customer_name'] = $account['full_name'];
                    $_SESSION['customer_email'] = $account['email'];
                    $_SESSION['customer_contact'] =
                        $account['contact_number'] ?? '';

                    header('Location: ' . $redirect);
                    exit;
                }
            }

            /* Generic error for invalid credentials or unsupported role. */
            $error = 'Invalid username/email or password.';

        } catch (PDOException $e) {

            $error =
                'Unable to log in right now. Please try again.';
        }
    }
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

    <title>Login | Minguito Veterinary Clinic</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

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
            --white: #ffffff;
            --text: #17231e;
            --muted: #66756e;
            --red: #a13d32;
            --red-bg: #fff1ee;
            --success: #14633f;
            --success-bg: #eaf7ef;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 36px 18px;

            font-family:
                "DM Sans",
                Arial,
                sans-serif;

            color: var(--text);

            background:
                radial-gradient(
                    circle at 12% 12%,
                    rgba(181,122,47,.10),
                    transparent 25%
                ),
                radial-gradient(
                    circle at 88% 88%,
                    rgba(7,59,42,.08),
                    transparent 26%
                ),
                linear-gradient(
                    135deg,
                    #fbf3e6,
                    #f1dfc4
                );

            overflow-x: hidden;
        }

        body::before,
        body::after {

            content: "";

            position: fixed;

            border-radius: 50%;

            pointer-events: none;
        }

        body::before {

            width: 360px;
            height: 360px;

            left: -170px;
            top: -150px;

            background:
                rgba(181,122,47,.07);
        }

        body::after {

            width: 350px;
            height: 350px;

            right: -160px;
            bottom: -150px;

            background:
                rgba(7,59,42,.06);
        }

        .auth-card {

            width: min(470px, 100%);

            position: relative;
            z-index: 2;

            padding: 42px;

            background:
                rgba(255,255,255,.94);

            border:
                1px solid
                rgba(181,122,47,.20);

            border-radius: 28px;

            box-shadow:
                0 25px 65px
                rgba(7,59,42,.14);

            backdrop-filter: blur(12px);
        }

        .brand-logo {

            width: 185px;

            max-width: 72%;

            display: block;

            margin:
                0 auto 18px;
        }

        .eyebrow {

            text-align: center;

            color: var(--gold);

            font-size: 12px;

            font-weight: 800;

            letter-spacing: 1.8px;

            margin-bottom: 8px;
        }

        h1 {

            color: var(--green);

            text-align: center;

            font:
                700 39px/1.06
                "Playfair Display",
                Georgia,
                serif;

            margin-bottom: 10px;
        }

        .intro {

            color: var(--muted);

            text-align: center;

            font-size: 14px;

            line-height: 1.65;

            margin-bottom: 24px;
        }

        .message {

            padding:
                13px 15px;

            border-radius: 12px;

            font-size: 13px;

            line-height: 1.5;

            text-align: center;

            margin-bottom: 18px;
        }

        .message.error {

            background:
                var(--red-bg);

            border:
                1px solid #efc4be;

            color:
                var(--red);
        }

        .message.success {

            background:
                var(--success-bg);

            border:
                1px solid #c9e6d5;

            color:
                var(--success);
        }

        .form-group {

            margin-bottom: 18px;
        }

        .form-group label {

            display: block;

            color: var(--green);

            font-size: 13px;

            font-weight: 800;

            margin-bottom: 8px;
        }

        .input-wrap {

            position: relative;
        }

        .form-group input {

            width: 100%;

            height: 52px;

            padding:
                0 15px;

            border:
                1px solid #dccab4;

            border-radius: 12px;

            background:
                #fffaf3;

            color:
                var(--text);

            font: inherit;

            font-size: 14px;

            outline: none;

            transition: .25s ease;
        }

        .form-group input:focus {

            border-color:
                var(--gold);

            box-shadow:
                0 0 0 4px
                rgba(181,122,47,.10);
        }

        .password-input {

            padding-right: 52px !important;
        }

        .password-toggle {

            position: absolute;

            right: 8px;
            top: 50%;

            transform:
                translateY(-50%);

            width: 36px;
            height: 36px;

            border: 0;

            border-radius: 9px;

            background:
                transparent;

            color:
                var(--gold);

            font-size: 16px;

            cursor: pointer;
        }

        .login-btn {

            width: 100%;

            height: 54px;

            border: 0;

            border-radius: 12px;

            background:
                var(--green);

            color:
                var(--white);

            font: inherit;

            font-size: 15px;

            font-weight: 800;

            letter-spacing: .3px;

            cursor: pointer;

            box-shadow:
                0 12px 24px
                rgba(7,59,42,.14);

            transition: .25s ease;
        }

        .login-btn:hover {

            background:
                var(--gold);

            transform:
                translateY(-2px);

            box-shadow:
                0 15px 28px
                rgba(181,122,47,.18);
        }

        .links {

            margin-top: 22px;

            text-align: center;

            color:
                var(--muted);

            font-size: 13px;

            line-height: 1.8;
        }

        .links a {

            color:
                var(--green);

            font-weight: 800;

            text-decoration: none;
        }

        .links a:hover {

            color:
                var(--gold);
        }

        .home-link {

            display: inline-block;

            margin-top: 7px;
        }

        @media (max-width: 520px) {

            .auth-card {

                padding: 30px 22px;

                border-radius: 22px;
            }

            h1 {

                font-size: 34px;
            }

            .brand-logo {

                width: 160px;
            }
        }

    </style>

</head>

<body>

<main class="auth-card">

    <img class="brand-logo" src="assets/images/logo.png" alt="Minguito Veterinary Clinic">

    <p class="eyebrow">SECURE ACCOUNT LOGIN</p>

    <h1>Welcome Back</h1>

    <p class="intro">
        Log in to access your Minguito Veterinary Clinic account.
    </p>

    <?php if ($registered): ?>
        <div class="message success">
            Account created successfully. You can now log in.
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="message error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">

        <input
            type="hidden"
            name="redirect"
            value="<?= htmlspecialchars(
                $redirect,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <div class="form-group">
            <label for="identifier">
                Username or Email Address
            </label>

            <input
                type="text"
                id="identifier"
                name="identifier"
                value="<?= htmlspecialchars(
                    $_POST['identifier'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                placeholder="Enter your username or email"
                autocomplete="username"
                autocapitalize="none"
                spellcheck="false"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">
                Password
            </label>

            <div class="input-wrap">

                <input
                    class="password-input"
                    type="password"
                    id="password"
                    name="password"
                    data-lpignore="true"
                    data-1p-ignore="true"
                    data-bwignore="true"
                    placeholder="Enter your password"
                    autocomplete="new-password"
                    spellcheck="false"
                    required
                >

                <button
                    class="password-toggle"
                    type="button"
                    id="passwordToggle"
                    aria-label="Show password"
                >
                    👁
                </button>

            </div>
        </div>

        <button
            class="login-btn"
            type="submit"
        >
            LOGIN TO YOUR ACCOUNT
        </button>

    </form>

    <div class="links">

        <p>
            Don't have an account?
            <a href="register.php<?= $redirect !== 'index.php' ? '?redirect=' . urlencode($redirect) : '' ?>">
                Create one here
            </a>
        </p>

        <a class="home-link" href="index.php">
            ← Back to Home
        </a>

    </div>

</main>

<script>

const passwordToggle =
    document.getElementById(
        'passwordToggle'
    );

const passwordInput =
    document.getElementById(
        'password'
    );

passwordToggle.addEventListener(
    'click',
    function () {

        const isPassword =
            passwordInput.type === 'password';

        passwordInput.type =
            isPassword
            ? 'text'
            : 'password';

        passwordToggle.textContent =
            isPassword
            ? '🙈'
            : '👁';

        passwordToggle.setAttribute(
            'aria-label',
            isPassword
            ? 'Hide password'
            : 'Show password'
        );

    }
);

</script>

</body>
</html>