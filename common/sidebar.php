<?php
$sidebar_categories = [];
if (isset($pdo)) {
    $sidebar_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
}
$current_cat_id = $_GET['cat_id'] ?? '';
?>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 w-64 h-full bg-white shadow-lg z-50 transform -translate-x-full transition-transform duration-300 ease-in-out">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-xl font-bold text-indigo-600"><i class="fas fa-shopping-cart text-indigo-600 text-xl mr-2"></i>Quick Kart</h2>
        <button id="close-menu-btn" class="text-gray-600 text-2xl">×</button>
    </div>
    <nav class="mt-5">
        <a href="index.php" class="flex items-center px-5 py-3 text-gray-700 hover:bg-gray-100">
            <i class="fas fa-home w-6"></i>
            <span>Home</span>
        </a> <hr>
        <?php if (!empty($sidebar_categories)): ?>
            <div class="px-5 py-2 bg-white rounded-lg">
                <button type="button" id="sidebar-category-toggle" class="w-full text-left flex items-center justify-between text-md font-semibold text-gray-700 mb-2 focus:outline-none">
                    <span><i class="fas fa-receipt text-md"></i>&nbsp; Category</span>
                    <i id="sidebar-category-toggle-icon" class="fas fa-chevron-down text-sm text-gray-500"></i>
                </button>
                <div id="sidebar-category-list" class="hidden border border-gray-200 rounded-md overflow-hidden">
                    <a href="product.php" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 <?= $current_cat_id === '' ? 'bg-gray-100 font-semibold' : '' ?>">All Categories</a>
                    <?php foreach ($sidebar_categories as $cat): ?>
                        <a href="product.php?cat_id=<?= $cat['id'] ?>" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 <?= ($cat['id'] == $current_cat_id) ? 'bg-gray-100 font-semibold' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?> <hr>
        <a href="order.php" class="flex items-center px-5 py-3 text-gray-700 hover:bg-gray-100">
            <i class="fas fa-box w-6"></i>
            <span>My Orders</span>
        </a> <hr>
        <a href="profile.php" class="flex items-center px-5 py-3 text-gray-700 hover:bg-gray-100">
            <i class="fas fa-user-circle w-6"></i>
            <span>Profile</span>
        </a> <hr>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php" class="flex items-center px-5 py-3 text-red-500 hover:bg-gray-100">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span>Logout</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="flex items-center px-5 py-3 text-green-500 hover:bg-gray-100">
                <i class="fas fa-sign-in-alt w-6"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>
    </nav>
    <hr>

    <!-- App Promotion -->
    <p class="px-5 py-3 text-gray-600">Get our Free App!</p>
    <div class="justify-center space-x-4 px-6">
        <a href="" class="bg-black rounded-lg mb-3 px-3 pb-2 flex items-center space-x-2">
            <i class="fab fa-apple text-3xl text-white"></i>
            <div class="info">
                <span class="text-white text-xs mb-0">Download on the</span>
                <p class="text-white mb-0 text-xl">App Store</p>
            </div>                       
        </a>
        <a href="" class="bg-black rounded-lg mb-3 px-3 pb-2 flex items-center space-x-2" style="margin-left: 0;">
            <i class="fab fa-google-play text-2xl text-white"></i>
            <div class="info">
                <span class="text-white text-xs mb-0">Get it ON</span>
                <p class="text-white mb-0 text-lg">Google Play</p>
            </div>                       
        </a>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggle = document.getElementById('sidebar-category-toggle');
            var list = document.getElementById('sidebar-category-list');
            var icon = document.getElementById('sidebar-category-toggle-icon');
            if (toggle && list && icon) {
                toggle.addEventListener('click', function() {
                    list.classList.toggle('hidden');
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
                });
            }
        });
    </script>
</aside>
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>