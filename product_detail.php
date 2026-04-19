<?php
require_once 'common/config.php';
require_once 'common/header.php';

$product_id = $_GET['id'] ?? 0;
if (!$product_id) {
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "Product not found.";
    exit();
}

// Fetch related products
$related_stmt = $pdo->prepare("SELECT * FROM products WHERE cat_id = ? AND id != ? LIMIT 4");
$related_stmt->execute([$product['cat_id'], $product_id]);
$related_products = $related_stmt->fetchAll();
?>

<main class="bg-white pb-40">
    <div class="relative">
        <img src="uploads/<?= htmlspecialchars($product['image'] ?: 'product_placeholder.jpg') ?>" 
             alt="<?= htmlspecialchars($product['name']) ?>" 
             class="w-full aspect-square object-cover">

        <a href="javascript:history.back()" 
           class="absolute top-4 left-4 bg-white rounded-full h-10 w-10 flex items-center justify-center shadow-md">
            <i class="fas fa-arrow-left text-gray-700"></i>
        </a>
    </div>

    <div class="p-4">
        <h1 class="text-3xl font-bold text-gray-800">
            <?= htmlspecialchars($product['name']) ?>
        </h1>

        <p class="text-2xl font-bold text-indigo-600 my-2">
            <?= format_price($product['price']) ?>
        </p>

        <span class="text-sm font-medium <?= $product['stock'] > 0 ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100' ?> py-1 px-3 rounded-full">
            <?= $product['stock'] > 0 ? 'In Stock (' . $product['stock'] . ')' : 'Out of Stock' ?>
        </span>

        <div class="my-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Description</h2>
            <p class="text-gray-600 text-sm leading-relaxed">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>
        </div>
    </div>

    <?php if(!empty($related_products)): ?>
    <div class="p-4 bg-gray-50 border-t">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Related Products</h2>
        <div class="grid grid-cols-2 gap-4">
            <?php foreach($related_products as $related): ?>
            <a href="product_detail.php?id=<?= $related['id'] ?>" 
               class="bg-white rounded-lg shadow-sm overflow-hidden">

                <img src="uploads/<?= htmlspecialchars($related['image'] ?: 'product_placeholder.jpg') ?>" 
                     alt="<?= htmlspecialchars($related['name']) ?>" 
                     class="w-full h-28 object-cover">

                <div class="p-2">
                    <h3 class="text-sm font-semibold text-gray-800 truncate">
                        <?= htmlspecialchars($related['name']) ?>
                    </h3>

                    <p class="text-base font-bold text-indigo-600 mt-1">
                        <?= format_price($related['price']) ?>
                    </p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<!-- Sticky Footer -->
<div class="fixed bottom-16 left-0 right-0 bg-white p-3 border-t flex items-center justify-between z-30">
    <form action="cart.php" method="POST" class="flex-1 ml-4 flex space-x-2">
    
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
        <input type="hidden" name="quantity" value="1">

        <button type="submit"
            class="flex-1 bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-indigo-700 disabled:bg-gray-400"
            <?= ($product['stock'] <= 0) ? 'disabled' : '' ?>>
            <i class="fas fa-shopping-cart text-white text-2xl mr-2"></i>
            <?= $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock' ?>
        </button>

    </form>

    <div class="flex-1 ml-4 flex space-x-2">
        <button id="buyNowBtn"
            class="flex-1 bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700 disabled:bg-gray-400"
            <?= ($product['stock'] <= 0) ? 'disabled' : '' ?>>
            Buy Now
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const buyNowBtn = document.getElementById('buyNowBtn');
        const productId = <?= json_encode($product['id']); ?>;

        // Buy Now
        buyNowBtn.addEventListener('click', (e) => {
            e.preventDefault();

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'checkout.php';
            form.style.display = 'none';

            form.innerHTML = `
                <input type="hidden" name="buy_now" value="1">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="quantity" value="1">
            `;

            document.body.appendChild(form);
            form.submit();
        });

    });
</script>

<?php require_once 'common/bottom.php'; ?>