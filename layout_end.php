</div>
</div>

<script>
    const body = document.body;
    const toggleButton = document.querySelector('.sidebar-toggle');

    if (toggleButton) {
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === '1') {
            body.classList.add('sidebar-collapsed');
        }

        toggleButton.addEventListener('click', function () {
            const collapsed = body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
        });
    }

    // Menu mobile (off-canvas) -- terpisah dari toggle collapse desktop di atas.
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    function tutupMenuMobile() {
        body.classList.remove('sidebar-mobile-open');
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function () {
            body.classList.toggle('sidebar-mobile-open');
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', tutupMenuMobile);
    }
    // Tutup otomatis begitu salah satu menu diklik (supaya tidak nutupin layar terus di HP)
    document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-bottom .btn-logout').forEach(function (a) {
        a.addEventListener('click', tutupMenuMobile);
    });
</script>

</body>
</html>
