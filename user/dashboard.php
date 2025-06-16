<?php

require '../config/db.php';
require '../auth/auth_check.php'; // Redirects to login if not logged in

// Fetch user name
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$name = $user ? $user['name'] : "User";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard - Campus Sync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-tr from-blue-50 to-purple-100 min-h-screen flex flex-col">

  <!-- Navbar -->
  <nav class="bg-white shadow sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-blue-700">Campus Sync</h1>
      <div class="hidden md:flex space-x-6">
        <a href="profile.php" class="text-gray-700 hover:text-blue-600 transition">Profile</a>
        <a href="submit_routine.php" class="text-gray-700 hover:text-blue-600 transition">Submit Routine</a>
        <a href="group_view.php" class="text-gray-700 hover:text-blue-600 transition">Group View</a>
        <a href="../notifications/view_notifications.php" class="text-gray-700 hover:text-blue-600 transition">Notifications</a>
        <a href="../auth/logout.php" class="text-red-600 hover:text-red-800 font-semibold transition">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Dashboard Content -->
  <main class="flex-grow max-w-5xl mx-auto px-4 py-10">
    <h2 class="text-3xl font-bold text-gray-800 mb-6 animate-fade-in">Welcome, <?= htmlspecialchars($name) ?> 👋</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-10">
      <div class="bg-white p-6 rounded-xl shadow hover:shadow-2xl transition transform hover:-translate-y-1">
        <h3 class="text-xl font-semibold text-blue-600 mb-2">Today</h3>
        <p class="text-gray-500"><?= date("l, F j") ?></p>
      </div>

      <div class="bg-white p-6 rounded-xl shadow hover:shadow-2xl transition transform hover:-translate-y-1">
        <h3 class="text-xl font-semibold text-purple-600 mb-2">Upcoming Routines</h3>
        <p class="text-gray-500">Check your routine overview in <a href="submit_routine.php" class="text-blue-600 hover:underline">My Routine</a>.</p>
      </div>

      <div class="bg-white p-6 rounded-xl shadow hover:shadow-2xl transition transform hover:-translate-y-1">
        <h3 class="text-xl font-semibold text-green-600 mb-2">Who's Free Now?</h3>
        <p class="text-gray-500">Visit <a href="group_view.php" class="text-blue-600 hover:underline">Group View</a> to find free friends.</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <a href="submit_routine.php" class="bg-blue-600 text-white p-4 rounded-lg text-center font-semibold hover:bg-blue-700 transition shadow-lg">Submit / Update Routine</a>
      <a href="group_view.php" class="bg-purple-600 text-white p-4 rounded-lg text-center font-semibold hover:bg-purple-700 transition shadow-lg">View Group Availability</a>
      <a href="../notifications/view_notifications.php" class="bg-yellow-500 text-white p-4 rounded-lg text-center font-semibold hover:bg-yellow-600 transition shadow-lg">View Notifications</a>
      <a href="profile.php" class="bg-gray-800 text-white p-4 rounded-lg text-center font-semibold hover:bg-gray-900 transition shadow-lg">Edit Profile</a>
    </div>
  </main>

  <!-- Footer -->
  <footer class="text-center py-6 text-sm text-gray-400">
    © 2025 Campus Sync. All rights reserved.
  </footer>

</body>
</html>