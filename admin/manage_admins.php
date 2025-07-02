<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Admins | CampusSync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 min-h-screen px-6 py-10">
  <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-xl p-8">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-purple-700">Manage Admins</h2>
      <a href="add_admin.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold transition">
        ➕ Add Admin
      </a>
    </div>

    <?php if (count($admins) === 0): ?>
      <p class="text-gray-600">No admins found.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
          <thead class="bg-purple-100 text-purple-800">
            <tr>
              <th class="text-left px-4 py-2 border-b">Name</th>
              <th class="text-left px-4 py-2 border-b">Email</th>
              <th class="text-left px-4 py-2 border-b">Created At</th>
              <th class="text-center px-4 py-2 border-b">Actions</th>
            </tr>
          </thead>
          <tbody class="text-gray-700">
            <?php foreach ($admins as $admin): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3"><?= htmlspecialchars($admin['name']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($admin['email']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($admin['created_at']) ?></td>
                <td class="px-4 py-3 text-center">
                  <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                    <form method="POST" action="delete_admin.php" onsubmit="return confirm('Are you sure you want to delete this admin?');" class="inline">
                      <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                      <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                        🗑 Delete
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-sm text-gray-500 italic">You</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="mt-6 text-center">
      <a href="admin_panel.php" class="text-blue-600 text-sm hover:underline">← Back to Admin Panel</a>
    </div>
  </div>
</body>
</html>