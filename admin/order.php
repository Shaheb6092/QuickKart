<?php
require_once 'common/header.php';
require_once 'common/sidebar.php';

$orders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">All Orders</h1>

<div class="bg-white shadow-md rounded-lg overflow-x-auto">
    <table class="min-w-full leading-normal">
        <thead>
            <tr>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Order ID</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">User</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Amount</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Status</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Date</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td class="px-5 py-4 border-b bg-white text-sm">#<?= $order['id'] ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm font-semibold"><?= htmlspecialchars($order['user_name']) ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm font-bold"><?= format_price($order['total_amount']) ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm">
                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight rounded-full
                    <?php 
                        if ($order['status'] == 'Delivered') echo 'bg-green-200 text-green-900';
                        elseif ($order['status'] == 'Dispatched') echo 'bg-blue-200 text-blue-900';
                        elseif ($order['status'] == 'Cancelled') echo 'bg-red-200 text-red-900';
                        else echo 'bg-yellow-200 text-yellow-900';
                    ?>">
                        <?= $order['status'] ?>
                    </span>
                </td>
                <td class="px-5 py-4 border-b bg-white text-sm"><?= date('d M, Y', strtotime($order['created_at'])) ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm">
                    <a href="order_detail.php?id=<?= $order['id'] ?>" class="text-indigo-600 hover:text-indigo-900">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'common/bottom.php'; ?>