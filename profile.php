<?php

require_once 'common/config.php';

// if the visitor isn't logged in send them straight to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit(); 
}
require_once 'common/header.php';
require_once 'common/sidebar.php';



// --- AJAX HANDLER FOR UPDATING PROFILE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $user_id = $_SESSION['user_id'];

    // --- THIS IS THE FIX ---
    // 1. Check if the new email or phone number is already used by ANOTHER user.
    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ?");
    $check_stmt->execute([$email, $phone, $user_id]);
    
    if ($check_stmt->rowCount() > 0) {
        // If a row is found, it means the email/phone is taken.
        $response['message'] = 'Email or phone number is already in use by another account.';
    } else {
        // 2. If no conflict, proceed with the update.
        $update_stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        if ($update_stmt->execute([$name, $email, $phone, $address, $user_id])) {
            $response = ['status' => 'success', 'message' => 'Profile updated successfully!'];
            // Also update the session name if it changed
            $_SESSION['user_name'] = $name;
        } else {
            $response['message'] = 'Failed to update profile due to a database error.';
        }
    }
    
    echo json_encode($response);
    exit;
}

// --- HTML PAGE DISPLAY ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<main class="p-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">My Profile</h1>
    <form id="profile-form" class="bg-white p-6 rounded-lg shadow-md space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
            <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2" required>
        </div>
        <div>
            <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
            <textarea name="address" id="address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm p-2"><?= htmlspecialchars($user['address']) ?></textarea>
        </div>
        <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-indigo-700">Save Changes</button>
    </form>
    
    <div class="mt-6">
        <a href="logout.php" class="block w-full text-center bg-red-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-red-600">Logout</a>
    </div>
</main>

<script>
document.getElementById('profile-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    showLoader();
    const formData = new FormData(this);
    
    try {
        const response = await fetch('profile.php', { method: 'POST', body: formData });
        const result = await response.json();
        showAlert(result.message, result.status === 'error');

        // Optional: If successful, you might want to reload or update UI elements
        if(result.status === 'success') {
            // Nothing to do, showAlert is enough. Page data is now current.
        }

    } catch (err) {
        // This catch block is what shows "An error occurred"
        showAlert('An unexpected error occurred. Please check the console.', true);
        console.error('Fetch Error:', err);
    } finally {
        hideLoader();
    }
});
</script>

<?php require_once 'common/bottom.php'; ?>```

<!-- After updating the file, the profile page will now correctly save user data and give you a meaningful error message if you try to use an email or phone number that's already registered. -->