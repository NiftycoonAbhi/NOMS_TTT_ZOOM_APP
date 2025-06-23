<?php
// Zoom API configuration
define('ZOOM_ACCOUNT_ID', '89NOV9jAT-SH7wJmjvsptg');
define('ZOOM_CLIENT_ID', '4y5ckqpJQ1WvJAmk3x6PvQ');
define('ZOOM_CLIENT_SECRET', '8eH7szslJoGeBbyRULvEm6Bx7eE630jB');

// JSON data file
define('ATTENDANCE_FILE', __DIR__ . '/attendance.json');

// Initialize attendance file if it doesn't exist
if (!file_exists(ATTENDANCE_FILE)) {
    file_put_contents(ATTENDANCE_FILE, json_encode(['meetings' => [], 'attendees' => []]));
}

