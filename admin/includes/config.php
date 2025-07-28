<?php
// Multi-account Zoom API configuration
require_once __DIR__ . '/multi_account_config.php';

// Get current Zoom credentials from session
function getCurrentZoomCredentials() {
    return getCurrentZoomAccount();
}

// JSON data file
define('ATTENDANCE_FILE', __DIR__ . '/attendance.json');

// Initialize attendance file if it doesn't exist
if (!file_exists(ATTENDANCE_FILE)) {
    file_put_contents(ATTENDANCE_FILE, json_encode(['meetings' => [], 'attendees' => []]));
}

