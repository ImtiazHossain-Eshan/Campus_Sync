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
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Campus Sync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <style>
    #notifDropdown {
      transition: opacity 0.2s ease;
      /* Initially hidden by Tailwind "hidden" */
    }
  </style>
</head>
<body class="bg-gradient-to-tr from-blue-50 to-purple-100 min-h-screen flex flex-col">

  <!-- Navbar -->
  <nav class="bg-white shadow sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <a href="../index.php" class="text-2xl font-bold text-blue-700">Campus Sync</a>
      <button class="md:hidden text-gray-600 focus:outline-none" aria-label="Toggle menu" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')">
        ☰
      </button>
      <div class="hidden md:flex space-x-6 items-center">
        <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition">Dashboard</a>
        <a href="profile.php" class="text-gray-700 hover:text-blue-600 transition">Profile</a>
        <a href="submit_routine.php" class="text-gray-700 hover:text-blue-600 transition">Submit Routine</a>
        <a href="friends.php" class="text-gray-700 hover:text-blue-600 transition">Friends</a>
        <a href="group_view.php" class="text-gray-700 hover:text-blue-600 transition">Group View</a>

        <!-- Notification Bell -->
        <div class="relative" id="notificationWrapper">
          <button id="notifBell" class="relative focus:outline-none" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-700 hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 00-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span id="notifCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full hidden"></span>
          </button>

          <!-- Dropdown -->
          <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded shadow-lg p-4 z-50 max-h-60 overflow-y-auto text-sm">
            <h3 class="font-semibold text-gray-800 mb-2">Notifications</h3>
            <div id="notifItems" class="space-y-2">
              <p class="text-gray-500">Loading...</p>
            </div>
            <div class="mt-3 text-right">
              <a href="../notifications/view_notifications.php" class="text-blue-600 hover:underline text-xs">See all</a>
            </div>
          </div>
        </div>

        <a href="../auth/logout.php" class="text-red-600 hover:text-red-800 font-semibold transition">Logout</a>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="md:hidden hidden bg-white border-t border-gray-200 px-4 pb-4">
      <a href="dashboard.php" class="block py-2 text-gray-700 hover:text-blue-600">Dashboard</a>
      <a href="profile.php" class="block py-2 text-gray-700 hover:text-blue-600">Profile</a>
      <a href="submit_routine.php" class="block py-2 text-gray-700 hover:text-blue-600">Submit Routine</a>
      <a href="friends.php" class="block py-2 text-gray-700 hover:text-blue-600">Friends</a>
      <a href="group_view.php" class="block py-2 text-gray-700 hover:text-blue-600">Group View</a>
      <a href="../notifications/view_notifications.php" class="block py-2 text-gray-700 hover:text-blue-600">Notifications</a>
      <a href="../auth/logout.php" class="block py-2 text-red-600 hover:text-red-800 font-semibold">Logout</a>
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

  <script>
    // Toggle mobile menu handled via onclick in button

    // Notification Dropdown toggle
    const notifBell = document.getElementById('notifBell');
    const notifDropdown = document.getElementById('notifDropdown');

    notifBell.addEventListener('click', () => {
      notifDropdown.classList.toggle('hidden');
      // Accessibility aria-expanded toggle
      const expanded = notifBell.getAttribute('aria-expanded') === 'true';
      notifBell.setAttribute('aria-expanded', !expanded);
    });

    // Close dropdown if click outside
    document.addEventListener('click', (e) => {
      if (!notifBell.contains(e.target) && !notifDropdown.contains(e.target)) {
        notifDropdown.classList.add('hidden');
        notifBell.setAttribute('aria-expanded', 'false');
      }
    });

    // Load notifications from backend
    async function loadNotifications() {
      try {
        const res = await fetch('../notifications/fetch_notifications.php');
        const data = await res.json();

        const notifCount = document.getElementById('notifCount');
        const notifItems = document.getElementById('notifItems');

        // Show unread badge or hide
        if (data.unread_count > 0) {
          notifCount.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
          notifCount.classList.remove('hidden');
        } else {
          notifCount.classList.add('hidden');
        }

        // Render notifications list
        notifItems.innerHTML = '';
        if (data.notifications.length === 0) {
          notifItems.innerHTML = '<p class="text-gray-500">No notifications.</p>';
        } else {
          data.notifications.forEach(n => {
            notifItems.innerHTML += `
              <div class="p-2 rounded ${!n.read_at ? 'bg-blue-50' : ''}">
                <p class="text-gray-800 font-semibold">${escapeHtml(n.sender_name)}</p>
                <p class="text-gray-700">${escapeHtml(n.message)}</p>
                <p class="text-xs text-gray-400">${new Date(n.created_at).toLocaleString()}</p>
              </div>
            `;
          });
        }
      } catch (err) {
        console.error('Failed to load notifications', err);
      }
    }

    function escapeHtml(text) {
      return text.replace(/[&<>"']/g, function(m) {
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        }[m];
      });
    }

    // Initial load and polling every 30 seconds
    loadNotifications();
    setInterval(loadNotifications, 30000);
  </script>

</body>
</html>