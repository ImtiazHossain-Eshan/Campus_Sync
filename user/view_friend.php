<?php
require '../auth/auth_check.php';
require '../config/db.php';

if (!isset($_GET['friend_id']) || !is_numeric($_GET['friend_id'])) {
    die('Invalid friend ID.');
}

$friend_id = (int) $_GET['friend_id'];
$current_user_id = $_SESSION['user_id'];

// Validate friendship
$stmt = $pdo->prepare("
  SELECT * FROM friends 
  WHERE 
    ((user_id = :uid AND friend_id = :fid) OR (user_id = :fid AND friend_id = :uid)) 
    AND status = 'accepted'
");
$stmt->execute(['uid' => $current_user_id, 'fid' => $friend_id]);
if ($stmt->rowCount() === 0) {
    die('You are not friends with this user.');
}

// Fetch friend's profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :fid");
$stmt->execute(['fid' => $friend_id]);
$friend = $stmt->fetch();
if (!$friend) die('User not found.');

// Fetch friend's full routine
$stmt = $pdo->prepare("SELECT * FROM routines WHERE user_id = :fid");
$stmt->execute(['fid' => $friend_id]);
$routinesAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare grid
$timeSlots = [
    "08:00:00" => "08:00 – 09:20",
    "09:30:00" => "09:30 – 10:50",
    "11:00:00" => "11:00 – 12:20",
    "12:30:00" => "12:30 – 01:50",
    "14:00:00" => "02:00 – 03:20",
    "15:30:00" => "03:30 – 04:50",
    "17:00:00" => "05:00 – 06:20",
];
$days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
$grid = [];

foreach ($routinesAll as $r) {
    $dayRow = $r['day'];
    $start = $r['start_time'];
    $span = 1;
    $dtStart = new DateTime($start);
    $dtEnd = new DateTime($r['end_time']);
    $diffH = ($dtEnd->getTimestamp() - $dtStart->getTimestamp()) / 3600.0;
    if ($diffH >= 1.5) {
        $span = 2;
    }

    $section = $r['section'];
    $faculty = $r['faculty'];
    $room = $r['room'];
    $displayParts = [$section];
    if ($faculty) $displayParts[] = $faculty;
    if ($room) $displayParts[] = $room;
    $displayStr = implode('-', $displayParts);

    $grid[$dayRow][$start] = [
        'course' => $displayStr,
        'span' => $span,
        'id' => $r['id'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($friend['name']) ?>'s Profile - CampusSync</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-indigo-50 via-blue-100 to-purple-50 min-h-screen">
<?php include '../includes/navbar.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-10">
  <!-- Profile Card -->
  <section class="bg-white shadow-2xl rounded-3xl p-8 flex flex-col md:flex-row items-center md:items-start gap-8 mb-12">
    <img src="<?= $friend['profile_pic'] ? '../' . htmlspecialchars($friend['profile_pic']) : '../assets/img/default-profile.png' ?>" 
         alt="Profile" class="w-32 h-32 rounded-full border-4 border-blue-500 object-cover">
    <div class="text-center md:text-left">
      <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($friend['name']) ?></h1>
      <p class="text-gray-600"><?= htmlspecialchars($friend['email']) ?></p>
      <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($friend['department'] ?? '') ?>, <?= htmlspecialchars($friend['semester'] ?? '') ?></p>
      <p class="text-sm text-gray-400 italic"><?= htmlspecialchars($friend['university']) ?></p>
    </div>
  </section>

  <!-- Weekly Routine Grid -->
  <section class="bg-white rounded-3xl shadow-xl p-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?= htmlspecialchars($friend['name']) ?>'s Weekly Routine</h2>
    <?php if (empty($routinesAll)): ?>
      <p class="text-gray-500 italic">No routine found.</p>
    <?php else: ?>
      <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm bg-white">
        <table class="min-w-full table-auto text-sm text-gray-800">
          <thead class="bg-gradient-to-r from-indigo-100 to-indigo-200">
            <tr>
              <th class="px-4 py-3 text-left font-semibold border border-gray-200">⏰ Time</th>
              <?php foreach ($days as $day): ?>
                <th class="px-4 py-3 text-center font-semibold border border-gray-200"><?= $day ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($timeSlots as $startTime => $label): ?>
              <tr>
                <td class="px-4 py-3 font-medium border border-gray-200 bg-gray-50"><?= $label ?></td>
                <?php foreach ($days as $day): ?>
                  <?php
                  $cellPrinted = false;
                  if (isset($grid[$day][$startTime])) {
                      $entry = $grid[$day][$startTime];
                      $bg = ($entry['span'] > 1) ? 'bg-yellow-100 text-yellow-900' : 'bg-indigo-100 text-indigo-900';
                      echo "<td class='px-4 py-3 text-center border border-gray-200 font-semibold {$bg}' rowspan='{$entry['span']}'>";
                      echo "<div class='text-sm leading-snug'>" . htmlspecialchars($entry['course']) . "</div>";
                      echo "</td>";
                      $cellPrinted = true;
                  }
                  if (!$cellPrinted) {
                      $skip = false;
                      if (!empty($grid[$day])) {
                          foreach ($grid[$day] as $start => $entry) {
                              $allTimes = array_keys($timeSlots);
                              $i = array_search($start, $allTimes);
                              $currentIndex = array_search($startTime, $allTimes);
                              if ($i !== false && $entry['span'] > 1 && $i + 1 === $currentIndex) {
                                  $skip = true;
                                  break;
                              }
                          }
                      }
                      if (!$skip) {
                          echo "<td class='px-4 py-3 text-center border border-gray-200 text-gray-300 italic'>—</td>";
                      }
                  }
                  ?>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>