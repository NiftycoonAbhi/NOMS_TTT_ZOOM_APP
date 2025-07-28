<?php
// Handle logout and switch account actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        // Clear session and redirect to login
        session_start();
        session_destroy();
        header('Location: select_zoom_account.php');
        exit;
    } elseif (isset($_POST['switch_account'])) {
        // Clear current account from session and redirect to account selection
        session_start();
        unset($_SESSION['selected_zoom_account']);
        header('Location: select_zoom_account.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTT Academy</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Poppins for better readability -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-bg: #1a2a3a;  /* Darker background for better contrast */
            --accent-color: #4fc3f7; /* Brighter accent color */
            --text-color: #f8f9fa;   /* Light text for maximum contrast */
            --hover-color: #3da5d9;
        }
        
        body {
            padding-top: 70px;
            font-family: 'Poppins', sans-serif;
        }
        
        .navbar {
            background-color: var(--primary-bg);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            padding: 0.8rem 1.5rem;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            color: var(--text-color) !important;
            letter-spacing: 0.5px;
        }
        
        .navbar-brand span {
            color: var(--accent-color);
            margin-right: 0.25rem;
        }
        
        .navbar-brand i {
            margin-right: 0.75rem;
            color: var(--accent-color);
            font-size: 1.8rem;
        }
        
        .nav-link {
            font-weight: 500;
            font-size: 1.05rem;
            padding: 0.6rem 1.5rem;
            margin: 0 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            position: relative;
            color: var(--text-color) !important;
            letter-spacing: 0.3px;
        }
        
        .nav-link:hover, .nav-link:focus {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            color: var(--accent-color) !important;
            font-weight: 600;
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 3px;
        }
        
        .navbar-toggler {
            border: none;
            padding: 0.6rem;
            color: var(--text-color);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.3);
        }
        
        /* Mobile menu adjustments */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background-color: var(--primary-bg);
                padding: 1rem;
                border-radius: 0 0 10px 10px;
                margin-top: 10px;
            }
            
            .nav-link {
                margin: 0.5rem 0;
                padding: 0.8rem 1.25rem;
                font-size: 1.1rem;
            }
            
            .nav-link:hover {
                transform: translateX(5px);
            }
            
            .nav-link.active::after {
                left: 20px;
                transform: none;
                bottom: 50%;
                transform: translateY(50%);
                width: 3px;
                height: 20px;
            }
        }
        
        /* Dropdown Menu Styles */
        .dropdown-menu {
            background-color: var(--primary-bg);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            color: black !important;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none !important;
            background: none !important;
            width: 100%;
            text-align: left;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: var(--accent-color) !important;
        }
        
        .dropdown-item.text-danger {
            color: #ff6b6b !important;
        }
        
        .dropdown-item.text-danger:hover {
            background-color: rgba(255, 107, 107, 0.1) !important;
            color: #ff5252 !important;
        }
        
        .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.15);
        }
        
        .dropdown-toggle::after {
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Fixed Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <!-- Logo/Brand with icon -->
           <a class="navbar-brand">
  <i class="fas fa-graduation-cap"></i>
  <span>TTT</span> Academy
</a>

            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Centered Navigation Items -->
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="https://zoom.us/meeting/schedule" target="_blank" rel="noopener noreferrer">
                            Create Meeting
                        </a>
                    </li>
                   <li class="nav-item">
                    <a class="nav-link" href="../Home/index">Main Dashboard</a>
                    </li>
                    <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard">Admin Dashboard</a>
                    </li>
                </ul>
                
                <!-- Right side - Logout Button -->
                <ul class="navbar-nav ms-auto">
                    <?php
                    // Check if multi-account config is available
                    if (file_exists(__DIR__ . '/../admin/includes/multi_account_config.php')) {
                        require_once __DIR__ . '/../admin/includes/multi_account_config.php';
                        $current_account = getCurrentZoomAccount();
                        if ($current_account): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-building"></i> <?= htmlspecialchars($current_account['name'] ?? 'Account') ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form method="POST" style="margin: 0;">
                                            <button type="submit" name="switch_account" class="dropdown-item">
                                                <i class="fas fa-exchange-alt"></i> Switch Account
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" style="margin: 0;">
                                            <button type="submit" name="logout" class="dropdown-item text-danger">
                                                <i class="fas fa-sign-out-alt"></i> Logout
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="select_zoom_account.php">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            </li>
                        <?php endif;
                    } ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS for active link highlighting -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            const currentPage = window.location.pathname.split('/').pop() || 'index';
            
            navLinks.forEach(link => {
                const linkPage = link.getAttribute('href');
                if (currentPage === linkPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
            
            // Handle logout and switch account actions
            const logoutForms = document.querySelectorAll('form[method="POST"]');
            logoutForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitButton = form.querySelector('button[type="submit"]');
                    
                    if (submitButton.name === 'logout') {
                        if (!confirm('Are you sure you want to logout?')) {
                            e.preventDefault();
                            return false;
                        }
                        // Redirect to select account page after logout
                        form.action = 'select_zoom_account.php';
                    } else if (submitButton.name === 'switch_account') {
                        // Redirect to select account page for switching
                        form.action = 'select_zoom_account.php';
                    }
                });
            });
            
            // Ensure dropdown works properly
            const dropdownToggle = document.getElementById('accountDropdown');
            if (dropdownToggle) {
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdownMenu = this.nextElementSibling;
                    const isOpen = dropdownMenu.classList.contains('show');
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                    
                    // Toggle current dropdown
                    if (!isOpen) {
                        dropdownMenu.classList.add('show');
                    }
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });
        });
    </script>
</body>
</html>