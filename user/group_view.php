<?php
require '../auth/auth_check.php'; // Redirects to login if not logged in
require '../config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch accepted friends: either direction
$stmt = $pdo->prepare("
    SELECT u.id, u.name 
    FROM users u 
    JOIN friends f 
      ON (
           (f.user_id = :uid AND f.friend_id = u.id) 
           OR 
           (f.friend_id = :uid AND f.user_id = u.id)
         )
    WHERE f.status = 'accepted'
    ORDER BY u.name
");
$stmt->execute([':uid' => $user_id]);
$friends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Match Free Time - Campus Sync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-gradient-to-tr from-blue-50 to-purple-100 min-h-screen flex flex-col">
  <?php include '../includes/navbar.php'; ?>

  <main class="flex-grow max-w-5xl mx-auto px-4 py-10">
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Match Free Time with Friends</h2>

    <?php if (empty($friends)): ?>
      <p class="text-gray-600">You have no friends yet. Add friends to compare free times.</p>
    <?php else: ?>
      <div class="bg-white p-6 rounded-xl shadow mb-8">
        <form id="matchFreeForm">
          <p class="font-semibold text-gray-700 mb-4">Select friends to match free time with:</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 max-h-60 overflow-y-auto mb-4">
            <?php foreach ($friends as $f): ?>
              <label class="flex items-center space-x-2">
                <input type="checkbox" name="friend_ids[]" value="<?= htmlspecialchars($f['id']) ?>" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-gray-700"><?= htmlspecialchars($f['name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <button type="submit" id="matchBtn" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
            Show Common Free Slots
          </button>
        </form>
      </div>

      <div id="resultsSection">
        <!-- Results or errors will be injected here -->
      </div>
    <?php endif; ?>
  </main>

  <?php include '../includes/footer.php'; ?>

  <script>
    (function(){
      const form = document.getElementById('matchFreeForm');
      const resultsSection = document.getElementById('resultsSection');
      const matchBtn = document.getElementById('matchBtn');

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        // Clear previous results/messages
        resultsSection.innerHTML = '';

        // Collect selected friend IDs
        const formData = new FormData(form);
        const friendIds = formData.getAll('friend_ids[]')
          .map(id => parseInt(id))
          .filter(id => !isNaN(id));
        if (friendIds.length === 0) {
          resultsSection.innerHTML = `
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-4">
              <p class="text-yellow-700">Please select at least one friend to compare free times.</p>
            </div>`;
          return;
        }

        // Show loading spinner and disable button
        resultsSection.innerHTML = `
          <div class="flex justify-center items-center py-10">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
          </div>`;
        matchBtn.disabled = true;

        try {
          const res = await fetch('../notifications/fetch_free_time.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ friend_ids: friendIds })
          });
          console.log('fetch_free_time response status:', res.status);
          const text = await res.text();
          let data;
          try {
            data = text ? JSON.parse(text) : {};
          } catch (parseErr) {
            console.error('Failed to parse JSON from fetch_free_time:', parseErr, 'Raw response:', text);
            throw new Error('Invalid JSON response from server.');
          }

          if (!res.ok) {
            const errMsg = data && data.error ? data.error : `Server returned status ${res.status}`;
            console.error('Error from fetch_free_time endpoint:', errMsg);
            resultsSection.innerHTML = `
              <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded mb-4">
                <p class="text-red-700">Error: ${errMsg}</p>
              </div>`;
            return;
          }

          if (data.error) {
            console.error('Error field in JSON:', data.error);
            resultsSection.innerHTML = `
              <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded mb-4">
                <p class="text-red-700">Error: ${data.error}</p>
              </div>`;
            return;
          }

          renderResults(data.free_slots || {});
        } catch (err) {
          console.error('Fetch exception:', err);
          resultsSection.innerHTML = `
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded mb-4">
              <p class="text-red-700">Failed to load free time data: ${err.message}</p>
            </div>`;
        } finally {
          matchBtn.disabled = false;
        }
      });

      // Helper: format "HH:MM:SS" → "HH:MM"
      // formatTime :
      function formatTime12(ts) {
        // ts: "HH:MM:SS"
        if (!ts) return '';
        const [hourStr, minStr] = ts.split(':');
        let hour = parseInt(hourStr, 10);
        const minute = minStr.padStart(2, '0');
        const suffix = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12;
        if (hour === 0) hour = 12;
        return `${hour}:${minute} ${suffix}`;
      }


      function renderResults(freeSlots) {
        // freeSlots: { "Monday": [ {start:"08:00:00",end:"09:30:00"}, ... ], ... }
        const days = Object.keys(freeSlots);
        if (days.length === 0) {
          resultsSection.innerHTML = `
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded mb-4">
              <p class="text-yellow-700">No routine data found for selected users.</p>
            </div>`;
          return;
        }

        // Build HTML: For each day, a card listing slots
        let html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">`;
        days.forEach(day => {
          const slots = freeSlots[day];
          html += `<div class="bg-white p-6 rounded-xl shadow hover:shadow-2xl transition transform hover:-translate-y-1">
            <h3 class="text-xl font-semibold text-gray-800 mb-3">${day}</h3>`;
          if (!slots || slots.length === 0) {
            html += `<p class="text-gray-500">No common free slots.</p>`;
          } else {
            html += `<ul class="space-y-1">`;
            slots.forEach(slot => {
              html += `<li class="text-gray-700">  
                  <span class="font-medium">${formatTime12(slot.start)}</span> &ndash; <span class="font-medium">${formatTime12(slot.start)}</span>
                </li>`;
            });
            html += `</ul>`;
          }
          html += `</div>`;
        });
        html += `</div>`;

        resultsSection.innerHTML = html;
      }
    })();
  </script>
</body>
</html>