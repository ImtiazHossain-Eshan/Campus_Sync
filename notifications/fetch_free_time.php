<?php
// Ensure no whitespace/BOM before this tag

// === Error reporting / logging settings ===
// During development, log errors but do NOT display them in responses
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Start session if not already
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require '../auth/auth_check.php'; // Must not emit output
require '../config/db.php';

// Clean any buffered output
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

    // Build list of user IDs: include current user
    $user_ids = array_unique(array_merge([$current_user_id], $friend_ids));

    // Validate friend IDs are actual accepted friends
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

    // Define campus window
    $day_start = '08:00:00';
    $day_end   = '19:00:00';

    // Helper: merge overlapping/contiguous busy intervals
    // Input: array of ['start_time'=>'HH:MM:SS', 'end_time'=>'HH:MM:SS']
    // Output: merged array sorted by start_time, no overlaps: each next.start > prev.end
    function merge_busy_intervals(array $intervals): array {
        if (empty($intervals)) {
            return [];
        }
        // Sort by start_time
        usort($intervals, function($a, $b){
            return strcmp($a['start_time'], $b['start_time']);
        });
        $merged = [];
        $current = $intervals[0];
        foreach (array_slice($intervals, 1) as $iv) {
            // If overlapping or contiguous (iv.start <= current.end), merge
            if ($iv['start_time'] <= $current['end_time']) {
                // Extend end_time if needed
                if ($iv['end_time'] > $current['end_time']) {
                    $current['end_time'] = $iv['end_time'];
                }
            } else {
                // No overlap: push current, move to next
                $merged[] = $current;
                $current = $iv;
            }
        }
        $merged[] = $current;
        return $merged;
    }

    // Helper: compute free intervals between window [day_start, day_end] given merged busy intervals
    // Input: merged busy array sorted, non-overlapping
    // Output: array of ['start'=>..., 'end'=>...] free intervals; may include before first busy and after last busy
    function compute_free_from_busy(array $busy_merged, string $day_start, string $day_end): array {
        $free = [];
        $cursor = $day_start;
        foreach ($busy_merged as $iv) {
            $s = $iv['start_time'];
            $e = $iv['end_time'];
            if ($s > $cursor) {
                $free[] = ['start' => $cursor, 'end' => $s];
            }
            // Move cursor forward
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

    if (!$daysFetched) {
        // No routines at all
        echo json_encode(['free_slots' => new stdClass()]);
        exit;
    }

    // Sort days if they are names "Monday", etc.
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
                // If any user has no classes this day => skip this day entirely
                $per_user_busy_merged = null;
                break;
            }
            // Merge busy intervals
            $merged = merge_busy_intervals($busy);
            $per_user_busy_merged[$uid] = $merged;
            // Earliest class start_time for this user
            $earliest_starts[] = $merged[0]['start_time'];
        }
        if ($per_user_busy_merged === null) {
            // Skip day: at least one user has no classes
            continue;
        }
        // Determine effective arrival time: max of earliest class starts among users
        $effective_arrival = $earliest_starts[0];
        foreach ($earliest_starts as $st) {
            if (strcmp($st, $effective_arrival) > 0) {
                $effective_arrival = $st;
            }
        }
        // Now compute each user's free intervals (full window) then trim by arrival
        $per_user_free_trimmed = [];
        foreach ($user_ids as $uid) {
            $busy_merged = $per_user_busy_merged[$uid];
            // Compute free between [day_start, day_end]
            $free_full = compute_free_from_busy($busy_merged, $day_start, $day_end);
            // Trim by arrival: keep only parts at/after effective_arrival
            $trimmed = [];
            foreach ($free_full as $fiv) {
                $fs = $fiv['start'];
                $fe = $fiv['end'];
                // If free interval ends <= arrival, discard
                if (strcmp($fe, $effective_arrival) <= 0) {
                    continue;
                }
                // If start < arrival < end, adjust start to arrival
                $new_start = (strcmp($fs, $effective_arrival) < 0) ? $effective_arrival : $fs;
                // Keep if new_start < end
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
                        // intersection
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
            // Optionally, you could merge adjacent free intervals in $common if they touch or are contiguous.
            // But with our logic, they should already be disjoint. If needed, implement merge here.
            $free_slots[$day] = $common;
        }
        // If $common empty, day yields no free slots; we could include day with empty array or skip it.
        // We skip days with no free slots so front-end shows only days with some free time.
    }

    echo json_encode(['free_slots' => $free_slots]);
    exit;

} catch (Exception $e) {
    // Log internally
    error_log("fetch_free_time exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again later.']);
    exit;
}