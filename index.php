<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Campus Sync</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-purple-100 min-h-screen transition-opacity duration-1000 opacity-0">

  <!-- Navbar -->
  <nav class="bg-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-blue-700">Campus Sync</h1>
      <button class="md:hidden text-gray-600 focus:outline-none" onclick="document.getElementById('mobileMenu').classList.toggle('hidden')">
        ☰
      </button>
      <div class="hidden md:flex space-x-6 items-center">
        <a href="auth/login.php" class="text-gray-700 hover:text-blue-600 transition">Login</a>
        <a href="auth/register.php" class="text-gray-700 hover:text-blue-600 transition">Register</a>
        <a href="about.php" class="text-gray-700 hover:text-blue-600 transition">About</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <main class="max-w-5xl mx-auto mt-12 px-6">
    <div class="bg-white shadow-xl rounded-2xl p-10 text-center animate-fade-in">
      <h2 class="text-4xl font-extrabold text-gray-800 mb-4">Welcome to CampusSync</h2>
      <p class="text-gray-600 mb-8 text-lg">Plan smarter. Sync faster. Stay connected with your university group in real-time.</p>
      <div class="flex flex-col sm:flex-row justify-center gap-4">
        <a href="auth/login.php" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition shadow-md">Login</a>
        <a href="auth/register.php" class="bg-purple-600 text-white px-8 py-3 rounded-lg hover:bg-purple-700 transition shadow-md">Register</a>
      </div>
    </div>

    <!-- Features -->
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

  <!-- Footer -->
  <footer class="text-center py-8 text-sm text-gray-400 mt-20">
    © 2025 Campus Sync. All rights reserved.
  </footer>

  <!-- JS Fade-in -->
  <script>
    window.onload = () => document.body.classList.remove('opacity-0');
  </script>
</body>
</html>