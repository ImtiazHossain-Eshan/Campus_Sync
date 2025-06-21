<?php
require '../config/db.php';
require '../auth/auth_check.php';

// Fetch notifications for the logged-in user, including sender info and profile pic
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT n.*, u.name AS sender_name, u.profile_pic
    FROM notifications n
    LEFT JOIN users u ON n.sender_id = u.id
    WHERE n.receiver_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Notifications - Campus Sync</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

  <!-- Include navbar -->
  <?php include '../includes/navbar.php'; ?>

  <main class="flex-grow max-w-4xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Notifications</h2>
      <form method="POST" action="mark_all_read.php">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Mark All as Read</button>
      </form>
    </div>

    <?php if (count($notifications) === 0): ?>
      <p class="text-center text-gray-500">No notifications yet.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($notifications as $n): ?>
          <div class="p-4 bg-white rounded-lg shadow flex items-center space-x-4 <?= $n['read_at'] === null ? 'border-l-4 border-blue-500' : '' ?>">
            <img
              src="<?= htmlspecialchars($n['profile_pic'] ? '../' . $n['profile_pic'] : '../assets/img/default-profile.png') ?>"
              alt="<?= htmlspecialchars($n['sender_name']) ?>'s profile picture"
              class="w-12 h-12 rounded-full object-cover flex-shrink-0"
              onerror="this.onerror=null;this.src='../assets/img/default-profile.png';"
            />
            <div class="flex-1">
              <p class="text-gray-800 font-semibold"><?= htmlspecialchars($n['sender_name']) ?>:</p>
              <p class="text-gray-600"><?= htmlspecialchars($n['message']) ?></p>
              <p class="text-sm text-gray-400 mt-1"><?= date("M d, Y h:i A", strtotime($n['created_at'])) ?></p>
            </div>
            <?php if ($n['read_at'] === null): ?>
              <form method="POST" action="mark_read.php" class="ml-4 flex-shrink-0">
                <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                <button class="text-blue-600 hover:underline text-sm font-semibold">Mark as Read</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <footer class="text-center py-6 text-sm text-gray-400 mt-10">
    © 2025 Campus Sync. All rights reserved.
  </footer>

</body>
</html>