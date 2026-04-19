<?php
require_once 'common/header.php';
require_once 'common/sidebar.php';

$users = $pdo->query("SELECT id, name, email, phone, created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Registered Users</h1>

<div class="bg-white shadow-md rounded-lg overflow-x-auto">
    <table class="min-w-full leading-normal">
        <thead>
            <tr>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Name</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Email</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Phone</th>
                <th class="px-5 py-3 border-b-2 bg-gray-100 text-left text-xs font-semibold uppercase">Registered On</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td class="px-5 py-4 border-b bg-white text-sm font-semibold"><?= htmlspecialchars($user['name']) ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm"><?= htmlspecialchars($user['email']) ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm"><?= htmlspecialchars($user['phone']) ?></td>
                <td class="px-5 py-4 border-b bg-white text-sm"><?= date('d M, Y', strtotime($user['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'common/bottom.php'; ?>