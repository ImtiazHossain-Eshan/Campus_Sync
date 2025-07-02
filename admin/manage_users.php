<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch only non-admin users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users | CampusSync Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
  <h1 class="text-3xl font-bold text-purple-700 mb-6">Manage Users</h1>
  <a href="admin_panel.php" class="mb-4 inline-block text-sm text-blue-600 hover:underline">← Back to Dashboard</a>

  <div class="overflow-x-auto bg-white rounded-lg shadow">
    <table class="min-w-full table-auto">
      <thead class="bg-purple-600 text-white">
        <tr>
          <th class="px-4 py-2">ID</th>
          <th class="px-4 py-2">Name</th>
          <th class="px-4 py-2">Email</th>
          <th class="px-4 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="px-4 py-2"><?= $user['id'] ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($user['name']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($user['email']) ?></td>
            <td class="px-4 py-2 space-x-2">
              <a href="view_user.php?id=<?= $user['id'] ?>" class="text-blue-600 hover:underline">View</a>
              <form action="delete_user.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <button type="submit" class="text-red-600 hover:underline">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>