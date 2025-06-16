<nav class="bg-white shadow sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
    <a href="../index.php" class="text-2xl font-bold text-blue-700">Campus Sync</a>
    <button class="md:hidden text-gray-600 focus:outline-none" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')">
      ☰
    </button>
    <div class="hidden md:flex space-x-6 items-center">
      <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition">Dashboard</a>
      <a href="profile.php" class="text-gray-700 hover:text-blue-600 transition">Profile</a>
      <a href="submit_routine.php" class="text-gray-700 hover:text-blue-600 transition">Submit Routine</a>
      <a href="group_view.php" class="text-gray-700 hover:text-blue-600 transition">Group View</a>
      <a href="../notifications/view_notifications.php" class="text-gray-700 hover:text-blue-600 transition">Notifications</a>
      <a href="../auth/logout.php" class="text-red-600 hover:text-red-800 font-semibold transition">Logout</a>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu" class="md:hidden hidden bg-white border-t border-gray-200 px-4 pb-4">
    <a href="dashboard.php" class="block py-2 text-gray-700 hover:text-blue-600">Dashboard</a>
    <a href="profile.php" class="block py-2 text-gray-700 hover:text-blue-600">Profile</a>
    <a href="submit_routine.php" class="block py-2 text-gray-700 hover:text-blue-600">Submit Routine</a>
    <a href="group_view.php" class="block py-2 text-gray-700 hover:text-blue-600">Group View</a>
    <a href="../notifications/view_notifications.php" class="block py-2 text-gray-700 hover:text-blue-600">Notifications</a>
    <a href="../auth/logout.php" class="block py-2 text-red-600 hover:text-red-800 font-semibold">Logout</a>
  </div>
</nav>