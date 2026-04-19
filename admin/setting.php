<?php
require_once 'common/header.php';

$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (password_verify($current_pass, $admin['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $_SESSION['admin_id']]);
            $message = 'Password updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'New passwords do not match.';
            $message_type = 'error';
        }
    } else {
        $message = 'Incorrect current password.';
        $message_type = 'error';
    }
}

require_once 'common/sidebar.php';
?>

<h1 class="text-2xl font-bold mb-6">Admin Settings</h1>
<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <form method="POST">
        <h2 class="text-xl font-semibold mb-4">Change Password</h2>
        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?= $message_type == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        <div class="mb-4">
            <label class="block text-sm font-medium">Current Password</label>
            <input type="password" name="current_password" class="mt-1 p-2 w-full border rounded-md" required>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium">New Password</label>
            <input type="password" name="new_password" class="mt-1 p-2 w-full border rounded-md" required>
        </div>
        <div class="mb-6">
            <label class="block text-sm font-medium">Confirm New Password</label>
            <input type="password" name="confirm_password" class="mt-1 p-2 w-full border rounded-md" required>
        </div>
        <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 rounded-lg">Update Password</button>
    </form>
</div>

<?php require_once 'common/bottom.php'; ?>