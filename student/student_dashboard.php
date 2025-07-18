<?php
session_start(); // Assuming session is used somewhere
// Set default timezone to IST
date_default_timezone_set('Asia/Kolkata');
$ist_timezone = new DateTimeZone('Asia/Kolkata');

// Include necessary files
require_once __DIR__ . '/../admin/includes/zoom_api.php'; // Adjust path if different

// Get student ID from GET or set empty
$student_id = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';
$get_zoom_meetings = [];
if ($student_id) {
    $get_zoom_meetings = get_zoom_meetings($student_id);
    // Sort meetings by start_time, upcoming first
    // This sort is also done inside get_zoom_meetings now, but keeping it here as a fallback
    usort($get_zoom_meetings, function($a, $b) {
        $timeA = new DateTime($a['start_time'] ?? '9999-01-01T00:00:00Z');
        $timeB = new DateTime($b['start_time'] ?? '9999-01-01T00:00:00Z');
        return $timeA <=> $timeB;
    });
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Meeting Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .dashboard-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 0 18px rgba(0,0,0,0.08);
        }
        .meeting-card {
            margin-bottom: 22px;
            border-left: 5px solid #0d6efd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .meeting-btn {
            min-width: 160px;
        }
        #zoom-iframe-container {
            display: none;
            margin: 30px 0;
            text-align: center;
        }
        #zoom-iframe {
            width: 100%;
            max-width: 900px;
            height: 600px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1 class="text-center mb-4">Student Meeting Dashboard</h1>
        <form method="get" class="mb-5">
            <div class="input-group input-group-lg">
                <input type="text" name="student_id" class="form-control" placeholder="Enter your Student ID" value="<?= htmlspecialchars($student_id) ?>" required>
                <button class="btn btn-primary" type="submit">Find My Meetings</button>
            </div>
        </form>

        <?php if ($student_id): ?>
            <?php if (!empty($get_zoom_meetings)): ?>
                <h3 class="mb-4">Your Scheduled Meetings</h3>
                <?php foreach ($get_zoom_meetings as $meeting):
                    $meeting_id = htmlspecialchars($meeting['meeting_id']);
                    $zoom_link = htmlspecialchars($meeting['zoom_link']);
                    $topic = htmlspecialchars($meeting['topic'] ?? 'No Topic');

                    // Convert registration timestamp to IST
                    $timestamp = !empty($meeting['timestamp'])
                        ? (new DateTime($meeting['timestamp']))->setTimezone($ist_timezone)->format('d M Y, h:i A')
                        : 'N/A';

                    $status = isset($meeting['status']) ? strtolower($meeting['status']) : '';
                    $start_time_utc_str = isset($meeting['start_time']) ? $meeting['start_time'] : '';
                    $meeting_time_ist = '';
                    $is_live = ($status === 'started');
                    $is_ended = ($status === 'ended');

                    if ($start_time_utc_str) {
                        try {
                            $dt = new DateTime($start_time_utc_str, new DateTimeZone('UTC')); // Parse as UTC
                            $dt->setTimezone($ist_timezone); // Convert to IST
                            $meeting_time_ist = $dt->format('d M Y, h:i A');

                            // Determine if meeting is in the past for display purposes
                            $current_time_ist = new DateTime('now', $ist_timezone);
                            // If meeting time is in the past, but status isn't 'started' or 'ended', consider it "Past"
                            if ($dt < $current_time_ist && !$is_live && !$is_ended) {
                                $is_ended = true; // Visually mark as ended if past and not live
                            }

                        } catch (Exception $e) {
                            error_log("Error parsing meeting start time in dashboard: " . $e->getMessage());
                            $meeting_time_ist = 'Invalid Time';
                        }
                    }
                    
                ?>
                <div class="card meeting-card">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div>
                            <h5 class="mb-2">Meeting ID: <span class="text-primary"><?= $meeting_id ?></span></h5>
                            <p class="mb-1"><strong>Topic:</strong> <?= $topic ?></p>
                            <p class="mb-1"><strong>Registered On:</strong> <?= $timestamp ?></p>
                            <?php if ($meeting_time_ist): ?>
                                <p class="mb-1"><strong>Meeting Time (IST):</strong> <?= $meeting_time_ist ?></p>
                            <?php endif; ?>
                            <p class="mb-1"><strong>Status:</strong>
                                <span class="<?= $is_live ? 'text-success' : ($is_ended ? 'text-danger' : 'text-secondary') ?>">
                                    <?= $is_live ? 'Live' : ($is_ended ? 'Ended' : (ucfirst($status) ?: 'Scheduled')) ?>
                                </span>
                            </p>
                        </div>
                        <div class="text-md-end mt-3 mt-md-0">
                            <button
                                class="btn btn-success btn-lg meeting-btn"
                                onclick="showZoomIframe('<?= $zoom_link ?>')"
                                <?= $is_live ? '' : 'disabled' ?>>
                                <?= $is_live ? 'Join Now' : ($is_ended ? 'Ended' : 'Not Live Yet') ?>
                            </button>
                            <div class="small mt-2">
                                <a href="<?= $zoom_link ?>" target="_blank" class="text-decoration-underline">Open in new tab</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning mt-4">
                    <h4>No meetings found for: <span class="text-primary"><?= htmlspecialchars($student_id) ?></span></h4>
                    <p>Please ensure your Student ID is correct and you have registered for meetings. If you believe this is an error, please contact support.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                Enter your Student ID above to view your meetings.
            </div>
        <?php endif; ?>

        <div id="zoom-iframe-container">
            <h4 class="mb-3">Zoom Meeting</h4>
            <iframe id="zoom-iframe" allow="camera; microphone; fullscreen;" frameborder="0"></iframe>
            <div class="mt-2">
                <button class="btn btn-outline-secondary" onclick="hideZoomIframe()">Close Meeting</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showZoomIframe(link) {
            var iframe = document.getElementById('zoom-iframe');
            var container = document.getElementById('zoom-iframe-container');
            iframe.src = link;
            container.style.display = 'block';
            // Scroll to iframe
            setTimeout(function() {
                container.scrollIntoView({behavior: 'smooth'});
            }, 200);
        }
        function hideZoomIframe() {
            var iframe = document.getElementById('zoom-iframe');
            var container = document.getElementById('zoom-iframe-container');
            iframe.src = '';
            container.style.display = 'none';
        }
    </script>
</body>
</html>