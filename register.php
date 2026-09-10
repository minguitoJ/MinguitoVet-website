<?php
session_start();

/*
 * CSRF protection
 */
if (empty($_SESSION['register_csrf_token'])) {
    $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    }

    if (($_SESSION['user_role'] ?? '') === 'customer') {
        header('Location: index.php');
        exit;
    }
}

$error = '';

$redirect =
    $_POST['redirect']
    ?? ($_GET['redirect'] ?? '');

$allowedRedirects = [
    'appointment.php',
    'appointments.php',
    'profile.php'
];

if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = 'index.php';
}

$fullName =
    trim($_POST['full_name'] ?? '');

$username =
    trim($_POST['username'] ?? '');

$email =
    trim($_POST['email'] ?? '');

$contact =
    trim($_POST['contact_number'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password =
        $_POST['password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';

    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['register_csrf_token']) ||
        !hash_equals(
            $_SESSION['register_csrf_token'],
            $_POST['csrf_token']
        )
    ) {

        $error =
            'Invalid form submission. Please refresh the page and try again.';

    } elseif (
        $fullName === '' ||
        $username === '' ||
        $email === '' ||
        $contact === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {

        $error =
            'Please fill in all required fields.';

    } elseif (
        strlen($username) < 3 ||
        strlen($username) > 50 ||
        !preg_match('/^[A-Za-z0-9_.-]+$/', $username)
    ) {

        $error =
            'Username must be 3 to 50 characters and may contain letters, numbers, dot, underscore, or hyphen.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } elseif (
        strlen($fullName) < 2 ||
        strlen($fullName) > 100
    ) {

        $error =
            'Full name must be between 2 and 100 characters.';

    } elseif (
        strlen($email) > 150
    ) {

        $error =
            'Email address is too long.';

    } elseif (
        !preg_match('/^09[0-9]{9}$/', $contact)
    ) {

        $error =
            'Contact number must be exactly 11 digits and start with 09.';

    } elseif (
        strlen($password) < 8
    ) {

        $error =
            'Password must be at least 8 characters.';

    } elseif (
        $password !== $confirmPassword
    ) {

        $error =
            'Passwords do not match.';

    } else {

        try {

            /*
             * ONE ACCOUNT TABLE
             * All registered customer accounts are stored in `users`.
             * Public registration always creates role = customer.
             */

            $check = $pdo->prepare(
                'SELECT id, username, email
                 FROM users
                 WHERE username = :username
                    OR email = :email
                 LIMIT 1'
            );

            $check->execute([
                ':username' => $username,
                ':email' => $email
            ]);

            $existing = $check->fetch();

            if ($existing) {

                if (
                    isset($existing['username']) &&
                    strcasecmp($existing['username'], $username) === 0
                ) {
                    $error = 'This username is already registered.';
                } else {
                    $error = 'This email address is already registered.';
                }

            } else {

                $hashedPassword =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                $stmt = $pdo->prepare(
                    'INSERT INTO users
                        (
                            full_name,
                            username,
                            email,
                            contact_number,
                            password,
                            role
                        )
                     VALUES
                        (
                            :full_name,
                            :username,
                            :email,
                            :contact_number,
                            :password,
                            :role
                        )'
                );

                $stmt->execute([

                    ':full_name' =>
                        $fullName,

                    ':username' =>
                        $username,

                    ':email' =>
                        $email,

                    ':contact_number' =>
                        $contact,

                    ':password' =>
                        $hashedPassword,

                    ':role' =>
                        'customer'
                ]);

                /*
                 * IMPORTANT:
                 * Do NOT log the new customer in automatically.
                 *
                 * After successful registration, send the customer
                 * back to login.php so they must enter their credentials.
                 */
                unset(
                    $_SESSION['customer_id'],
                    $_SESSION['customer_name'],
                    $_SESSION['customer_email'],
                    $_SESSION['customer_contact'],
                    $_SESSION['user_id'],
                    $_SESSION['user_role']
                );

                header(
                    'Location: login.php?registered=1'
                    . ($redirect !== 'index.php'
                        ? '&redirect=' . urlencode($redirect)
                        : '')
                );

                exit;
            }

        } catch (PDOException $e) {

            $error =
                'Registration failed. Please try again.';
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

    <title>
        Create Account |
        Minguito Veterinary Clinic
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {

            --green: #073b2a;
            --cream: #f8efe2;
            --gold: #b57a2f;
            --text: #17231e;
            --muted: #66756e;
            --red: #a13d32;
            --red-bg: #fff1ee;

        }

        * {

            box-sizing: border-box;

            margin: 0;
            padding: 0;

        }

        body {

            min-height: 100vh;

            display: flex;

            justify-content: center;

            padding: 38px 18px;

            font-family:
                "DM Sans",
                Arial,
                sans-serif;

            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(181,122,47,.10),
                    transparent 25%
                ),
                radial-gradient(
                    circle at 90% 90%,
                    rgba(7,59,42,.08),
                    transparent 26%
                ),
                linear-gradient(
                    135deg,
                    #fbf3e6,
                    #f1dfc4
                );

            color:
                var(--text);
        }

        .card {

            position: relative;

            z-index: 2;

            width:
                min(540px, 100%);

            align-self:
                center;

            background:
                rgba(255,255,255,.94);

            border:
                1px solid
                rgba(181,122,47,.20);

            border-radius:
                28px;

            padding:
                40px;

            box-shadow:
                0 25px 65px
                rgba(7,59,42,.14);
        }

        .logo {

            display: block;

            width: 175px;

            max-width: 70%;

            margin:
                0 auto 16px;
        }

        .eyebrow {

            text-align: center;

            color:
                var(--gold);

            font-size: 12px;

            font-weight: 800;

            letter-spacing:
                1.8px;

            margin-bottom: 8px;
        }

        h1 {

            text-align: center;

            color:
                var(--green);

            font:
                700 38px/1.06
                "Playfair Display",
                Georgia,
                serif;

            margin-bottom:
                10px;
        }

        .intro {

            color:
                var(--muted);

            text-align: center;

            font-size: 14px;

            line-height:
                1.65;

            margin-bottom:
                24px;
        }

        .error {

            padding:
                13px 15px;

            border-radius:
                12px;

            background:
                var(--red-bg);

            border:
                1px solid #efc4be;

            color:
                var(--red);

            font-size:
                13px;

            line-height:
                1.5;

            text-align:
                center;

            margin-bottom:
                19px;
        }

        .row {

            display:
                grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                15px;
        }

        .group {

            margin-bottom:
                17px;
        }

        .group label {

            display:
                block;

            color:
                var(--green);

            font-size:
                13px;

            font-weight:
                800;

            margin-bottom:
                7px;
        }

        .group input {

            width:
                100%;

            height:
                50px;

            padding:
                0 14px;

            border:
                1px solid #dccab4;

            border-radius:
                12px;

            background:
                #fffaf3;

            color:
                var(--text);

            font:
                inherit;

            font-size:
                14px;

            outline:
                none;

            transition:
                .25s ease;
        }

        .group input:focus {

            border-color:
                var(--gold);

            box-shadow:
                0 0 0 4px
                rgba(181,122,47,.10);
        }

        .password {

            position:
                relative;
        }

        .password input {

            padding-right:
                48px;
        }

        .toggle {

            position:
                absolute;

            right:
                8px;

            top:
                50%;

            transform:
                translateY(-50%);

            border:
                0;

            background:
                transparent;

            color:
                var(--gold);

            font-size:
                16px;

            cursor:
                pointer;
        }

        .btn {

            width:
                100%;

            height:
                54px;

            border:
                0;

            border-radius:
                12px;

            background:
                var(--green);

            color:
                #fff;

            font:
                inherit;

            font-size:
                15px;

            font-weight:
                800;

            cursor:
                pointer;

            box-shadow:
                0 12px 24px
                rgba(7,59,42,.14);

            transition:
                .25s ease;
        }

        .btn:hover {

            background:
                var(--gold);

            transform:
                translateY(-2px);
        }

        .links {

            color:
                var(--muted);

            text-align:
                center;

            font-size:
                13px;

            margin-top:
                20px;

            line-height:
                1.8;
        }

        .links a {

            color:
                var(--green);

            font-weight:
                800;

            text-decoration:
                none;
        }

        .links a:hover {

            color:
                var(--gold);
        }

        @media (max-width:560px) {

            .card {

                padding:
                    30px 22px;

                border-radius:
                    22px;
            }

            .row {

                grid-template-columns:
                    1fr;
            }

            h1 {

                font-size:
                    34px;
            }

        }

    </style>

</head>

<body>

<main class="card">

    <img
        class="logo"
        src="assets/images/logo.png"
        alt="Minguito Veterinary Clinic"
    >

    <p class="eyebrow">
        CUSTOMER PORTAL
    </p>

    <h1>
        Create Your Account
    </h1>

    <p class="intro">
        Register once and book appointments
        faster with your saved information.
    </p>

    <?php if ($error !== ''): ?>

        <div class="error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>

    <form
        method="POST"
        action="register.php"
    >

        <input
            type="hidden"
            name="redirect"
            value="<?= htmlspecialchars(
                $redirect,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['register_csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <div class="group">

            <label for="full_name">
                Full Name
            </label>

            <input
                id="full_name"
                type="text"
                name="full_name"
                value="<?= htmlspecialchars(
                    $fullName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                placeholder="Enter your full name"
                autocomplete="name"
                minlength="2"
                maxlength="100"
                required
            >

        </div>

        <div class="group">

            <label for="username">
                Username
            </label>

            <input
                id="username"
                type="text"
                name="username"
                value="<?= htmlspecialchars(
                    $username,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                placeholder="Choose a username"
                autocomplete="username"
                minlength="3"
                maxlength="50"
                pattern="[A-Za-z0-9_.-]+"
                title="Use 3 to 50 characters: letters, numbers, dot, underscore, or hyphen."
                required
            >

        </div>

        <div class="row">

            <div class="group">

                <label for="email">
                    Email Address
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="you@example.com"
                    autocomplete="email"
                    maxlength="150"
                    required
                >

            </div>

            <div class="group">

                <label for="contact_number">
                    Contact Number
                </label>

                <input
                    id="contact_number"
                    type="tel"
                    name="contact_number"
                    value="<?= htmlspecialchars(
                        $contact,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="09XXXXXXXXX"
                    autocomplete="tel"
                    inputmode="numeric"
                    pattern="09[0-9]{9}"
                    minlength="11"
                    maxlength="11"
                    title="Contact number must be exactly 11 digits and start with 09."
                    required
                >

            </div>

        </div>

        <div class="row">

            <div class="group">

                <label for="password">
                    Password
                </label>

                <div class="password">

                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="At least 8 characters"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >

                    <button
                        class="toggle"
                        type="button"
                        data-target="password"
                        aria-label="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>

            <div class="group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <div class="password">

                    <input
                        id="confirm_password"
                        type="password"
                        name="confirm_password"
                        placeholder="Repeat your password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >

                    <button
                        class="toggle"
                        type="button"
                        data-target="confirm_password"
                        aria-label="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>

        </div>

        <button
            class="btn"
            type="submit"
        >
            CREATE ACCOUNT
        </button>

    </form>

    <div class="links">

        <p>

            Already have an account?

            <a
                href="login.php<?= 
                    $redirect !== 'index.php'
                    ? '?redirect=' . urlencode($redirect)
                    : ''
                ?>"
            >
                Login here
            </a>

        </p>

        <p>

            <a href="index.php">
                ← Back to Home
            </a>

        </p>

    </div>

</main>

<script>

const contactInput =
    document.getElementById('contact_number');

if (contactInput) {
    contactInput.addEventListener('input', function() {
        this.value = this.value
            .replace(/\D/g, '')
            .slice(0, 11);
    });
}

document.querySelectorAll(
    '.toggle'
).forEach(function(button) {

    button.addEventListener(
        'click',
        function() {

            const input =
                document.getElementById(
                    button.dataset.target
                );

            const show =
                input.type === 'password';

            input.type =
                show
                ? 'text'
                : 'password';

            button.textContent =
                show
                ? '🙈'
                : '👁';

        }
    );

});

</script>

</body>
</html>