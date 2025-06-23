<?php 
require '../auth/auth_check.php'; // Redirects to login if not logged in
require '../config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch accepted friends: either direction
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.name 
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
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Match Free Time - Campus Sync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <style>
    /* Custom scrollbar for friend list */
    .custom-scrollbar::-webkit-scrollbar {
      width: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background-color: #3b82f6; /* blue-500 */
      border-radius: 9999px;
      border: 2px solid transparent;
      background-clip: content-box;
    }
    /* Fade-in animation */
    @keyframes fadeInUp {
      0% {
        opacity: 0;
        transform: translateY(10px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .fade-in-up {
      animation: fadeInUp 0.4s ease forwards;
    }
  </style>
</head>
<body class="bg-gradient-to-tr from-blue-50 to-purple-100 min-h-screen flex flex-col text-gray-900 selection:bg-blue-300 selection:text-white">
  <?php include '../includes/navbar.php'; ?>

  <main class="flex-grow max-w-6xl mx-auto px-5 py-12 sm:px-8 lg:px-12">
    <h1 class="text-5xl font-extrabold tracking-tight mb-12 text-center text-gray-900 select-none drop-shadow-md">
      Match Free Time with Friends
    </h1>

    <?php if (empty($friends)): ?>
      <p class="max-w-xl mx-auto text-center text-lg text-gray-600 italic border-l-4 border-blue-400 pl-5 py-3 shadow-sm rounded-md bg-white/60 backdrop-blur-sm">
        You have no friends yet. Add friends to compare free times.
      </p>
    <?php else: ?>
      <section class="bg-white rounded-3xl shadow-xl p-10 mb-12 max-w-4xl mx-auto">
        <form id="matchFreeForm" class="space-y-8" aria-label="Match free time form">
          <fieldset>
            <legend class="text-xl font-semibold mb-6 text-gray-700">Select friends to match free time with:</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 max-h-72 overflow-y-auto custom-scrollbar px-3 py-2 rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-blue-500 transition">
              <?php foreach ($friends as $f): ?>
                <label class="flex items-center space-x-4 cursor-pointer select-none rounded-md p-3 hover:bg-blue-50 focus-within:bg-blue-100 transition">
                  <input type="checkbox" name="friend_ids[]" value="<?= htmlspecialchars($f['id']) ?>" 
                         class="h-6 w-6 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:ring-offset-0" />
                  <span class="text-gray-900 font-medium truncate max-w-full"><?= htmlspecialchars($f['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </fieldset>

          <button type="submit" id="matchBtn" 
                  class="group relative w-full sm:w-auto inline-flex items-center justify-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:ring-4 focus:ring-blue-400 focus:outline-none text-white font-semibold px-10 py-4 rounded-2xl shadow-lg transition-all duration-300 ease-in-out disabled:opacity-60 disabled:cursor-not-allowed">
            <span class="mr-3">Show Common Free Slots</span>
            <svg class="w-6 h-6 stroke-white stroke-2 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
              <path d="M9 18l6-6-6-6"></path>
            </svg>
          </button>
        </form>
      </section>

      <section id="resultsSection" aria-live="polite" class="max-w-5xl mx-auto min-h-[140px] px-4"></section>
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
        resultsSection.innerHTML = '';

        const formData = new FormData(form);
        const friendIds = formData.getAll('friend_ids[]')
          .map(id => parseInt(id))
          .filter(id => !isNaN(id));

        if (friendIds.length === 0) {
          resultsSection.innerHTML = `
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-5 rounded-lg mb-6 shadow-sm text-yellow-900 font-semibold text-center">
              Please select at least one friend to compare free times.
            </div>`;
          return;
        }

        // Show loading spinner and disable button
        resultsSection.innerHTML = `
          <div class="flex justify-center items-center py-12" aria-label="Loading...">
            <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" role="img" aria-hidden="true">
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

          const text = await res.text();
          let data;
          try {
            data = text ? JSON.parse(text) : {};
          } catch {
            throw new Error('Invalid response from server.');
          }

          if (!res.ok) {
            const msg = data && data.error ? data.error : `Server returned ${res.status}`;
            resultsSection.innerHTML = `
              <div class="bg-red-50 border-l-4 border-red-400 p-5 rounded-lg mb-6 shadow-sm text-red-800 font-semibold text-center">
                Error: ${msg}
              </div>`;
            return;
          }

          if (data.error) {
            resultsSection.innerHTML = `
              <div class="bg-red-50 border-l-4 border-red-400 p-5 rounded-lg mb-6 shadow-sm text-red-800 font-semibold text-center">
                Error: ${data.error}
              </div>`;
            return;
          }

          renderResults(data.free_slots || {});
        } catch (err) {
          resultsSection.innerHTML = `
            <div class="bg-red-50 border-l-4 border-red-400 p-5 rounded-lg mb-6 shadow-sm text-red-800 font-semibold text-center">
              Failed to load free time data: ${err.message}
            </div>`;
        } finally {
          matchBtn.disabled = false;
        }
      });

      function formatTime12(ts) {
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
        const days = Object.keys(freeSlots);
        if (days.length === 0) {
          resultsSection.innerHTML = `
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-5 rounded-lg mb-6 shadow-sm text-yellow-900 font-semibold text-center fade-in-up">
              No mutual free time found for selected users.
            </div>`;
          return;
        }

        let html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-12">`;
        days.forEach((day, i) => {
          const slots = freeSlots[day];
          html += `
            <article class="bg-white p-8 rounded-3xl shadow-xl hover:shadow-2xl transition-transform transform hover:-translate-y-2 fade-in-up" style="animation-delay: ${i * 100}ms">
              <h3 class="text-3xl font-extrabold text-gray-900 mb-6 select-text">${day}</h3>`;

          if (!slots || slots.length === 0) {
            html += `<p class="text-gray-500 italic select-text">No mutual free slots.</p>`;
          } else {
            html += `<ul class="space-y-3">`;
            slots.forEach(slot => {
              html += `
                <li class="text-gray-700 font-semibold select-text">
                  <time datetime="${slot.start}">${formatTime12(slot.start)}</time>
                  &ndash;
                  <time datetime="${slot.end}">${formatTime12(slot.end)}</time>
                </li>`;
            });
            html += `</ul>`;
          }
          html += `</article>`;
        });
        html += `</div>`;
        resultsSection.innerHTML = html;
      }
    })();
  </script>
</body>
</html>