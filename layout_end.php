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
</script>

</body>
</html>
