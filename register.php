<?php
session_start();

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
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
        $fullName === '' ||
        $email === '' ||
        $contact === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {

        $error =
            'Please fill in all required fields.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } elseif (
        strlen($password) < 6
    ) {

        $error =
            'Password must be at least 6 characters.';

    } elseif (
        $password !== $confirmPassword
    ) {

        $error =
            'Passwords do not match.';

    } else {

        try {

            $check = $pdo->prepare(
                'SELECT id
                 FROM customers
                 WHERE email = :email
                 LIMIT 1'
            );

            $check->execute([
                ':email' => $email
            ]);

            if ($check->fetch()) {

                $error =
                    'This email address is already registered.';

            } else {

                $hashedPassword =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                $stmt = $pdo->prepare(
                    'INSERT INTO customers
                        (
                            full_name,
                            email,
                            contact_number,
                            password
                        )
                     VALUES
                        (
                            :full_name,
                            :email,
                            :contact_number,
                            :password
                        )'
                );

                $stmt->execute([

                    ':full_name' =>
                        $fullName,

                    ':email' =>
                        $email,

                    ':contact_number' =>
                        $contact,

                    ':password' =>
                        $hashedPassword

                ]);

                $customerId =
                    (int) $pdo->lastInsertId();

                session_regenerate_id(true);

                $_SESSION['customer_id'] =
                    $customerId;

                $_SESSION['customer_name'] =
                    $fullName;

                $_SESSION['customer_email'] =
                    $email;

                $_SESSION['customer_contact'] =
                    $contact;

                header(
                    'Location: ' . $redirect
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
                        placeholder="At least 6 characters"
                        autocomplete="new-password"
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