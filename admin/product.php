<?php
require_once 'common/header.php';

// --- AJAX HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Invalid Request'];

    if ($_POST['action'] == 'save_product') {
        $id = $_POST['id'] ?? null;
        $cat_id = $_POST['cat_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $image_name = $_POST['current_image'] ?? '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../uploads/";
            $image_name = time() . '_' . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image_name);
        }

        if (empty($id)) { // Add
            $stmt = $pdo->prepare("INSERT INTO products (cat_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cat_id, $name, $description, $price, $stock, $image_name]);
            $response = ['status' => 'success', 'message' => 'Product added.'];
        } else { // Edit
            $stmt = $pdo->prepare("UPDATE products SET cat_id=?, name=?, description=?, price=?, stock=?, image=? WHERE id=?");
            $stmt->execute([$cat_id, $name, $description, $price, $stock, $image_name, $id]);
            $response = ['status' => 'success', 'message' => 'Product updated.'];
        }
    }
    
    if ($_POST['action'] == 'delete_product') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $response = ['status' => 'success', 'message' => 'Product deleted.'];
    }

    echo json_encode($response);
    exit;
}

require_once 'common/sidebar.php';
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.cat_id = c.id ORDER BY p.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Manage Products</h1>
<button id="add-product-btn" class="bg-indigo-600 text-white py-2 px-4 rounded-lg mb-6 hover:bg-indigo-700">Add New Product</button>

<div class="bg-white shadow-md rounded-lg overflow-x-auto">
    <table class="min-w-full leading-normal">
        <thead>
            <tr>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Product</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Category</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Price</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Stock</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $prod): ?>
            <tr id="prod-row-<?= $prod['id'] ?>">
                <td class="px-5 py-4 border-b bg-white text-sm">
                    <div class="flex items-center">
                        <img src="../uploads/<?= htmlspecialchars($prod['image'] ?: 'product_placeholder.jpg') ?>" class="w-12 h-12 rounded object-cover mr-4">
                        <p class="font-semibold"><?= htmlspecialchars($prod['name']) ?></p>
                    </div>
                </td>
                <td class="px-5 py-4 border-b bg-white text-sm"><?= htmlspecialchars($prod['category_name']) ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm"><?= format_price($prod['price']) ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm"><?= $prod['stock'] ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm">
                    <button onclick='editProduct(<?= json_encode($prod) ?>)' class="text-blue-600 mr-3"><i class="fas fa-edit"></i></button>
                    <button onclick='deleteProduct(<?= $prod["id"] ?>)' class="text-red-600"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Add/Edit Product -->
<div id="product-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-5 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium text-gray-900" id="modal-title">Add Product</h3>
        <form id="product-form" class="mt-4 space-y-4">
            <input type="hidden" name="action" value="save_product">
            <input type="hidden" name="id" id="prod-id">
            <input type="hidden" name="current_image" id="prod-current-image">
            <div>
                <label class="block text-sm">Name</label>
                <input type="text" name="name" id="prod-name" class="p-2 w-full border rounded-md" required>
            </div>
            <div>
                <label class="block text-sm">Category</label>
                <select name="cat_id" id="prod-cat" class="p-2 w-full border rounded-md" required>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm">Description</label>
                <textarea name="description" id="prod-desc" rows="3" class="p-2 w-full border rounded-md"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm">Price (₹)</label>
                    <input type="number" name="price" id="prod-price" step="0.01" class="p-2 w-full border rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm">Stock</label>
                    <input type="number" name="stock" id="prod-stock" class="p-2 w-full border rounded-md" required>
                </div>
            </div>
            <div>
                <label class="block text-sm">Image</label>
                <input type="file" name="image" id="prod-image" class="p-2 w-full border rounded-md">
            </div>
            <div class="flex justify-end space-x-2 pt-4">
                <button id="close-modal-prod" type="button" class="px-4 py-2 bg-gray-300 rounded-md">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save Product</button>
            </div>
        </form>
    </div>
</div>

<script>
const prodModal = document.getElementById('product-modal');
const addProdBtn = document.getElementById('add-product-btn');
const closeProdModalBtn = document.getElementById('close-modal-prod');
const prodForm = document.getElementById('product-form');

addProdBtn.onclick = () => { prodForm.reset(); document.getElementById('modal-title').innerText="Add New Product"; prodModal.style.display = 'block'; };
closeProdModalBtn.onclick = () => { prodModal.style.display = 'none'; };

function editProduct(product) {
    prodForm.reset();
    document.getElementById('modal-title').innerText = "Edit Product";
    document.getElementById('prod-id').value = product.id;
    document.getElementById('prod-name').value = product.name;
    document.getElementById('prod-cat').value = product.cat_id;
    document.getElementById('prod-desc').value = product.description;
    document.getElementById('prod-price').value = product.price;
    document.getElementById('prod-stock').value = product.stock;
    document.getElementById('prod-current-image').value = product.image;
    prodModal.style.display = 'block';
}

prodForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(prodForm);
    const response = await fetch('product.php', { method: 'POST', body: formData });
    const result = await response.json();
    if (result.status === 'success') { location.reload(); } else { alert(result.message); }
});

async function deleteProduct(id) {
    if (!confirm('Are you sure?')) return;
    const formData = new FormData();
    formData.append('action', 'delete_product');
    formData.append('id', id);
    const response = await fetch('product.php', { method: 'POST', body: formData });
    const result = await response.json();
    if (result.status === 'success') { document.getElementById(`prod-row-${id}`).remove(); } else { alert(result.message); }
}
</script>

<?php require_once 'common/bottom.php'; ?>