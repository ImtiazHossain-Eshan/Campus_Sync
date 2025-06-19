<?php
// routines/fetch_course_sections.php

header('Content-Type: application/json');
include '../config/db.php'; // $pdo

$cacheTTL = 24 * 60 * 60; // e.g. 24 hours
$nodeApiUrl = 'http://localhost:3001/scrape-preprereg';

try {
    // 1. Check last_updated
    $stmt = $pdo->prepare("SELECT MAX(last_updated) AS last_updated FROM course_sections");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastUpdated = $row['last_updated'] ?? null;

    $needRefresh = false;
    if (!$lastUpdated) {
        $needRefresh = true;
    } else {
        $lastTs = strtotime($lastUpdated);
        if (time() - $lastTs > $cacheTTL) {
            $needRefresh = true;
        }
    }

    if ($needRefresh) {
        // Fetch from Node scraper
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $nodeApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new Exception("Failed to fetch Node API: $err");
        }
        $json = json_decode($resp, true);
        if (!isset($json['data']) || !is_array($json['data'])) {
            throw new Exception("Unexpected JSON structure from Node API");
        }
        $dataList = $json['data'];

        // Clear old entries
        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM course_sections");
        $insertStmt = $pdo->prepare("
            INSERT INTO course_sections
            (section_code, course_name, faculty_initials, room, start_time, end_time, raw_time, last_updated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $nowStr = date('Y-m-d H:i:s');

        foreach ($dataList as $item) {
            // Normalize section_code
            $courseNameRaw = isset($item['courseName']) ? trim($item['courseName']) : '';
            $sectionNumRaw = isset($item['section']) ? trim($item['section']) : '';
            if ($courseNameRaw !== '' && $sectionNumRaw !== '') {
                $section_code = $courseNameRaw . '-' . $sectionNumRaw;
            } else {
                // fallback parse from raw "courseSection"
                $rawCS = $item['courseSection'] ?? '';
                $parts = explode(':', $rawCS);
                if (count($parts) >= 2) {
                    $cn = trim($parts[0]);
                    $secPart = trim($parts[1]);
                    // remove leading "sec" or variants
                    $secPart = preg_replace('/^(sec[\s\-:]*)/i', '', $secPart);
                    $secPart = trim($secPart);
                    if ($cn !== '' && $secPart !== '') {
                        $section_code = $cn . '-' . $secPart;
                    } else {
                        $section_code = preg_replace('/\s+/', '', str_replace(':', '-', $rawCS));
                    }
                } else {
                    $section_code = preg_replace('/\s+/', '', str_replace(':', '-', $rawCS));
                }
            }

            // Course name
            $course_name = $courseNameRaw;

            // Faculty initials
            $faculty = isset($item['faculty']) ? trim($item['faculty']) : '';

            // raw_time: full scraped time string
            $raw_time = isset($item['time']) ? trim($item['time']) : null;

            // Attempt to parse the *first* timeslot for start_time/end_time (for suggestion). But we still store raw_time.
            $start_time = null;
            $end_time = null;
            if ($raw_time) {
                // Look for first occurrence of "HH:MM AM/PM - HH:MM AM/PM"
                if (preg_match('/(\d{1,2}:\d{2}\s*(?:AM|PM))\s*-\s*(\d{1,2}:\d{2}\s*(?:AM|PM))/i', $raw_time, $m)) {
                    $dt1 = DateTime::createFromFormat('g:i A', strtoupper($m[1]));
                    $dt2 = DateTime::createFromFormat('g:i A', strtoupper($m[2]));
                    if ($dt1 && $dt2) {
                        $start_time = $dt1->format('H:i:s');
                        $end_time = $dt2->format('H:i:s');
                    }
                }
            }

            // Attempt to parse room from first timeslot if embedded: some strings like "3:30 PM-4:50 PM-09G-31T"
            $room = '';
            if ($raw_time) {
                // Extract first parenthesis group: e.g. SUNDAY(3:30 PM-4:50 PM-09G-31T)
                if (preg_match('/\w+\(([^)]+)\)/', $raw_time, $pmatch)) {
                    $inside = $pmatch[1]; // e.g. "3:30 PM-4:50 PM-09G-31T"
                    // match start-end-room
                    if (preg_match('/\d{1,2}:\d{2}\s*(?:AM|PM)\s*-\s*\d{1,2}:\d{2}\s*(?:AM|PM)\s*-\s*(\S+)/i', $inside, $rm)) {
                        $room = trim($rm[1]);
                    }
                }
            }

            // Insert
            $insertStmt->execute([
                $section_code,
                $course_name,
                $faculty,
                $room,
                $start_time,
                $end_time,
                $raw_time,
                $nowStr
            ]);
        }
        $pdo->commit();
    }

    // Return list of section_codes
    $stmt2 = $pdo->prepare("SELECT section_code, course_name FROM course_sections ORDER BY section_code ASC");
    $stmt2->execute();
    $all = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'sections' => $all]);
    exit;
}
catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}