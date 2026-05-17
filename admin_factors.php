<?php
// admin_factors.php
// Dynamic Emission Catalog Multiplier Editor (CRUD: R, U) - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force administrator login check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_name = $_SESSION['admin_name'];
$error_msg = "";
$success_msg = "";

// ----------------------------------------------------
// DYNAMIC COEFFICIENT UPDATE CONTROLLER (CRUD: Update)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_factor'])) {
    $factor_id = filter_input(INPUT_POST, 'factor_id', FILTER_VALIDATE_INT);
    $factor_val = filter_input(INPUT_POST, 'factor_value', FILTER_VALIDATE_FLOAT);
    
    if (!$factor_id || $factor_val === false || $factor_val < 0) {
        $error_msg = "Please provide a valid non-negative emission multiplier value.";
    } else {
        try {
            // Prepared SQL UPDATE query changing system-wide coefficients
            $stmt = $pdo->prepare("UPDATE emission_factors SET factor_value = ? WHERE factor_id = ?");
            $stmt->execute([$factor_val, $factor_id]);
            
            $success_msg = "Emission multiplier updated successfully. Platform calculations synchronized.";
        } catch (PDOException $e) {
            $error_msg = "Database Error updating standard coefficients: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// FETCH EMISSION COEFFICIENTS CATALOG
// ----------------------------------------------------
try {
    $stmt = $pdo->query("SELECT * FROM emission_factors ORDER BY category, sub_category ASC");
    $factors_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Critical database catalog retrieval error: " . $e->getMessage();
    $factors_list = [];
}

// Theme check
$theme_class = "";
$is_dark = false;
if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') {
    $theme_class = 'data-theme="dark"';
    $is_dark = true;
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo $theme_class; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoTrace Admin - Coefficient Catalog</title>
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

    <div class="app-container">
        <!-- Admin Navigation Sidebar -->
        <aside class="sidebar" style="border-right-color: var(--secondary-light);">
            <div class="sidebar-logo" style="color: var(--secondary);">
                <i class="fas fa-user-shield"></i>
                <span>EcoTrace Admin</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-label">System Control</li>
                <li class="menu-item">
                    <a href="admin_dashboard.php"><i class="fas fa-sliders-h"></i> <span>Overview Stats</span></a>
                </li>
                <li class="menu-item">
                    <a href="admin_users.php"><i class="fas fa-users-cog"></i> <span>Manage Users</span></a>
                </li>
                <li class="menu-item">
                    <a href="admin_activities.php"><i class="fas fa-globe-americas"></i> <span>Platform Activity</span></a>
                </li>
                <li class="menu-item active">
                    <a href="admin_factors.php"><i class="fas fa-balance-scale-right"></i> <span>Emission Catalog</span></a>
                </li>
                
                <li class="menu-label">Portal Return</li>
                <li class="menu-item">
                    <a href="dashboard.php" style="color: var(--primary);"><i class="fas fa-arrow-left"></i> <span>User Dashboard</span></a>
                </li>
            </ul>
            
            <div class="sidebar-user">
                <div class="user-avatar" style="background-color: var(--secondary-light); color: var(--secondary);"><i class="fas fa-user-cog"></i></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <a href="logout.php" class="logout-btn" title="Sign Out"><i class="fas fa-power-off"></i></a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="top-navbar">
                <div class="nav-left">
                    <h2>Emission Factors Catalog</h2>
                    <p>Adjust standard multipliers dynamically. Updates will instantly govern future carbon calculations.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="admin_dashboard.php" class="btn-secondary" style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-arrow-left"></i> Dashboard</a>
                </div>
            </header>

            <!-- Alerts -->
            <?php if (!empty($success_msg)): ?>
                <div style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <!-- SQL Coeff Catalog Card Table -->
            <section class="table-card" style="margin-bottom: 30px;">
                <div class="table-header">
                    <h3>System Multiplier Coefficients Catalog</h3>
                </div>

                <div class="custom-table-wrapper">
                    <?php if (count($factors_list) === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-calculator"></i>
                            <p>No factors recorded in system standard catalog.</p>
                        </div>
                    <?php else: ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Sector Category</th>
                                    <th>Sub-Category Name</th>
                                    <th>Multiplier Value (CO₂ / Unit)</th>
                                    <th>Standard Unit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($factors_list as $row): ?>
                                    <tr>
                                        <td style="font-family: monospace; font-weight: bold; color: var(--text-muted);">#<?php echo $row['factor_id']; ?></td>
                                        <td>
                                            <span class="badge <?php echo strtolower($row['category']); ?>" style="padding: 2px 6px; font-size: 10px;">
                                                <?php echo $row['category']; ?>
                                            </span>
                                        </td>
                                        <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['sub_category']); ?></td>
                                        
                                        <!-- Inline update form for administrators -->
                                        <td style="padding: 6px 16px;">
                                            <form action="admin_factors.php" method="POST" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                                                <input type="hidden" name="factor_id" value="<?php echo $row['factor_id']; ?>">
                                                <input class="search-input" 
                                                       style="width: 100px; padding: 4px 10px; height: 32px; font-family: monospace; font-weight: bold;" 
                                                       type="number" 
                                                       step="0.0001" 
                                                       name="factor_value" 
                                                       value="<?php echo htmlspecialchars($row['factor_value']); ?>" 
                                                       required>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 13px;">kg CO₂ / <?php echo htmlspecialchars($row['unit']); ?></td>
                                        <td>
                                                <button class="btn-primary" 
                                                        style="background: linear-gradient(135deg, var(--secondary) 0%, #2563eb 100%); padding: 6px 12px; font-size: 11px; height: 32px;" 
                                                        type="submit" 
                                                        name="update_factor">
                                                    <i class="fas fa-save"></i> Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
