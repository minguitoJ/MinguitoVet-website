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
    ['page' => 'messages.php',     'label' => 'Messages',      'icon' => 'fa-solid fa-envelope'],
    ['page' => 'settings.php',     'label' => 'Settings',      'icon' => 'fa-solid fa-gear'],
];
?>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="sidebar-brand-link">
            <div class="brand-mark">
                <i class="fa-solid fa-paw"></i>
            </div>
            <div class="brand-copy">
                <strong>Minguito</strong>
                <span>Veterinary Clinic</span>
            </div>
        </a>

        <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="sidebar-profile">
        <div class="sidebar-avatar">
            <i class="fa-solid fa-user-shield"></i>
        </div>
        <div class="sidebar-profile-text">
            <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator') ?></strong>
            <span>Administrator</span>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Admin navigation">
        <p class="sidebar-section-title">MAIN MENU</p>

        <?php foreach ($navItems as $item): ?>
            <a
                href="<?= htmlspecialchars($item['page']) ?>"
                class="sidebar-link <?= $currentPage === $item['page'] ? 'active' : '' ?>"
            >
                <span class="sidebar-link-icon">
                    <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                </span>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <p class="sidebar-section-title sidebar-section-spaced">QUICK LINKS</p>

        <a href="../index.php" class="sidebar-link">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-house"></i>
            </span>
            <span>View Website</span>
        </a>

        <a href="../logout.php" class="sidebar-link sidebar-logout">
            <span class="sidebar-link-icon">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>
            <span>Logout</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <i class="fa-solid fa-heart"></i>
        <span>Caring for pets, every day.</span>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<button type="button" class="mobile-sidebar-toggle" id="sidebarOpen" aria-label="Open navigation">
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

    openBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 980) closeSidebar();
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) closeSidebar();
    });
})();
</script>
