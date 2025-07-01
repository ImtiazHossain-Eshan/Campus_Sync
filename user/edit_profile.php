<?php
include '../auth/auth_check.php';
include '../config/db.php';

$error = "";
$success = "";

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $semester = htmlspecialchars(trim($_POST['semester']));
    $university = htmlspecialchars(trim($_POST['university']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $department = htmlspecialchars(trim($_POST['department']));
    $social = htmlspecialchars(trim($_POST['social']));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $profile_pic = $user['profile_pic'];

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
            $profile_pic = 'assets/uploads/' . $filename;
        }
    }

    if (!empty($password)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, semester = ?, university = ?, gender = ?, department = ?, social = ?, profile_pic = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $semester, $university, $gender, $department, $social, $profile_pic, $hashedPassword, $_SESSION['user_id']]);
            $success = "Profile and password updated successfully.";
        }
    }

    if (empty($password) && empty($error)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, semester = ?, university = ?, gender = ?, department = ?, social = ?, profile_pic = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $semester, $university, $gender, $department, $social, $profile_pic, $_SESSION['user_id']]);
        $success = "Profile updated successfully.";
    }

    if ($success) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<main class="max-w-3xl mx-auto px-4 py-10">
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-3xl font-extrabold text-blue-700 mb-6 text-center">✏️ Edit Your Profile</h2>

    <?php if (!empty($success)): ?>
      <div id="successAlert" class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded mb-6">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php elseif (!empty($error)): ?>
      <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded mb-6">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700">Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full px-4 py-2 border rounded-lg">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full px-4 py-2 border rounded-lg">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Semester</label>
        <input type="text" name="semester" value="<?= htmlspecialchars($user['semester']) ?>" class="w-full px-4 py-2 border rounded-lg">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">University</label>
        <input type="text" name="university" value="<?= htmlspecialchars($user['university']) ?>" class="w-full px-4 py-2 border rounded-lg">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Gender</label>
        <select name="gender" class="w-full px-4 py-2 border rounded-lg">
          <option value="Male" <?= $user['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= $user['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
          <option value="Other" <?= $user['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Department</label>
        <input type="text" name="department" value="<?= htmlspecialchars($user['department']) ?>" class="w-full px-4 py-2 border rounded-lg">
      </div>

      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700">Social Link</label>
        <input type="url" name="social" value="<?= htmlspecialchars($user['social']) ?>" class="w-full px-4 py-2 border rounded-lg">
      </div>

      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700">Profile Picture</label>
        <input type="file" name="profile_pic" accept="image/*" class="w-full text-sm">
        <?php if (!empty($user['profile_pic'])): ?>
          <img src="../<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile" class="w-24 h-24 rounded-full mt-3">
        <?php endif; ?>
      </div>

      <!-- Password -->
      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700">New Password</label>
        <div class="relative">
          <input type="password" name="password" id="password" class="w-full px-4 py-2 border rounded-lg pr-10" oninput="checkStrength(this.value)">
          <button type="button" onclick="toggleVisibility('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">👁️</button>
        </div>
        <div id="strengthText" class="text-sm mt-1 text-gray-500"></div>
      </div>

      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700">Confirm Password</label>
        <div class="relative">
          <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-2 border rounded-lg pr-10">
          <button type="button" onclick="toggleVisibility('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">👁️</button>
        </div>
      </div>

      <div class="col-span-2">
        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 font-semibold">Update Profile</button>
      </div>
    </form>
  </div>
</main>

<script>
function toggleVisibility(fieldId) {
  const input = document.getElementById(fieldId);
  input.type = input.type === "password" ? "text" : "password";
}

function checkStrength(password) {
  const strengthText = document.getElementById("strengthText");
  let strength = "Weak", color = "red";

  if (password.length >= 8 && /[A-Z]/.test(password) && /\d/.test(password)) {
    strength = "Strong"; color = "green";
  } else if (password.length >= 6) {
    strength = "Moderate"; color = "orange";
  }

  strengthText.textContent = `Strength: ${strength}`;
  strengthText.style.color = color;
}

setTimeout(() => {
  const alert = document.getElementById("successAlert");
  if (alert) alert.style.display = "none";
}, 3000);
</script>

<?php include '../includes/footer.php'; ?>