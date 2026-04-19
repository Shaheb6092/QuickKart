<?php
require_once 'common/header.php';

// --- AJAX HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Invalid Request'];

    // Add New Slide
    if ($_POST['action'] == 'add_slide') {
        $link_url = $_POST['link_url'] ?? '#';
        $image_name = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../uploads/";
            $image_name = 'slide_' . time() . '_' . basename($_FILES["image"]["name"]);
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image_name)) {
                // Set is_active to 1 (inactive) and sort_order to 0 by default
                $stmt = $pdo->prepare("INSERT INTO slideshow (image, link_url, is_active, sort_order) VALUES (?, ?, 1, 0)");
                $stmt->execute([$image_name, $link_url]);
                $response = ['status' => 'success', 'message' => 'Slide added successfully. It is inactive by default.'];
            } else {
                $response['message'] = 'Failed to upload image.';
            }
        } else {
            $response['message'] = 'No image was uploaded or an error occurred.';
        }
    }

    // Toggle Active Status
    if ($_POST['action'] == 'toggle_status') {
        $id = $_POST['id'];
        // First, get the current status
        $stmt = $pdo->prepare("SELECT is_active FROM slideshow WHERE id = ?");
        $stmt->execute([$id]);
        $current_status = $stmt->fetchColumn();
        
        // Determine the new status
        $new_status = ($current_status == 2) ? 1 : 2;

        // Update the database
        $update_stmt = $pdo->prepare("UPDATE slideshow SET is_active = ? WHERE id = ?");
        if ($update_stmt->execute([$new_status, $id])) {
            $response = ['status' => 'success', 'message' => 'Status updated.', 'new_status' => $new_status];
        } else {
            $response['message'] = 'Failed to update status.';
        }
    }
    
    // Delete Slide
    if ($_POST['action'] == 'delete_slide') {
        $id = $_POST['id'];
        // First, get the image filename to delete it from the server
        $stmt = $pdo->prepare("SELECT image FROM slideshow WHERE id = ?");
        $stmt->execute([$id]);
        $image_to_delete = $stmt->fetchColumn();

        // Second, delete the record from the database
        $delete_stmt = $pdo->prepare("DELETE FROM slideshow WHERE id = ?");
        if ($delete_stmt->execute([$id])) {
            // Third, if DB deletion was successful, delete the file
            if ($image_to_delete && file_exists("../uploads/" . $image_to_delete)) {
                unlink("../uploads/" . $image_to_delete);
            }
            $response = ['status' => 'success', 'message' => 'Slide deleted.'];
        } else {
            $response['message'] = 'Failed to delete slide from database.';
        }
    }

    echo json_encode($response);
    exit;
}

require_once 'common/sidebar.php';
$slides = $pdo->query("SELECT * FROM slideshow ORDER BY sort_order ASC, created_at DESC")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Manage Slideshow</h1>

<!-- Add New Slide Form -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4">Add New Slide</h2>
    <form id="add-slide-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_slide">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700">Slide Image (1280x720 recommended)</label>
                <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100" required>
            </div>
            <div>
                <label for="link_url" class="block text-sm font-medium text-gray-700">Link URL (optional)</label>
                <input type="text" name="link_url" id="link_url" placeholder="e.g., product_detail.php?id=123" class="mt-1 p-2 w-full border rounded-md">
            </div>
            <div class="self-end">
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i>Add Slide
                </button>
            </div>
        </div>
    </form>
</div>


<!-- Existing Slides -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($slides)): ?>
        <p class="text-gray-500 col-span-full text-center">No slides have been uploaded yet.</p>
    <?php else: ?>
        <?php foreach ($slides as $slide): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden" id="slide-card-<?= $slide['id'] ?>">
            <img src="../uploads/<?= htmlspecialchars($slide['image']) ?>" alt="Slide Image" class="w-full aspect-video object-cover">
            <div class="p-4">
                <p class="text-sm text-gray-600 truncate mb-2">
                    <strong>Link:</strong> <?= htmlspecialchars($slide['link_url'] ?: 'Not set') ?>
                </p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Status:
                        <span id="status-text-<?= $slide['id'] ?>" class="<?= $slide['is_active'] == 2 ? 'text-green-600' : 'text-gray-500' ?>">
                            <?= $slide['is_active'] == 2 ? 'Active' : 'Inactive' ?>
                        </span>
                    </span>
                    <button onclick='toggleStatus(<?= $slide["id"] ?>)' 
                            id="status-btn-<?= $slide['id'] ?>"
                            class="text-white text-xs font-semibold py-1 px-3 rounded-full <?= $slide['is_active'] == 2 ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600' ?>">
                        <?= $slide['is_active'] == 2 ? 'Deactivate' : 'Activate' ?>
                    </button>
                </div>
                <button onclick='deleteSlide(<?= $slide["id"] ?>)' class="w-full mt-4 bg-red-500 text-white font-bold py-2 rounded-lg hover:bg-red-600 text-sm">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.getElementById('add-slide-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    // Simple validation
    if (!formData.get('image').name) {
        alert('Please select an image file.');
        return;
    }

    try {
        const response = await fetch('slideshow.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.status === 'success') {
            alert(result.message);
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('An unexpected error occurred.');
    }
});

async function toggleStatus(id) {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('id', id);

    try {
        const response = await fetch('slideshow.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.status === 'success') {
            const statusText = document.getElementById(`status-text-${id}`);
            const statusBtn = document.getElementById(`status-btn-${id}`);
            
            if (result.new_status == 2) {
                statusText.textContent = 'Active';
                statusText.classList.remove('text-gray-500');
                statusText.classList.add('text-green-600');
                statusBtn.textContent = 'Deactivate';
                statusBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
                statusBtn.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
            } else {
                statusText.textContent = 'Inactive';
                statusText.classList.remove('text-green-600');
                statusText.classList.add('text-gray-500');
                statusBtn.textContent = 'Activate';
                statusBtn.classList.remove('bg-yellow-500', 'hover:bg-yellow-600');
                statusBtn.classList.add('bg-green-500', 'hover:bg-green-600');
            }
            // alert(result.message); // Optional: uncomment for feedback
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('An unexpected error occurred while toggling status.');
    }
}

async function deleteSlide(id) {
    if (!confirm('Are you sure you want to delete this slide?')) return;
    const formData = new FormData();
    formData.append('action', 'delete_slide');
    formData.append('id', id);

    try {
        const response = await fetch('slideshow.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.status === 'success') {
            document.getElementById(`slide-card-${id}`).remove();
            alert(result.message);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('An unexpected error occurred.');
    }
}
</script>

<?php require_once 'common/bottom.php'; ?>