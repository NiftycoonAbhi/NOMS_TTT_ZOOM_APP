<?php
/**
 * TTT ZOOM Attendance Management System - Main Landing Page
 * Version: 2.0.0
 * Professional multi-account Zoom meeting management platform
 */

// Include application configuration
require_once __DIR__ . '/config.php';

// Log page access
logEvent('Main landing page accessed', 'DEBUG', [
    'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo APP_DESCRIPTION; ?>">
    <meta name="author" content="<?php echo APP_AUTHOR; ?>">
    <meta name="version" content="<?php echo APP_VERSION; ?>">
    
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="common/images/favicon.ico">
    
    <!-- Stylesheets -->
    <link href="common/css/bootstrap.min.css" rel="stylesheet">
    <link href="common/css/style.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --admin-gradient: linear-gradient(45deg, #FF6B6B, #FF8E53);
            --admin-gradient-hover: linear-gradient(45deg, #FF5252, #FF7043);
            --student-gradient: linear-gradient(45deg, #4ECDC4, #44A08D);
            --student-gradient-hover: linear-gradient(45deg, #26C6DA, #388E3C);
            --registration-gradient: linear-gradient(45deg, #9C27B0, #673AB7);
            --registration-gradient-hover: linear-gradient(45deg, #8E24AA, #5E35B1);
        }
        
        .hero-section {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .card-custom:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .btn-custom {
            border-radius: 50px;
            padding: 15px 35px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-custom:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            transform: translate(-50%, -50%);
        }
        
        .btn-custom:hover:before {
            width: 300px;
            height: 300px;
        }
        
        .btn-admin {
            background: var(--admin-gradient);
            color: white;
        }
        
        .btn-admin:hover {
            background: var(--admin-gradient-hover);
            color: white;
            transform: scale(1.05);
        }
        
        .btn-student {
            background: var(--student-gradient);
            color: white;
        }
        
        .btn-student:hover {
            background: var(--student-gradient-hover);
            color: white;
            transform: scale(1.05);
        }
        
        .btn-registration {
            background: var(--registration-gradient);
            color: white;
        }
        
        .btn-registration:hover {
            background: var(--registration-gradient-hover);
            color: white;
            transform: scale(1.05);
        }
        
        .icon-large {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }
        
        .feature-list li:before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .system-info {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 0.8rem;
            z-index: 1000;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 0;
            }
            
            .icon-large {
                font-size: 3rem;
            }
            
            .btn-custom {
                padding: 12px 25px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-xl-10">
                    <!-- Header Section -->
                    <div class="text-white mb-5">
                        <h1 class="display-3 mb-4 fw-bold">
                            🎯 <?php echo APP_NAME; ?>
                        </h1>
                        <p class="lead mb-5 fs-4">
                            <?php echo APP_DESCRIPTION; ?>
                        </p>
                        <div class="badge bg-light text-dark px-3 py-2 mb-4">
                            Version <?php echo APP_VERSION; ?> • Professional Edition
                        </div>
                    </div>
                    
                    <!-- Main Portal Cards -->
                    <div class="row justify-content-center g-4">
                        <!-- Admin Portal -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card card-custom h-100">
                                <div class="card-body text-center p-4">
                                    <div class="icon-large text-danger">
                                        👨‍💼
                                    </div>
                                    <h3 class="card-title mb-4 fw-bold">Admin Portal</h3>
                                    <a href="admin/select_zoom_account.php" 
                                       class="btn btn-admin btn-custom btn-lg w-100">
                                        🔐 Admin Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Information (Debug Mode Only) -->
    <?php if (isDebugMode()): ?>
    <div class="system-info">
        <strong>System Info:</strong><br>
        Version: <?php echo APP_VERSION; ?><br>
        Environment: <?php echo ENVIRONMENT; ?><br>
        PHP: <?php echo PHP_VERSION; ?>
    </div>
    <?php endif; ?>
    
    <!-- Scripts -->
    <script src="common/js/bootstrap.bundle.min.js"></script>
    
    <!-- Analytics & Tracking (if needed) -->
    <script>
        // Page load analytics
        document.addEventListener('DOMContentLoaded', function() {
            console.log('<?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?> loaded successfully');
            
            // Track button clicks for analytics
            document.querySelectorAll('.btn-custom').forEach(button => {
                button.addEventListener('click', function() {
                    const portal = this.textContent.trim();
                    console.log('Portal accessed:', portal);
                });
            });
        });
    </script>
</body>
</html>

