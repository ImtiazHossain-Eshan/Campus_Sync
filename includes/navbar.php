<nav class="bg-white shadow sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
    <a href="/Campus_Sync/index.php" class="text-2xl font-bold text-blue-700">Campus Sync</a>
    
    <!-- Mobile menu button -->
    <button id="mobileMenuBtn" aria-label="Toggle menu" class="md:hidden text-gray-600 focus:outline-none">
      <!-- Hamburger icon -->
      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>

    <!-- Desktop menu -->
    <div id="desktopMenu" class="hidden md:flex space-x-6 items-center">
      <a href="/Campus_Sync/user/dashboard.php" class="text-gray-700 hover:text-blue-600 transition">Dashboard</a>
      <a href="/Campus_Sync/user/profile.php" class="text-gray-700 hover:text-blue-600 transition">Profile</a>
      <a href="/Campus_Sync/user/submit_routine.php" class="text-gray-700 hover:text-blue-600 transition">Submit Routine</a>
      <a href="/Campus_Sync/user/friends.php" class="text-gray-700 hover:text-blue-600 transition">Friends</a>
      <a href="/Campus_Sync/user/group_view.php" class="text-gray-700 hover:text-blue-600 transition">Group View</a>

      <!-- Notification Bell -->
      <div class="relative group">
        <button id="notifBell" class="relative focus:outline-none" aria-label="Notifications">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-700 hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 00-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
          </svg>
          <span id="notifCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full hidden"></span>
        </button>

        <!-- Notification dropdown -->
        <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded shadow-lg p-4 z-50 group-focus-within:block">
          <h3 class="font-semibold text-gray-800 mb-2">Notifications</h3>
          <div id="notifItems" class="space-y-2 max-h-60 overflow-y-auto text-sm">
            <p class="text-gray-500">Loading...</p>
          </div>
          <div class="mt-3 text-right">
            <a href="/Campus_Sync/notifications/view_notifications.php" class="text-blue-600 hover:underline text-xs">See all</a>
          </div>
        </div>
      </div>

      <a href="/Campus_Sync/auth/logout.php" class="text-red-600 hover:text-red-800 font-semibold transition">Logout</a>
    </div>
  </div>

  <!-- Mobile menu -->
  <div id="mobileMenu" class="md:hidden hidden bg-white border-t border-gray-200 px-4 pb-4 space-y-2">
    <a href="/Campus_Sync/user/dashboard.php" class="block py-2 text-gray-700 hover:text-blue-600">Dashboard</a>
    <a href="/Campus_Sync/user/profile.php" class="block py-2 text-gray-700 hover:text-blue-600">Profile</a>
    <a href="/Campus_Sync/user/submit_routine.php" class="block py-2 text-gray-700 hover:text-blue-600">Submit Routine</a>
    <a href="/Campus_Sync/user/friends.php" class="block py-2 text-gray-700 hover:text-blue-600">Friends</a>
    <a href="/Campus_Sync/user/group_view.php" class="block py-2 text-gray-700 hover:text-blue-600">Group View</a>
    <a href="/Campus_Sync/notifications/view_notifications.php" class="block py-2 text-gray-700 hover:text-blue-600">Notifications</a>
    <a href="/Campus_Sync/auth/logout.php" class="block py-2 text-red-600 hover:text-red-800 font-semibold">Logout</a>
  </div>

  <script>
    // Toggle mobile menu visibility
    document.getElementById('mobileMenuBtn').addEventListener('click', () => {
      document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    // Fetch notifications and update badge and dropdown items
    async function fetchNotifications() {
      try {
        const res = await fetch("/Campus_Sync/notifications/fetch_notifications.php");
        const data = await res.json();

        const notifCountElem = document.getElementById("notifCount");
        const notifItems = document.getElementById("notifItems");

        // Update badge
        if (data.unread_count > 0) {
          notifCountElem.textContent = data.unread_count > 9 ? "9+" : data.unread_count;
          notifCountElem.classList.remove("hidden");
        } else {
          notifCountElem.classList.add("hidden");
        }

        // Update dropdown list
        notifItems.innerHTML = "";
        if (data.notifications && data.notifications.length > 0) {
          data.notifications.forEach(n => {
            const item = document.createElement("div");
            item.className = "p-2 rounded hover:bg-blue-50 cursor-pointer truncate";
            item.textContent = n.message;
            notifItems.appendChild(item);
          });
        } else {
          notifItems.innerHTML = '<p class="text-gray-500">No new notifications.</p>';
        }
      } catch (e) {
        console.error("Failed to fetch notifications:", e);
      }
    }

    document.addEventListener("DOMContentLoaded", () => {
      fetchNotifications();
      setInterval(fetchNotifications, 60000); // Refresh every 60s
    });
  </script>
</nav>