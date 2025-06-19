<?php
// routines/fetch_course_details.php

header('Content-Type: application/json');
include '../config/db.php'; // $pdo

if (!isset($_GET['section_code']) || trim($_GET['section_code']) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing section_code']);
    exit;
}
$section_code = trim($_GET['section_code']);

try {
    $stmt = $pdo->prepare("SELECT * FROM course_sections WHERE section_code = ?");
    $stmt->execute([$section_code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Section not found']);
        exit;
    }
    $course_name = $row['course_name'];
    $faculty = $row['faculty_initials'];
    $raw_time = $row['raw_time']; // e.g. "SUNDAY(3:30 PM-4:50 PM-09G-31T),TUESDAY(...)"
    $slots = [];

    if ($raw_time) {
        // Split by comma between day-parentheses groups.
        // But since time strings may contain commas only between day groups, splitting on ',' is acceptable.
        $parts = preg_split('/\s*,\s*/', $raw_time);
        foreach ($parts as $part) {
            // Expect pattern: DAY(...) e.g. "SUNDAY(3:30 PM-4:50 PM-09G-31T)"
            if (preg_match('/^([A-Za-z]+)\(([^)]+)\)$/', trim($part), $m)) {
                $day = ucfirst(strtolower($m[1])); // e.g. "Sunday"
                $inside = $m[2]; // e.g. "3:30 PM-4:50 PM-09G-31T"
                // Parse start_time, end_time, room from inside
                $start_time = null;
                $end_time = null;
                $room = '';
                // Look for pattern: start-end-room OR start-end
                // e.g. "3:30 PM-4:50 PM-09G-31T"
                if (preg_match('/^(\d{1,2}:\d{2}\s*(?:AM|PM))\s*-\s*(\d{1,2}:\d{2}\s*(?:AM|PM))(?:\s*-\s*(\S+))?$/i', trim($inside), $tm)) {
                    $t1 = DateTime::createFromFormat('g:i A', strtoupper($tm[1]));
                    $t2 = DateTime::createFromFormat('g:i A', strtoupper($tm[2]));
                    if ($t1 && $t2) {
                        $start_time = $t1->format('H:i:s');
                        $end_time = $t2->format('H:i:s');
                    }
                    if (isset($tm[3])) {
                        $room = trim($tm[3]);
                    }
                }
                $slots[] = [
                    'day' => $day,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'room' => $room,
                ];
            }
        }
    }

    // If no slots parsed, fallback to single suggestion from stored start_time/end_time
    if (empty($slots)) {
        if ($row['start_time'] && $row['end_time']) {
            $slots[] = [
                'day' => '', // unknown
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'room' => $row['room'] ?? '',
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'section_code' => $section_code,
            'course_name' => $course_name,
            'faculty_initials' => $faculty,
            'slots' => $slots, // array of {day, start_time, end_time, room}
        ]
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}