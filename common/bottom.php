        <!-- Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white shadow-[0_-1px_5px_rgba(0,0,0,0.1)] z-40 flex justify-around">
            <a href="index.php" class="flex-1 flex flex-col items-center justify-center py-2 text-indigo-600">
                <i class="fas fa-home text-xl"></i>
                <span class="text-xs font-medium">Home</span>
            </a>
            <a href="cart.php" class="flex-1 flex flex-col items-center justify-center py-2 text-gray-500 hover:text-indigo-600">
                <i class="fas fa-shopping-cart text-xl"></i>
                <span class="text-xs font-medium">Cart</span>
            </a>
            <a href="order.php" class="flex-1 flex flex-col items-center justify-center py-2 text-gray-500 hover:text-indigo-600">
                <i class="fas fa-box-open text-xl"></i>
                <span class="text-xs font-medium">Orders</span>
            </a>
            <a href="profile.php" class="flex-1 flex flex-col items-center justify-center py-2 text-gray-500 hover:text-indigo-600">
                <i class="fas fa-user-circle text-xl"></i>
                <span class="text-xs font-medium">Profile</span>
            </a>
        </nav>
    </div> <!-- Close #app-container -->

    <script>
        // Disable unwanted user actions
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.addEventListener('keydown', function (e) {
            if (e.key === "F12" || (e.ctrlKey && e.shiftKey && (e.key === "I" || e.key === "J" || e.key === "C")) || (e.ctrlKey && e.key === "U")) {
                e.preventDefault();
            }
        });

        // Sidebar functionality
        const menuBtn = document.getElementById('menu-btn');
        const closeMenuBtn = document.getElementById('close-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        }

        menuBtn.addEventListener('click', openSidebar);
        closeMenuBtn.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
        
        // --- Global Helper Functions ---
        const loadingModal = document.getElementById('loading-modal');

        function showLoader() {
            loadingModal.classList.remove('hidden');
        }

        function hideLoader() {
            loadingModal.classList.add('hidden');
        }

        function showAlert(message, isError = false) {
            const alertBox = document.createElement('div');
            alertBox.className = `fixed top-5 right-5 p-4 rounded-lg shadow-lg text-white z-50 transform transition-transform animate-pulse`;
            alertBox.className += isError ? ' bg-red-500' : ' bg-green-500';
            alertBox.textContent = message;
            document.body.appendChild(alertBox);
            setTimeout(() => {
                alertBox.remove();
            }, 3000);
        }
    </script>
</body>
</html>