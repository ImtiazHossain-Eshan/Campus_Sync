<?php
// Ensure no whitespace or BOM before this <?php tag.

// === Error reporting settings ===
// During development, you may log errors but do NOT display them in HTTP responses.
// In production, you definitely should not display errors to users.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Adjust error_reporting as needed; here we log all except notices/warnings if desired:
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Start session only if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require '../auth/auth_check.php'; // Should not emit output
require '../config/db.php';

// We want to ensure no prior output. If there is buffered output, clean it.
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Read raw body
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        throw new Exception('Failed to read request body.');
    }
    if (trim($raw) === '') {
        throw new Exception('Empty request body.');
    }

    $input = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON parse error: ' . json_last_error_msg());
    }
    if (!isset($input['friend_ids']) || !is_array($input['friend_ids'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input: expected JSON with friend_ids array.']);
        exit;
    }

    $current_user_id = $_SESSION['user_id'] ?? null;
    if (!$current_user_id) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated.']);
        exit;
    }

    // Sanitize friend IDs
    $friend_ids = array_filter($input['friend_ids'], function($v){
        return is_numeric($v) && intval($v) > 0;
    });
    $friend_ids = array_map('intval', $friend_ids);

    // Always include current user
    $user_ids = array_unique(array_merge([$current_user_id], $friend_ids));

    // Validate friend_ids: ensure each ID is an accepted friend
    if (!empty($friend_ids)) {
        // Prepare placeholders
        $placeholders = implode(',', array_fill(0, count($friend_ids), '?'));
        // Query both directions
        $sql = "
            SELECT DISTINCT 
                CASE 
                  WHEN user_id = ? THEN friend_id 
                  WHEN friend_id = ? THEN user_id 
                END AS fid
            FROM friends 
            WHERE status = 'accepted' 
              AND (
                (user_id = ? AND friend_id IN ($placeholders))
                OR
                (friend_id = ? AND user_id IN ($placeholders))
              )
        ";
        // Build parameters:
        $params = [];
        // CASE: current_user twice
        $params[] = $current_user_id;
        $params[] = $current_user_id;
        // First IN clause: user_id = current_user AND friend_id IN (...)
        $params[] = $current_user_id;
        foreach ($friend_ids as $fid) {
            $params[] = $fid;
        }
        // Second IN clause: friend_id = current_user AND user_id IN (...)
        $params[] = $current_user_id;
        foreach ($friend_ids as $fid) {
            $params[] = $fid;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $valid = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($friend_ids as $fid) {
            if (!in_array($fid, $valid)) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized friend ID: ' . $fid]);
                exit;
            }
        }
    }

    // Fetch distinct days where any of these users has a routine
    if (empty($user_ids)) {
        echo json_encode(['free_slots' => new stdClass()]);
        exit;
    }
    $inPlaceholders = implode(',', array_fill(0, count($user_ids), '?'));
    $sqlDays = "SELECT DISTINCT day FROM routines WHERE user_id IN ($inPlaceholders)";
    $stmt = $pdo->prepare($sqlDays);
    $stmt->execute($user_ids);
    $daysFetched = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!$daysFetched) {
        // No routines at all: return empty object
        echo json_encode(['free_slots' => new stdClass()]);
        exit;
    }

    // Sort days if they are full names "Monday", etc.
    $dayOrderMap = [
        'Saturday' => 0,
        'Sunday'   => 1,
        'Monday'   => 2,
        'Tuesday'  => 3,
        'Wednesday'=> 4,
        'Thursday' => 5,
        'Friday'   => 6,
    ];
    usort($daysFetched, function($a, $b) use ($dayOrderMap) {
        $oa = $dayOrderMap[$a] ?? 999;
        $ob = $dayOrderMap[$b] ?? 999;
        if ($oa === $ob) return strcmp($a, $b);
        return $oa - $ob;
    });

    // Define daily window (adjust if needed)
    $day_start = '08:00:00';
    $day_end   = '20:00:00';

    // Helper to compute free intervals from busy ones
    function compute_free_intervals(array $intervals, string $day_start, string $day_end): array {
        $free = [];
        $cursor = $day_start;
        foreach ($intervals as $iv) {
            $s = $iv['start_time'];
            $e = $iv['end_time'];
            if ($s > $cursor) {
                $free[] = ['start' => $cursor, 'end' => $s];
            }
            if ($e > $cursor) {
                $cursor = $e;
            }
        }
        if ($cursor < $day_end) {
            $free[] = ['start' => $cursor, 'end' => $day_end];
        }
        return $free;
    }

    // Main intersection logic
    $free_slots = [];
    foreach ($daysFetched as $day) {
        $all_free = null;
        foreach ($user_ids as $uid) {
            // Fetch busy intervals for this user/day
            $stmt = $pdo->prepare("SELECT start_time, end_time FROM routines WHERE user_id = ? AND day = ? ORDER BY start_time ASC");
            $stmt->execute([$uid, $day]);
            $busy = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $user_free = compute_free_intervals($busy, $day_start, $day_end);

            if ($all_free === null) {
                $all_free = $user_free;
            } else {
                $new_all = [];
                foreach ($all_free as $af) {
                    foreach ($user_free as $uf) {
                        $start = ($af['start'] > $uf['start']) ? $af['start'] : $uf['start'];
                        $end   = ($af['end']   < $uf['end'])   ? $af['end']   : $uf['end'];
                        if ($start < $end) {
                            $new_all[] = ['start' => $start, 'end' => $end];
                        }
                    }
                }
                $all_free = $new_all;
            }
            if (empty($all_free)) {
                break;
            }
        }
        $free_slots[$day] = $all_free ?: [];
    }

    echo json_encode(['free_slots' => $free_slots]);
    exit;

} catch (Exception $e) {
    // Log the exception internally
    error_log("fetch_free_time exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again later.']);
    exit;
}