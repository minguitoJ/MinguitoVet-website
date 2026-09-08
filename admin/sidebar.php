<?php
// admin/sidebar.php
// Shared sidebar for all admin pages.

$currentPage = basename($_SERVER['PHP_SELF']);

$navItems = [
    ['page' => 'dashboard.php',    'label' => 'Dashboard',     'icon' => 'fa-solid fa-gauge-high'],
    ['page' => 'appointments.php', 'label' => 'Appointments',  'icon' => 'fa-solid fa-calendar-check'],
    ['page' => 'calendar.php',     'label' => 'Calendar',      'icon' => 'fa-solid fa-calendar-days'],
    ['page' => 'patients.php',     'label' => 'Patients',      'icon' => 'fa-solid fa-paw'],
    ['page' => 'services.php',     'label' => 'Services',      'icon' => 'fa-solid fa-stethoscope'],
    ['page' => 'medicines.php',    'label' => 'Medicines',     'icon' => 'fa-solid fa-pills'],
    ['page' => 'messages.php',     'label' => 'Messages',      'icon' => 'fa-solid fa-envelope'],
    ['page' => 'settings.php',     'label' => 'Settings',      'icon' => 'fa-solid fa-gear'],
];
?>

<aside class="admin-sidebar" id="adminSidebar">

    <!-- SIDEBAR BRAND -->
    <div class="sidebar-brand">

        <a href="dashboard.php" class="sidebar-brand-link">

            <div class="brand-mark">
                <img
                    src="../assets/images/logo2.png"
                    alt="Minguito Veterinary Clinic Logo"
                >
            </div>

            <div class="brand-copy">
                <strong>Minguito</strong>
                <span>Veterinary Clinic</span>
            </div>

        </a>

        <button
            type="button"
            class="sidebar-close"
            id="sidebarClose"
            aria-label="Close sidebar"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>


    <!-- ADMIN PROFILE -->
    <div class="sidebar-profile">

        <div class="sidebar-avatar" aria-label="Administrator">
            <svg
                class="admin-profile-icon"
                viewBox="0 0 24 24"
                aria-hidden="true"
                focusable="false"
            >
                <circle cx="12" cy="8" r="3.2"></circle>
                <path d="M5.5 20c.55-3.35 3.05-5.2 6.5-5.2s5.95 1.85 6.5 5.2"></path>
            </svg>
        </div>

        <div class="sidebar-profile-text">

            <strong>
                <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator') ?>
            </strong>

            <span>Administrator</span>

        </div>

    </div>


    <!-- NAVIGATION -->
    <nav class="sidebar-nav" aria-label="Admin navigation">

        <p class="sidebar-section-title">
            MAIN MENU
        </p>

        <?php foreach ($navItems as $item): ?>

            <a
                href="<?= htmlspecialchars($item['page']) ?>"
                class="sidebar-link <?= $currentPage === $item['page'] ? 'active' : '' ?>"
            >

                <span class="sidebar-link-icon">
                    <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                </span>

                <span>
                    <?= htmlspecialchars($item['label']) ?>
                </span>

            </a>

        <?php endforeach; ?>


        <!-- QUICK LINKS -->
        <p class="sidebar-section-title sidebar-section-spaced">
            QUICK LINKS
        </p>


        <a
            href="../index.php"
            class="sidebar-link"
        >

            <span class="sidebar-link-icon">
                <i class="fa-solid fa-house"></i>
            </span>

            <span>
                View Website
            </span>

        </a>


        <!-- LOGOUT -->
        <a
            href="../logout.php"
            class="sidebar-link sidebar-logout"
        >

            <span class="sidebar-link-icon">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>

            <span>
                Logout
            </span>

        </a>

    </nav>


    <!-- SIDEBAR FOOTER -->
    <div class="sidebar-footer">

        <i class="fa-solid fa-heart"></i>

        <span>
            Caring for pets, every day.
        </span>

    </div>

</aside>

<style>
    /* Administrator profile icon */
    .sidebar-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .admin-profile-icon {
        width: 27px;
        height: 27px;
        fill: none;
        stroke: #ffffff;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
</style>


<!-- MOBILE OVERLAY -->
<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- MOBILE SIDEBAR TOGGLE -->
<button
    type="button"
    class="mobile-sidebar-toggle"
    id="sidebarOpen"
    aria-label="Open navigation"
>
    <i class="fa-solid fa-bars"></i>
</button>


<script>
(function () {

    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('sidebarOpen');
    const closeBtn = document.getElementById('sidebarClose');


    function openSidebar() {

        sidebar?.classList.add('open');

        overlay?.classList.add('show');

        document.body.classList.add('sidebar-open');

    }


    function closeSidebar() {

        sidebar?.classList.remove('open');

        overlay?.classList.remove('show');

        document.body.classList.remove('sidebar-open');

    }


    openBtn?.addEventListener(
        'click',
        openSidebar
    );


    closeBtn?.addEventListener(
        'click',
        closeSidebar
    );


    overlay?.addEventListener(
        'click',
        closeSidebar
    );


    document
        .querySelectorAll('.sidebar-link')
        .forEach(link => {

            link.addEventListener(
                'click',
                () => {

                    if (window.innerWidth <= 980) {
                        closeSidebar();
                    }

                }
            );

        });


    window.addEventListener(
        'resize',
        () => {

            if (window.innerWidth > 980) {
                closeSidebar();
            }

        }
    );

})();
</script>