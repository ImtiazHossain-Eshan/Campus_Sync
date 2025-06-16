<?php
include '../auth/auth_check.php';
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $course = htmlspecialchars($_POST['course']);

    $stmt = $pdo->prepare("INSERT INTO routines (user_id, day, start_time, end_time, course) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $day, $start_time, $end_time, $course]);
    echo "Routine saved.";
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<h2>Submit Routine</h2>
<form method="post">
    Day: 
    <select name="day" required>
        <option value="Sunday">Sunday</option>
        <option value="Monday">Monday</option>
        <option value="Tuesday">Tuesday</option>
        <option value="Wednesday">Wednesday</option>
        <option value="Thursday">Thursday</option>
    </select><br>
    Start Time: <input type="time" name="start_time" required><br>
    End Time: <input type="time" name="end_time" required><br>
    Course Name: <input type="text" name="course" required><br>
    <button type="submit">Add</button>
</form>

<?php include '../includes/footer.php'; ?>