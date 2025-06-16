<?php
include '../auth/auth_check.php';
include '../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<main class="max-w-4xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-10">
    <h2 class="text-3xl font-extrabold text-blue-700 mb-6 text-center">👤 Your Profile</h2>

    <div class="grid gap-6 sm:grid-cols-2 text-gray-700">
      <div class="bg-blue-50 p-5 rounded-lg border border-blue-100">
        <p class="text-sm text-gray-500 mb-1">Name</p>
        <p class="text-xl font-semibold"><?= htmlspecialchars($user['name']) ?></p>
      </div>

      <div class="bg-blue-50 p-5 rounded-lg border border-blue-100">
        <p class="text-sm text-gray-500 mb-1">Email</p>
        <p class="text-xl font-semibold"><?= htmlspecialchars($user['email']) ?></p>
      </div>

      <?php if (!empty($user['phone'])): ?>
      <div class="bg-blue-50 p-5 rounded-lg border border-blue-100">
        <p class="text-sm text-gray-500 mb-1">Phone</p>
        <p class="text-xl font-semibold"><?= htmlspecialchars($user['phone']) ?></p>
      </div>
      <?php endif; ?>

      <?php if (!empty($user['social'])): ?>
      <div class="bg-blue-50 p-5 rounded-lg border border-blue-100">
        <p class="text-sm text-gray-500 mb-1">Social Link</p>
        <a href="<?= htmlspecialchars($user['social']) ?>" class="text-xl font-semibold text-blue-600 hover:underline" target="_blank"><?= htmlspecialchars($user['social']) ?></a>
      </div>
      <?php endif; ?>
    </div>

    <div class="text-center mt-10">
      <a href="edit_profile.php" class="inline-block bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition shadow-lg font-semibold">
        ✏️ Edit Profile
      </a>
    </div>
  </div>
</main>

<?php include '../includes/footer.php'; ?>