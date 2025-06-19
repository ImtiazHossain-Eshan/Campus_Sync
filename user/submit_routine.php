<?php
include '../auth/auth_check.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';

// Handle Add (unchanged)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_routine'])) {
    $section_code = $_POST['section_code'] ?? '';
    $course_name = $_POST['course_name'] ?? '';
    $faculty = $_POST['faculty'] ?? '';

    // Expect slots as array: $_POST['slots'][0]['day'], etc.
    $slots = $_POST['slots'] ?? [];

    if (!$section_code || !$course_name || !$faculty || !is_array($slots) || count($slots) === 0) {
        $errorMessage = "Please select a course section code to auto-fill slots.";
    } else {
        // Insert one row per slot
        $insertStmt = $pdo->prepare("
            INSERT INTO routines 
            (user_id, day, start_time, end_time, course_code, course_name, faculty, section, room)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $pdo->beginTransaction();
        try {
            foreach ($slots as $slot) {
                if (empty($slot['day']) || empty($slot['start_time']) || empty($slot['end_time'])) {
                    continue;
                }
                $day = $slot['day'];
                $start_time = $slot['start_time'];
                $end_time = $slot['end_time'];
                $room = $slot['room'] ?? '';

                $insertStmt->execute([
                    $user_id,
                    $day,
                    $start_time,
                    $end_time,
                    $section_code,
                    $course_name,
                    $faculty,
                    $section_code, // storing section same as course_code
                    $room
                ]);
            }
            $pdo->commit();
            $successMessage = "✅ Routine added for all slots successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "Error adding routines: " . $e->getMessage();
        }
    }
}

// Handle Delete: delete all slots for a section_code
if (isset($_GET['delete_section'])) {
    $delSection = $_GET['delete_section'];
    // Delete all entries for this user and that section
    $stmt = $pdo->prepare("DELETE FROM routines WHERE user_id = ? AND section = ?");
    $stmt->execute([$user_id, $delSection]);
    // Redirect to avoid repeated deletion on refresh
    header("Location: submit_routine.php");
    exit();
}

// Fetch routines for display: we still need all for Weekly Overview
// But for the “Submitted Routines” table, we'll group by section_code.
$stmtAll = $pdo->prepare("SELECT * FROM routines WHERE user_id = ?");
$stmtAll->execute([$user_id]);
$routinesAll = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

// Prepare weekly overview grid (unchanged logic, but include Friday/Saturday if used)
$timeSlots = [
    "08:00:00" => "08:00 – 09:20",
    "09:30:00" => "09:30 – 10:50",
    "11:00:00" => "11:00 – 12:20",
    "12:30:00" => "12:30 – 01:50",
    "14:00:00" => "02:00 – 03:20",
    "15:30:00" => "03:30 – 04:50",
    "17:00:00" => "05:00 – 06:20",
];
// Include Friday and Saturday if desired
$days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

$grid = [];
foreach ($routinesAll as $r) {
    $dayRow = $r['day'];
    $start = $r['start_time'];
    $span = 1;
    $dtStart = new DateTime($start);
    $dtEnd = new DateTime($r['end_time']);
    $diffH = ($dtEnd->getTimestamp() - $dtStart->getTimestamp())/3600.0;
    if ($diffH >= 1.5) {
        $span = 2;
    }
    // Build display string for grid cell, if you like: here we show section-faculty-room as well
    $section = $r['section'];       // e.g. "ECO101-25"
    $facultyInitial = $r['faculty']; // e.g. "TBA"
    $room = $r['room'];             // e.g. "08B" or "10C"
    $displayParts = [$section];
    if ($facultyInitial !== '') {
        $displayParts[] = $facultyInitial;
    }
    if ($room !== '') {
        $displayParts[] = $room;
    }
    $displayStr = implode('-', $displayParts);
    $grid[$dayRow][$start] = [
        'course' => $displayStr,
        'span' => $span,
        'id' => $r['id'],
    ];
}

// For “Submitted Routines” grouping: group $routinesAll by section_code
$grouped = [];
foreach ($routinesAll as $r) {
    $sec = $r['section'];
    if (!isset($grouped[$sec])) {
        $grouped[$sec] = [
            'course_name' => $r['course_name'],
            'faculty' => $r['faculty'],
            'rooms' => [],
            'slots' => [], // optional if you want to show times
        ];
    }
    // Collect distinct rooms
    if ($r['room'] !== '' && !in_array($r['room'], $grouped[$sec]['rooms'])) {
        $grouped[$sec]['rooms'][] = $r['room'];
    }
    // Optionally collect slots (day/time) if needed later
    $grouped[$sec]['slots'][] = [
        'day' => $r['day'],
        'start_time' => $r['start_time'],
        'end_time' => $r['end_time'],
    ];
}

?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<main class="max-w-7xl mx-auto mt-10 px-6">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    <!-- Submit Routine Form (unchanged) -->
    <div class="bg-white p-8 rounded-xl shadow-lg">
      <h2 class="text-2xl font-bold text-blue-700 mb-6">Submit Your Class Routine</h2>
      <?php if ($successMessage): ?>
        <div class="mb-4 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded">
          <?= htmlspecialchars($successMessage) ?>
        </div>
      <?php endif; ?>
      <?php if ($errorMessage): ?>
        <div class="mb-4 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded">
          <?= htmlspecialchars($errorMessage) ?>
        </div>
      <?php endif; ?>

      <form method="post" id="routineForm" class="space-y-6">
        <input type="hidden" name="submit_routine" value="1">
        <!-- Course Section Code with searchable dropdown -->
        <div>
          <label class="block text-gray-700 mb-1">Course Section Code</label>
          <input list="courseSections" id="courseInput" name="section_code" required
                 placeholder="Type or select section code"
                 class="w-full px-4 py-2 border border-gray-300 rounded-lg">
          <datalist id="courseSections">
            <!-- Populated by JS -->
          </datalist>
        </div>
        <!-- Auto-filled Course Name & Faculty -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 mb-1">Course Name</label>
            <input type="text" id="courseNameField" name="course_name" readonly
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" placeholder="Auto-filled">
          </div>
          <div>
            <label class="block text-gray-700 mb-1">Faculty Initials</label>
            <input type="text" id="facultyField" name="faculty" readonly
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100" placeholder="Auto-filled">
          </div>
        </div>
        <!-- Slot summary -->
        <div>
          <label class="block text-gray-700 mb-1">Scheduled Slots</label>
          <div id="slotSummary" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
            <em>Select a section code to see slots</em>
          </div>
        </div>
        <!-- Hidden inputs container for slots -->
        <div id="hiddenSlotsContainer"></div>
        <div class="text-right">
          <button type="submit"
                  class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-semibold shadow-md">
            ➕ Add to Routine
          </button>
        </div>
      </form>
    </div>

    <!-- Submitted Routines: grouped by section_code -->
    <div class="bg-white p-8 rounded-xl shadow-lg">
      <h3 class="text-xl font-semibold text-gray-800 mb-4">📅 Your Submitted Routines</h3>
      <?php if (count($grouped) > 0): ?>
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-700 border">
            <thead class="bg-gray-100 text-xs uppercase">
              <tr>
                <th class="px-4 py-3">Course</th>
                <th class="px-4 py-3">Time Slots</th>
                <th class="px-4 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($grouped as $section_code => $info): ?>
                <?php
                  // Build display: section_code-facultyInitial-room1-room2-...
                  $parts = [$section_code];
                  if (!empty($info['faculty'])) {
                      $parts[] = $info['faculty'];
                  }
                  if (!empty($info['rooms'])) {
                      // append each room; if multiple, join with '-'
                      foreach ($info['rooms'] as $rm) {
                          if ($rm !== '') {
                              $parts[] = $rm;
                          }
                      }
                  }
                  $displayStr = implode('-', $parts);

                  // Build time slots summary: e.g. "Sun 15:30–16:50; Tue 15:30–16:50"
                  $timeParts = [];
                  foreach ($info['slots'] as $slot) {
                      if (!empty($slot['day']) && !empty($slot['start_time']) && !empty($slot['end_time'])) {
                          $st = substr($slot['start_time'], 0, 5);
                          $et = substr($slot['end_time'], 0, 5);
                          // Abbreviate day if desired, e.g. "Sun" instead of "Sunday"
                          $dayShort = substr($slot['day'], 0, 3);
                          $timeParts[] = $dayShort . ' ' . $st . '–' . $et;
                      }
                  }
                  $timeSummary = implode('; ', $timeParts);
                ?>
                <tr class="border-t hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium"><?= htmlspecialchars($displayStr) ?></td>
                  <td class="px-4 py-3"><?= htmlspecialchars($timeSummary) ?></td>
                  <td class="px-4 py-3 text-right">
                    <a href="?delete_section=<?= urlencode($section_code) ?>" onclick="return confirm('Delete entire course from your routine?')" class="text-red-600 hover:underline">🗑️ Delete</a>
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

  <!-- Weekly Routine Overview: unchanged grouping by day/time -->
  <div class="mt-20">
    <h3 class="text-2xl font-bold text-gray-800 mb-6">📆 Weekly Routine Overview</h3>
    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm bg-white">
      <table class="min-w-full table-auto text-sm text-gray-800">
        <thead class="bg-gradient-to-r from-blue-100 to-blue-200">
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
                      $bg = ($entry['span'] > 1) ? 'bg-yellow-100 text-yellow-900' : 'bg-blue-100 text-blue-900';
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
  </div>
</main>

<footer class="mt-16 bg-gray-100 border-t text-center py-6 text-sm text-gray-600">
  © <?= date("Y") ?> Campus Sync. All rights reserved. 
</footer>

<!-- Include JS: -->
<script src="../assets/js/submit_routine.js"></script>
</body>
</html>