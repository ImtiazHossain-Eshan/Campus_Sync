<?php
session_start();
require '../config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $phone = htmlspecialchars(trim($_POST['phone']));
    $semester = htmlspecialchars(trim($_POST['semester']));
    $university = htmlspecialchars(trim($_POST['university']));
    $gender = htmlspecialchars(trim($_POST['gender']));
    $department = htmlspecialchars(trim($_POST['department']));
    $profile_pic = null;

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
            $profile_pic = 'assets/uploads/' . $filename;
        }
    }

    if (!$email) {
        $error = "Invalid email address.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, social, semester, university, gender, department, profile_pic) VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $hashedPassword, $phone, $semester, $university, $gender, $department, $profile_pic]);
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "This email is already registered. Please use a different email or login.";
            } else {
                $error = "An error occurred. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - CampusSync</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    input[type="file"] { display: none; }
    .strength-bar { height: 6px; border-radius: 5px; margin-top: 4px; }
  </style>
</head>
<body class="bg-gradient-to-br from-purple-100 to-blue-50 min-h-screen flex items-center justify-center">

<div class="w-full max-w-2xl bg-white p-8 rounded-2xl shadow-xl">
  <h2 class="text-3xl font-bold text-center text-blue-700 mb-6">Create Your Account</h2>

  <?php if (!empty($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="registerForm">
    <div class="col-span-2">
      <label class="block text-sm font-semibold text-gray-700">Full Name</label>
      <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700">Email</label>
      <input type="email" name="email" id="email" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
      <p id="emailStatus" class="text-sm mt-1"></p>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700">Password</label>
      <div class="relative">
        <input type="password" name="password" id="password" required minlength="6" class="w-full px-4 py-2 border rounded-lg pr-10 focus:ring-2 focus:ring-blue-400">
        <button type="button" onclick="togglePassword()" class="absolute right-3 top-2.5 text-gray-500">
          👁️
        </button>
      </div>
      <div id="strengthBar" class="w-full strength-bar bg-gray-300 mt-1"></div>
      <p id="strengthText" class="text-xs text-gray-600 mt-1"></p>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700">Phone</label>
      <input type="text" name="phone" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700">Semester</label>
      <input type="text" name="semester" placeholder="e.g. Fall 2025" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700">University</label>
      <input type="text" name="university" value="BRAC University" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700">Gender</label>
      <select name="gender" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
        <option value="">Select</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700">Department</label>
      <input type="text" name="department" placeholder="e.g. CSE, BBA" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
    </div>

    <div class="col-span-2">
      <label class="block text-sm font-semibold text-gray-700 mb-1">Profile Picture</label>
      <input type="file" name="profile_pic" id="profile_pic" accept="image/*" />
      <label for="profile_pic" class="cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
        Upload Image
      </label>
      <div id="previewContainer" class="mt-3">
        <img id="previewImage" src="#" alt="Profile Preview" class="max-w-xs rounded-lg shadow-md hidden" />
      </div>
    </div>

    <div class="col-span-2">
      <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition font-semibold">
        Register
      </button>
    </div>
  </form>

  <p class="mt-6 text-sm text-center text-gray-500">
    Already have an account?
    <a href="login.php" class="text-blue-600 hover:underline font-medium">Login here</a>
  </p>
</div>

<script>
  function togglePassword() {
    const input = document.getElementById("password");
    input.type = input.type === "password" ? "text" : "password";
  }

  document.getElementById('profile_pic').addEventListener('change', function() {
    const file = this.files[0];
    const previewImage = document.getElementById('previewImage');
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        previewImage.src = e.target.result;
        previewImage.classList.remove('hidden');
      };
      reader.readAsDataURL(file);
    } else {
      previewImage.src = '#';
      previewImage.classList.add('hidden');
    }
  });

  // ✅ Password strength
  const passwordInput = document.getElementById('password');
  const strengthBar = document.getElementById('strengthBar');
  const strengthText = document.getElementById('strengthText');

  passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    let score = 0;
    if (val.length >= 6) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    let colors = ["bg-red-500", "bg-yellow-400", "bg-green-500", "bg-green-600"];
    let levels = ["Weak", "Fair", "Good", "Strong"];

    strengthBar.className = `w-full strength-bar ${colors[score-1] || "bg-gray-300"}`;
    strengthText.innerText = score ? `Password strength: ${levels[score-1]}` : "";
  });

  // ✅ AJAX email check
  const emailInput = document.getElementById("email");
  const emailStatus = document.getElementById("emailStatus");

  emailInput.addEventListener("blur", () => {
    const email = emailInput.value.trim();
    if (!email) return;

    fetch("check_email.php?email=" + encodeURIComponent(email))
      .then(res => res.text())
      .then(data => {
        if (data === "taken") {
          emailStatus.innerText = "❌ Email already in use";
          emailStatus.className = "text-sm text-red-500";
        } else {
          emailStatus.innerText = "✅ Email is available";
          emailStatus.className = "text-sm text-green-600";
        }
      });
  });
</script>
</body>
</html>