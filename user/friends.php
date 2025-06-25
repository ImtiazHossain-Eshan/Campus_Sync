<?php
require '../auth/auth_check.php';
require '../config/db.php';

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Friends - Campus Sync</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
  <?php include '../includes/navbar.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
      <div class="bg-gradient-to-r from-green-400 via-blue-500 to-purple-500 p-6 text-white">
        <h2 class="text-3xl font-bold">Friends</h2>
        <p class="text-sm mt-1 opacity-90">Manage your friend list and requests</p>
      </div>

      <div class="p-6 bg-gray-50">
        <!-- Search -->
        <div class="mb-10">
          <input type="text" id="searchUser" placeholder="Search by name or email"
            class="w-full px-4 py-2 rounded-full border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" />
          <div id="searchResults" class="mt-4 space-y-2"></div>
        </div>

        <!-- Accepted Friends -->
        <div class="mb-12">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Accepted Friends</h3>
          <div id="friendList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Dynamically filled -->
          </div>
        </div>

        <!-- Pending Requests -->
        <div class="mb-12">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Incoming Friend Requests</h3>
          <div id="pendingRequests" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <!-- Dynamically filled -->
          </div>
        </div>

        <!-- Sent Requests -->
        <div class="mb-12">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Sent Friend Requests 
            <span class="ml-2 inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">Pending</span>
          </h3>
          <div id="sentRequests" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <!-- Dynamically filled -->
          </div>
        </div>

      </div>
    </div>
  </main>

  <script src="../assets/js/friends.js" defer></script>
</body>
</html>