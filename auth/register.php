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

    // Handle file upload
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
            // Check if duplicate entry error (email already exists)
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "This email is already registered. Please use a different email or login.";
            } else {
                $error = "An error occurred. Please try again later.";
                // For debugging only: uncomment below line to see error details
                // $error .= " Error: " . $e->getMessage();
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
    /* Hide default file input */
    input[type="file"] {
      display: none;
    }
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
        <input type="text" name="name" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Email</label>
        <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Password</label>
        <input type="password" name="password" required minlength="6" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Phone</label>
        <input type="text" name="phone" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Semester</label>
        <input type="text" name="semester" placeholder="e.g. Fall 2025" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">University</label>
        <input type="text" name="university" value="BRAC University" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Gender</label>
        <select name="gender" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
          <option value="">Select</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700">Department</label>
        <input type="text" name="department" placeholder="e.g. CSE, BBA" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <!-- Modern Profile Picture Upload -->
      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Profile Picture</label>
        
        <!-- Hidden file input -->
        <input type="file" name="profile_pic" id="profile_pic" accept="image/*" />

        <!-- Custom upload button -->
        <label for="profile_pic" 
          class="cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold select-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 104 4H3v-4zm9-9h4l3 3v4a4 4 0 11-4-4h-3z" />
          </svg>
          Choose Image
        </label>

        <!-- Preview container -->
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
    const inputFile = document.getElementById('profile_pic');
    const previewImage = document.getElementById('previewImage');
    const previewContainer = document.getElementById('previewContainer');

    inputFile.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          previewImage.setAttribute('src', e.target.result);
          previewImage.classList.remove('hidden');
        }
        reader.readAsDataURL(file);
      } else {
        previewImage.setAttribute('src', '#');
        previewImage.classList.add('hidden');
      }
    });
  </script>
</body>
</html>