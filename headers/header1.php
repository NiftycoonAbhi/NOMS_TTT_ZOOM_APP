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
            --primary-bg: #1a2a3a;
            /* Darker background for better contrast */
            --accent-color: #4fc3f7;
            /* Brighter accent color */
            --text-color: #f8f9fa;
            /* Light text for maximum contrast */
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

        .nav-link:hover,
        .nav-link:focus {
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Centered Navigation Items -->
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="https://zoom.us/meeting/schedule" target="_blank"
                            rel="noopener noreferrer">
                            Create Meeting
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../home/index">Main Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/admin_dashboard">Admin Dashboard</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS for active link highlighting -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
        });
    </script>
</body>

</html>