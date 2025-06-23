<?php include __DIR__ . '/../headers/header2.php'; ?>
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
                " ";

            $message = "Attendance data for meeting {$meetingId} has been saved successfully!{$statusNote}";
            $attendanceData = getAttendanceData();
            $specificMeetingId = $meetingId;
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
        .duration-cell { text-align: right; padding-right: 2rem !important; }
        .zoom-icon { color: #2D8CFF; margin-right: 8px; }
        .meeting-card { border-left: 4px solid #2D8CFF; margin-bottom: 15px; padding: 15px; 
                        background-color: #f8f9fa; border-radius: 4px; transition: all 0.3s ease; }
        .meeting-card:hover { background-color: #e9ecef; cursor: pointer; }
        .meeting-details { background-color: #f8f9fa; border-radius: 5px; padding: 20px; margin-bottom: 20px; }
        .meeting-actions { margin-top: 15px; }
        .badge-active { background-color: #28a745; }
        .badge-completed { background-color: #6c757d; }
        .participant-count { font-size: 1.2rem; font-weight: bold; }
        .participant-row { cursor: pointer; transition: background-color 0.2s; }
        .participant-row:hover { background-color: #f8f9fa; }
        #participantsTable { font-size: 0.9rem; }
        #participantsTable thead th { position: sticky; top: 0; background-color: white; z-index: 10; }
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
                    <div class="meeting-details">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-video zoom-icon"></i> Meeting ID: <?= htmlspecialchars($specificMeetingId) ?></h5>
                                <p class="text-muted"><i class="fas fa-calendar-alt me-1"></i> Last updated: 
                                    <?= date('M j, Y g:i A', strtotime($attendanceData['meetings'][$specificMeetingId]['last_updated'])) ?>
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
                        <div class="meeting-actions">
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-info view-participants-btn" data-meeting-id="<?= htmlspecialchars($specificMeetingId) ?>">
                                    <i class="fas fa-list me-1"></i> View Participants
                                </button>
                                <?php if ($attendanceData['meetings'][$specificMeetingId]['status'] === 'active'): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to end this meeting?');">
                                        <input type="hidden" name="meeting_id" value="<?= htmlspecialchars($specificMeetingId) ?>">
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
            <div class="alert alert-info">No meetings tracked yet. Enter a meeting ID above to get started.</div>
        <?php endif; ?>
    </div>

    <!-- Participant Modal -->
    <div class="modal fade" id="participantsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-users me-2"></i>Participants for Meeting: <span id="modalMeetingId"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="container-fluid p-3 border-bottom bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div id="meetingStatusBadge" class="d-inline-block me-3"></div>
                                <small class="text-muted" id="lastUpdatedText"></small>
                            </div>
                            <div class="col-md-6 text-end">
                                <button id="refreshParticipantsBtn" class="btn btn-sm btn-outline-primary">
                                    <!-- <i class="fas fa-sync-alt me-1"></i> Refresh -->
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                        <table class="table table-hover table-striped mb-0" id="participantsTable">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Join Time</th>
                                    <th>Leave Time</th>
                                    <th class="text-end">Duration</th>
                                    <th class="text-center">Sessions</th>
                                    <th class="text-end">Total Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="participantsTableBody"></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light border-top">
                        <div class="text-muted small" id="participantCountText"></div>
                        <div class="text-end small">
                            <span class="badge bg-success me-2"><i class="fas fa-circle me-1"></i> Active</span>
                            <span class="badge bg-secondary"><i class="fas fa-check-circle me-1"></i> Left</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Details Modal -->
    <div class="modal fade" id="sessionDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="sessionDetailsTitle"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <span id="sessionEmail"></span></p>
                            <p><strong>Total Sessions:</strong> <span id="totalSessions"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Duration:</strong> <span id="totalDuration"></span> minutes</p>
                            <p><strong>Status:</strong> <span id="currentStatus"></span></p>
                        </div>
                    </div>
                    <h6 class="border-top pt-3">Session History</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Join Time</th>
                                    <th>Leave Time</th>
                                    <th class="text-end">Duration</th>
                                </tr>
                            </thead>
                            <tbody id="sessionHistoryBody"></tbody>
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

        function showParticipants(meetingId) {
            const modal = new bootstrap.Modal(document.getElementById('participantsModal'));
            const tableBody = document.getElementById('participantsTableBody');
            
            // Show loading state
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0">Loading participant data...</p>
                    </td>
                </tr>
            `;
            
            document.getElementById('modalMeetingId').textContent = meetingId;
            modal.show();

            fetch(`get_participants.php?meeting_id=${encodeURIComponent(meetingId)}`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Server returned an error');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('API Response:', data);
                    
                    // Update meeting status
                    const statusBadge = document.getElementById('meetingStatusBadge');
                    statusBadge.innerHTML = `
                        <span class="badge ${data.is_active ? 'bg-success' : 'bg-secondary'}">
                            <i class="fas ${data.is_active ? 'fa-play-circle' : 'fa-check-circle'} me-1"></i>
                            ${data.meeting_status.toUpperCase()}
                        </span>
                    `;
                    
                    // Update last updated time
                    document.getElementById('lastUpdatedText').innerHTML = `
                        <i class="far fa-clock me-1"></i>
                        Last updated: ${new Date(data.last_updated).toLocaleString()}
                    `;
                    
                    // Update participant count
                    document.getElementById('participantCountText').textContent = 
                        `Showing ${data.count} participant${data.count !== 1 ? 's' : ''}`;
                    
                    // Clear and populate table
                    tableBody.innerHTML = '';
                    
                    if (data.participants.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No participants found for this meeting
                                </td>
                            </tr>
                        `;
                    } else {
                        data.participants.forEach(participant => {
                            const row = document.createElement('tr');
                            row.className = 'participant-row';
                            row.innerHTML = `
                                <td>${participant.name}</td>
                                <td><small class="text-muted">${participant.email}</small></td>
                                <td><small>${participant.join_time}</small></td>
                                <td><small>${participant.leave_time}</small></td>
                                <td class="text-end">${participant.duration} min</td>
                                <td class="text-center">${participant.session_count}</td>
                                <td class="text-end fw-bold">${participant.total_duration} min</td>
                                <td>
                                    <span class="badge ${participant.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                                        <i class="fas ${participant.status === 'active' ? 'fa-circle-notch fa-spin' : 'fa-check-circle'} me-1"></i>
                                        ${participant.status === 'active' ? 'Active' : 'Left'}
                                    </span>
                                </td>
                            `;
                            
                            // Add click event for session details
                            row.addEventListener('click', () => {
                                showSessionDetails(participant);
                            });
                            
                            tableBody.appendChild(row);
                        });
                    }
                    
                    // Setup refresh button
                    document.getElementById('refreshParticipantsBtn').onclick = () => {
                        showParticipants(meetingId);
                    };
                })
                .catch(error => {
                    console.error('Error:', error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-4 text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${error.message}
                            </td>
                        </tr>
                    `;
                });
        }

        function showSessionDetails(participant) {
            const modal = new bootstrap.Modal(document.getElementById('sessionDetailsModal'));
            
            // Set modal title
            document.getElementById('sessionDetailsTitle').textContent = 
                `Attendance Details for ${participant.name}`;
            
            // Set participant info
            document.getElementById('sessionEmail').textContent = participant.email;
            document.getElementById('totalSessions').textContent = participant.session_count;
            document.getElementById('totalDuration').textContent = participant.total_duration;
            
            // Set status badge
            const statusBadge = document.getElementById('currentStatus');
            statusBadge.innerHTML = `
                <span class="badge ${participant.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                    ${participant.status === 'active' ? 'Active' : 'Left'}
                </span>
            `;
            
            // Populate session history
            const sessionBody = document.getElementById('sessionHistoryBody');
            sessionBody.innerHTML = participant.all_sessions.map((session, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${new Date(session.join_time).toLocaleString()}</td>
                    <td>${session.leave_time === 'Still in meeting' ? 
                        '<span class="text-muted">Still in meeting</span>' : 
                        new Date(session.leave_time).toLocaleString()}
                    </td>
                    <td class="text-end">${session.duration} min</td>
                </tr>
            `).join('');
            
            modal.show();
        }
    </script>
</body>
</html>