<aside class="w-64 bg-gray-800 text-white flex-shrink-0">
    <div class="p-4 text-center text-2xl font-bold border-b border-gray-700">
        Admin Panel
    </div>
    <nav class="mt-6">
        <a href="index.php" class="flex items-center px-4 py-3 hover:bg-gray-700"><i class="fas fa-tachometer-alt w-6 mr-2"></i>Dashboard</a>
        <a href="category.php" class="flex items-center px-4 py-3 hover:bg-gray-700"><i class="fas fa-tags w-6 mr-2"></i>Categories</a>
        <a href="product.php" class="flex items-center px-4 py-3 hover:bg-gray-700"><i class="fas fa-box w-6 mr-2"></i>Products</a>
        <a href="slideshow.php" class="flex items-center px-4 py-3 hover:bg-gray-700"><i class="fas fa-images w-6 mr-2"></i>Slideshow</a>
        <a href="order.php" class="flex items-center px-4 py-3 hover:bg-gray-700"><i class="fas fa-receipt w-6 mr-2"></i>Orders</a>
        <a href="user.php" class="flex items-center px-4 py-3 hover:bg-gray-700"><i class="fas fa-users w-6 mr-2"></i>Users</a>
        <a href="setting.php" class="flex items-center px-4 py-3 hover:bg-gray-700"><i class="fas fa-cog w-6 mr-2"></i>Settings</a>
        <a href="logout.php" class="flex items-center px-4 py-3 hover:bg-gray-700"><i class="fas fa-sign-out-alt w-6 mr-2"></i>Logout</a>
    </nav>
</aside>
<div class="flex-1 flex flex-col">
    <header class="bg-white shadow p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">Dashboard</h1>
        <span>Welcome, <?= $_SESSION['admin_user'] ?></span>
    </header>
    <main class="flex-1 p-6">