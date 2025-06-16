<footer class="bg-white mt-10 py-6 shadow-inner text-center text-sm text-gray-500">
  <div class="max-w-7xl mx-auto px-4">
    <p>&copy; <?= date('Y') ?> Campus Sync. All rights reserved.</p>
    <!--<p class="mt-2">Made with ❤️ for university collaboration.</p>-->
  </div>
</footer>

<!-- Optional: Smooth fade-in animation -->
<script>
  document.body.classList.add('transition-opacity', 'duration-700', 'opacity-0');
  window.addEventListener('load', () => {
    document.body.classList.remove('opacity-0');
  });
</script>
</body>
</html>