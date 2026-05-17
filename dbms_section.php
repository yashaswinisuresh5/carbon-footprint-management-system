<?php
// dbms_section.php
// Dedicated DBMS Visualization & Academic Concept Sandbox
require_once __DIR__ . '/config/db.php';

// Force user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['user_name'];

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
    <title>EcoTrace - DBMS Database Concepts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .sandbox-header {
            margin-bottom: 25px;
            padding: 20px;
            background-color: var(--primary-light);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: var(--radius-md);
            font-size: 14px;
            line-height: 1.6;
        }
        .relational-link-line {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 20px;
            margin: 10px 0;
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
                <li class="menu-item">
                    <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Monthly Reports</span></a>
                </li>
                <li class="menu-item">
                    <a href="leaderboard.php"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a>
                </li>
                
                <li class="menu-label">DBMS Administration</li>
                <li class="menu-item active">
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
                    <h2>DBMS Conceptual Schema</h2>
                    <p>Academic evaluation sandbox highlighting entity normalization, keys, and SQL triggers.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="dashboard.php" class="btn-secondary" style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-arrow-left"></i> Dashboard</a>
                </div>
            </header>

            <!-- Introductory explanation banner -->
            <div class="sandbox-header">
                <strong><i class="fas fa-university"></i> Academic Evaluation Note:</strong> This dedicated DBMS portal is engineered to showcase relational database compliance, Primary/Foreign Key relational mapping integrity, and 3NF normalization principles behind the EcoTrace Carbon Footprint Tracker. Below, you can explore interactive schema structures and exact SQL queries used throughout the server backend.
            </div>

            <!-- SECTION 1: RELATIONAL SCHEMA / ER DIAGRAM VISUALIZER -->
            <section class="dbms-section">
                <div class="table-header" style="padding: 0 0 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 25px;">
                    <h3><i class="fas fa-network-wired" style="color: var(--primary);"></i> Relational Entity Schema Visualizer</h3>
                </div>
                
                <div class="schema-visualizer">
                    
                    <!-- Table Box 1: USERS -->
                    <div class="db-table-box">
                        <div class="db-table-header">
                            <span><i class="fas fa-users"></i> users</span>
                            <span style="font-size: 10px; color: var(--primary);">Table 1</span>
                        </div>
                        <ul class="db-columns-list">
                            <li class="db-column-row">
                                <span class="col-name"><span class="key-badge pk">PK</span> user_id</span>
                                <span class="col-type">INT AI</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">name</span>
                                <span class="col-type">VARCHAR(100)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">age</span>
                                <span class="col-type">INT</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">email (UNIQUE)</span>
                                <span class="col-type">VARCHAR(100)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">password</span>
                                <span class="col-type">VARCHAR(255)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">location</span>
                                <span class="col-type">VARCHAR(100)</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Table Box 2: ACTIVITIES -->
                    <div class="db-table-box">
                        <div class="db-table-header">
                            <span><i class="fas fa-list-alt"></i> activities</span>
                            <span style="font-size: 10px; color: var(--primary);">Table 2</span>
                        </div>
                        <ul class="db-columns-list">
                            <li class="db-column-row">
                                <span class="col-name"><span class="key-badge pk">PK</span> activity_id</span>
                                <span class="col-type">INT AI</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name"><span class="key-badge fk">FK</span> user_id</span>
                                <span class="col-type">INT</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">category</span>
                                <span class="col-type">VARCHAR(50)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">sub_category</span>
                                <span class="col-type">VARCHAR(50)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">quantity</span>
                                <span class="col-type">DECIMAL(10,2)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">co2_emitted</span>
                                <span class="col-type">DECIMAL(10,3)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">emission_level</span>
                                <span class="col-type">VARCHAR(10)</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Table Box 3: EMISSION_FACTORS -->
                    <div class="db-table-box">
                        <div class="db-table-header">
                            <span><i class="fas fa-calculator"></i> emission_factors</span>
                            <span style="font-size: 10px; color: var(--primary);">Table 3</span>
                        </div>
                        <ul class="db-columns-list">
                            <li class="db-column-row">
                                <span class="col-name"><span class="key-badge pk">PK</span> factor_id</span>
                                <span class="col-type">INT AI</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">category</span>
                                <span class="col-type">VARCHAR(50)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">sub_category (UNIQ)</span>
                                <span class="col-type">VARCHAR(50)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">factor_value</span>
                                <span class="col-type">DECIMAL(10,4)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">unit</span>
                                <span class="col-type">VARCHAR(20)</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Table Box 4: RECOMMENDATIONS -->
                    <div class="db-table-box">
                        <div class="db-table-header">
                            <span><i class="fas fa-lightbulb"></i> recommendations</span>
                            <span style="font-size: 10px; color: var(--primary);">Table 4</span>
                        </div>
                        <ul class="db-columns-list">
                            <li class="db-column-row">
                                <span class="col-name"><span class="key-badge pk">PK</span> rec_id</span>
                                <span class="col-type">INT AI</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">category</span>
                                <span class="col-type">VARCHAR(50)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">emission_level</span>
                                <span class="col-type">VARCHAR(10)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">tip_title</span>
                                <span class="col-type">VARCHAR(100)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">tip_text</span>
                                <span class="col-type">TEXT</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">potential_saving</span>
                                <span class="col-type">DECIMAL(10,2)</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Table Box 5: REPORTS -->
                    <div class="db-table-box">
                        <div class="db-table-header">
                            <span><i class="fas fa-file-invoice"></i> reports</span>
                            <span style="font-size: 10px; color: var(--primary);">Table 5</span>
                        </div>
                        <ul class="db-columns-list">
                            <li class="db-column-row">
                                <span class="col-name"><span class="key-badge pk">PK</span> report_id</span>
                                <span class="col-type">INT AI</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name"><span class="key-badge fk">FK</span> user_id</span>
                                <span class="col-type">INT</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">report_month</span>
                                <span class="col-type">INT</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">report_year</span>
                                <span class="col-type">INT</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">total_co2</span>
                                <span class="col-type">DECIMAL(10,3)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">sustainability_score</span>
                                <span class="col-type">INT</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Table Box 6: ADMIN -->
                    <div class="db-table-box">
                        <div class="db-table-header">
                            <span><i class="fas fa-user-shield"></i> admin</span>
                            <span style="font-size: 10px; color: var(--primary);">Table 6</span>
                        </div>
                        <ul class="db-columns-list">
                            <li class="db-column-row">
                                <span class="col-name"><span class="key-badge pk">PK</span> admin_id</span>
                                <span class="col-type">INT AI</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">name</span>
                                <span class="col-type">VARCHAR(100)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">username (UNIQUE)</span>
                                <span class="col-type">VARCHAR(50)</span>
                            </li>
                            <li class="db-column-row">
                                <span class="col-name">password</span>
                                <span class="col-type">VARCHAR(255)</span>
                            </li>
                        </ul>
                    </div>

                </div>

                <!-- Labeled description of FK constraints -->
                <div style="border-top: 1px solid var(--border-color); padding-top: 20px; font-size: 13px; color: var(--text-muted); display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <strong style="color: var(--text-main); display: block; margin-bottom: 6px;"><i class="fas fa-link"></i> Referenced Keys (Foreign Keys):</strong>
                        <ul style="padding-left: 20px; display: flex; flex-direction: column; gap: 4px;">
                            <li><code>activities.user_id</code> references <code>users.user_id</code> (ON DELETE CASCADE)</li>
                            <li><code>reports.user_id</code> references <code>users.user_id</code> (ON DELETE CASCADE)</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: var(--text-main); display: block; margin-bottom: 6px;"><i class="fas fa-check-double"></i> 3NF Normalization Standards:</strong>
                        <p style="line-height: 1.5;">This model complies fully with <strong>Third Normal Form (3NF)</strong>. Non-prime attributes are completely dependent only on primary candidate keys, removing transitive dependencies. Sector multipliers reside independently in <code>emission_factors</code>, avoiding redundant calculation factors.</p>
                    </div>
                </div>
            </section>

            <!-- SECTION 2: INTERACTIVE SQL QUERY CODE BLOCKS -->
            <section class="dbms-section">
                <div class="table-header" style="padding: 0 0 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 25px;">
                    <h3><i class="fas fa-code" style="color: var(--secondary);"></i> Dynamic SQL Script Sandbox</h3>
                </div>

                <!-- Tabs header -->
                <div class="sql-query-tabs">
                    <div class="sql-tab active" onclick="switchSqlTab(1, this)">1. INNER JOIN Query</div>
                    <div class="sql-tab" onclick="switchSqlTab(2, this)">2. Aggregate GROUP BY</div>
                    <div class="sql-tab" onclick="switchSqlTab(3, this)">3. Nested Subquery</div>
                    <div class="sql-tab" onclick="switchSqlTab(4, this)">4. Table DDL Structure</div>
                </div>

                <!-- Tab Pane 1: JOIN -->
                <div class="sql-code-pane" id="sql-pane-1">
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
                        <strong>Purpose:</strong> Used in compilation of the competitive Leaderboard module. Joins the user profiles with activities rows and performs aggregate calculations to establish standings.
                    </p>
                    <pre class="sql-code-display">
<span class="sql-keyword">SELECT</span> 
    u.user_id, 
    u.name, 
    u.location, 
    <span class="sql-func">SUM</span>(a.co2_emitted) <span class="sql-keyword">as</span> total_co2, 
    <span class="sql-func">COUNT</span>(a.activity_id) <span class="sql-keyword">as</span> activity_count 
<span class="sql-keyword">FROM</span> <span class="sql-table">users</span> u 
<span class="sql-keyword">INNER JOIN</span> <span class="sql-table">activities</span> a <span class="sql-keyword">ON</span> u.user_id = a.user_id 
<span class="sql-keyword">GROUP BY</span> u.user_id 
<span class="sql-keyword">ORDER BY</span> total_co2 <span class="sql-keyword">ASC</span>;
                    </pre>
                </div>

                <!-- Tab Pane 2: GROUP BY -->
                <div class="sql-code-pane" id="sql-pane-2" style="display: none;">
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
                        <strong>Purpose:</strong> Used to render the Monthly carbon audit breakdown. Groups historical activities by year/month to compile dynamic progress sheets.
                    </p>
                    <pre class="sql-code-display">
<span class="sql-keyword">SELECT</span> 
    <span class="sql-func">MONTH</span>(activity_date) <span class="sql-keyword">as</span> month, 
    <span class="sql-func">YEAR</span>(activity_date) <span class="sql-keyword">as</span> year, 
    <span class="sql-func">SUM</span>(co2_emitted) <span class="sql-keyword">as</span> total_co2, 
    <span class="sql-func">COUNT</span>(*) <span class="sql-keyword">as</span> activity_count 
<span class="sql-keyword">FROM</span> <span class="sql-table">activities</span> 
<span class="sql-keyword">WHERE</span> user_id = <span class="sql-number">1</span> 
<span class="sql-keyword">GROUP BY</span> <span class="sql-func">YEAR</span>(activity_date), <span class="sql-func">MONTH</span>(activity_date) 
<span class="sql-keyword">ORDER BY</span> year <span class="sql-keyword">DESC</span>, month <span class="sql-keyword">DESC</span>;
                    </pre>
                </div>

                <!-- Tab Pane 3: SUBQUERY -->
                <div class="sql-code-pane" id="sql-pane-3" style="display: none;">
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
                        <strong>Purpose:</strong> Used in the user dashboard metrics to identify the dominant emission level dynamically using a subquery sorting maximum values.
                    </p>
                    <pre class="sql-code-display">
<span class="sql-keyword">SELECT</span> emission_level, count 
<span class="sql-keyword">FROM</span> (
    <span class="sql-keyword">SELECT</span> 
        emission_level, 
        <span class="sql-func">COUNT</span>(*) <span class="sql-keyword">as</span> count 
    <span class="sql-keyword">FROM</span> <span class="sql-table">activities</span> 
    <span class="sql-keyword">WHERE</span> user_id = <span class="sql-number">1</span> 
    <span class="sql-keyword">GROUP BY</span> emission_level
) <span class="sql-keyword">as</span> sub_table 
<span class="sql-keyword">ORDER BY</span> count <span class="sql-keyword">DESC</span> 
<span class="sql-keyword">LIMIT</span> <span class="sql-number">1</span>;
                    </pre>
                </div>

                <!-- Tab Pane 4: DDL -->
                <div class="sql-code-pane" id="sql-pane-4" style="display: none;">
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
                        <strong>Purpose:</strong> The actual SQL DDL statement establishing the central carbon activity log table, highlighting references and cascading constraints.
                    </p>
                    <pre class="sql-code-display">
<span class="sql-keyword">CREATE TABLE</span> <span class="sql-table">activities</span> (
    activity_id <span class="sql-keyword">INT AUTO_INCREMENT PRIMARY KEY</span>,
    user_id <span class="sql-keyword">INT NOT NULL</span>,
    category <span class="sql-keyword">VARCHAR</span>(<span class="sql-number">50</span>) <span class="sql-keyword">NOT NULL</span>,
    sub_category <span class="sql-keyword">VARCHAR</span>(<span class="sql-number">50</span>) <span class="sql-keyword">NOT NULL</span>,
    quantity <span class="sql-keyword">DECIMAL</span>(<span class="sql-number">10</span>,<span class="sql-number">2</span>) <span class="sql-keyword">NOT NULL</span>,
    co2_emitted <span class="sql-keyword">DECIMAL</span>(<span class="sql-number">10</span>,<span class="sql-number">3</span>) <span class="sql-keyword">NOT NULL</span>,
    emission_level <span class="sql-keyword">VARCHAR</span>(<span class="sql-number">10</span>) <span class="sql-keyword">NOT NULL</span>,
    activity_date <span class="sql-keyword">DATE NOT NULL</span>,
    <span class="sql-keyword">FOREIGN KEY</span> (user_id) <span class="sql-keyword">REFERENCES</span> <span class="sql-table">users</span>(user_id) <span class="sql-keyword">ON DELETE CASCADE</span>
) ENGINE=InnoDB;
                    </pre>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function switchSqlTab(tabIdx, tabElement) {
            // Remove active class from all tabs
            document.querySelectorAll('.sql-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Set active on clicked
            tabElement.classList.add('active');
            
            // Hide all code panes
            document.querySelectorAll('.sql-code-pane').forEach(pane => {
                pane.style.display = 'none';
            });
            
            // Show target pane
            const targetPane = document.getElementById(`sql-pane-${tabIdx}`);
            if (targetPane) {
                targetPane.style.display = 'block';
            }
        }
    </script>
</body>
</html>
