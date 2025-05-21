<?php
// Zabezpieczenie przed bezpośrednim dostępem do pliku
if (!defined('ADMIN_PANEL')) {
    header("Location: ../login.php");
    exit;
}
?>
        </div><!-- .admin-content -->
    </div><!-- .flex -->

    <script>
        // Obsługa przełączania sidebaru na urządzeniach mobilnych
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.querySelector('.admin-sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
                
                // Zamykanie sidebaru po kliknięciu poza nim na urządzeniach mobilnych
                document.addEventListener('click', function(event) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggle = sidebarToggle.contains(event.target);
                    
                    if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth < 768 && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                    }
                });
            }
            
            // Dodanie klasy active do aktualnej strony w menu
            const currentPage = window.location.pathname.split('/').pop();
            const menuItems = document.querySelectorAll('.admin-sidebar a');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href === currentPage) {
                    item.classList.add('active');
                }
            });
        });
        
        <?php if (isset($extra_js)): ?>
        <?php echo $extra_js; ?>
        <?php endif; ?>
    </script>
</body>
</html>
