<?php 
require_once 'common/header.php';
require_once 'common/sidebar.php';

// Fetch stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered'")->fetchColumn() ?? 0;
$active_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">Total Users</p>
            <p class="text-3xl font-bold"><?= $total_users ?></p>
        </div>
        <i class="fas fa-users text-4xl text-blue-400"></i>
    </div>
     <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">Total Orders</p>
            <p class="text-3xl font-bold"><?= $total_orders ?></p>
        </div>
        <i class="fas fa-receipt text-4xl text-green-400"></i>
    </div>
     <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">Total Revenue</p>
            <p class="text-3xl font-bold"><?= format_price($total_revenue) ?></p>
        </div>
        <i class="fas fa-rupee-sign text-4xl text-yellow-400"></i>
    </div>
     <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">Active Products</p>
            <p class="text-3xl font-bold"><?= $active_products ?></p>
        </div>
        <i class="fas fa-box-open text-4xl text-red-400"></i>
    </div>
</div>

<div class="mt-8">
    <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
    <div class="flex space-x-4">
        <a href="product.php" class="bg-indigo-500 text-white font-bold py-3 px-5 rounded-lg hover:bg-indigo-600">
            <i class="fas fa-plus mr-2"></i>Add Product
        </a>
         <a href="order.php" class="bg-green-500 text-white font-bold py-3 px-5 rounded-lg hover:bg-green-600">
            <i class="fas fa-list-alt mr-2"></i>Manage Orders
        </a>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>