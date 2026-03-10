            </div> <!-- Cierra el contenedor de contenido -->
        </main> <!-- Cierra main-content -->
    </div> <!-- Cierra app-wrapper -->
    
    <script>
        function actualizarHora() {
            const ahora = new Date();
            const opciones = { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const horaFormateada = ahora.toLocaleTimeString('es-MX', opciones);
            const horaElement = document.getElementById('horaActual');
            if (horaElement) {
                horaElement.textContent = horaFormateada;
            }
        }
        
        setInterval(actualizarHora, 1000);
        actualizarHora();
        
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleBtn = document.getElementById('sidebarToggle');
            const toggleMobileBtn = document.getElementById('sidebarToggleMobile');
            
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
            
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                });
            }
            
            if (toggleMobileBtn) {
                toggleMobileBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                });
            }
            
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    const isClickInside = sidebar.contains(event.target) || toggleMobileBtn.contains(event.target);
                    if (!isClickInside && sidebar.classList.contains('mobile-open')) {
                        sidebar.classList.remove('mobile-open');
                    }
                }
            });
        });
    </script>
</body>
</html>