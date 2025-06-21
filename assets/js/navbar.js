function loadNotifications() {
  fetch("/notifications/fetch_notifications.php")
    .then(res => res.json())
    .then(data => {
      const countSpan = document.getElementById("notifCount");
      const notifItems = document.getElementById("notifItems");
      
      // Update bell count
      if (data.unread_count > 0) {
        countSpan.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
        countSpan.classList.remove("hidden");
      } else {
        countSpan.classList.add("hidden");
      }

      // Fill dropdown
      notifItems.innerHTML = '';
      if (data.notifications.length === 0) {
        notifItems.innerHTML = '<p class="text-gray-500">No notifications.</p>';
        return;
      }

      data.notifications.forEach(n => {
        notifItems.innerHTML += `
          <div class="p-2 rounded ${!n.read_at ? 'bg-blue-50' : ''}">
            <p class="text-gray-800">${n.sender_name}: ${n.message}</p>
            <p class="text-xs text-gray-400">${new Date(n.created_at).toLocaleString()}</p>
          </div>`;
      });
    });
}

// Load once on start
loadNotifications();

// Poll every 30 seconds
setInterval(loadNotifications, 30000);