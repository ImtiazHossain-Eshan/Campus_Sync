<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Stats
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$routineCount = $pdo->query("SELECT COUNT(*) FROM routines")->fetchColumn();
$adminCount = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$groupCount = $pdo->query("SELECT COUNT(*) FROM groups")->fetchColumn();
$recentUsers = $pdo->query("SELECT name, email, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

// Weekly activity
$barData = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $barData['labels'][] = date('D', strtotime($day));

    $usersOnDay = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
    $usersOnDay->execute([$day]);
    $barData['users'][] = $usersOnDay->fetchColumn();

    $routinesOnDay = $pdo->prepare("SELECT COUNT(*) FROM routines WHERE DATE(created_at) = ?");
    $routinesOnDay->execute([$day]);
    $barData['routines'][] = $routinesOnDay->fetchColumn();
}

// Gender distribution
$maleCount = $pdo->query("SELECT COUNT(*) FROM users WHERE gender = 'Male'")->fetchColumn();
$femaleCount = $pdo->query("SELECT COUNT(*) FROM users WHERE gender = 'Female'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard | CampusSync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
  <div class="p-6 bg-white shadow flex flex-col md:flex-row justify-between md:items-center gap-4">
    <div>
      <h1 class="text-2xl font-bold text-purple-700">Campus Sync Dashboard, <?= htmlspecialchars($admin_name) ?></h1>
    </div>
    <div class="flex gap-3">
      <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm">🔒 Logout</a>
      <a href="manage_users.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm">👥 Manage Users</a>
      <a href="manage_admins.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">🛠 Manage Admins</a>
    </div>
  </div>

  <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto mt-6">
    <div class="bg-white rounded-xl shadow p-5 text-center">
      <p class="text-sm text-gray-500">Total Users</p>
      <p class="text-3xl font-bold text-purple-700 mt-1"><?= $userCount ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5 text-center">
      <p class="text-sm text-gray-500">Routines Submitted</p>
      <p class="text-3xl font-bold text-purple-700 mt-1"><?= $routineCount ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5 text-center">
      <p class="text-sm text-gray-500">Admins</p>
      <p class="text-3xl font-bold text-purple-700 mt-1"><?= $adminCount ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5 text-center">
      <p class="text-sm text-gray-500">Groups Created</p>
      <p class="text-3xl font-bold text-purple-700 mt-1"><?= $groupCount ?></p>
    </div>
  </div>

  <div class="max-w-6xl mx-auto mt-8 px-6">
    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-lg font-semibold mb-4">🕒 Recent Registrations</h2>
      <ul class="divide-y divide-gray-200">
        <?php foreach ($recentUsers as $user): ?>
          <li class="py-3 flex justify-between items-start">
            <div>
              <p class="font-medium text-gray-800"><?= htmlspecialchars($user['name']) ?></p>
              <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <p class="text-sm text-gray-400"><?= date("M d, Y", strtotime($user['created_at'] ?? 'now')) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="max-w-6xl mx-auto mt-10 grid md:grid-cols-2 gap-6 px-6">
    <div class="bg-white p-6 rounded-xl shadow">
      <h3 class="text-lg font-semibold mb-4">📈 Weekly Activity</h3>
      <canvas id="activityChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-xl shadow">
      <h3 class="text-lg font-semibold mb-4">👥 Gender Distribution</h3>
      <canvas id="genderChart"></canvas>
    </div>
  </div>

  <div class="fixed bottom-6 right-6 flex flex-col gap-3">
    <a href="add_admin.php" class="bg-green-500 hover:bg-green-600 text-white px-5 py-3 rounded-full shadow-lg text-sm font-semibold">
      ➕ Add Admin
    </a>
    <a href="add_user.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-3 rounded-full shadow-lg text-sm font-semibold">
      ➕ Add User
    </a>
  </div>

  <script>
    new Chart(document.getElementById('activityChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: <?= json_encode($barData['labels']) ?>,
        datasets: [
          {
            label: 'Users',
            data: <?= json_encode($barData['users']) ?>,
            backgroundColor: '#6D28D9'
          },
          {
            label: 'Routines',
            data: <?= json_encode($barData['routines']) ?>,
            backgroundColor: '#9333EA'
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'User & Routine Activity' }
        }
      }
    });

    new Chart(document.getElementById('genderChart').getContext('2d'), {
      type: 'pie',
      data: {
        labels: ['Male', 'Female'],
        datasets: [{
          data: [<?= $maleCount ?>, <?= $femaleCount ?>],
          backgroundColor: ['#3B82F6', '#EC4899']
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          title: { display: true, text: 'Gender Breakdown' }
        }
      }
    });
  </script>
</body>
</html>