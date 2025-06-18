<?php
include '../auth/auth_check.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];
$successMessage = '';

// Handle Add
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_routine'])) {
    $section_code = $_POST['section_code'];

    // Fetch all info about the selected section
    $stmt = $pdo->prepare("SELECT * FROM course_sections WHERE section_code = ?");
    $stmt->execute([$section_code]);
    $course = $stmt->fetch();

    if ($course) {
        $stmt = $pdo->prepare("
            INSERT INTO routines
              (user_id, day, start_time, end_time, course_code, course_name, faculty, section, room)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $course['day'],
            $course['start_time'],
            $course['end_time'],
            $section_code,
            $course['course_name'],
            $course['faculty_initials'],
            $section_code,
            $course['room']
        ]);
        $successMessage = "✅ Routine added successfully.";
    }
}

// Fetch routines
$stmt = $pdo->prepare("
    SELECT * FROM routines 
    WHERE user_id = ? 
    ORDER BY FIELD(day, 'Sunday','Monday','Tuesday','Wednesday','Thursday'), start_time
");
$stmt->execute([$user_id]);
$routines = $stmt->fetchAll();

// Fetch course sections dropdown
$stmt = $pdo->query("SELECT section_code, course_name FROM course_sections ORDER BY section_code");
$courseSections = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<main class="max-w-7xl mx-auto mt-10 px-6">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    <!-- Submit Routine Form -->
    <div class="bg-white p-8 rounded-xl shadow-lg">
      <h2 class="text-2xl font-bold text-blue-700 mb-6">Submit Your Class Routine</h2>
      <?php if ($successMessage): ?>
        <div class="mb-4 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded">
          <?= $successMessage ?>
        </div>
      <?php endif; ?>
<form method="post" class="space-y-6">
  <input type="hidden" name="submit_routine" value="1">
  <div>
  <label class="block text-gray-700 mb-1">Search & Select Course Section</label>
  <select id="sectionSelect" name="section_code" required class="w-full px-4 py-2 border rounded-lg">
    <option disabled selected>Select a course</option>
    <?php foreach ($courseSections as $cs): ?>
      <option value="<?= htmlspecialchars($cs['section_code']) ?>">
        <?= htmlspecialchars($cs['course_name']) ?> – <?= htmlspecialchars($cs['section_code']) ?> –
        <?= htmlspecialchars($cs['faculty_initials']) ?> –
        <?= htmlspecialchars($cs['room']) ?> –
        <?= date("g:i A", strtotime($cs['start_time'])) ?> to <?= date("g:i A", strtotime($cs['end_time'])) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

</form>

    </div>

    <!-- Submitted Routines Table -->
    <div class="bg-white p-8 rounded-xl shadow-lg">
      <h3 class="text-xl font-semibold mb-4">📅 Your Submitted Routines</h3>
      <?php if ($routines): ?>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-700 border">
            
            <thead class="bg-gray-100 text-xs uppercase">
              <tr>
                <th class="px-4 py-3">Day</th>
                <th class="px-4 py-3">Course</th>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Room</th>
                <th class="px-4 py-3">Faculty</th>
                <th class="px-4 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($routines as $r): ?>
                <tr class="border-t hover:bg-gray-50">
                  <td class="px-4 py-3"><?= $r['day'] ?></td>
                  <td class="px-4 py-3 font-semibold">
                    <?= htmlspecialchars($r['section']) ?> – <?= htmlspecialchars($r['course_name']) ?>
                  </td>
                  <td class="px-4 py-3">
                    <?= date("g:i A", strtotime($r['start_time'])) ?>
                    – <?= date("g:i A", strtotime($r['end_time'])) ?>
                  </td>
                  <td class="px-4 py-3"><?= htmlspecialchars($r['room']) ?></td>
                  <td class="px-4 py-3"><?= htmlspecialchars($r['faculty']) ?></td>
                  <td class="px-4 py-3 text-right">
                    <a href="?delete=<?= $r['id'] ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:underline">🗑️ Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-gray-500">No routine added yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Weekly Routine Overview -->
  <?php
  $timeSlots = [
    "08:00:00" => "08:00 – 09:20",
    "09:30:00" => "09:30 – 10:50",
    "11:00:00" => "11:00 – 12:20",
    "12:30:00" => "12:30 – 13:50",
    "14:00:00" => "14:00 – 15:20",
    "15:30:00" => "15:30 – 16:50",
    "17:00:00" => "17:00 – 18:20",
  ];

  $days = ["Sunday","Monday","Tuesday","Wednesday","Thursday"];
  // reuse $routines stored above
  $grid = [];
  foreach ($routines as $r) {
    $isLab = stripos($r['course_name'], 'Lab') !== false;
    $span = $isLab ? 2 : 1;
    $grid[$r['day']][$r['start_time']] = [
      'text' => "{$r['course_code']}-{$r['section']}-{$r['room']}",
      'span' => $span
    ];
  }
  ?>
  <div class="mt-16">
    <h3 class="text-2xl font-bold mb-6">📆 Weekly Routine Overview</h3>
    <div class="overflow-x-auto rounded-xl border shadow bg-white">
      <table class="min-w-full table-auto text-sm text-gray-800">
        <thead class="bg-gradient-to-r from-blue-100 to-blue-200">
          <tr>
            <th class="px-4 py-3 font-semibold border">⏰ Time</th>
            <?php foreach ($days as $day): ?>
              <th class="px-4 py-3 text-center font-semibold border"><?= $day ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($timeSlots as $start => $label): ?>
            <tr>
              <td class="px-4 py-3 font-medium border bg-gray-50"><?= $label ?></td>
              <?php foreach ($days as $day): ?>
                <?php
                  $cellprinted = false;
                  if (isset($grid[$day][$start])) {
                    $entry = $grid[$day][$start];
                    $bg = $entry['span'] === 2 ? 'bg-yellow-100 text-yellow-900' : 'bg-blue-100 text-blue-900';
                    echo "<td class='px-4 py-3 text-center border font-semibold {$bg}' rowspan='{$entry['span']}'>";
                    echo htmlspecialchars($entry['text']);
                    echo "</td>";
                    $cellprinted = true;
                  }

                  if (!$cellprinted) {
                    // skip merged cell rows
                    echo "<td class='px-4 py-3 text-center border text-gray-300 italic'>—</td>";
                  }
                ?>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include '../includes/footer.php'; ?>