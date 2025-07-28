<?php
/**
 * TTT ZOOM System Installation Script
 * Version: 2.0.0
 * 
 * This script helps set up the TTT ZOOM system for both local and live environments
 */

require_once __DIR__ . '/config.php';

// Set execution time limit for installation
set_time_limit(300);

class TTTZoomInstaller {
    private $errors = [];
    private $warnings = [];
    private $success = [];
    
    public function __construct() {
        $this->checkEnvironment();
    }
    
    /**
     * Check system requirements
     */
    private function checkEnvironment() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $this->errors[] = 'PHP 8.0+ is required. Current version: ' . PHP_VERSION;
        } else {
            $this->success[] = 'PHP version check passed: ' . PHP_VERSION;
        }
        
        // Check required PHP extensions
        $required_extensions = ['mysqli', 'json', 'curl', 'openssl', 'mbstring'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $this->errors[] = "Required PHP extension missing: $ext";
            } else {
                $this->success[] = "PHP extension available: $ext";
            }
        }
        
        // Check directory permissions
        $directories = [LOGS_PATH, DATABASE_PATH, COMMON_PATH];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $this->errors[] = "Cannot create directory: $dir";
                } else {
                    $this->success[] = "Created directory: $dir";
                }
            } else {
                $this->success[] = "Directory exists: $dir";
            }
            
            if (!is_writable($dir)) {
                $this->warnings[] = "Directory not writable: $dir";
            }
        }
        
        // Check database connection
        $this->checkDatabaseConnection();
    }
    
    /**
     * Check database connection
     */
    private function checkDatabaseConnection() {
        try {
            $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
            
            if ($conn->connect_error) {
                $this->errors[] = 'Database connection failed: ' . $conn->connect_error;
                return false;
            }
            
            $this->success[] = 'Database connection successful';
            
            // Check if database exists
            $result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
            if ($result->num_rows == 0) {
                $this->warnings[] = "Database '" . DB_NAME . "' does not exist. You may need to create it.";
            } else {
                $this->success[] = "Database '" . DB_NAME . "' exists";
            }
            
            $conn->close();
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = 'Database connection error: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Install/Update database schema
     */
    public function installDatabase() {
        try {
            // First check if we can connect
            $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
            if ($conn->connect_error) {
                throw new Exception('Cannot connect to database: ' . $conn->connect_error);
            }
            
            // Create database if it doesn't exist
            $conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $conn->select_db(DB_NAME);
            
            // Check if we have the complete SQL file
            $sqlFile = DATABASE_PATH . '/ttt_zoom_complete.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception('SQL file not found: ' . $sqlFile);
            }
            
            // Read and execute SQL file
            $sql = file_get_contents($sqlFile);
            
            // Split SQL into individual queries
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            
            $successful = 0;
            $failed = 0;
            
            foreach ($queries as $query) {
                if (empty($query) || strpos($query, '--') === 0) continue;
                
                if ($conn->query($query)) {
                    $successful++;
                } else {
                    $failed++;
                    $this->warnings[] = 'SQL query failed: ' . $conn->error;
                }
            }
            
            $this->success[] = "Database installation completed. Successful: $successful, Failed: $failed";
            $conn->close();
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = 'Database installation failed: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Test Zoom API connectivity
     */
    public function testZoomAPI() {
        try {
            // Include zoom API functions
            require_once ADMIN_PATH . '/includes/zoom_api.php';
            
            // Get credentials from database
            $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            $result = $conn->query("SELECT * FROM zoom_api_credentials WHERE is_active = 1 LIMIT 1");
            
            if ($result->num_rows == 0) {
                $this->warnings[] = 'No active Zoom API credentials found in database';
                return false;
            }
            
            $credentials = $result->fetch_assoc();
            
            // Set session credentials for testing
            $_SESSION['current_zoom_account'] = $credentials;
            
            // Try to get access token
            $token = getZoomAccessToken();
            
            if ($token) {
                $this->success[] = 'Zoom API connectivity test passed';
                return true;
            } else {
                $this->warnings[] = 'Zoom API connectivity test failed - check credentials';
                return false;
            }
            
        } catch (Exception $e) {
            $this->warnings[] = 'Zoom API test error: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Create default admin user (if needed)
     */
    public function createDefaultAdmin() {
        try {
            $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            // Check if admin table exists and create default admin
            $result = $conn->query("SHOW TABLES LIKE 'admin_users'");
            if ($result->num_rows > 0) {
                // Check if any admin exists
                $adminResult = $conn->query("SELECT COUNT(*) as count FROM admin_users");
                $adminCount = $adminResult->fetch_assoc()['count'];
                
                if ($adminCount == 0) {
                    // Create default admin
                    $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, email, created_at) VALUES (?, ?, ?, NOW())");
                    $username = 'admin';
                    $email = 'admin@ttt-zoom.local';
                    $stmt->bind_param("sss", $username, $defaultPassword, $email);
                    
                    if ($stmt->execute()) {
                        $this->success[] = 'Default admin user created (username: admin, password: admin123)';
                    } else {
                        $this->warnings[] = 'Failed to create default admin user';
                    }
                }
            }
            
            $conn->close();
            return true;
            
        } catch (Exception $e) {
            $this->warnings[] = 'Admin user creation error: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Generate installation report
     */
    public function getReport() {
        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'success' => $this->success
        ];
    }
    
    /**
     * Check if installation is successful
     */
    public function isSuccessful() {
        return empty($this->errors);
    }
}

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Run installation if requested
$installer = new TTTZoomInstaller();
$installDb = false;
$testZoom = false;
$createAdmin = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['install_db'])) {
        $installDb = $installer->installDatabase();
    }
    
    if (isset($_POST['test_zoom'])) {
        $testZoom = $installer->testZoomAPI();
    }
    
    if (isset($_POST['create_admin'])) {
        $createAdmin = $installer->createDefaultAdmin();
    }
}

$report = $installer->getReport();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTT ZOOM System Installer</title>
    <link href="common/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-success { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3>TTT ZOOM System Installer</h3>
                        <p class="mb-0">Version 2.0.0 - System Setup and Configuration</p>
                    </div>
                    <div class="card-body">
                        
                        <!-- System Check Results -->
                        <div class="mb-4">
                            <h5>System Requirements Check</h5>
                            
                            <?php if (!empty($report['success'])): ?>
                                <div class="alert alert-success">
                                    <strong>✅ Passed Checks:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($report['success'] as $success): ?>
                                            <li><?php echo htmlspecialchars($success); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($report['warnings'])): ?>
                                <div class="alert alert-warning">
                                    <strong>⚠️ Warnings:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($report['warnings'] as $warning): ?>
                                            <li><?php echo htmlspecialchars($warning); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($report['errors'])): ?>
                                <div class="alert alert-danger">
                                    <strong>❌ Errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($report['errors'] as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Installation Actions -->
                        <?php if ($installer->isSuccessful()): ?>
                            <div class="mb-4">
                                <h5>Installation Actions</h5>
                                <form method="POST" class="d-inline-block me-2">
                                    <button type="submit" name="install_db" class="btn btn-primary">
                                        Install/Update Database
                                    </button>
                                </form>
                                
                                <form method="POST" class="d-inline-block me-2">
                                    <button type="submit" name="test_zoom" class="btn btn-info">
                                        Test Zoom API
                                    </button>
                                </form>
                                
                                <form method="POST" class="d-inline-block me-2">
                                    <button type="submit" name="create_admin" class="btn btn-warning">
                                        Create Default Admin
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <strong>Installation Cannot Continue</strong><br>
                                Please fix the errors above before proceeding.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Next Steps -->
                        <?php if ($installer->isSuccessful()): ?>
                            <div class="mt-4">
                                <h5>Next Steps</h5>
                                <ol>
                                    <li>Ensure the database is installed and up to date</li>
                                    <li>Configure your Zoom API credentials in the admin panel</li>
                                    <li>Test the Zoom API connectivity</li>
                                    <li>Access the system:
                                        <ul>
                                            <li><a href="index.php" class="btn btn-sm btn-outline-primary">Main Page</a></li>
                                            <li><a href="admin/admin_dashboard.php" class="btn btn-sm btn-outline-success">Admin Dashboard</a></li>
                                            <li><a href="Home/index.php" class="btn btn-sm btn-outline-info">Student Registration</a></li>
                                        </ul>
                                    </li>
                                </ol>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Configuration Info -->
                        <div class="mt-4">
                            <h5>Current Configuration</h5>
                            <table class="table table-sm">
                                <tr><th>Environment:</th><td><?php echo ENVIRONMENT; ?></td></tr>
                                <tr><th>Debug Mode:</th><td><?php echo DEBUG_MODE ? 'Enabled' : 'Disabled'; ?></td></tr>
                                <tr><th>Database Host:</th><td><?php echo DB_HOST; ?></td></tr>
                                <tr><th>Database Name:</th><td><?php echo DB_NAME; ?></td></tr>
                                <tr><th>App Version:</th><td><?php echo APP_VERSION; ?></td></tr>
                                <tr><th>PHP Version:</th><td><?php echo PHP_VERSION; ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="common/js/bootstrap.bundle.min.js"></script>
</body>
</html>
