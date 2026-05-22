<?php
// includes/header.php
// Common header for the Association Management System

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in and not on the login page
$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $currentPage !== 'index.php') {
    header('Location: index.php');
    exit;
}

// Set default page title if not already specified
if (!isset($pageTitle)) {
    $pageTitle = 'Association Management System';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Green City</title>
    <!-- FontAwesome for Premium Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables & Buttons Styles -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Responsive Premium Navbar -->
        <header class="app-navbar no-print">
            <a href="dashboard.php" class="nav-brand">
                <i class="fa-solid fa-mountain-city"></i>
                <span>Green City Association</span>
            </a>
            
            <button class="menu-toggle" id="menu-toggle-btn" aria-label="Toggle navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
            
            <nav class="nav-links" id="nav-links-menu">
                <a href="dashboard.php" class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
                <a href="plots.php" class="nav-link <?php echo ($currentPage == 'plots.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-map-location-dot"></i> Plots Form
                </a>
                <a href="receipts.php" class="nav-link <?php echo ($currentPage == 'receipts.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-receipt"></i> Receipts
                </a>
                <a href="document_types.php" class="nav-link <?php echo ($currentPage == 'document_types.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-signature"></i> Dastavej Master
                </a>
                <a href="plot_statuses.php" class="nav-link <?php echo ($currentPage == 'plot_statuses.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-list-check"></i> Plot Status Master
                </a>
                <a href="fee_masters.php" class="nav-link <?php echo ($currentPage == 'fee_masters.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gears"></i> Fee Masters
                </a>
                <a href="due_masters.php" class="nav-link <?php echo ($currentPage == 'due_masters.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Due Master
                </a>
                <div class="nav-user-mobile-only">
                    <span><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </nav>
            
            <div class="nav-user nav-user-desktop-only">
                <span><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </header>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle-btn');
            const navLinks = document.getElementById('nav-links-menu');
            
            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    navLinks.classList.toggle('active');
                    const icon = menuToggle.querySelector('i');
                    if (navLinks.classList.contains('active')) {
                        icon.className = 'fa-solid fa-xmark';
                    } else {
                        icon.className = 'fa-solid fa-bars';
                    }
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (!menuToggle.contains(event.target) && !navLinks.contains(event.target)) {
                        if (navLinks.classList.contains('active')) {
                            navLinks.classList.remove('active');
                            const icon = menuToggle.querySelector('i');
                            icon.className = 'fa-solid fa-bars';
                        }
                    }
                });
            }
        });
        </script>
        <?php endif; ?>
        
        <main class="<?php echo ($currentPage == 'index.php') ? 'login-main' : 'main-content'; ?>">
