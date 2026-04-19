<?php
require_once 'common/header.php';
require_once 'common/sidebar.php';

$cat_id = $_GET['cat_id'] ?? 0;
$sort = $_GET['sort'] ?? 'new';

$where = '';
$params = [];
if ($cat_id) {
    $where = 'WHERE cat_id = ?';
    $params[] = $cat_id;
}

$order_by = 'ORDER BY created_at DESC';
if ($sort === 'price_asc') {
    $order_by = 'ORDER BY price ASC';
} elseif ($sort === 'price_desc') {
    $order_by = 'ORDER BY price DESC';
}

$stmt = $pdo->prepare("SELECT * FROM products $where $order_by");
$stmt->execute($params);
$products = $stmt->fetchAll();

$category_name = 'All Products';
if($cat_id) {
    $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_stmt->execute([$cat_id]);
    $category = $cat_stmt->fetch();
    if($category) $category_name = $category['name'];
}
?>

<main class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($category_name) ?></h1>
        <!-- Filter/Sort Dropdown could go here -->
    </div>
    
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <a href="product_detail.php?id=<?= $product['id'] ?>" class="block">
                    <img src="uploads/<?= htmlspecialchars($product['image'] ?: 'product_placeholder.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-32 object-cover">
                    <div class="p-3">
                        <h3 class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="text-md font-bold text-indigo-600 mt-1"><?= format_price($product['price']) ?></p>
                    </div>
                </a>
                 <div class="px-3 pb-3">
                     <button onclick="addToCart(<?= $product['id'] ?>)" class="w-full bg-indigo-500 text-white text-xs font-bold py-2 rounded-md hover:bg-indigo-600 transition-colors">
                        Add to Cart
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="col-span-full text-center text-gray-500 mt-8">No products found in this category.</p>
        <?php endif; ?>
    </div>
</main>

<script>
    async function addToCart(productId) {
        showLoader();
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', 1);
        formData.append('ajax', '1');

        try {
            const response = await fetch('cart.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if(result.status === 'success') {
                showAlert('Product added to cart!');
                const cartCountEl = document.getElementById('cart-count');
                if (cartCountEl && result.cart_count !== undefined) {
                    cartCountEl.textContent = result.cart_count;
                }
            } else {
                showAlert(result.message || 'Failed to add to cart.', true);
            }
        } catch (error) {
            showAlert('An error occurred.', true);
        } finally {
            hideLoader();
        }
    }
</script>

<?php require_once 'common/bottom.php'; ?>