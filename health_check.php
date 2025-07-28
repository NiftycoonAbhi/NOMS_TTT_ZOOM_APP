<?php
/**
 * TTT ZOOM System Health Check Script
 * Run this script to verify all components are working properly
 */

// Include configuration
require_once __DIR__ . '/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SystemHealthCheck {
    private $results = [];
    
    public function runAllTests() {
        $this->testDatabaseConnection();
        $this->testRequiredFiles();
        $this->testFunctions();
        $this->testDirectoryPermissions();
        $this->testZoomApiCredentials();
        
        return $this->results;
    }
    
    private function addResult($test, $status, $message, $details = '') {
        $this->results[] = [
            'test' => $test,
            'status' => $status, // 'pass', 'fail', 'warning'
            'message' => $message,
            'details' => $details
        ];
    }
    
    private function testDatabaseConnection() {
        try {
            require_once __DIR__ . '/db/dbconn.php';
            
            $this->addResult(
                'Database Connection',
                'pass',
                'Database connection successful',
                'Connected to ' . DB_NAME . ' on ' . DB_HOST
            );
            
            // Test if main tables exist
            $tables = ['student_details', 'zoom', 'meeting_att_head', 'zoom_api_credentials'];
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows > 0) {
                    $this->addResult(
                        "Table: $table",
                        'pass',
                        "Table $table exists"
                    );
                } else {
                    $this->addResult(
                        "Table: $table",
                        'fail',
                        "Table $table is missing"
                    );
                }
            }
            
        } catch (Exception $e) {
            $this->addResult(
                'Database Connection',
                'fail',
                'Database connection failed',
                $e->getMessage()
            );
        }
    }
    
    private function testRequiredFiles() {
        $requiredFiles = [
            'config.php' => 'Application configuration',
            'db/dbconn.php' => 'Database connection',
            'common/php/niftycoon_functions.php' => 'Core functions',
            'common/css/bootstrap.min.css' => 'Bootstrap CSS',
            'common/js/bootstrap.bundle.min.js' => 'Bootstrap JS',
            'admin/includes/zoom_api.php' => 'Zoom API functions',
            'admin/includes/multi_account_config.php' => 'Multi-account config'
        ];
        
        foreach ($requiredFiles as $file => $description) {
            if (file_exists(__DIR__ . '/' . $file)) {
                $this->addResult(
                    "File: $file",
                    'pass',
                    "$description file exists"
                );
            } else {
                $this->addResult(
                    "File: $file",
                    'fail',
                    "$description file is missing"
                );
            }
        }
    }
    
    private function testFunctions() {
        // Test NifTycoon functions
        if (file_exists(__DIR__ . '/common/php/niftycoon_functions.php')) {
            include_once __DIR__ . '/common/php/niftycoon_functions.php';
            
            $functions = [
                'NifTycoon_Get_Count',
                'NifTycoon_Insert_Data',
                'NifTycoon_Select_Data',
                'NifTycoon_Update_Data',
                'get_date_time'
            ];
            
            foreach ($functions as $func) {
                if (function_exists($func)) {
                    $this->addResult(
                        "Function: $func",
                        'pass',
                        "Function $func is available"
                    );
                } else {
                    $this->addResult(
                        "Function: $func",
                        'fail',
                        "Function $func is missing"
                    );
                }
            }
        }
        
        // Test config functions
        $configFunctions = ['getConfig', 'isDebugMode', 'getAppVersion', 'logEvent'];
        foreach ($configFunctions as $func) {
            if (function_exists($func)) {
                $this->addResult(
                    "Config Function: $func",
                    'pass',
                    "Config function $func is available"
                );
            } else {
                $this->addResult(
                    "Config Function: $func",
                    'fail',
                    "Config function $func is missing"
                );
            }
        }
    }
    
    private function testDirectoryPermissions() {
        $directories = [
            'logs' => 'Log files directory',
            'data' => 'Data files directory',
            'common' => 'Common assets directory'
        ];
        
        foreach ($directories as $dir => $description) {
            $path = __DIR__ . '/' . $dir;
            
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    $this->addResult(
                        "Directory: $dir",
                        'pass',
                        "Created $description"
                    );
                } else {
                    $this->addResult(
                        "Directory: $dir",
                        'fail',
                        "Cannot create $description"
                    );
                    continue;
                }
            }
            
            if (is_writable($path)) {
                $this->addResult(
                    "Permissions: $dir",
                    'pass',
                    "$description is writable"
                );
            } else {
                $this->addResult(
                    "Permissions: $dir",
                    'warning',
                    "$description is not writable"
                );
            }
        }
    }
    
    private function testZoomApiCredentials() {
        try {
            require_once __DIR__ . '/db/dbconn.php';
            
            $result = $conn->query("SELECT COUNT(*) as count FROM zoom_api_credentials WHERE is_active = 1");
            if ($result) {
                $row = $result->fetch_assoc();
                $count = $row['count'];
                
                if ($count > 0) {
                    $this->addResult(
                        'Zoom API Credentials',
                        'pass',
                        "Found $count active Zoom API credential(s)"
                    );
                } else {
                    $this->addResult(
                        'Zoom API Credentials',
                        'warning',
                        'No active Zoom API credentials found',
                        'Add Zoom API credentials in admin panel'
                    );
                }
            }
            
        } catch (Exception $e) {
            $this->addResult(
                'Zoom API Credentials',
                'fail',
                'Cannot check Zoom API credentials',
                $e->getMessage()
            );
        }
    }
}

// Run tests if this file is accessed directly
if (basename($_SERVER['SCRIPT_NAME']) === 'health_check.php') {
    $healthCheck = new SystemHealthCheck();
    $results = $healthCheck->runAllTests();
    
    $totalTests = count($results);
    $passedTests = count(array_filter($results, function($r) { return $r['status'] === 'pass'; }));
    $failedTests = count(array_filter($results, function($r) { return $r['status'] === 'fail'; }));
    $warningTests = count(array_filter($results, function($r) { return $r['status'] === 'warning'; }));
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TTT ZOOM System Health Check</title>
        <link href="common/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .status-pass { color: #28a745; }
            .status-fail { color: #dc3545; }
            .status-warning { color: #ffc107; }
            .health-score {
                font-size: 2rem;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3>TTT ZOOM System Health Check</h3>
                            <div class="health-score <?php echo $failedTests > 0 ? 'status-fail' : ($warningTests > 0 ? 'status-warning' : 'status-pass'); ?>">
                                <?php echo round(($passedTests / $totalTests) * 100); ?>%
                            </div>
                        </div>
                        <div class="card-body">
                            
                            <!-- Summary -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h5 class="status-pass"><?php echo $passedTests; ?></h5>
                                        <small>Passed</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h5 class="status-fail"><?php echo $failedTests; ?></h5>
                                        <small>Failed</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h5 class="status-warning"><?php echo $warningTests; ?></h5>
                                        <small>Warnings</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h5><?php echo $totalTests; ?></h5>
                                        <small>Total Tests</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Test Results -->
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Test</th>
                                            <th>Status</th>
                                            <th>Message</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['test']); ?></td>
                                                <td>
                                                    <span class="status-<?php echo $result['status']; ?>">
                                                        <?php 
                                                        echo $result['status'] === 'pass' ? '✅' : 
                                                            ($result['status'] === 'fail' ? '❌' : '⚠️');
                                                        echo ' ' . ucfirst($result['status']); 
                                                        ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($result['message']); ?></td>
                                                <td><?php echo htmlspecialchars($result['details']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- System Information -->
                            <div class="mt-4">
                                <h5>System Information</h5>
                                <table class="table table-sm">
                                    <tr><th>Environment:</th><td><?php echo ENVIRONMENT; ?></td></tr>
                                    <tr><th>Debug Mode:</th><td><?php echo DEBUG_MODE ? 'Enabled' : 'Disabled'; ?></td></tr>
                                    <tr><th>PHP Version:</th><td><?php echo PHP_VERSION; ?></td></tr>
                                    <tr><th>App Version:</th><td><?php echo APP_VERSION; ?></td></tr>
                                    <tr><th>Database:</th><td><?php echo DB_NAME . ' on ' . DB_HOST; ?></td></tr>
                                    <tr><th>Timezone:</th><td><?php echo date_default_timezone_get(); ?></td></tr>
                                    <tr><th>Memory Limit:</th><td><?php echo ini_get('memory_limit'); ?></td></tr>
                                    <tr><th>Max File Size:</th><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
                                </table>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="mt-4">
                                <h5>Quick Actions</h5>
                                <a href="install.php" class="btn btn-primary me-2">Run Installer</a>
                                <a href="index.php" class="btn btn-success me-2">Go to Main Page</a>
                                <a href="admin/admin_dashboard.php" class="btn btn-info me-2">Admin Dashboard</a>
                                <button onclick="location.reload()" class="btn btn-secondary">Refresh Tests</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="common/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>
