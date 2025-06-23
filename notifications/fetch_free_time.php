<?php
// Ensure no whitespace/BOM before this tag

// === Error reporting / logging settings ===
// Do NOT display errors in HTTP response; log them instead.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Start session if not already
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require '../auth/auth_check.php'; // Ensure this does NOT emit output or start session again
require '../config/db.php';

// Clean any buffered output
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Read raw POST body
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

    // Get current user
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

    // Build user_ids list including current user
    $user_ids = array_unique(array_merge([$current_user_id], $friend_ids));

    // Validate friend IDs: ensure each is an accepted friend
    if (!empty($friend_ids)) {
        $placeholders = implode(',', array_fill(0, count($friend_ids), '?'));
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
        $params = [];
        // CASE bindings
        $params[] = $current_user_id;
        $params[] = $current_user_id;
        // First IN clause
        $params[] = $current_user_id;
        foreach ($friend_ids as $fid) {
            $params[] = $fid;
        }
        // Second IN clause
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

    // Campus window bounds
    $day_start = '08:00:00';
    $day_end   = '19:00:00';

    // Helper: merge overlapping/contiguous busy intervals
    function merge_busy_intervals(array $intervals): array {
        if (empty($intervals)) {
            return [];
        }
        usort($intervals, function($a, $b){
            return strcmp($a['start_time'], $b['start_time']);
        });
        $merged = [];
        $current = $intervals[0];
        foreach (array_slice($intervals, 1) as $iv) {
            // If overlapping or contiguous: next.start_time <= current.end_time
            if ($iv['start_time'] <= $current['end_time']) {
                // Extend end_time if needed
                if ($iv['end_time'] > $current['end_time']) {
                    $current['end_time'] = $iv['end_time'];
                }
            } else {
                $merged[] = $current;
                $current = $iv;
            }
        }
        $merged[] = $current;
        return $merged;
    }

    // Helper: compute free intervals between window [day_start, day_end] given merged busy intervals
    function compute_free_from_busy(array $busy_merged, string $day_start, string $day_end): array {
        $free = [];
        $cursor = $day_start;
        foreach ($busy_merged as $iv) {
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

    if (empty($daysFetched)) {
        // No routines at all
        echo json_encode(['free_slots' => new stdClass()]);
        exit;
    }

    // Sort days if they are full names ("Monday", etc.)
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

    $free_slots = [];

    foreach ($daysFetched as $day) {
        $per_user_busy_merged = [];
        $earliest_starts = [];

        // For each user, fetch busy intervals and merge
        foreach ($user_ids as $uid) {
            $stmt = $pdo->prepare("SELECT start_time, end_time FROM routines WHERE user_id = ? AND day = ? ORDER BY start_time ASC");
            $stmt->execute([$uid, $day]);
            $busy = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($busy)) {
                // If any user has no classes this day => skip this day
                $per_user_busy_merged = null;
                break;
            }
            $merged = merge_busy_intervals($busy);
            $per_user_busy_merged[$uid] = $merged;
            // Earliest class start_time for this user
            $earliest_starts[] = $merged[0]['start_time'];
        }
        if ($per_user_busy_merged === null) {
            // Skip because at least one user has no classes that day
            continue;
        }
        // Effective arrival time: max of earliest class starts
        $effective_arrival = $earliest_starts[0];
        foreach ($earliest_starts as $st) {
            if (strcmp($st, $effective_arrival) > 0) {
                $effective_arrival = $st;
            }
        }

        // Compute each user’s free intervals then trim by arrival
        $per_user_free_trimmed = [];
        foreach ($user_ids as $uid) {
            $busy_merged = $per_user_busy_merged[$uid];
            $free_full = compute_free_from_busy($busy_merged, $day_start, $day_end);
            $trimmed = [];
            foreach ($free_full as $fiv) {
                $fs = $fiv['start'];
                $fe = $fiv['end'];
                // If free interval ends <= arrival, discard
                if (strcmp($fe, $effective_arrival) <= 0) {
                    continue;
                }
                // If starts < arrival < end, adjust start to arrival
                $new_start = (strcmp($fs, $effective_arrival) < 0) ? $effective_arrival : $fs;
                if (strcmp($new_start, $fe) < 0) {
                    $trimmed[] = ['start' => $new_start, 'end' => $fe];
                }
            }
            $per_user_free_trimmed[$uid] = $trimmed;
        }

        // Intersect trimmed free intervals across users
        $common = null;
        foreach ($user_ids as $uid) {
            $user_free = $per_user_free_trimmed[$uid];
            if ($common === null) {
                $common = $user_free;
            } else {
                $new_common = [];
                foreach ($common as $cf) {
                    foreach ($user_free as $uf) {
                        $start = (strcmp($cf['start'], $uf['start']) > 0) ? $cf['start'] : $uf['start'];
                        $end   = (strcmp($cf['end'],   $uf['end'])   < 0) ? $cf['end']   : $uf['end'];
                        if (strcmp($start, $end) < 0) {
                            $new_common[] = ['start' => $start, 'end' => $end];
                        }
                    }
                }
                $common = $new_common;
            }
            if (empty($common)) {
                break;
            }
        }
        if (!empty($common)) {
            $free_slots[$day] = $common;
        }
        // If empty, skip including this day
        // Example threshold: 30 minutes
$thresholdMinutes = 30;
$filtered = [];
foreach ($common as $iv) {
    list($h1,$m1,$s1) = explode(':', $iv['start']);
    list($h2,$m2,$s2) = explode(':', $iv['end']);
    $startSec = $h1*3600 + $m1*60 + $s1;
    $endSec   = $h2*3600 + $m2*60 + $s2;
    $diffMin = ($endSec - $startSec) / 60;
    if ($diffMin >= $thresholdMinutes) {
        $filtered[] = $iv;
    }
}
if (!empty($filtered)) {
    $free_slots[$day] = $filtered;
}
// If $filtered is empty, skip the day entirely.

    }

    echo json_encode(['free_slots' => $free_slots]);
    exit;

} catch (Exception $e) {
    error_log("fetch_free_time exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again later.']);
    exit;
}