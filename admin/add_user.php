<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $semester = trim($_POST['semester']);
    $gender = $_POST['gender'] ?? null;
    $department = trim($_POST['department']);
    $social = trim($_POST['social']);
    $university = trim($_POST['university']) ?: "BRAC University";

    if (!$name || !$email || !$password || !$confirm || !$phone || !$semester || !$gender || !$department) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $error = "A user with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, semester, gender, department, social, university) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $phone, $semester, $gender, $department, $social, $university]);
            $success = "User added successfully.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add User | CampusSync Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
  <div class="bg-white shadow-lg rounded-xl w-full max-w-2xl p-8">
    <h2 class="text-2xl font-bold text-purple-700 mb-6 text-center">Add New User</h2>

    <?php if ($success): ?>
      <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Name*</label>
        <input type="text" name="name" required class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Email*</label>
        <input type="email" name="email" required class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Phone*</label>
        <input type="text" name="phone" required class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Semester*</label>
        <input type="text" name="semester" required class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Gender*</label>
        <select name="gender" required class="w-full px-4 py-2 border rounded-md">
          <option value="" disabled selected>Select</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Department*</label>
        <input type="text" name="department" required class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Social Link</label>
        <input type="url" name="social" class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">University</label>
        <input type="text" name="university" placeholder="BRAC University" class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Password*</label>
        <input type="password" name="password" required class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Confirm Password*</label>
        <input type="password" name="confirm_password" required class="w-full px-4 py-2 border rounded-md" />
      </div>

      <div class="md:col-span-2 text-center">
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-md transition">
          ➕ Add User
        </button>
      </div>
    </form>

    <div class="mt-6 text-center">
      <a href="admin_panel.php" class="text-blue-600 text-sm hover:underline">← Back to Dashboard</a>
    </div>
  </div>
</body>
</html>