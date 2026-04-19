<?php
require_once 'common/config.php';

// if the visitor isn't logged in send them straight to the login page
// we include a `redirect` query argument so the login handler can send
// them back to this page after they successfully authenticate.

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit(); 
}


require_once 'common/header.php';
require_once 'common/sidebar.php';




// Fetch active orders (Placed, Dispatched)
$active_stmt = $pdo->prepare("
    SELECT o.id, o.total_amount, o.status, o.created_at, p.name as product_name, p.image as product_image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND o.status IN ('Placed', 'Dispatched')
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$active_stmt->execute([$_SESSION['user_id']]);
$active_orders = $active_stmt->fetchAll();

// Fetch past orders (Delivered, Cancelled)
$history_stmt = $pdo->prepare("
    SELECT o.id, o.total_amount, o.status, o.created_at, p.name as product_name, p.image as product_image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND o.status IN ('Delivered', 'Cancelled')
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$history_stmt->execute([$_SESSION['user_id']]);
$past_orders = $history_stmt->fetchAll();
?>

<main class="p-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">My Orders</h1>

    <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="order-tabs">
            <li class="mr-2">
                <button class="inline-block p-4 border-b-2 rounded-t-lg" data-tab="active">Active Orders</button>
            </li>
            <li>
                <button class="inline-block p-4 border-b-2 rounded-t-lg" data-tab="history">Order History</button>
            </li>
        </ul>
    </div>

    <!-- Active Orders Tab -->
    <div id="active-orders-content" class="order-tab-content space-y-4">
        <?php if (empty($active_orders)): ?>
            <p class="text-center text-gray-500 py-8">You have no active orders.</p>
        <?php else: ?>
            <?php foreach ($active_orders as $order): ?>
                <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex items-start space-x-4">
                        <img src="uploads/<?= htmlspecialchars($order['product_image'] ?: 'product_placeholder.jpg') ?>" class="w-16 h-16 rounded-md object-cover">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($order['product_name']) ?></p>
                            <p class="text-sm text-gray-500">Order #<?= $order['id'] ?></p>
                            <p class="text-lg font-bold text-indigo-600"><?= format_price($order['total_amount']) ?></p>
                        </div>
                    </div>
                    <!-- Progress Tracker -->
                    <div class="mt-4">
                        <?php
                            $status = $order['status'];
                            $placed_class = $status === 'Placed' || $status === 'Dispatched' || $status === 'Delivered' ? 'text-indigo-600' : 'text-gray-400';
                            $dispatched_class = $status === 'Dispatched' || $status === 'Delivered' ? 'text-indigo-600' : 'text-gray-400';
                            $delivered_class = $status === 'Delivered' ? 'text-indigo-600' : 'text-gray-400';

                            $line_bg = 'bg-gray-300';
                            if ($status === 'Dispatched') $line_bg = 'bg-gradient-to-r from-indigo-500 to-gray-300';
                            if ($status === 'Delivered') $line_bg = 'bg-indigo-500';
                        ?>
                        <div class="relative w-full h-1 <?= $line_bg ?>"></div>
                        <div class="flex justify-between items-start mt-2">
                            <div class="text-center w-1/3 <?= $placed_class ?>">
                                <i class="fas fa-check-circle text-xl"></i>
                                <p class="text-xs font-semibold">Placed</p>
                            </div>
                            <div class="text-center w-1/3 <?= $dispatched_class ?>">
                                <i class="fas fa-truck text-xl"></i>
                                <p class="text-xs font-semibold">Dispatched</p>
                            </div>
                             <div class="text-center w-1/3 <?= $delivered_class ?>">
                                <i class="fas fa-box-open text-xl"></i>
                                <p class="text-xs font-semibold">Delivered</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Order History Tab -->
    <div id="history-orders-content" class="order-tab-content space-y-4 hidden">
        <?php if (empty($past_orders)): ?>
            <p class="text-center text-gray-500 py-8">You have no past orders.</p>
        <?php else: ?>
            <?php foreach ($past_orders as $order): ?>
                 <div class="bg-white rounded-lg shadow-md p-4 opacity-75">
                    <div class="flex items-start space-x-4">
                        <img src="uploads/<?= htmlspecialchars($order['product_image'] ?: 'product_placeholder.jpg') ?>" class="w-16 h-16 rounded-md object-cover">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($order['product_name']) ?></p>
                            <p class="text-sm text-gray-500">Order #<?= $order['id'] ?></p>
                            <p class="font-bold <?= $order['status'] === 'Delivered' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $order['status'] ?> on <?= date('d M Y', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <p class="text-lg font-bold text-gray-800"><?= format_price($order['total_amount']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('#order-tabs button');
    const tabContents = document.querySelectorAll('.order-tab-content');

    function switchTab(targetTab) {
        tabs.forEach(tab => {
            if (tab.dataset.tab === targetTab) {
                tab.classList.add('border-indigo-500', 'text-indigo-600');
                tab.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
            } else {
                tab.classList.remove('border-indigo-500', 'text-indigo-600');
                tab.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
            }
        });
        tabContents.forEach(content => {
            content.id.includes(targetTab) ? content.classList.remove('hidden') : content.classList.add('hidden');
        });
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });

    // Set initial tab
    switchTab('active');

    // Show success message if redirected from checkout
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success')) {
        showAlert('🎉 Order placed successfully!');
        // Clean the URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<?php require_once 'common/bottom.php'; ?>