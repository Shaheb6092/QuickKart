<?php
require_once 'common/config.php';
require_once 'common/sidebar.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Automatically move any pending buy_now items to the cart
if (!empty($_SESSION['buy_now'])) {
    $buy_now = $_SESSION['buy_now'];
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

// --- AJAX HANDLER (সার্ভার সাইড লজিক) ---
// determine whether this request came via XHR (JavaScript fetch/ajax) or a normal form post
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($isAjax) {
        header('Content-Type: application/json');
    }
    $response = ['status' => 'error', 'message' => 'Invalid action.'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $product_id = intval($_POST['product_id'] ?? 0);

    if ($product_id > 0) {
        switch ($_POST['action']) {
            case 'add':
                $quantity = intval($_POST['quantity'] ?? 1);

                // Validate product
                $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();

                if (!$product) {
                    if ($isAjax) {
                        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
                        exit;
                    }
                }

                if ($product['stock'] < $quantity || $product['stock'] <= 0) {
                    if ($isAjax) {
                        echo json_encode(['status' => 'error', 'message' => 'Insufficient stock.']);
                        exit;
                    }
                    header('Location: cart.php?error=stock');
                    exit;
                }

                // Add to cart (session-based)
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }

                // Optionally, limit cart quantity to available stock
                if ($_SESSION['cart'][$product_id] > $product['stock']) {
                    $_SESSION['cart'][$product_id] = $product['stock'];
                }

                $response = ['status' => 'success'];
                break;
            case 'update':
                $quantity = intval($_POST['quantity'] ?? 1);
                if ($quantity > 0) { $_SESSION['cart'][$product_id] = $quantity; } 
                else { unset($_SESSION['cart'][$product_id]); }
                $response = ['status' => 'success'];
                break;
            case 'delete':
                unset($_SESSION['cart'][$product_id]);
                $response = ['status' => 'success', 'message' => 'Item removed successfully.'];
                break;
            case 'get_cart_data':
                // Return cart data for AJAX updates without page reload
                $cart_items = [];
                $total_price = 0;
                if (!empty($_SESSION['cart'])) {
                    $product_ids = array_keys($_SESSION['cart']);
                    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
                    $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
                    $stmt->execute($product_ids);
                    $products = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
                    foreach ($_SESSION['cart'] as $pid => $qty) {
                        if (isset($products[$pid])) {
                            $p = $products[$pid];
                            $cart_items[] = ['id' => $pid, 'name' => $p['name'], 'price' => $p['price'], 'quantity' => $qty];
                            $total_price += $p['price'] * $qty;
                        }
                    }
                }
                $response = [
                    'status' => 'success',
                    'cart_data' => [
                        'items' => $cart_items,
                        'total_price' => $total_price,
                        'total_price_formatted' => format_price($total_price)
                    ]
                ];
            break;
            
        }
    }
    
    // Return JSON response for AJAX requests
    if ($isAjax) {
        // Add cart count to response
        $cart_count = count($_SESSION['cart'] ?? []);
        $response['cart_count'] = $cart_count;
        echo json_encode($response);
        exit;
    }
}

// --- PAGE DISPLAY (ব্যবহারকারী যা দেখবে) ---
require_once 'common/header.php';

$cart_items = [];
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        if (isset($products[$product_id])) {
            $product = $products[$product_id];
            $cart_items[] = [ 'id' => $product_id, 'name' => $product['name'], 'price' => $product['price'], 'image' => $product['image'], 'quantity' => $quantity ];
            $total_price += $product['price'] * $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]); // যদি কোনো প্রোডাক্ট ডাটাবেস থেকে মুছে ফেলা হয়
        }
    }
}
?>

<main class="p-4 pb-40" id="cart-page"> <!-- ফুটারের জন্য নিচে অতিরিক্ত প্যাডিং যোগ করা হয়েছে -->
    <h1 class="text-2xl font-bold text-gray-800 mb-6">My Cart</h1>

    <div id="cart-items-container">
        <?php if (empty($cart_items)): ?>
            <div class="text-center py-16">
                <i class="fas fa-shopping-cart text-6xl text-gray-300"></i>
                <p class="text-gray-500 mt-4">Your cart is empty.</p>
                <a href="index.php" class="mt-6 inline-block bg-indigo-600 text-white font-bold py-2 px-6 rounded-lg">Shop Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="bg-white rounded-lg shadow-md p-4 mb-4 flex items-center space-x-4" id="item-<?= $item['id'] ?>">
                    <img src="uploads/<?= htmlspecialchars($item['image'] ?: 'product_placeholder.jpg') ?>" class="w-20 h-20 rounded-md object-cover">
                    <div class="flex-1">
                        <h2 class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></h2>
                        <p class="text-indigo-600 font-bold"><?= format_price($item['price']) ?></p>
                        <div class="flex items-center mt-2">
                            <input type="number" value="<?= $item['quantity'] ?>" min="1" onchange="updateCart(<?= $item['id'] ?>, this.value)" class="w-16 border rounded-md text-center p-1">
                        </div>
                    </div>
                    <!-- ডিলিট বাটনের জন্য এই ফাংশনটি কাজ করবে -->
                    <button onclick="deleteCartItem(<?= $item['id'] ?>)" class="text-red-500 hover:text-red-700 px-2">
                        <i class="fas fa-trash-alt text-xl"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- এই ফুটারটি এখন সঠিকভাবে দেখা যাবে এবং কাজ করবে -->
<?php if (!empty($cart_items)): ?>
<footer class="fixed bottom-16 left-0 right-0 bg-white border-t p-4 z-30">
    <div class="flex justify-between items-center mb-4">
        <span class="text-lg font-medium text-gray-700">Total:</span>
        <span class="text-xl font-bold text-indigo-600" id="total-price"><?= format_price($total_price) ?></span>
    </div>
    <a href="checkout.php" class="block w-full text-center bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700">
        Proceed to Checkout
    </a>
</footer>
<?php endif; ?>


<script>
    // এই জাভাস্ক্রিপ্ট কোডটি কার্ট পেজের সমস্ত ফাংশন পরিচালনা করবে
    async function updateCart(productId, quantity) {
        showLoader();
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        formData.append('ajax', '1');
        try {
            const response = await fetch('cart.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                // Update cart count in header
                if (result.cart_count !== undefined) {
                    document.getElementById('cart-count').textContent = result.cart_count;
                }
                // Update total price without full page reload
                updateCartDisplay();
            } else {
                showAlert(result.message || 'Failed to update cart.', true);
                location.reload();
            }
        } catch (error) {
            showAlert('Successfully updated cart.', true);
            location.reload();
        } finally {
            hideLoader();
        }
    }

    // Update cart display (total price) without page reload
    function updateCartDisplay() {
        const formData = new FormData();
        formData.append('action', 'get_cart_data');
        formData.append('ajax', '1');
        fetch('cart.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.cart_data) {
                document.getElementById('total-price').textContent = data.cart_data.total_price_formatted;
            }
        })
        .catch(err => console.error('Error updating cart display:', err));
    }

    async function deleteCartItem(productId) {
        showLoader();
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('product_id', productId);
        formData.append('ajax', '1');
        try {
            const response = await fetch('cart.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                showAlert('Item removed successfully.', true);
                // Remove item from DOM
                const itemElement = document.getElementById('item-' + productId);
                if (itemElement) {
                    itemElement.remove();
                }
                // Update cart count in header
                if (result.cart_count !== undefined) {
                    document.getElementById('cart-count').textContent = result.cart_count;
                }
                // Update total price
                updateCartDisplay();
                // Check if cart is empty
                if (result.cart_count === 0) {
                    location.reload();
                }
            } else {
                showAlert(result.message || 'Failed to remove item.', true);
                location.reload();
            }
        } catch (error) {
            showAlert('Failed to remove item.', true);
            location.reload();
        } finally {
            hideLoader();
        }
    }
</script>

<?php require_once 'common/bottom.php'; ?>