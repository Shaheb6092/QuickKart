<?php
require_once 'common/header.php';

if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    header('Content-Type: application/json');
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $order_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
    }
    exit;
}

$order_id = $_GET['id'] ?? 0;
// Fetch order details
$stmt = $pdo->prepare("SELECT o.*, u.name, u.email, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Fetch order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll();

require_once 'common/sidebar.php';
?>

<h1 class="text-2xl font-bold mb-6">Order Details #<?= $order['id'] ?></h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 border-b pb-2">Ordered Items</h2>
        <?php foreach($order_items as $item): ?>
        <div class="flex items-center space-x-4 mb-4">
            <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" class="w-16 h-16 rounded object-cover">
            <div class="flex-1">
                <p class="font-semibold"><?= htmlspecialchars($item['product_name']) ?></p>
                <p class="text-sm text-gray-600">Qty: <?= $item['quantity'] ?> x <?= format_price($item['price']) ?></p>
            </div>
            <p class="font-bold"><?= format_price($item['quantity'] * $item['price']) ?></p>
        </div>
        <?php endforeach; ?>
        <div class="text-right border-t pt-4 mt-4">
            <p class="text-xl font-bold">Total: <?= format_price($order['total_amount']) ?></p>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-md space-y-4">
        <h2 class="text-xl font-semibold mb-2 border-b pb-2">Customer & Shipping</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($order['name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
        <p><strong>Address:</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
        
        <div class="pt-4">
             <label for="order_status" class="block text-sm font-medium text-gray-700">Update Order Status</label>
             <select id="order_status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                <option <?= $order['status'] == 'Placed' ? 'selected' : '' ?>>Placed</option>
                <option <?= $order['status'] == 'Dispatched' ? 'selected' : '' ?>>Dispatched</option>
                <option <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                <option <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
             </select>
             <button onclick="updateStatus()" class="mt-3 w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg">Update Status</button>
        </div>
    </div>
</div>

<script>
async function updateStatus() {
    const status = document.getElementById('order_status').value;
    if (!confirm(`Are you sure you want to change status to "${status}"?`)) return;

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', <?= $order_id ?>);
    formData.append('status', status);

    const response = await fetch('order_detail.php', { method: 'POST', body: formData });
    const result = await response.json();
    alert(result.message);
    if(result.status === 'success') location.reload();
}
</script>

<?php require_once 'common/bottom.php'; ?>