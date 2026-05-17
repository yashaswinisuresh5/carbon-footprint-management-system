<?php
// reports.php
// Carbon Footprint Report Module - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_location = $_SESSION['user_location'];

// Get active target month/year parameters, default to current month
$target_month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT) ?? intval(date('n'));
$target_year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?? intval(date('Y'));

try {
    // 1. Fetch distinct months of activities to build a dropdown/select switcher
    $stmt = $pdo->prepare("SELECT DISTINCT MONTH(activity_date) as month, YEAR(activity_date) as year FROM activities WHERE user_id = ? ORDER BY year DESC, month DESC");
    $stmt->execute([$user_id]);
    $available_months = $stmt->fetchAll();
    
    // 2. Fetch compile list of monthly aggregate logs for the user (GROUP BY query)
    $stmt = $pdo->prepare("SELECT 
        MONTH(activity_date) as month, 
        YEAR(activity_date) as year, 
        SUM(co2_emitted) as total_co2, 
        COUNT(*) as activity_count 
        FROM activities 
        WHERE user_id = ? 
        GROUP BY YEAR(activity_date), MONTH(activity_date) 
        ORDER BY year DESC, month DESC");
    $stmt->execute([$user_id]);
    $monthly_aggregates = $stmt->fetchAll();
    
    // 3. Compile report metrics for the SELECTED month
    $stmt = $pdo->prepare("SELECT SUM(co2_emitted) as total, COUNT(*) as count FROM activities WHERE user_id = ? AND MONTH(activity_date) = ? AND YEAR(activity_date) = ?");
    $stmt->execute([$user_id, $target_month, $target_year]);
    $report_meta = $stmt->fetch();
    
    $report_total_co2 = $report_meta['total'] ? floatval($report_meta['total']) : 0.0;
    $report_count = intval($report_meta['count']);
    
    // 4. Category breakdown for the selected month to render sector tables & charts
    $stmt = $pdo->prepare("SELECT category, SUM(co2_emitted) as total, COUNT(*) as count FROM activities WHERE user_id = ? AND MONTH(activity_date) = ? AND YEAR(activity_date) = ? GROUP BY category ORDER BY total DESC");
    $stmt->execute([$user_id, $target_month, $target_year]);
    $category_breakdown = $stmt->fetchAll();
    
    // Calculate Sustainability Score for this specific report month
    $report_score = 100;
    if ($report_count > 0) {
        $stmt = $pdo->prepare("SELECT 
            SUM(CASE WHEN emission_level = 'Low' THEN 1 ELSE 0 END) as low_count,
            SUM(CASE WHEN emission_level = 'Medium' THEN 1 ELSE 0 END) as med_count,
            SUM(CASE WHEN emission_level = 'High' THEN 1 ELSE 0 END) as high_count
            FROM activities WHERE user_id = ? AND MONTH(activity_date) = ? AND YEAR(activity_date) = ?");
        $stmt->execute([$user_id, $target_month, $target_year]);
        $counts = $stmt->fetch();
        
        $lows = intval($counts['low_count']);
        $meds = intval($counts['med_count']);
        $highs = intval($counts['high_count']);
        
        $report_score = max(10, min(100, 80 + ($lows * 4) + ($meds * 1) - ($highs * 8)));
    }
    
    // Standard target allowance for comparison (e.g. 300kg CO2 per month allowance)
    $monthly_target = 300.0;
    $reduction_percentage = 0.0;
    if ($report_total_co2 > 0) {
        $reduction_percentage = (($monthly_target - $report_total_co2) / $monthly_target) * 100;
    }
    
    // Insert/Update reports table inside database to follow normalized schema standards!
    if ($report_count > 0) {
        $report_stmt = $pdo->prepare("INSERT INTO reports (user_id, report_month, report_year, total_co2, sustainability_score) 
            VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE total_co2 = VALUES(total_co2), sustainability_score = VALUES(sustainability_score)");
        $report_stmt->execute([$user_id, $target_month, $target_year, $report_total_co2, $report_score]);
    }
    
} catch (PDOException $e) {
    die("Database Error generating carbon report: " . $e->getMessage());
}

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$selected_month_name = $month_names[$target_month] ?? '';

// Chart prep
$chart_cats = [];
$chart_co2 = [];
foreach ($category_breakdown as $row) {
    $chart_cats[] = $row['category'];
    $chart_co2[] = floatval($row['total']);
}

// Theme set
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
    <title>EcoTrace - Monthly Carbon Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* PDF and Print styling additions */
        .report-grid {
            display: grid;
            grid-template-columns: 1.2fr 2fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        @media (max-width: 991px) {
            .report-grid {
                grid-template-columns: 1fr;
            }
        }
        .report-sheet {
            background-color: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            padding: 40px;
            box-shadow: var(--shadow-md);
            position: relative;
        }
        .report-sheet:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }
    </style>
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
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-leaf"></i>
                <span>EcoTrace</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-label">Main</li>
                <li class="menu-item">
                    <a href="dashboard.php"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
                </li>
                <li class="menu-item">
                    <a href="add_activity.php"><i class="fas fa-plus-circle"></i> <span>Log Activity</span></a>
                </li>
                <li class="menu-item">
                    <a href="history.php"><i class="fas fa-history"></i> <span>Activity Log</span></a>
                </li>
                
                <li class="menu-label">Insights</li>
                <li class="menu-item">
                    <a href="recommendations.php"><i class="fas fa-lightbulb"></i> <span>Recommendations</span></a>
                </li>
                <li class="menu-item active">
                    <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Monthly Reports</span></a>
                </li>
                <li class="menu-item">
                    <a href="leaderboard.php"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a>
                </li>
                
                <li class="menu-label">DBMS Administration</li>
                <li class="menu-item">
                    <a href="dbms_section.php"><i class="fas fa-project-diagram"></i> <span>DBMS Conceptual</span></a>
                </li>
                <li class="menu-item">
                    <a href="profile.php"><i class="far fa-user-circle"></i> <span>My Profile</span></a>
                </li>
            </ul>
            
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role">User</div>
                </div>
                <a href="logout.php" class="logout-btn" title="Sign Out"><i class="fas fa-power-off"></i></a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="top-navbar">
                <div class="nav-left">
                    <h2>Monthly Carbon Assessment</h2>
                    <p>Compile database-driven reports and generate high-fidelity print documents.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <button class="nav-action-btn" onclick="window.print()"><i class="fas fa-file-pdf"></i> Download PDF</button>
                </div>
            </header>

            <div class="report-grid">
                
                <!-- Left panel: Month switcher + Logged Months list -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <!-- Month Selector Card -->
                    <section class="table-card" style="padding: 20px;">
                        <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 12px;">Switch Assessment Month</h4>
                        <form action="reports.php" method="GET" style="display: flex; flex-direction: column; gap: 10px;">
                            <select class="filter-select" name="month" required>
                                <?php foreach ($month_names as $num => $name): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $target_month === $num ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select class="filter-select" name="year" required>
                                <option value="<?php echo date('Y'); ?>" <?php echo $target_year === intval(date('Y')) ? 'selected' : ''; ?>><?php echo date('Y'); ?></option>
                                <option value="<?php echo date('Y') - 1; ?>" <?php echo $target_year === (intval(date('Y')) - 1) ? 'selected' : ''; ?>><?php echo date('Y') - 1; ?></option>
                            </select>
                            
                            <button type="submit" class="btn-primary" style="padding: 10px; font-size: 13px; text-align: center;"><i class="fas fa-sync"></i> Refresh Report</button>
                        </form>
                    </section>

                    <!-- Summary table of all months in DB -->
                    <section class="table-card">
                        <div class="table-header" style="padding: 16px 20px;">
                            <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase;">Assessment Log History</h4>
                        </div>
                        <div class="custom-table-wrapper">
                            <?php if (count($monthly_aggregates) === 0): ?>
                                <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 12px;">No logged assessments.</div>
                            <?php else: ?>
                                <table class="custom-table" style="font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th style="padding: 10px 16px;">Month</th>
                                            <th style="padding: 10px 16px;">CO₂ Total</th>
                                            <th style="padding: 10px 16px;">View</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($monthly_aggregates as $agg): ?>
                                            <tr style="<?php echo ($agg['month'] == $target_month && $agg['year'] == $target_year) ? 'background-color: var(--primary-light); font-weight: bold;' : ''; ?>">
                                                <td style="padding: 10px 16px;"><?php echo $month_names[$agg['month']] . ' ' . $agg['year']; ?></td>
                                                <td style="padding: 10px 16px;"><?php echo number_format($agg['total_co2'], 1); ?> kg</td>
                                                <td style="padding: 10px 16px;">
                                                    <a href="reports.php?month=<?php echo $agg['month']; ?>&year=<?php echo $agg['year']; ?>" class="action-icon-btn" style="width: 26px; height: 26px; font-size: 11px;"><i class="fas fa-eye"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <!-- Right panel: Printable Assessment Report Card Sheet -->
                <section class="report-sheet" id="report-printable-area">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; margin-bottom: 30px;">
                        <div>
                            <h2 style="font-size: 26px; font-weight: 800; font-family: var(--font-heading); color: var(--text-main); margin-bottom: 4px;">Carbon Footprint Audit</h2>
                            <p style="font-size: 13px; color: var(--text-muted);">Audited by: <strong><?php echo htmlspecialchars($user_name); ?></strong> | Location: <strong><?php echo htmlspecialchars($user_location); ?></strong></p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 800; color: var(--primary); font-family: var(--font-heading);"><?php echo $selected_month_name . ' ' . $target_year; ?></div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Audit Target Period</div>
                        </div>
                    </div>

                    <?php if ($report_count === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-excel" style="font-size: 40px; margin-bottom: 12px;"></i>
                            <p>No carbon activities recorded for <strong><?php echo $selected_month_name . ' ' . $target_year; ?></strong>.</p>
                            <a href="add_activity.php" class="btn-primary" style="display: inline-block; margin-top: 15px;"><i class="fas fa-plus"></i> Add Log Row</a>
                        </div>
                    <?php else: ?>
                        <!-- Dynamic Report Stats grid -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; text-align: center;">
                            <div style="background-color: var(--bg-app); border: 1px solid var(--border-color); padding: 20px; border-radius: var(--radius-md);">
                                <h4 style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">Total Footprint</h4>
                                <div style="font-size: 24px; font-weight: 800; color: var(--text-main); font-family: var(--font-heading);"><?php echo number_format($report_total_co2, 1); ?> <span style="font-size: 12px; color: var(--text-muted);">kg</span></div>
                            </div>
                            <div style="background-color: var(--bg-app); border: 1px solid var(--border-color); padding: 20px; border-radius: var(--radius-md);">
                                <h4 style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">Audit Logs Count</h4>
                                <div style="font-size: 24px; font-weight: 800; color: var(--text-main); font-family: var(--font-heading);"><?php echo $report_count; ?> <span style="font-size: 12px; color: var(--text-muted);">rows</span></div>
                            </div>
                            <div style="background-color: var(--bg-app); border: 1px solid var(--border-color); padding: 20px; border-radius: var(--radius-md);">
                                <h4 style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">Eco Score</h4>
                                <div style="font-size: 24px; font-weight: 800; color: var(--primary); font-family: var(--font-heading);"><?php echo $report_score; ?>%</div>
                            </div>
                        </div>

                        <!-- Reduction Analysis comparison section -->
                        <div style="margin-bottom: 45px;">
                            <h3 style="font-size: 18px; margin-bottom: 16px; font-family: var(--font-heading);">Assessment & Reduction Target</h3>
                            
                            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                                <span>Monthly Average Target: <strong><?php echo $monthly_target; ?> kg CO₂</strong></span>
                                <span>Assessment Emitted: <strong style="color: <?php echo $report_total_co2 > $monthly_target ? 'var(--danger)' : 'var(--primary)'; ?>;"><?php echo number_format($report_total_co2, 1); ?> kg CO₂</strong></span>
                            </div>
                            <div style="background-color: var(--border-color); height: 8px; border-radius: var(--radius-full); overflow: hidden; margin-bottom: 12px;">
                                <div style="background-color: <?php echo $report_total_co2 > $monthly_target ? 'var(--danger)' : 'var(--primary)'; ?>; width: <?php echo min(100, ($report_total_co2 / $monthly_target) * 100); ?>%; height: 100%; border-radius: var(--radius-full);"></div>
                            </div>
                            
                            <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">
                                <?php if ($report_total_co2 > $monthly_target): ?>
                                    <i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i> You are currently exceeding typical carbon targets by <strong><?php echo number_format($report_total_co2 - $monthly_target, 1); ?> kg CO₂</strong>. Review the highest emitter sectors in the breakdown table below to formulate savings.
                                <?php else: ?>
                                    <i class="fas fa-check-circle" style="color: var(--success);"></i> Outstanding! You beat the standard monthly average target. Your emissions are <strong><?php echo number_format($monthly_target - $report_total_co2, 1); ?> kg</strong> below threshold, representing a <strong><?php echo number_format($reduction_percentage, 1); ?>%</strong> saving!
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Sector Emission Breakdown Table -->
                        <div style="margin-bottom: 40px;">
                            <h3 style="font-size: 18px; margin-bottom: 16px; font-family: var(--font-heading);">Sector Breakdown Analysis</h3>
                            
                            <table class="custom-table" style="font-size: 13px;">
                                <thead>
                                    <tr>
                                        <th style="padding: 12px 16px;">Activity Sector</th>
                                        <th style="padding: 12px 16px;">Total Logs</th>
                                        <th style="padding: 12px 16px;">Total CO₂ Emitted</th>
                                        <th style="padding: 12px 16px;">Impact Contribution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_breakdown as $row): ?>
                                        <?php 
                                        $percent = ($row['total'] / $report_total_co2) * 100;
                                        ?>
                                        <tr>
                                            <td style="padding: 12px 16px; font-weight: 600;">
                                                <span class="badge <?php echo strtolower($row['category']); ?>" style="padding: 2px 6px; font-size: 10px;">
                                                    <?php echo $row['category']; ?>
                                                </span>
                                            </td>
                                            <td style="padding: 12px 16px;"><?php echo $row['count']; ?></td>
                                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-main);"><?php echo number_format($row['total'], 2); ?> kg</td>
                                            <td style="padding: 12px 16px;">
                                                <!-- Mini visual percentage slider inside table -->
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <div style="background-color: var(--border-color); width: 80px; height: 6px; border-radius: var(--radius-full); overflow: hidden; display: inline-block;">
                                                        <div style="background-color: var(--primary); width: <?php echo $percent; ?>%; height: 100%;"></div>
                                                    </div>
                                                    <span><?php echo number_format($percent, 1); ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Report Signature / Validation section -->
                        <div style="border-top: 1px dashed var(--border-color); padding-top: 25px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-family: var(--font-heading); font-weight: 800; font-size: 16px; color: var(--primary);"><i class="fas fa-leaf"></i> EcoTrace Auditor</div>
                                <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">V2.1 Database Certified</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; font-weight: 600; color: var(--text-main);"><?php echo date('F d, Y'); ?></div>
                                <div style="font-size: 10px; color: var(--text-muted); text-transform: uppercase;">Audit Stamp Date</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
