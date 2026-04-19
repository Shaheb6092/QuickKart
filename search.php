<?php
require_once 'common/header.php';
require_once 'common/sidebar.php';

// Get the search query from the URL
$search_query = $_GET['q'] ?? '';
$products = [];

if (!empty(trim($search_query))) {
    // Prepare a search term for the LIKE clause in SQL
    $search_term = '%' . $search_query . '%';

    // Prepare a secure statement to prevent SQL injection
    $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE name LIKE ? OR description LIKE ?");
    $stmt->execute([$search_term, $search_term]);
    $products = $stmt->fetchAll();
}
?>

<main class="p-4">
    <h1 class="text-xl font-bold text-gray-800 mb-4">
        <?php if (!empty(trim($search_query))): ?>
            Search Results for "<?= htmlspecialchars($search_query) ?>"
        <?php else: ?>
            Please enter a search term.
        <?php endif; ?>
    </h1>

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
        <?php elseif (!empty(trim($search_query))): ?>
            <p class="col-span-full text-center text-gray-500 mt-8">
                <i class="fas fa-search-minus text-4xl text-gray-300"></i>
                <br>No products found matching your search.
            </p>
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
            const response = await fetch('cart.php', { 
                method: 'POST', 
                body: formData
            });
            const result = await response.json();
            
            if(result.status === 'success') {
                showAlert('Product added to cart!');
                const cartCountEl = document.getElementById('cart-count');
                if (cartCountEl && result.cart_count !== undefined) {
                    cartCountEl.textContent = result.cart_count;
                }
            } else {
                showAlert(result.message, true);
            }
        } catch (error) {
            showAlert('An error occurred.', true);
        } finally {
            hideLoader();
        }
    }
</script>

<?php require_once 'common/bottom.php'; ?>