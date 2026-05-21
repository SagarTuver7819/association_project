<?php
// index.php
// Login page for Association Management System

require_once 'config/db.php';

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

$pageTitle = 'Login';
require_once 'includes/header.php';
?>

<div class="login-wrapper">
    <!-- Animated background organic glowing blobs -->
    <div class="login-bg-blob blob-1"></div>
    <div class="login-bg-blob blob-2"></div>
    <div class="login-bg-blob blob-3"></div>

    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="fa-solid fa-leaf"></i>
            </div>
            <h2 class="login-title">Green City</h2>
            <p class="login-subtitle">Association Management System</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="login-alert alert-danger">
                <i class="fa-solid fa-circle-exclamation alert-icon"></i>
                <div class="alert-message"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <form action="index.php" method="POST" autocomplete="off" class="login-form">
            <div class="login-form-group">
                <label for="username" class="login-label">Username</label>
                <div class="login-input-wrapper">
                    <span class="login-input-icon"><i class="fa-solid fa-user"></i></span>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="login-input-control" 
                        placeholder="Enter username" 
                        required 
                        autofocus
                    >
                </div>
            </div>
            
            <div class="login-form-group">
                <label for="password" class="login-label">Password</label>
                <div class="login-input-wrapper">
                    <span class="login-input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="login-input-control" 
                        placeholder="Enter password" 
                        required
                    >
                </div>
            </div>
            
            <button type="submit" class="btn btn-accent login-submit-btn">
                <span>Access Dashboard</span>
                <i class="fa-solid fa-right-to-bracket"></i>
            </button>
        </form>
        
        <div class="login-footer-badge">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Default Login: <strong>admin</strong> / <strong>admin123</strong></span>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
