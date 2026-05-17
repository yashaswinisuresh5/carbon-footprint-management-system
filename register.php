<?php
// register.php
// User Registration Portal
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
} elseif (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Server-side validations
    if (empty($name) || !$age || !$email || empty($password) || empty($location)) {
        $error_msg = "Please fill in all fields with valid information.";
    } elseif ($age < 5 || $age > 120) {
        $error_msg = "Please enter a realistic age.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if email already exists
            $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                $error_msg = "This email is already registered. Please login instead.";
            } else {
                // Hash the password securely
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // SQL INSERT statement with prepared variables
                $insert_stmt = $pdo->prepare("INSERT INTO users (name, age, email, password, location) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$name, $age, $email, $hashed_password, $location]);
                
                // Get the generated user_id
                $user_id = $pdo->lastInsertId();
                
                // Automatically log user in and create session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_location'] = $location;
                
                // Redirect to dashboard with a welcome parameter
                header("Location: dashboard.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Check theme cookie
$theme_class = "";
if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') {
    $theme_class = 'data-theme="dark"';
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo $theme_class; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoTrace - Register Account</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Loading Screen -->
    <div class="loader-overlay">
        <div class="loader-spinner"></div>
        <div class="loader-logo">
            <i class="fas fa-leaf"></i>
            <span>EcoTrace</span>
        </div>
    </div>

    <div class="auth-wrapper">
        <!-- Sidebar Info Section -->
        <div class="auth-sidebar">
            <div class="auth-sidebar-logo">
                <i class="fas fa-leaf"></i>
                <span>EcoTrace</span>
            </div>
            <div class="auth-sidebar-content">
                <h1>Join the Eco-Revolution today.</h1>
                <p>Register an account to log activities, calculate emissions dynamically from our SQL backend, generate customized monthly reports, and receive database-driven sustainability advice.</p>
                
                <div style="margin-top: 40px; display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background-color: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                            <i class="fas fa-database"></i>
                        </div>
                        <div>
                            <h4 style="font-weight: 700; color: white;">Real-Time DB Storage</h4>
                            <p style="font-size: 13px; color: #9ca3af;">All logs instantly write via prepared statements.</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background-color: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h4 style="font-weight: 700; color: white;">Dynamic Trend Analytics</h4>
                            <p style="font-size: 13px; color: #9ca3af;">Interactive line charts and gauge meter indicators.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="auth-sidebar-footer">
                <p>&copy; <?php echo date('Y'); ?> EcoTrace DBMS System.</p>
            </div>
        </div>

        <!-- Signup Form Panel -->
        <div class="auth-panel">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Create Account</h2>
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
                
                <!-- Error Alert Box -->
                <?php if (!empty($error_msg)): ?>
                    <div style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo $error_msg; ?></span>
                    </div>
                <?php endif; ?>
                
                <form id="registerForm" action="register.php" method="POST">
                    <!-- Name Input -->
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <div class="form-control-wrapper">
                            <i class="far fa-user"></i>
                            <input class="form-control" type="text" id="name" name="name" placeholder="John Doe" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Age & Location row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="age">Age</label>
                            <div class="form-control-wrapper">
                                <i class="far fa-calendar-alt"></i>
                                <input class="form-control" style="padding-left: 42px;" type="number" id="age" name="age" min="5" max="120" placeholder="25" required value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="location">Location</label>
                            <div class="form-control-wrapper">
                                <i class="fas fa-map-marker-alt"></i>
                                <input class="form-control" style="padding-left: 42px;" type="text" id="location" name="location" placeholder="California" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Input -->
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="form-control-wrapper">
                            <i class="far fa-envelope"></i>
                            <input class="form-control" type="email" id="email" name="email" placeholder="john@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Password Input -->
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="form-control-wrapper">
                            <i class="fas fa-lock"></i>
                            <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" required minlength="6">
                        </div>
                        <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">Must be at least 6 characters.</span>
                    </div>
                    
                    <button class="auth-btn" type="submit">Sign Up Account</button>
                    
                    <!-- Return to landing link -->
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="index.php" style="font-size: 13px; color: var(--text-muted);"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Optional client-side quick checks
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const age = parseInt(document.getElementById('age').value);
            if (isNaN(age) || age < 5 || age > 120) {
                e.preventDefault();
                showToast("Validation Error", "Please provide a realistic age.", "error");
            }
        });
    </script>
</body>
</html>
