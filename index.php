<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CampusSync</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" />
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-100 min-h-screen">

  <nav class="bg-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-blue-700">CampusSync</h1>
      <div class="hidden md:flex space-x-4">
        <a href="user/dashboard.php" class="text-gray-700 hover:text-blue-600 transition">Dashboard</a>
        <a href="user/profile.php" class="text-gray-700 hover:text-blue-600 transition">Profile</a>
        <a href="user/submit_routine.php" class="text-gray-700 hover:text-blue-600 transition">Submit Routine</a>
        <a href="user/group_view.php" class="text-gray-700 hover:text-blue-600 transition">Group View</a>
        <a href="notifications/view_notifications.php" class="text-gray-700 hover:text-blue-600 transition">Notifications</a>
        <a href="auth/logout.php" class="text-red-600 hover:text-red-800 font-semibold transition">Logout</a>
      </div>
    </div>
  </nav>

  <main class="max-w-5xl mx-auto mt-12 px-6">
    <div class="bg-white shadow-xl rounded-2xl p-10 text-center animate-fade-in">
      <h2 class="text-4xl font-extrabold text-gray-800 mb-4">Welcome to CampusSync</h2>
      <p class="text-gray-600 mb-8 text-lg">Plan smarter. Sync faster. Stay connected with your university group in real-time.</p>
      <div class="flex flex-col sm:flex-row justify-center gap-4">
        <a href="auth/login.php" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition shadow-md">Login</a>
        <a href="auth/register.php" class="bg-purple-600 text-white px-8 py-3 rounded-lg hover:bg-purple-700 transition shadow-md">Register</a>
      </div>
    </div>

    <section class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
      <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1">
        <h3 class="text-2xl font-semibold text-blue-600 mb-3">Real-time Routine Sync</h3>
        <p class="text-gray-500">View and manage class schedules with your group instantly and efficiently.</p>
      </div>
      <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1">
        <h3 class="text-2xl font-semibold text-purple-600 mb-3">Free Time Matching</h3>
        <p class="text-gray-500">Automatically see which friends are available at the same time.</p>
      </div>
      <div class="bg-white p-8 rounded-xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-1">
        <h3 class="text-2xl font-semibold text-green-600 mb-3">Analytics & Notifications</h3>
        <p class="text-gray-500">Track your study hours, receive alerts, and stay on top of your schedule.</p>
      </div>
    </section>
  </main>

  <footer class="text-center py-8 text-sm text-gray-400 mt-20">
    © 2025 CampusSync. All rights reserved.
  </footer>

  <script>
    // Basic fade-in animation using Tailwind
    document.body.classList.add('transition-opacity', 'duration-1000', 'opacity-0');
    window.onload = () => document.body.classList.remove('opacity-0');
  </script>

</body>
</html>