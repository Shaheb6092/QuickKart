<?php
// --- ধাপ ১: সমস্ত লজিক আগে সম্পন্ন করা ---
require_once 'common/config.php';

// capture buy-now details before any redirects so they survive authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now'])) {
    $pid = intval($_POST['product_id'] ?? 0);
    $qt  = intval($_POST['quantity'] ?? 1);
    if ($pid > 0 && $qt > 0) {
        $_SESSION['buy_now'] = ['product_id' => $pid, 'quantity' => $qt];
    }
}

// ব্যবহারকারী লগইন না থাকলে রিডাইরেক্ট করতে হবে (ডাটাও সেশনে সংরক্ষণ করা হয়েছে)
if (!isset($_SESSION['user_id'])) {
    $redirect = 'checkout.php';
    header('Location: login.php?redirect=' . urlencode($redirect));
    exit;
}

$buy_now = $_SESSION['buy_now'] ?? null;

// যদি নরম কার্টও খালি হয় এবং বায়-নাও সেট না থাকে তবে কার্টে পাঠিয়ে দিন
if (empty($_SESSION['cart']) && empty($buy_now)) {
    header('Location: cart.php');
    exit;
}


// Handle "Continue Shopping" button - move buy_now item to cart and redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart_instead'])) {
    if (!empty($buy_now)) {
        // Add the buy_now product to the regular cart
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$buy_now['product_id']])) {
            $_SESSION['cart'][$buy_now['product_id']] += $buy_now['quantity'];
        } else {
            $_SESSION['cart'][$buy_now['product_id']] = $buy_now['quantity'];
        }
        // Clear the buy_now session
        unset($_SESSION['buy_now']);
    }
    // Redirect to cart page
    header('Location: cart.php');
    exit;
}


// --- ধাপ ২: অর্ডার প্লেস করার জন্য POST রিকোয়েস্ট হ্যান্ডেল করা ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address']);
    $shipping_area = $_POST['shipping_area'] ?? 'inside'; // 'inside' or 'outside'
    $user_id = $_SESSION['user_id'];
    
    // Calculate shipping cost based on selected area
    $shipping_cost = 0;
    if ($shipping_area === 'outside') {
        $shipping_cost = 100.00;
    } elseif ($shipping_area === 'inside') {
        $shipping_cost = 60.00;
    }

    // প্রস্তুত হবে এমন আইটেম ও টোটাল
    $order_items = [];
    $total_price = 0;

    if ($buy_now) {
        // শুধু একটি প্রোডাক্ট
        $stmt = $pdo->prepare("SELECT id, price, stock FROM products WHERE id = ?");
        $stmt->execute([$buy_now['product_id']]);
        $productData = $stmt->fetch();
        if (!$productData || $productData['stock'] < $buy_now['quantity']) {
            header('Location: product_detail.php?id=' . $buy_now['product_id'] . '&error=stock');
            exit;
        }
        $order_items[] = ['product_id' => $productData['id'], 'quantity' => $buy_now['quantity'], 'price' => $productData['price']];
        $total_price += $productData['price'] * $buy_now['quantity'];
    } else {
        // কার্ট থেকে সব আইটেম
        if (!empty($_SESSION['cart'])) {
            $product_ids = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $stmt = $pdo->prepare("SELECT id, price, stock FROM products WHERE id IN ($placeholders)");
            $stmt->execute($product_ids);
            $products_in_db = $stmt->fetchAll(PDO::FETCH_UNIQUE);

            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                if (isset($products_in_db[$product_id])) {
                    if ($products_in_db[$product_id]['stock'] < $quantity) {
                        header('Location: cart.php?error=stock_out&product_id=' . $product_id);
                        exit;
                    }
                    $order_items[] = ['product_id' => $product_id, 'quantity' => $quantity, 'price' => $products_in_db[$product_id]['price']];
                    $total_price += $products_in_db[$product_id]['price'] * $quantity;
                }
            }
        }
    }

    if (empty($address) || $total_price <= 0) {
        header('Location: checkout.php?error=Invalid data');
        exit;
    }

    // Add shipping cost to total
    $total_with_shipping = $total_price + $shipping_cost;

    try {
        $pdo->beginTransaction();

        $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, status) VALUES (?, ?, ?, 'Placed')");
        $stmt_order->execute([$user_id, $total_with_shipping, $address]);
        $order_id = $pdo->lastInsertId();

        $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($order_items as $item) {
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }

        $pdo->prepare("UPDATE users SET address = ? WHERE id = ?")->execute([$address, $user_id]);

        $pdo->commit();

        if ($buy_now) {
            unset($_SESSION['buy_now']);
        } else {
            unset($_SESSION['cart']);
        }

        header('Location: order.php?success=true');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: checkout.php?error=order_failed');
        exit;
    }
}

// --- ধাপ ৩: পেজ প্রদর্শনের জন্য ডেটা প্রস্তুত করা ---
$user_stmt = $pdo->prepare("SELECT name, phone, email, address FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch();

// পুনরায় মোট মূল্য ও আইটেম তালিকা গণনা করা (শুধু প্রদর্শনের জন্য)
$display_total_price = 0;
$checkout_items = [];
if (!empty($buy_now)) {
    $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ?");
    $stmt->execute([$buy_now['product_id']]);
    $p = $stmt->fetch();
    if ($p) {
        $checkout_items[] = ['id'=>$p['id'], 'name'=>$p['name'], 'price'=>$p['price'], 'quantity'=>$buy_now['quantity']];
        $display_total_price = $p['price'] * $buy_now['quantity'];
    }
} elseif (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, price, name FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_UNIQUE);
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        if (isset($products[$product_id])) {
            $checkout_items[] = ['id'=>$product_id,'name'=>$products[$product_id]['name'],'price'=>$products[$product_id]['price'],'quantity'=>$quantity];
            $display_total_price += $products[$product_id]['price'] * $quantity;
        }
    }
}

// --- ধাপ ৪: HTML প্রদর্শন ---
require_once 'common/header.php';
?>

<main class="p-4">
    <div class="flex items-center mb-6">
        <a href="cart.php" class="text-gray-600 text-lg"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-800 ml-4">Checkout</h1>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <form action="checkout.php" method="POST" id="checkout-form">
            <!-- order summary (cart items or buy-now product) -->
            <?php if(!empty($checkout_items)): ?>
            <h2 class="text-lg font-semibold mb-4 border-b pb-2">Order Summary</h2>
            <ul class="mb-4 space-y-2">
                <?php foreach($checkout_items as $item): ?>
                    <li class="flex justify-between">
                        <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                        <span><?= format_price($item['price'] * $item['quantity']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <h2 class="text-lg font-semibold mb-4 border-b pb-2">Shipping Information</h2>
            
            <!-- Shipping Area Selection -->
            <div class="mb-6 p-4 border border-gray-300 rounded-lg bg-gray-50">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Required: Select Shipping Area</label>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="radio" id="shipping_inside" name="shipping_area" value="inside" checked class="h-4 w-4 text-indigo-600 cursor-pointer" onchange="updateShippingCost()">
                        <label for="shipping_inside" class="ml-3 cursor-pointer">
                            <span class="text-sm font-medium text-gray-700">ঢাকার ভিতরে (Inside Dhaka)</span>
                            <span class="ml-2 text-indigo-600 font-semibold">৳ 60.00</span>
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="shipping_outside" name="shipping_area" value="outside" class="h-4 w-4 text-indigo-600 cursor-pointer" onchange="updateShippingCost()">
                        <label for="shipping_outside" class="ml-3 cursor-pointer">
                            <span class="text-sm font-medium text-gray-700">ঢাকার বাইরে (Outside Dhaka)</span>
                            <span class="ml-2 text-indigo-600 font-semibold">৳ 100.00</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" value="<?= htmlspecialchars($user['name']) ?>" class="mt-1 block w-full bg-gray-100 p-2 border rounded-md" readonly>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" value="<?= htmlspecialchars($user['phone']) ?>" class="mt-1 block w-full bg-gray-100 p-2 border rounded-md" readonly>
            </div>
            <div class="mb-4">
                <label for="address" class="block text-sm font-medium text-gray-700">Shipping Address</label>
                <textarea id="address" name="address" rows="4" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required><?= htmlspecialchars($user['address']) ?></textarea>
            <h2 class="text-lg font-semibold my-4 border-b pb-2">Payment Method</h2>
            <div class="mb-4 bg-gray-100 p-4 rounded-md flex items-center">
                <input type="radio" id="cod" name="payment_method" value="cod" class="h-4 w-4 text-indigo-600 border-gray-300" checked>
                <label for="cod" class="ml-3 block text-sm font-medium text-gray-700"><i class="fas fa-money-bill-wave mr-2"></i>Cash on Delivery (COD)</label>
            </div>
        </form>
    </div>
</main>

<footer class="fixed bottom-16 left-0 right-0 bg-white border-t p-4 z-30">
    <div class="mb-2 text-sm text-gray-600">
        <div class="flex justify-between mb-1">
            <span>Subtotal:</span>
            <span id="subtotal-amount"><?= format_price($display_total_price) ?></span>
        </div>
        <div class="flex justify-between pb-2 border-b">
            <span>Shipping:</span>
            <span id="shipping-amount">৳ 60.00</span>
        </div>
    </div>
    <div class="flex justify-between items-center mb-4">
        <span class="text-lg font-medium text-gray-700">Total Payable:</span>
        <span class="text-xl font-bold text-indigo-600" id="total-amount"><?= format_price($display_total_price + 60) ?></span>
    </div>
    <div class="flex gap-2">
        <form method="POST" action="" class="flex-1">
            <input type="hidden" name="add_to_cart_instead" value="1">
            <button type="submit" class="w-full text-center bg-gray-400 text-white font-bold py-3 rounded-lg hover:bg-gray-500">
                Continue Shopping
            </button>
        </form>
        <button onclick="document.getElementById('checkout-form').submit()" class="flex-1 text-center bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700">
            Place Order
        </button>
    </div>
</footer>

<script>
    const subtotalAmount = <?= $display_total_price ?>;
    
    function updateShippingCost() {
        const shippingArea = document.querySelector('input[name="shipping_area"]:checked').value;
        let shippingCost = 60; // default inside Dhaka
        let shippingDisplay = '৳ 60.00';
        
        if (shippingArea === 'outside') {
            shippingCost = 100;
            shippingDisplay = '৳ 100.00';
        }
        
        // Update shipping cost display
        document.getElementById('shipping-amount').textContent = shippingDisplay;
        
        // Update total
        const total = subtotalAmount + shippingCost;
        document.getElementById('total-amount').textContent = formatCurrency(total);
    }
    
    function formatCurrency(amount) {
        // Format as Bengali Taka with 2 decimal places
        return '৳ ' + amount.toFixed(2);
    }
    
    // Initialize on page load
    updateShippingCost();
</script>

<?php require_once 'common/bottom.php'; ?>```
