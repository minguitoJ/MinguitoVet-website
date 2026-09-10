<?php

session_start();

require_once __DIR__ . '/config/database.php';

/*
|--------------------------------------------------------------------------
| CUSTOMER AUTHENTICATION
|--------------------------------------------------------------------------
| The customer account now comes from the unified users table.
| Keep customer_id as a legacy fallback so older sessions do not
| immediately break, but all profile data is loaded from users.
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $_SESSION['user_id']
    ?? $_SESSION['customer_id']
    ?? 0
);

$userRole = $_SESSION['user_role'] ?? '';

if ($userId <= 0 || ($userRole !== '' && $userRole !== 'customer')) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$customerId = $userId;

$success = '';
$error = '';

$customer = null;


/* =========================================================
   LOAD CUSTOMER FROM USERS
========================================================= */

try {

    $stmt = $pdo->prepare(
        'SELECT
            id,
            full_name,
            email,
            contact_number,
            created_at
         FROM users
         WHERE id = :id
           AND role = :role
         LIMIT 1'
    );

    $stmt->execute([
        ':id' => $customerId,
        ':role' => 'customer'
    ]);

    $customer = $stmt->fetch();

    if (!$customer) {

        $_SESSION = [];
        session_destroy();

        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {

    error_log(
        'Minguito profile load error: ' .
        $e->getMessage()
    );

    $error =
        'Unable to load your profile right now.';
}


/* =========================================================
   UPDATE CUSTOMER IN USERS
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $customer
) {

    $fullName =
        trim(
            $_POST['full_name'] ?? ''
        );

    $email =
        trim(
            $_POST['email'] ?? ''
        );

    $contactNumber =
        trim(
            $_POST['contact_number'] ?? ''
        );


    if (
        $fullName === ''
        ||
        $email === ''
        ||
        $contactNumber === ''
    ) {

        $error =
            'Please fill in all fields.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } else {

        try {

            /* ---------------------------------------------
               CHECK DUPLICATE EMAIL
            --------------------------------------------- */

            $check =
                $pdo->prepare(
                    'SELECT id
                     FROM users
                     WHERE email = :email
                     AND id <> :id
                     AND role = :role
                     LIMIT 1'
                );

            $check->execute([
                ':email' => $email,
                ':id' => $customerId,
                ':role' => 'customer'
            ]);


            if ($check->fetch()) {

                $error =
                    'That email address is already being used by another customer.';

            } else {

                /* -----------------------------------------
                   UPDATE USERS TABLE
                ----------------------------------------- */

                $update =
                    $pdo->prepare(
                        'UPDATE users
                         SET
                            full_name = :full_name,
                            email = :email,
                            contact_number = :contact_number
                         WHERE id = :id
                           AND role = :role'
                    );

                $update->execute([

                    ':full_name' =>
                        $fullName,

                    ':email' =>
                        $email,

                    ':contact_number' =>
                        $contactNumber,

                    ':id' =>
                        $customerId,

                    ':role' =>
                        'customer'

                ]);


                /* -----------------------------------------
                   UPDATE SESSION
                ----------------------------------------- */

                $_SESSION['user_id'] =
                    $customerId;

                $_SESSION['user_role'] =
                    'customer';

                $_SESSION['customer_id'] =
                    $customerId;

                $_SESSION['customer_name'] =
                    $fullName;

                $_SESSION['customer_email'] =
                    $email;

                $_SESSION['customer_contact'] =
                    $contactNumber;


                /* -----------------------------------------
                   UPDATE DISPLAYED DATA
                ----------------------------------------- */

                $customer['full_name'] =
                    $fullName;

                $customer['email'] =
                    $email;

                $customer['contact_number'] =
                    $contactNumber;


                $success =
                    'Your profile has been updated successfully.';
            }

        } catch (PDOException $e) {

            error_log(
                'Minguito profile update error: ' .
                $e->getMessage()
            );

            $error =
                'Unable to update your profile right now.';
        }
    }
}


$pageTitle =
    'My Profile';

?>

<?php include 'includes/header.php'; ?>

<?php include 'includes/navbar.php'; ?>


<style>

/* =========================================================
   MINGUITO CUSTOMER PROFILE
   All styles are scoped to avoid conflicts with the
   site's main CSS and navbar.
========================================================= */

.minguito-profile-page {

    min-height:
        calc(100vh - 90px);

    padding:
        72px 20px 90px;

    background:

        radial-gradient(
            circle at 10% 10%,
            rgba(181,122,47,.10),
            transparent 28%
        ),

        radial-gradient(
            circle at 90% 85%,
            rgba(7,59,42,.08),
            transparent 30%
        ),

        linear-gradient(
            135deg,
            #fbf3e6 0%,
            #f2e3ce 100%
        );

}


.minguito-profile-container {

    width:
        min(
            1120px,
            100%
        );

    margin:
        0 auto;

}


.minguito-profile-grid {

    display:
        grid;

    grid-template-columns:
        320px minmax(0,1fr);

    gap:
        25px;

    align-items:
        start;

}


/* =========================================================
   LEFT PROFILE SUMMARY
========================================================= */

.minguito-profile-summary {

    position:
        relative;

    overflow:
        hidden;

    background:
        #073b2a;

    border-radius:
        26px;

    padding:
        34px 28px;

    color:
        #ffffff;

    box-shadow:
        0 20px 45px
        rgba(7,59,42,.16);

}


.minguito-profile-summary::before {

    content:
        "";

    position:
        absolute;

    width:
        180px;

    height:
        180px;

    right:
        -80px;

    top:
        -75px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.06);

}


.minguito-profile-summary::after {

    content:
        "";

    position:
        absolute;

    width:
        130px;

    height:
        130px;

    left:
        -55px;

    bottom:
        -65px;

    border-radius:
        50%;

    background:
        rgba(181,122,47,.16);

}


.minguito-profile-avatar {

    position:
        relative;

    z-index:
        1;

    width:
        84px;

    height:
        84px;

    margin:
        0 auto 18px;

    display:
        grid;

    place-items:
        center;

    border-radius:
        50%;

    background:
        linear-gradient(
            135deg,
            #d5aa63,
            #b57a2f
        );

    color:
        #ffffff;

    font-size:
        28px;

    font-weight:
        800;

    box-shadow:
        0 10px 25px
        rgba(0,0,0,.16);

}


.minguito-profile-summary
.profile-label {

    position:
        relative;

    z-index:
        1;

    margin:
        0 0 8px;

    color:
        #d9b777;

    text-align:
        center;

    font-size:
        11px;

    font-weight:
        800;

    letter-spacing:
        1.8px;

}


.minguito-profile-summary h2 {

    position:
        relative;

    z-index:
        1;

    margin:
        0 0 8px;

    color:
        #ffffff;

    text-align:
        center;

    font:
        700 27px/1.15
        "Playfair Display",
        Georgia,
        serif;

}


.minguito-profile-email {

    position:
        relative;

    z-index:
        1;

    margin:
        0;

    color:
        rgba(255,255,255,.72);

    text-align:
        center;

    font-size:
        12px;

    line-height:
        1.6;

    word-break:
        break-word;

}


.minguito-profile-summary-divider {

    position:
        relative;

    z-index:
        1;

    height:
        1px;

    margin:
        25px 0 20px;

    background:
        rgba(255,255,255,.14);

}


.minguito-profile-summary-link {

    position:
        relative;

    z-index:
        1;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    min-height:
        46px;

    padding:
        0 14px;

    margin-bottom:
        8px;

    border-radius:
        11px;

    color:
        rgba(255,255,255,.90);

    text-decoration:
        none;

    font-size:
        12px;

    font-weight:
        700;

    transition:
        background .2s ease,
        color .2s ease;

}


.minguito-profile-summary-link:hover {

    background:
        rgba(255,255,255,.09);

    color:
        #ffffff;

}


.minguito-profile-summary-link.active {

    background:
        rgba(255,255,255,.12);

    color:
        #ffffff;

}


.minguito-profile-summary-link .link-arrow {

    color:
        #d5aa63;

    font-size:
        12px;

}


/* =========================================================
   RIGHT PROFILE CARD
========================================================= */

.minguito-profile-card {

    background:
        rgba(255,255,255,.96);

    border:
        1px solid
        rgba(7,59,42,.09);

    border-radius:
        26px;

    padding:
        34px 36px;

    box-shadow:
        0 20px 45px
        rgba(7,59,42,.10);

}


.minguito-profile-card-header {

    margin-bottom:
        25px;

}


.minguito-profile-card-header .eyebrow {

    margin:
        0 0 8px;

    color:
        #b57a2f;

    font-size:
        11px;

    font-weight:
        800;

    letter-spacing:
        1.8px;

}


.minguito-profile-card-header h1 {

    margin:
        0 0 9px;

    color:
        #073b2a;

    font:
        700 clamp(38px,5vw,52px)/1.02
        "Playfair Display",
        Georgia,
        serif;

}


.minguito-profile-card-header p {

    margin:
        0;

    color:
        #66756e;

    font-size:
        14px;

    line-height:
        1.7;

}


/* =========================================================
   MESSAGES
========================================================= */

.minguito-profile-message {

    margin:
        0 0 20px;

    padding:
        14px 16px;

    border-radius:
        12px;

    font-size:
        13px;

    line-height:
        1.5;

}


.minguito-profile-message.success {

    background:
        #eaf7ef;

    border:
        1px solid
        #c9e6d5;

    color:
        #14633f;

}


.minguito-profile-message.error {

    background:
        #fff1ee;

    border:
        1px solid
        #efc4be;

    color:
        #a13d32;

}


/* =========================================================
   FORM GRID
========================================================= */

.minguito-profile-form {

    display:
        grid;

    gap:
        18px;

}


.minguito-profile-field {

    display:
        grid;

    gap:
        8px;

}


.minguito-profile-field label {

    color:
        #073b2a;

    font-size:
        12px;

    font-weight:
        800;

}


.minguito-profile-field input {

    width:
        100%;

    height:
        56px;

    padding:
        0 16px;

    border:
        1.5px solid
        #d8c3a5;

    border-radius:
        12px;

    background:
        #fffaf3;

    color:
        #18382f;

    font-family:
        "DM Sans",
        Arial,
        sans-serif;

    font-size:
        14px;

    outline:
        none;

    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease;

}


.minguito-profile-field input:hover {

    border-color:
        #caa06a;

}


.minguito-profile-field input:focus {

    border-color:
        #b57a2f;

    background:
        #ffffff;

    box-shadow:
        0 0 0 4px
        rgba(181,122,47,.10);

}


/* =========================================================
   ACCOUNT INFORMATION
========================================================= */

.minguito-profile-account-info {

    display:
        grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap:
        13px;

    margin-top:
        3px;

}


.minguito-profile-info-box {

    padding:
        15px 16px;

    border-radius:
        13px;

    background:
        #f8f1e5;

}


.minguito-profile-info-box span {

    display:
        block;

    margin-bottom:
        5px;

    color:
        #89958f;

    font-size:
        10px;

    font-weight:
        800;

    letter-spacing:
        .9px;

    text-transform:
        uppercase;

}


.minguito-profile-info-box strong {

    color:
        #214f43;

    font-size:
        13px;

}


/* =========================================================
   ACTIONS
========================================================= */

.minguito-profile-actions {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        12px;

    padding-top:
        4px;

}


.minguito-profile-buttons {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    flex-wrap:
        wrap;

}


.minguito-profile-btn {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    min-height:
        49px;

    padding:
        0 19px;

    border-radius:
        11px;

    font-family:
        "DM Sans",
        Arial,
        sans-serif;

    font-size:
        12px;

    font-weight:
        800;

    text-decoration:
        none;

    border:
        0;

    cursor:
        pointer;

    transition:
        transform .2s ease,
        background .2s ease,
        border-color .2s ease;

}


.minguito-profile-btn:hover {

    transform:
        translateY(-1px);

}


.minguito-profile-btn.save {

    background:
        #073b2a;

    color:
        #ffffff;

}


.minguito-profile-btn.save:hover {

    background:
        #b57a2f;

}


.minguito-profile-btn.secondary {

    background:
        #f5e9d9;

    color:
        #073b2a;

}


.minguito-profile-btn.secondary:hover {

    background:
        #ead8bd;

}


.minguito-profile-btn.logout {

    color:
        #a13d32;

    background:
        transparent;

    border:
        1px solid
        #edc9c4;

}


.minguito-profile-btn.logout:hover {

    background:
        #fff1ee;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width:900px) {

    .minguito-profile-grid {

        grid-template-columns:
            1fr;

    }


    .minguito-profile-summary {

        max-width:
            720px;

        width:
            100%;

        margin:
            0 auto;

    }

}


@media (max-width:600px) {

    .minguito-profile-page {

        padding:
            45px 15px 65px;

    }


    .minguito-profile-card {

        padding:
            25px 20px;

        border-radius:
            21px;

    }


    .minguito-profile-summary {

        padding:
            28px 21px;

        border-radius:
            21px;

    }


    .minguito-profile-account-info {

        grid-template-columns:
            1fr;

    }


    .minguito-profile-actions {

        flex-direction:
            column;

        align-items:
            stretch;

    }


    .minguito-profile-buttons {

        display:
            grid;

        grid-template-columns:
            1fr;

    }


    .minguito-profile-btn {

        width:
            100%;

    }

}

</style>


<section class="minguito-profile-page">

    <div class="minguito-profile-container">

        <div class="minguito-profile-grid">


            <!-- =================================================
                 PROFILE SUMMARY
            ================================================== -->

            <aside class="minguito-profile-summary">


                <div class="minguito-profile-avatar">

                    <?php
                    $initial =
                        strtoupper(
                            substr(
                                trim(
                                    $customer['full_name']
                                    ?? 'U'
                                ),
                                0,
                                1
                            )
                        );

                    echo htmlspecialchars(
                        $initial,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                </div>


                <p class="profile-label">
                    CUSTOMER ACCOUNT
                </p>


                <h2>

                    <?= htmlspecialchars(
                        $customer['full_name'] ?? 'Customer',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </h2>


                <p class="minguito-profile-email">

                    <?= htmlspecialchars(
                        $customer['email'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </p>


                <div
                    class="minguito-profile-summary-divider"
                ></div>


                <a
                    href="profile.php"
                    class="minguito-profile-summary-link active"
                >

                    <span>
                        👤 My Profile
                    </span>

                    <span class="link-arrow">
                        →
                    </span>

                </a>


                <a
                    href="appointments.php"
                    class="minguito-profile-summary-link"
                >

                    <span>
                        📅 My Appointments
                    </span>

                    <span class="link-arrow">
                        →
                    </span>

                </a>


                <a
                    href="appointment.php"
                    class="minguito-profile-summary-link"
                >

                    <span>
                        🐾 Book Appointment
                    </span>

                    <span class="link-arrow">
                        →
                    </span>

                </a>


                <a
                    href="logout.php"
                    class="minguito-profile-summary-link"
                >

                    <span>
                        🚪 Logout
                    </span>

                    <span class="link-arrow">
                        →
                    </span>

                </a>


            </aside>


            <!-- =================================================
                 PROFILE FORM
            ================================================== -->

            <main class="minguito-profile-card">


                <header
                    class="minguito-profile-card-header"
                >

                    <p class="eyebrow">
                        CUSTOMER PORTAL
                    </p>

                    <h1>
                        My Profile
                    </h1>

                    <p>
                        Manage your account information
                        and keep your contact details
                        up to date.
                    </p>

                </header>


                <?php if ($success !== ''): ?>

                    <div
                        class="minguito-profile-message success"
                    >

                        <?= htmlspecialchars(
                            $success,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <?php if ($error !== ''): ?>

                    <div
                        class="minguito-profile-message error"
                    >

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <form
                    class="minguito-profile-form"
                    method="POST"
                    action="profile.php"
                >


                    <!-- FULL NAME -->

                    <div class="minguito-profile-field">

                        <label for="full_name">
                            Full Name
                        </label>

                        <input
                            id="full_name"
                            type="text"
                            name="full_name"
                            value="<?= htmlspecialchars(
                                $customer['full_name'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            required
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="minguito-profile-field">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars(
                                $customer['email'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <!-- CONTACT -->

                    <div class="minguito-profile-field">

                        <label for="contact_number">
                            Contact Number
                        </label>

                        <input
                            id="contact_number"
                            type="tel"
                            name="contact_number"
                            value="<?= htmlspecialchars(
                                $customer['contact_number'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Enter your contact number"
                            autocomplete="tel"
                            required
                        >

                    </div>


                    <!-- ACCOUNT INFO -->

                    <div class="minguito-profile-account-info">


                        <div
                            class="minguito-profile-info-box"
                        >

                            <span>
                                Account ID
                            </span>

                            <strong>

                                #<?= (int)
                                    $customer['id']
                                ?>

                            </strong>

                        </div>


                        <div
                            class="minguito-profile-info-box"
                        >

                            <span>
                                Member Since
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    date(
                                        'F j, Y',
                                        strtotime(
                                            $customer['created_at']
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </strong>

                        </div>


                    </div>


                    <!-- ACTIONS -->

                    <div
                        class="minguito-profile-actions"
                    >

                        <div
                            class="minguito-profile-buttons"
                        >

                            <button
                                type="submit"
                                class="minguito-profile-btn save"
                            >

                                SAVE CHANGES

                            </button>


                            <a
                                href="appointments.php"
                                class="minguito-profile-btn secondary"
                            >

                                MY APPOINTMENTS

                            </a>

                        </div>


                        <a
                            href="logout.php"
                            class="minguito-profile-btn logout"
                        >

                            LOGOUT

                        </a>

                    </div>


                </form>


            </main>

        </div>

    </div>

</section>


<?php include 'includes/footer.php'; ?>
