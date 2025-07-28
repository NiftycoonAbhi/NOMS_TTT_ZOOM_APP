<?php
/**
 * Performance Monitoring for Zoom Registration
 * Track registration speeds and identify bottlenecks
 */

require_once 'admin/includes/multi_account_config.php';
require_once 'admin/includes/zoom_api.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Auto-select first available account for testing
$all_accounts = getAllZoomCredentials();
if (!empty($all_accounts)) {
    setCurrentZoomAccount($all_accounts[0]['id']);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Performance Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container py-4">
        <h1>🚀 Registration Performance Monitor</h1>
        
        <?php if (isset($_POST['test_performance'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Performance Test Results</h3>
                </div>
                <div class="card-body">
                    <?php
                    $meeting_id = $_POST['meeting_id'];
                    $test_count = intval($_POST['test_count']);
                    
                    echo "<h4>Testing Registration Speed with {$test_count} Students</h4>";
                    
                    // Generate test students
                    $test_students = [];
                    for ($i = 1; $i <= $test_count; $i++) {
                        $test_students[] = [
                            'student_id' => 'TTT-PERF-TEST-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                            'name' => 'Test Student ' . $i
                        ];
                    }
                    
                    // Test Old Method (individual calls)
                    echo "<h5>🐌 Old Method (Individual API Calls):</h5>";
                    $start_time = microtime(true);
                    
                    $old_success = 0;
                    $old_errors = 0;
                    
                    foreach (array_slice($test_students, 0, min(5, $test_count)) as $student) {
                        $result = registerStudent($meeting_id, $student['name'], '', $student['student_id']);
                        if (strpos($result, 'https://') === 0) {
                            $old_success++;
                        } else {
                            $old_errors++;
                        }
                    }
                    
                    $old_time = microtime(true) - $start_time;
                    $old_per_student = $old_time / min(5, $test_count);
                    
                    echo "<div class='alert alert-warning'>";
                    echo "Time for " . min(5, $test_count) . " students: <strong>" . number_format($old_time, 2) . " seconds</strong><br>";
                    echo "Average per student: <strong>" . number_format($old_per_student, 2) . " seconds</strong><br>";
                    echo "Success: {$old_success}, Errors: {$old_errors}";
                    echo "</div>";
                    
                    // Test New Method (bulk registration)
                    echo "<h5>🚀 New Method (Optimized Bulk Registration):</h5>";
                    $start_time = microtime(true);
                    
                    $bulk_results = registerStudentsBulk($meeting_id, $test_students);
                    
                    $new_time = microtime(true) - $start_time;
                    $new_per_student = $new_time / $test_count;
                    
                    echo "<div class='alert alert-success'>";
                    echo "Time for {$test_count} students: <strong>" . number_format($new_time, 2) . " seconds</strong><br>";
                    echo "Average per student: <strong>" . number_format($new_per_student, 2) . " seconds</strong><br>";
                    echo "Success: {$bulk_results['success_count']}, Errors: {$bulk_results['error_count']}";
                    echo "</div>";
                    
                    // Calculate improvement
                    $estimated_old_time = $old_per_student * $test_count;
                    $speed_improvement = $estimated_old_time / $new_time;
                    $time_saved = $estimated_old_time - $new_time;
                    
                    echo "<div class='alert alert-info'>";
                    echo "<h5>📊 Performance Improvement:</h5>";
                    echo "Estimated old method time for {$test_count} students: <strong>" . number_format($estimated_old_time, 2) . " seconds</strong><br>";
                    echo "New method time: <strong>" . number_format($new_time, 2) . " seconds</strong><br>";
                    echo "Speed improvement: <strong>" . number_format($speed_improvement, 1) . "x faster</strong><br>";
                    echo "Time saved: <strong>" . number_format($time_saved, 2) . " seconds</strong><br>";
                    
                    if ($test_count >= 50) {
                        echo "For a typical class of 50 students: <strong>" . number_format($time_saved * (50/$test_count), 1) . " seconds saved</strong>";
                    }
                    echo "</div>";
                    
                    // Show detailed breakdown
                    if (!empty($bulk_results['errors'])) {
                        echo "<h5>❌ Errors Encountered:</h5>";
                        echo "<ul>";
                        foreach (array_slice($bulk_results['errors'], 0, 10) as $error) {
                            echo "<li>" . htmlspecialchars($error) . "</li>";
                        }
                        if (count($bulk_results['errors']) > 10) {
                            echo "<li>... and " . (count($bulk_results['errors']) - 10) . " more errors</li>";
                        }
                        echo "</ul>";
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Performance Test</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Meeting ID</label>
                            <input type="text" name="meeting_id" class="form-control" 
                                   placeholder="Enter meeting ID for testing" 
                                   value="<?= $_POST['meeting_id'] ?? '' ?>" required>
                            <div class="form-text">Use a test meeting ID to avoid affecting real registrations</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Number of Test Students</label>
                            <select name="test_count" class="form-control" required>
                                <option value="10" <?= ($_POST['test_count'] ?? '') == '10' ? 'selected' : '' ?>>10 students</option>
                                <option value="25" <?= ($_POST['test_count'] ?? '') == '25' ? 'selected' : '' ?>>25 students</option>
                                <option value="50" <?= ($_POST['test_count'] ?? '') == '50' ? 'selected' : '' ?>>50 students</option>
                                <option value="100" <?= ($_POST['test_count'] ?? '') == '100' ? 'selected' : '' ?>>100 students</option>
                            </select>
                            <div class="form-text">Larger counts show more dramatic improvements</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" name="test_performance" class="btn btn-primary">
                            🚀 Run Performance Test
                        </button>
                        <a href="Home/index.php" class="btn btn-secondary">← Back to Registration</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h3>Optimization Features Implemented</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>🔧 Backend Optimizations</h5>
                        <ul>
                            <li>✅ <strong>Meeting Details Caching</strong> - Fetch once, use for all students</li>
                            <li>✅ <strong>Bulk API Processing</strong> - Optimized batch handling</li>
                            <li>✅ <strong>Reduced Logging</strong> - Summary logging instead of per-student</li>
                            <li>✅ <strong>Database Optimization</strong> - Batch checks and inserts</li>
                            <li>✅ <strong>Rate Limit Management</strong> - Strategic pauses between batches</li>
                            <li>✅ <strong>Occurrence Pre-calculation</strong> - One-time recurring meeting handling</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>💻 Frontend Enhancements</h5>
                        <ul>
                            <li>✅ <strong>Progressive Loading</strong> - Real-time status updates</li>
                            <li>✅ <strong>Better User Feedback</strong> - Detailed progress messages</li>
                            <li>✅ <strong>Form Validation</strong> - Prevent incomplete submissions</li>
                            <li>✅ <strong>Comprehensive Results</strong> - Success/error breakdowns</li>
                            <li>✅ <strong>Enhanced Error Handling</strong> - Clear error messages</li>
                            <li>✅ <strong>Performance Monitoring</strong> - Speed testing capabilities</li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-success mt-3">
                    <h5>📈 Expected Performance Improvements</h5>
                    <ul class="mb-0">
                        <li><strong>10-20x faster</strong> for bulk registrations of 50+ students</li>
                        <li><strong>Reduced server load</strong> through optimized API calls</li>
                        <li><strong>Better reliability</strong> with enhanced error handling</li>
                        <li><strong>Improved user experience</strong> with real-time feedback</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
