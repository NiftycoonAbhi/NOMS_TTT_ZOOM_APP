<?php include __DIR__ . '/../headers/header.php'; ?>
<?php
require_once '../admin/includes/config.php';
require_once '../admin/includes/functions.php';

// Define helper functions if not already defined in functions.php
if (!function_exists('calculateAverageDuration')) {
    function calculateAverageDuration($participants) {
        if (empty($participants)) return 0;
        $total = 0;
        $count = 0;
        foreach ($participants as $p) {
            if (isset($p['duration']) && is_numeric($p['duration'])) {
                $total += $p['duration'];
                $count++;
            }
        }
        return $count > 0 ? round($total / $count, 1) : 0;
    }
}

if (!function_exists('getFirstJoinTime')) {
    function getFirstJoinTime($participants) {
        if (empty($participants)) return 'N/A';
        $firstTime = null;
        foreach ($participants as $p) {
            if (isset($p['join_time'])) {
                $time = strtotime($p['join_time']);
                if (!$firstTime || $time < $firstTime) {
                    $firstTime = $time;
                }
            }
        }
        return $firstTime ? date('M j, Y g:i A', $firstTime) : 'N/A';
    }
}

if (!function_exists('getLastLeaveTime')) {
    function getLastLeaveTime($participants) {
        if (empty($participants)) return 'N/A';
        $lastTime = null;
        foreach ($participants as $p) {
            if (isset($p['leave_time']) && !empty($p['leave_time'])) {
                $time = strtotime($p['leave_time']);
                if (!$lastTime || $time > $lastTime) {
                    $lastTime = $time;
                }
            }
        }
        return $lastTime ? date('M j, Y g:i A', $lastTime) : 'N/A';
    }
}

$message = '';
$attendanceData = getAttendanceData();
$specificMeetingId = isset($_GET['id']) ? trim($_GET['id']) : null;

// Handle meeting end request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_meeting'])) {
    $meetingIdToEnd = trim($_POST['meeting_id']);
    $result = endZoomMeeting($meetingIdToEnd);
    
    if ($result['success']) {
        $message = "Meeting {$meetingIdToEnd} has been ended successfully!";
        // Update meeting status in our data
        if (isset($attendanceData['meetings'][$meetingIdToEnd])) {
            $attendanceData['meetings'][$meetingIdToEnd]['status'] = 'completed';
            saveAttendanceData($attendanceData);
        }
    } else {
        $message = "Error ending meeting: " . ($result['error'] ?? 'Unknown error');
    }
}

// Handle attendance tracking request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meeting_id'])) {
    $meetingId = trim($_POST['meeting_id']);

    if (!empty($meetingId)) {
        $result = getMeetingParticipants($meetingId);

        if (isset($result['participants'])) {
            $meetingStatus = $result['meeting_status'] ?? 'completed';
            saveAttendanceData($meetingId, $result['participants'], $meetingStatus);

            $statusNote = $meetingStatus === 'active' ?
                " (Meeting is still ongoing - data will update automatically)" :
                " (Completed meeting)";

            $message = "Attendance data for meeting {$meetingId} has been saved successfully!{$statusNote}";
            $attendanceData = getAttendanceData();
            $specificMeetingId = $meetingId; // Set the specific meeting ID to show
        } else {
            $errorMsg = $result['error'] ?? 'Unknown error occurred';
            $message = "Error: Could not fetch participants. $errorMsg";

            if (strpos($errorMsg, 'Meeting does not exist') !== false) {
                $message .= "<br><strong>Note:</strong> This meeting might not exist or you may not have permission to access it.";
            }

            if (isset($result['details'])) {
                $message .= "<br>Details: " . json_encode($result['details']);
            }
        }
    } else {
        $message = "Please enter a valid meeting ID";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoom Attendance Tracker</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .duration-cell {
            text-align: right;
            padding-right: 2rem !important;
        }

        .zoom-icon {
            color: #2D8CFF;
            margin-right: 8px;
        }

        .meeting-card {
            border-left: 4px solid #2D8CFF;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .meeting-card:hover {
            background-color: #e9ecef;
            cursor: pointer;
        }

        .meeting-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .meeting-actions {
            margin-top: 15px;
        }

        .badge-active {
            background-color: #28a745;
        }

        .badge-completed {
            background-color: #6c757d;
        }

        .participant-count {
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h1 class="mb-4">Zoom Meeting Attendance Tracker</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?= strpos($message, 'Error') !== false ? 'danger' : 'info' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Track Meeting Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4>Track Meeting</h4>
            </div>
            <div class="card-body">
                <form method="POST" id="trackForm">
                    <div class="mb-3">
                        <label for="meeting_id" class="form-label">Zoom Meeting ID</label>
                        <input type="text" class="form-control" id="meeting_id" name="meeting_id" required pattern="\d+"
                            title="Please enter only numbers" value="<?= htmlspecialchars($specificMeetingId ?? '') ?>">
                        <div class="form-text">Enter the numeric meeting ID from Zoom</div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="trackButton">
                        <span id="buttonText">Track Attendance</span>
                        <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Meeting Details Section -->
        <?php if ($specificMeetingId && isset($attendanceData['meetings'][$specificMeetingId])): ?>
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4>Meeting Details</h4>
                    <span class="badge <?= $attendanceData['meetings'][$specificMeetingId]['status'] === 'active' ? 
                        'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($attendanceData['meetings'][$specificMeetingId]['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <!-- Meeting Information Card -->
                    <div class="meeting-details">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>
                                    <i class="fas fa-video zoom-icon"></i>
                                    Meeting ID: <?= htmlspecialchars($specificMeetingId) ?>
                                </h5>
                                <p class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Last updated: <?= date('M j, Y g:i A', strtotime($attendanceData['meetings'][$specificMeetingId]['last_updated'])) ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="participant-count">
                                    <i class="fas fa-users me-2"></i>
                                    <?= isset($attendanceData['attendees'][$specificMeetingId]) ? 
                                        count($attendanceData['attendees'][$specificMeetingId]) : 0 ?> Participants
                                </div>
                            </div>
                        </div>

                        <!-- Meeting Actions -->
                        <div class="meeting-actions">
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-info view-participants-btn" data-meeting-id="<?= htmlspecialchars($specificMeetingId) ?>">
                                    <i class="fas fa-list me-1"></i> View Participants
                                </button>
                                
                                <?php if ($attendanceData['meetings'][$specificMeetingId]['status'] === 'active'): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to end this meeting?');">
                                        <input type="hidden" name="meeting_id" value="<?= htmlspecialchars($specificMeetingId) ?>">
                                        <button type="submit" name="end_meeting" class="btn btn-danger">
                                            <i class="fas fa-stop-circle me-1"></i> End Meeting
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-check-circle me-1"></i> Meeting Completed
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        <?php elseif (empty($attendanceData['meetings'])): ?>
            <div class="alert alert-info">
                No meetings tracked yet. Enter a meeting ID above to get started.
            </div>
        <?php endif; ?>
    </div>

    <!-- Participant Modal -->
    <div class="modal fade" id="participantsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Participants for Meeting: <span id="modalMeetingId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table" id="participantsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Join Time (IST)</th>
                                    <th>Leave Time (IST)</th>
                                    <th class="duration-cell">Duration (min)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle participant view button click
        document.addEventListener('DOMContentLoaded', function() {
            // Set up event listener for view participants button
            document.querySelector('.view-participants-btn')?.addEventListener('click', function() {
                const meetingId = this.getAttribute('data-meeting-id');
                showParticipants(meetingId);
            });

            // Auto-focus on meeting ID field
            document.getElementById('meeting_id')?.focus();

            // Handle form submission loading state
            document.getElementById('trackForm')?.addEventListener('submit', function() {
                const button = document.getElementById('trackButton');
                const spinner = document.getElementById('spinner');
                const buttonText = document.getElementById('buttonText');

                if (button && spinner && buttonText) {
                    button.disabled = true;
                    buttonText.textContent = 'Processing...';
                    spinner.classList.remove('d-none');
                }
            });
        });

        // Show participants modal
        function showParticipants(meetingId) {
            const modal = new bootstrap.Modal(document.getElementById('participantsModal'));
            const tableBody = document.querySelector('#participantsTable tbody');

            // Show loading state
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
            document.getElementById('modalMeetingId').textContent = meetingId;
            modal.show();

            // Fetch participant data
            fetch('get_participants.php?meeting_id=' + encodeURIComponent(meetingId))
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    tableBody.innerHTML = '';

                    if (data && data.length > 0) {
                        data.forEach(participant => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${participant.name || 'N/A'}</td>
                                <td>${participant.email || 'N/A'}</td>
                                <td>${participant.join_time || 'N/A'}</td>
                                <td>${participant.leave_time || 'N/A'}</td>
                                <td class="duration-cell">${participant.duration || '0'}</td>
                                <td>${participant.status === 'active' ?
                                    '<span class="badge bg-success">Active</span>' :
                                    '<span class="badge bg-secondary">Left</span>'}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="6" class="text-center">No participants found</td>';
                        tableBody.appendChild(row);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading participants</td></tr>';
                });
        }
    </script>
</body>
</html>