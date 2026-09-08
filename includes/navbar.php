<?php
/* =========================================================
   MINGUITO VETERINARY CLINIC
   SHARED PUBLIC NAVBAR
   CUSTOMER + ADMIN + GUEST
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   CURRENT PAGE
========================================================= */

$currentPage = basename($_SERVER['PHP_SELF']);


/* =========================================================
   LOGIN STATUS
========================================================= */

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

$isAdminLoggedIn =
    isset($_SESSION['admin_id']) &&
    !empty($_SESSION['admin_id']);

$adminUsername =
    $_SESSION['admin_username']
    ?? 'Administrator';


/*
|--------------------------------------------------------------------------
| CUSTOMER
|--------------------------------------------------------------------------
*/

$isCustomerLoggedIn =
    isset($_SESSION['customer_id']) &&
    !empty($_SESSION['customer_id']);

$customerName =
    $_SESSION['customer_name']
    ?? 'Customer';


/* =========================================================
   ACTIVE LINK HELPER
========================================================= */

function navActive(string $page): string
{
    global $currentPage;

    return $currentPage === $page
        ? 'active'
        : '';
}

?>


<header class="site-header minguito-navbar">

    <div class="container navbar">


        <!-- =================================================
             LOGO
        ================================================== -->

        <a
            href="index.php"
            class="logo"
        >

            <img
                src="assets/images/logo.png"
                alt="Minguito Veterinary Clinic"
            >

        </a>


        <!-- =================================================
             MOBILE MENU BUTTON
        ================================================== -->

        <button
            type="button"
            class="menu-toggle"
            id="menuToggle"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            ☰
        </button>


        <!-- =================================================
             MAIN NAVIGATION
        ================================================== -->

        <nav
            id="mainNav"
            class="minguito-main-nav"
        >

            <a
                href="index.php"
                class="<?= navActive('index.php') ?>"
            >
                HOME
            </a>


            <a
                href="about.php"
                class="<?= navActive('about.php') ?>"
            >
                ABOUT
            </a>


            <a
                href="services.php"
                class="<?= navActive('services.php') ?>"
            >
                SERVICES
            </a>


            <a
                href="team.php"
                class="<?= navActive('team.php') ?>"
            >
                OUR TEAM
            </a>


            <a
                href="contact.php"
                class="<?= navActive('contact.php') ?>"
            >
                CONTACT
            </a>


            <!-- BOOK APPOINTMENT -->

            <a
                href="appointment.php"
                class="nav-appointment <?= navActive('appointment.php') ?>"
            >

                <span class="appointment-icon">
                    📅
                </span>

                <span>
                    BOOK APPOINTMENT
                </span>

            </a>

        </nav>


        <!-- =================================================
             ACCOUNT AREA
             ADMIN / CUSTOMER / GUEST
        ================================================== -->

        <div class="customer-nav-area">


            <?php if ($isAdminLoggedIn): ?>

            <!-- =================================================
                 ADMIN ACCOUNT
            ================================================== -->

            <div class="profile-dropdown">

                <button
                    type="button"
                    class="profile-toggle"
                    id="profileToggle"
                    aria-haspopup="true"
                    aria-expanded="false"
                >

                    <div class="profile-user">

                        <span class="profile-avatar">
                            🛡️
                        </span>

                        <div class="profile-text">

                            <strong>
                                <?= htmlspecialchars($adminUsername) ?>
                            </strong>

                            <small>
                                Administrator
                            </small>

                        </div>

                    </div>

                    <span class="profile-arrow">
                        ▾
                    </span>

                </button>


                <div
                    class="profile-menu"
                    id="profileMenu"
                >

                    <div class="profile-menu-header">

                        <span class="profile-avatar large">
                            🛡️
                        </span>

                        <div>

                            <strong>
                                <?= htmlspecialchars($adminUsername) ?>
                            </strong>

                            <small>
                                Admin Account
                            </small>

                        </div>

                    </div>


                    <a href="admin/dashboard.php">
                        📊 Admin Dashboard
                    </a>


                    <a href="admin/appointments.php">
                        📅 Appointments
                    </a>


                    <a href="admin/calendar.php">
                        🗓️ Calendar
                    </a>


                    <a href="admin/patients.php">
                        🐾 Patients
                    </a>


                    <a href="admin/services.php">
                        🩺 Services
                    </a>


                    <a href="admin/messages.php">
                        ✉️ Messages
                    </a>


                    <a href="admin/settings.php">
                        ⚙️ Settings
                    </a>


                    <div class="menu-divider"></div>


                    <a
                        href="logout.php"
                        class="logout-item"
                    >
                        🚪 Logout
                    </a>

                </div>

            </div>


            <?php elseif ($isCustomerLoggedIn): ?>


            <!-- =================================================
                 CUSTOMER ACCOUNT
            ================================================== -->

            <div class="profile-dropdown">

                <button
                    type="button"
                    class="profile-toggle"
                    id="profileToggle"
                    aria-haspopup="true"
                    aria-expanded="false"
                >

                    <div class="profile-user">

                        <span class="profile-avatar">
                            👤
                        </span>

                        <div class="profile-text">

                            <strong>
                                <?= htmlspecialchars($customerName) ?>
                            </strong>

                            <small>
                                Customer
                            </small>

                        </div>

                    </div>

                    <span class="profile-arrow">
                        ▾
                    </span>

                </button>


                <div
                    class="profile-menu"
                    id="profileMenu"
                >

                    <div class="profile-menu-header">

                        <span class="profile-avatar large">
                            👤
                        </span>

                        <div>

                            <strong>
                                <?= htmlspecialchars($customerName) ?>
                            </strong>

                            <small>
                                Signed in
                            </small>

                        </div>

                    </div>


                    <a href="profile.php">
                        👤 My Profile
                    </a>


                    <a href="appointments.php">
                        📅 My Appointments
                    </a>


                    <a href="appointment.php">
                        🐾 Book Appointment
                    </a>


                    <div class="menu-divider"></div>


                    <a
                        href="logout.php"
                        class="logout-item"
                    >
                        🚪 Logout
                    </a>

                </div>

            </div>


            <?php else: ?>


            <!-- =================================================
                 GUEST ACCOUNT
            ================================================== -->

            <div class="guest-nav-area">

                <a
                    href="login.php"
                    class="guest-link <?= navActive('login.php') ?>"
                >
                    LOGIN
                </a>


                <a
                    href="register.php"
                    class="guest-link register-link <?= navActive('register.php') ?>"
                >
                    REGISTER
                </a>

            </div>


            <?php endif; ?>


        </div>

    </div>

</header>


<style>

/* =========================================================
   MINGUITO NAVBAR
========================================================= */


/* ---------------------------------------------------------
   HEADER
--------------------------------------------------------- */

.minguito-navbar {

    position: sticky;

    top: 0;

    z-index: 9999;

    width: 100%;

    background: #f8efe2;
}


/* ---------------------------------------------------------
   NAVBAR CONTAINER
--------------------------------------------------------- */

.minguito-navbar .navbar {

    width: min(1440px, 94%);

    min-height: 104px;

    margin: 0 auto;

    display: flex;

    align-items: center;

    justify-content: flex-start;

    gap: 0;
}


/* ---------------------------------------------------------
   LOGO
--------------------------------------------------------- */

.minguito-navbar .logo {

    flex: 0 0 auto;

    width: 210px;

    display: flex;

    align-items: center;
}


.minguito-navbar .logo img {

    width: 200px;

    max-width: 200px;

    height: auto;
}


/* ---------------------------------------------------------
   MAIN NAVIGATION
--------------------------------------------------------- */

.minguito-main-nav {

    flex: 1 1 auto;

    min-width: 0;

    display: flex;

    align-items: right;

    justify-content: right;

    gap: clamp(20px, 2.2vw, 34px);

    margin-left: 10px;
}


/* ---------------------------------------------------------
   NORMAL NAV LINKS
--------------------------------------------------------- */

.minguito-main-nav > a:not(.nav-appointment) {

    position: relative;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    height: 104px;

    color: #073b2a;

    font-size: 16px;

    font-weight: 700;

    white-space: nowrap;

    text-decoration: none;
}


/* ---------------------------------------------------------
   NAV UNDERLINE
--------------------------------------------------------- */

.minguito-main-nav > a:not(.nav-appointment)::after {

    content: "";

    position: absolute;

    left: 0;

    right: 0;

    bottom: 16px;

    width: 0;

    height: 3px;

    margin: auto;

    background: #b57a2f;

    transition: width .25s ease;
}


.minguito-main-nav > a:not(.nav-appointment):hover::after,

.minguito-main-nav > a:not(.nav-appointment).active::after {

    width: 100%;
}


/* ---------------------------------------------------------
   BOOK APPOINTMENT
--------------------------------------------------------- */

.minguito-main-nav .nav-appointment {

    flex: 0 0 auto;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    min-width: 190px;

    min-height: 64px;

    padding: 0 22px;

    border-radius: 20px;

    background: #b57a2f;

    color: #ffffff;

    font-size: 14px;

    font-weight: 800;

    line-height: 1.2;

    text-align: center;

    text-decoration: none;

    box-shadow:
        0 8px 20px rgba(181,122,47,.15);

    transition:
        transform .2s ease,
        background .2s ease,
        box-shadow .2s ease;
}


.minguito-main-nav .nav-appointment:hover,

.minguito-main-nav .nav-appointment.active {

    background: #98631f;

    transform: translateY(-1px);

    box-shadow:
        0 11px 25px rgba(181,122,47,.23);
}


.appointment-icon {

    font-size: 15px;

    line-height: 1;
}


/* =========================================================
   ACCOUNT AREA
========================================================= */

.customer-nav-area {

    flex: 0 0 auto;

    display: flex;

    align-items: center;

    gap: 16px;

    min-height: 64px;

    margin-left: 20px;

    padding-left: 20px;

    border-left:
        1px solid rgba(7,59,42,.14);
}


/* =========================================================
   GUEST LOGIN / REGISTER
========================================================= */

.guest-nav-area {

    display: flex;

    align-items: center;

    gap: 13px;
}


.guest-link {

    position: relative;

    color: #073b2a;

    font-size: 12px;

    font-weight: 800;

    white-space: nowrap;

    text-decoration: none;
}


.guest-link::after {

    content: "";

    position: absolute;

    left: 0;

    bottom: -6px;

    width: 0;

    height: 2px;

    background: #b57a2f;

    transition: width .2s ease;
}


.guest-link:hover::after,

.guest-link.active::after {

    width: 100%;
}


.register-link {

    color: #b57a2f;
}


/* =========================================================
   PROFILE DROPDOWN
========================================================= */

.profile-dropdown {

    position: relative;
}


.profile-toggle {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 10px 16px;

    border: none;

    border-radius: 14px;

    background: #fffaf3;

    border: 1px solid rgba(7,59,42,.12);

    cursor: pointer;

    transition: .25s;
}


.profile-toggle:hover {

    background: #f6ead8;
}


.profile-user {

    display: flex;

    align-items: center;

    gap: 10px;
}


.profile-avatar {

    width: 38px;

    height: 38px;

    border-radius: 50%;

    background: #d9b777;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;
}


.profile-avatar.large {

    width: 46px;

    height: 46px;
}


.profile-text {

    display: flex;

    flex-direction: column;

    align-items: flex-start;
}


.profile-text strong {

    color: #073b2a;

    font-size: 13px;

    max-width: 130px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.profile-text small {

    color: #7b7b7b;

    font-size: 11px;
}


.profile-arrow {

    font-size: 14px;

    color: #073b2a;

    transition: .25s;
}


.profile-dropdown.open .profile-arrow {

    transform: rotate(180deg);
}


/* ---------------------------------------------------------
   DROPDOWN MENU
--------------------------------------------------------- */

.profile-menu {

    position: absolute;

    right: 0;

    top: 115%;

    width: 250px;

    background: #ffffff;

    border-radius: 18px;

    border: 1px solid rgba(7,59,42,.12);

    box-shadow:
        0 18px 40px rgba(0,0,0,.12);

    overflow: hidden;

    opacity: 0;

    visibility: hidden;

    transform: translateY(8px);

    transition: .25s;

    z-index: 999;
}


.profile-dropdown.open .profile-menu {

    opacity: 1;

    visibility: visible;

    transform: translateY(0);
}


/* ---------------------------------------------------------
   PROFILE MENU HEADER
--------------------------------------------------------- */

.profile-menu-header {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 18px;

    background: #f8efe2;
}


.profile-menu-header strong {

    color: #073b2a;

    display: block;

    max-width: 165px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.profile-menu-header small {

    color: #777;
}


/* ---------------------------------------------------------
   DROPDOWN LINKS
--------------------------------------------------------- */

.profile-menu a {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 14px 18px;

    color: #073b2a;

    text-decoration: none;

    font-weight: 600;

    transition: .2s;
}


.profile-menu a:hover {

    background: #f8efe2;

    color: #b57a2f;
}


/* ---------------------------------------------------------
   DIVIDER
--------------------------------------------------------- */

.menu-divider {

    height: 1px;

    background: #ece4d7;
}


/* ---------------------------------------------------------
   LOGOUT
--------------------------------------------------------- */

.logout-item {

    color: #a13d32 !important;
}


.logout-item:hover {

    background: #fff3f0 !important;
}


/* =========================================================
   MOBILE MENU BUTTON
========================================================= */

.minguito-navbar .menu-toggle {

    display: none;

    margin-left: auto;

    padding: 7px 10px;

    border: 0;

    background: transparent;

    color: #073b2a;

    font-size: 27px;

    cursor: pointer;
}


/* =========================================================
   LARGE LAPTOP
========================================================= */

@media (max-width: 1350px) {

    .minguito-navbar .logo {

        width: 180px;
    }


    .minguito-navbar .logo img {

        width: 175px;
    }


    .minguito-main-nav {

        gap: 20px;
    }


    .minguito-main-nav > a:not(.nav-appointment) {

        font-size: 14px;
    }


    .minguito-main-nav .nav-appointment {

        min-width: 165px;

        padding: 0 15px;

        font-size: 12px;
    }


    .customer-nav-area {

        gap: 10px;

        margin-left: 12px;

        padding-left: 12px;
    }

}


/* =========================================================
   TABLET / SMALL LAPTOP
========================================================= */

@media (max-width: 1100px) {

    .minguito-navbar .navbar {

        min-height: 88px;
    }


    .minguito-navbar .logo {

        width: 155px;
    }


    .minguito-navbar .logo img {

        width: 150px;
    }


    .minguito-main-nav {

        gap: 14px;

        margin-left: 5px;
    }


    .minguito-main-nav > a:not(.nav-appointment) {

        font-size: 12px;
    }


    .minguito-main-nav .nav-appointment {

        min-width: 145px;

        min-height: 54px;

        padding: 0 12px;

        border-radius: 15px;

        font-size: 11px;
    }


    .customer-nav-area {

        gap: 8px;

        margin-left: 8px;

        padding-left: 8px;
    }


    .profile-text strong {

        max-width: 85px;
    }


    .profile-toggle {

        padding: 8px 10px;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 900px) {

    .minguito-navbar .navbar {

        min-height: 78px;

        width: 92%;
    }


    .minguito-navbar .logo {

        width: auto;
    }


    .minguito-navbar .logo img {

        width: 145px;
    }


    .minguito-navbar .menu-toggle {

        display: block;
    }


    /* -----------------------------------------------------
       MOBILE MAIN NAV
    ----------------------------------------------------- */

    .minguito-main-nav {

        position: absolute;

        top: 78px;

        left: 0;

        right: 0;

        display: none;

        flex-direction: column;

        align-items: stretch;

        gap: 0;

        width: 100%;

        margin: 0;

        padding: 12px 20px 20px;

        background: #f8efe2;

        border-top:
            1px solid rgba(7,59,42,.10);

        box-shadow:
            0 15px 25px rgba(7,59,42,.10);
    }


    .minguito-main-nav.open {

        display: flex;
    }


    .minguito-main-nav > a:not(.nav-appointment) {

        justify-content: flex-start;

        height: auto;

        padding: 13px 5px;

        font-size: 14px;
    }


    .minguito-main-nav > a:not(.nav-appointment)::after {

        display: none;
    }


    .minguito-main-nav .nav-appointment {

        width: 100%;

        min-height: 52px;

        margin-top: 8px;
    }


    /* -----------------------------------------------------
       ACCOUNT AREA
    ----------------------------------------------------- */

    .customer-nav-area {

        margin-left: auto;

        padding-left: 0;

        border-left: 0;
    }


    .profile-toggle {

        padding: 7px 9px;
    }


    .profile-text strong {

        max-width: 75px;
    }


    .profile-menu {

        right: 0;

        width: 235px;
    }

}


/* =========================================================
   SMALL PHONES
========================================================= */

@media (max-width: 520px) {

    .minguito-navbar .logo img {

        width: 125px;
    }


    .profile-toggle {

        gap: 6px;

        padding: 6px 7px;
    }


    .profile-user {

        gap: 6px;
    }


    .profile-avatar {

        width: 32px;

        height: 32px;

        font-size: 15px;
    }


    .profile-text strong {

        max-width: 58px;

        font-size: 11px;
    }


    .profile-text small {

        font-size: 9px;
    }


    .profile-arrow {

        font-size: 12px;
    }


    .profile-menu {

        width: 220px;
    }


    .guest-nav-area {

        gap: 8px;
    }


    .guest-link {

        font-size: 10px;
    }

}

</style>


<script>

document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       MOBILE MENU
    ===================================================== */

    const menuToggle =
        document.getElementById("menuToggle");

    const mainNav =
        document.getElementById("mainNav");


    if (menuToggle && mainNav) {

        menuToggle.addEventListener("click", function () {

            const isOpen =
                mainNav.classList.toggle("open");

            menuToggle.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );

            menuToggle.setAttribute(
                "aria-label",
                isOpen
                    ? "Close navigation"
                    : "Open navigation"
            );

        });

    }


    /* =====================================================
       PROFILE DROPDOWN
    ===================================================== */

    const dropdown =
        document.querySelector(".profile-dropdown");

    const profileToggle =
        document.getElementById("profileToggle");


    if (dropdown && profileToggle) {

        profileToggle.addEventListener("click", function (event) {

            event.stopPropagation();

            const isOpen =
                dropdown.classList.toggle("open");

            profileToggle.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );

        });


        /* Close dropdown when clicking outside */

        document.addEventListener("click", function () {

            dropdown.classList.remove("open");

            profileToggle.setAttribute(
                "aria-expanded",
                "false"
            );

        });


        /* Prevent dropdown clicks from closing it */

        const profileMenu =
            document.getElementById("profileMenu");


        if (profileMenu) {

            profileMenu.addEventListener(
                "click",
                function (event) {

                    event.stopPropagation();

                }
            );

        }

    }


    /* =====================================================
       CLOSE MOBILE MENU WHEN A LINK IS CLICKED
    ===================================================== */

    if (mainNav) {

        const navLinks =
            mainNav.querySelectorAll("a");


        navLinks.forEach(function (link) {

            link.addEventListener("click", function () {

                mainNav.classList.remove("open");

                if (menuToggle) {

                    menuToggle.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                    menuToggle.setAttribute(
                        "aria-label",
                        "Open navigation"
                    );

                }

            });

        });

    }

});

</script>