<?php
// add_activity.php
// Wizard Logger Module - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Check user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$error_msg = "";

// ----------------------------------------------------
// DATABASE-DRIVEN CALCULATION & INSERTION (CRUD: C)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS);
    $activity_date = filter_input(INPUT_POST, 'activity_date', FILTER_DEFAULT);
    
    // Sub-category and quantity extraction depending on category
    $sub_category = "";
    $quantity = 0.0;
    
    switch ($category) {
        case 'Transport':
            $sub_category = filter_input(INPUT_POST, 'transport_mode', FILTER_SANITIZE_SPECIAL_CHARS);
            $quantity = filter_input(INPUT_POST, 'transport_distance', FILTER_VALIDATE_FLOAT);
            break;
        case 'Electricity':
            $sub_category = 'Grid Average';
            $quantity = filter_input(INPUT_POST, 'electricity_units', FILTER_VALIDATE_FLOAT);
            break;
        case 'Food':
            $sub_category = filter_input(INPUT_POST, 'food_type', FILTER_SANITIZE_SPECIAL_CHARS);
            $quantity = filter_input(INPUT_POST, 'food_quantity', FILTER_VALIDATE_FLOAT);
            break;
        case 'Shopping':
            $sub_category = filter_input(INPUT_POST, 'shopping_type', FILTER_SANITIZE_SPECIAL_CHARS);
            $quantity = filter_input(INPUT_POST, 'shopping_quantity', FILTER_VALIDATE_FLOAT);
            break;
        case 'Waste':
            $sub_category = filter_input(INPUT_POST, 'waste_type', FILTER_SANITIZE_SPECIAL_CHARS);
            $quantity = filter_input(INPUT_POST, 'waste_weight', FILTER_VALIDATE_FLOAT);
            break;
        case 'Fuel':
            $sub_category = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_SPECIAL_CHARS);
            $quantity = filter_input(INPUT_POST, 'fuel_quantity', FILTER_VALIDATE_FLOAT);
            break;
        default:
            $error_msg = "Invalid activity category selected.";
    }
    
    if (empty($error_msg) && (empty($category) || empty($sub_category) || $quantity === false || $quantity <= 0 || empty($activity_date))) {
        $error_msg = "Please provide valid activity details. Quantity must be greater than zero.";
    }
    
    if (empty($error_msg)) {
        try {
            // 1. DATABASE-DRIVEN FACTOR RETRIEVAL (No hardcoding multiplier!)
            $factor_stmt = $pdo->prepare("SELECT factor_value FROM emission_factors WHERE category = ? AND sub_category = ?");
            $factor_stmt->execute([$category, $sub_category]);
            $factor_val = $factor_stmt->fetchColumn();
            
            if ($factor_val === false) {
                throw new Exception("Emission factor not found in standard lookup tables for {$category} / {$sub_category}.");
            }
            
            // 2. Perform math
            $co2_emitted = floatval($factor_val) * floatval($quantity);
            
            // 3. Classify level dynamically
            $emission_level = getEmissionLevel($category, $co2_emitted);
            
            // 4. DATABASE INSERT OPERATION (Prepared Secure SQL Statement)
            $insert_stmt = $pdo->prepare("INSERT INTO activities (user_id, category, sub_category, quantity, co2_emitted, emission_level, activity_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->execute([$user_id, $category, $sub_category, $quantity, $co2_emitted, $emission_level, $activity_date]);
            
            $new_activity_id = $pdo->lastInsertId();
            
            // Redirect to dedicated results panel
            header("Location: results.php?activity_id=" . $new_activity_id);
            exit;
            
        } catch (Exception $e) {
            $error_msg = "Failed to process carbon logging: " . $e->getMessage();
        }
    }
}

// Fetch list of active factors for real-time frontend calculations
try {
    $factors_stmt = $pdo->query("SELECT category, sub_category, factor_value, unit FROM emission_factors");
    $db_factors = $factors_stmt->fetchAll();
    
    // Convert DB factors to JSON for JS live preview calculator
    $factors_json = [];
    foreach ($db_factors as $row) {
        $factors_json[$row['category']][$row['sub_category']] = [
            'factor' => floatval($row['factor_value']),
            'unit' => $row['unit']
        ];
    }
} catch (PDOException $e) {
    $factors_json = [];
}

// Set theme
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
    <title>EcoTrace - Add Carbon Activity</title>
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
                <li class="menu-item active">
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
                    <h2>Record Carbon Activity</h2>
                    <p>Enter details of daily carbon events to commit to the MySQL database.</p>
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

            <!-- Error Banner -->
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 16px 20px; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; max-width: 800px; margin-left: auto; margin-right: auto;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <!-- Wizard Log Box -->
            <section class="wizard-container">
                <!-- Step progress header -->
                <div class="wizard-steps">
                    <div class="step-node active" id="step-node-1" onclick="goToStep(1)">
                        <div class="step-circle">1</div>
                        <span class="step-label">Category</span>
                    </div>
                    <div class="step-node" id="step-node-2" onclick="goToStep(2)">
                        <div class="step-circle">2</div>
                        <span class="step-label">Details</span>
                    </div>
                    <div class="step-node" id="step-node-3">
                        <div class="step-circle">3</div>
                        <span class="step-label">Confirm</span>
                    </div>
                </div>

                <!-- Logger Form -->
                <form id="activityForm" action="add_activity.php" method="POST">
                    
                    <!-- Hidden active category variable -->
                    <input type="hidden" id="selected-category" name="category" value="">

                    <!-- STEP 1: CATEGORY SELECTION -->
                    <div class="step-pane active" id="step-pane-1">
                        <h3 style="font-size: 20px; margin-bottom: 12px; font-family: var(--font-heading);">Select Activity Category</h3>
                        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 30px;">Choose the sector of emissions you would like to log today.</p>
                        
                        <div class="category-select-grid">
                            <div class="category-option-card" onclick="selectCategory('Transport', this)">
                                <i class="fas fa-bus"></i>
                                <span>Transport</span>
                            </div>
                            <div class="category-option-card" onclick="selectCategory('Electricity', this)">
                                <i class="fas fa-lightbulb"></i>
                                <span>Electricity</span>
                            </div>
                            <div class="category-option-card" onclick="selectCategory('Food', this)">
                                <i class="fas fa-utensils"></i>
                                <span>Food</span>
                            </div>
                            <div class="category-option-card" onclick="selectCategory('Shopping', this)">
                                <i class="fas fa-shopping-bag"></i>
                                <span>Shopping</span>
                            </div>
                            <div class="category-option-card" onclick="selectCategory('Waste', this)">
                                <i class="fas fa-trash"></i>
                                <span>Waste</span>
                            </div>
                            <div class="category-option-card" onclick="selectCategory('Fuel', this)">
                                <i class="fas fa-gas-pump"></i>
                                <span>Fuel</span>
                            </div>
                        </div>

                        <div style="text-align: right; margin-top: 20px;">
                            <button type="button" class="btn-primary" id="btn-next-1" disabled onclick="goToStep(2)">Configure Details <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 2: DYNAMIC DETAILS PANEL -->
                    <div class="step-pane" id="step-pane-2">
                        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
                            
                            <!-- Left: Input form fields -->
                            <div class="activity-details-panel">
                                <h3 id="details-pane-title" style="font-size: 18px; margin-bottom: 20px; font-family: var(--font-heading);">Enter Activity Inputs</h3>
                                
                                <!-- TRANSPORT INPUTS -->
                                <div class="dynamic-fields" id="fields-Transport" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="transport_mode">Mode of Transport</label>
                                        <select class="form-control" style="padding-left: 16px;" id="transport_mode" name="transport_mode" onchange="calculateLiveCO2()">
                                            <option value="Car">Car (Gasoline)</option>
                                            <option value="Bus">Bus (Transit)</option>
                                            <option value="Train">Local Subway/Train</option>
                                            <option value="Bike">Motorcycle</option>
                                            <option value="Flight">Short-Haul Flight</option>
                                        </select>
                                    </div>
                                    <div class="slider-group">
                                        <div class="slider-header">
                                            <label class="form-label">Distance Travelled</label>
                                            <span class="slider-val" id="val-distance">50 km</span>
                                        </div>
                                        <input type="range" class="custom-range-slider" id="transport_distance" name="transport_distance" min="1" max="1000" value="50" oninput="updateSliderVal('distance', this.value); calculateLiveCO2();">
                                    </div>
                                </div>

                                <!-- ELECTRICITY INPUTS -->
                                <div class="dynamic-fields" id="fields-Electricity" style="display: none;">
                                    <div class="slider-group">
                                        <div class="slider-header">
                                            <label class="form-label">Power Consumption</label>
                                            <span class="slider-val" id="val-electricity">100 kWh</span>
                                        </div>
                                        <input type="range" class="custom-range-slider" id="electricity_units" name="electricity_units" min="1" max="2000" value="100" oninput="updateSliderVal('electricity', this.value); calculateLiveCO2();">
                                    </div>
                                </div>

                                <!-- FOOD INPUTS -->
                                <div class="dynamic-fields" id="fields-Food" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="food_type">Diet Category</label>
                                        <select class="form-control" style="padding-left: 16px;" id="food_type" name="food_type" onchange="calculateLiveCO2()">
                                            <option value="Beef">Beef / Red Meat</option>
                                            <option value="Chicken">Chicken / Poultry</option>
                                            <option value="Fish">Seafood / Fish</option>
                                            <option value="Vegetables">Vegetables / Plant-Based</option>
                                        </select>
                                    </div>
                                    <div class="slider-group">
                                        <div class="slider-header">
                                            <label class="form-label">Quantity Consumed</label>
                                            <span class="slider-val" id="val-food">1 kg</span>
                                        </div>
                                        <input type="range" class="custom-range-slider" id="food_quantity" name="food_quantity" min="1" max="50" value="1" oninput="updateSliderVal('food', this.value); calculateLiveCO2();">
                                    </div>
                                </div>

                                <!-- SHOPPING INPUTS -->
                                <div class="dynamic-fields" id="fields-Shopping" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="shopping_type">Product Item Category</label>
                                        <select class="form-control" style="padding-left: 16px;" id="shopping_type" name="shopping_type" onchange="calculateLiveCO2()">
                                            <option value="Electronics">Smartphone / Laptop</option>
                                            <option value="Clothing">Apparel / Clothes</option>
                                            <option value="Shoes">Pair of Shoes</option>
                                        </select>
                                    </div>
                                    <div class="slider-group">
                                        <div class="slider-header">
                                            <label class="form-label">Quantity Purchased</label>
                                            <span class="slider-val" id="val-shopping">1 items</span>
                                        </div>
                                        <input type="range" class="custom-range-slider" id="shopping_quantity" name="shopping_quantity" min="1" max="20" value="1" oninput="updateSliderVal('shopping', this.value); calculateLiveCO2();">
                                    </div>
                                </div>

                                <!-- WASTE INPUTS -->
                                <div class="dynamic-fields" id="fields-Waste" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="waste_type">Waste Composition</label>
                                        <select class="form-control" style="padding-left: 16px;" id="waste_type" name="waste_type" onchange="calculateLiveCO2()">
                                            <option value="Plastic">Plastic Packaging</option>
                                            <option value="Paper">Paper / Cardboard</option>
                                            <option value="Organic">Organic / Food Waste</option>
                                        </select>
                                    </div>
                                    <div class="slider-group">
                                        <div class="slider-header">
                                            <label class="form-label">Total Weight</label>
                                            <span class="slider-val" id="val-waste">5 kg</span>
                                        </div>
                                        <input type="range" class="custom-range-slider" id="waste_weight" name="waste_weight" min="1" max="100" value="5" oninput="updateSliderVal('waste', this.value); calculateLiveCO2();">
                                    </div>
                                </div>

                                <!-- FUEL INPUTS -->
                                <div class="dynamic-fields" id="fields-Fuel" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label" for="fuel_type">Fuel Type</label>
                                        <select class="form-control" style="padding-left: 16px;" id="fuel_type" name="fuel_type" onchange="updateSliderVal('fuel', document.getElementById('fuel_quantity').value); calculateLiveCO2();">
                                            <option value="Petrol">Petrol (Liters)</option>
                                            <option value="Diesel">Diesel (Liters)</option>
                                            <option value="LPG">LPG (Liters)</option>
                                            <option value="CNG">CNG (kg)</option>
                                        </select>
                                    </div>
                                    <div class="slider-group">
                                        <div class="slider-header">
                                            <label class="form-label">Quantity Consumed</label>
                                            <span class="slider-val" id="val-fuel">10 Liters</span>
                                        </div>
                                        <input type="range" class="custom-range-slider" id="fuel_quantity" name="fuel_quantity" min="1" max="200" value="10" oninput="updateSliderVal('fuel', this.value); calculateLiveCO2();">
                                    </div>
                                </div>

                                <!-- Shared Date Picker -->
                                <div class="form-group" style="margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                                    <label class="form-label" for="activity_date">Activity Event Date</label>
                                    <input type="date" class="form-control" style="padding-left: 16px;" id="activity_date" name="activity_date" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>

                            <!-- Right: Live estimation card -->
                            <div style="background-color: var(--bg-app); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 30px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; height: fit-content; align-self: flex-start; box-shadow: var(--shadow-sm);">
                                <i class="fas fa-leaf" style="font-size: 36px; color: var(--primary); margin-bottom: 16px; animation: pulseGlow 2s infinite ease-in-out;"></i>
                                <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 6px;">Live Impact Estimation</h4>
                                <div style="font-size: 38px; font-weight: 800; font-family: var(--font-heading); color: var(--text-main); line-height: 1;">
                                    <span id="live-co2-val">0.00</span>
                                    <span style="font-size: 14px; font-weight: 600; color: var(--text-muted); display: block; margin-top: 4px;">kg CO₂ Equivalents</span>
                                </div>
                                
                                <div id="live-level-badge" class="meter-label low" style="margin-top: 16px;">Low Impact</div>
                            </div>
                        </div>

                        <div class="wizard-footer">
                            <button type="button" class="btn-secondary" onclick="goToStep(1)"><i class="fas fa-chevron-left"></i> Change Category</button>
                            <button type="button" class="btn-primary" onclick="goToStep(3)">Compute Results <i class="fas fa-calculator"></i></button>
                        </div>
                    </div>

                    <!-- STEP 3: CONFIRM & CALCULATE -->
                    <div class="step-pane" id="step-pane-3">
                        <div style="text-align: center; padding: 30px 20px;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 24px;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3 style="font-size: 24px; margin-bottom: 10px; font-family: var(--font-heading);">Ready to Commit to MySQL</h3>
                            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 30px; font-size: 14px;">Your inputs are configured correctly. By clicking the button below, the system will resolve calculation factors from the database, evaluate emission levels, and commit your log to the database.</p>
                            
                            <div style="background-color: var(--bg-app); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; max-width: 400px; margin: 0 auto 30px; text-align: left;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                                    <span style="color: var(--text-muted);">Sector:</span>
                                    <strong id="confirm-category" style="color: var(--text-main);">-</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                                    <span style="color: var(--text-muted);">Sub-Type:</span>
                                    <strong id="confirm-subtype" style="color: var(--text-main);">-</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                                    <span style="color: var(--text-muted);">Quantity Input:</span>
                                    <strong id="confirm-qty" style="color: var(--text-main);">-</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 14px;">
                                    <span style="color: var(--text-muted);">Estimated CO₂:</span>
                                    <strong id="confirm-co2" style="color: var(--primary);">-</strong>
                                </div>
                            </div>
                        </div>

                        <div class="wizard-footer">
                            <button type="button" class="btn-secondary" onclick="goToStep(2)"><i class="fas fa-chevron-left"></i> Edit Details</button>
                            <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="fas fa-save"></i> Calculate & Log Row</button>
                        </div>
                    </div>

                </form>
            </section>
        </main>
    </div>

    <!-- Include global JS and specialized scripts -->
    <script src="assets/js/main.js"></script>
    <script>
        // Inject dynamic DB factors into Javascript environment
        const carbonFactors = <?php echo json_encode($factors_json); ?>;
        
        let currentStep = 1;
        let selectedCategory = '';

        function selectCategory(category, cardElement) {
            // Remove selection state from all options
            document.querySelectorAll('.category-option-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Set selection on clicked card
            cardElement.classList.add('selected');
            
            // Update hidden form input
            selectedCategory = category;
            document.getElementById('selected-category').value = category;
            
            // Enable next step button
            document.getElementById('btn-next-1').removeAttribute('disabled');
            
            // Pre-show corresponding field pane and hide others
            document.querySelectorAll('.dynamic-fields').forEach(pane => {
                pane.style.display = 'none';
            });
            
            const fieldPane = document.getElementById(`fields-${category}`);
            if (fieldPane) {
                fieldPane.style.display = 'block';
            }
            
            // Update titles
            document.getElementById('details-pane-title').innerText = `${category} Parameters`;
            
            // Recalculate preview
            calculateLiveCO2();
        }

        function updateSliderVal(type, val) {
            let label = val;
            if (type === 'fuel') {
                const fuelType = document.getElementById('fuel_type').value;
                label = `${val} ${fuelType === 'CNG' ? 'kg' : 'Liters'}`;
            }
            const displayMap = {
                'distance': `${val} km`,
                'electricity': `${val} kWh`,
                'food': `${val} kg`,
                'shopping': `${val} items`,
                'waste': `${val} kg`,
                'fuel': label
            };
            
            const element = document.getElementById(`val-${type}`);
            if (element) {
                element.innerText = displayMap[type] || val;
            }
        }

        function calculateLiveCO2() {
            if (!selectedCategory) return;
            
            let quantity = 0;
            let subCategory = '';
            
            if (selectedCategory === 'Transport') {
                subCategory = document.getElementById('transport_mode').value;
                quantity = parseFloat(document.getElementById('transport_distance').value);
            } else if (selectedCategory === 'Electricity') {
                subCategory = 'Grid Average';
                quantity = parseFloat(document.getElementById('electricity_units').value);
            } else if (selectedCategory === 'Food') {
                subCategory = document.getElementById('food_type').value;
                quantity = parseFloat(document.getElementById('food_quantity').value);
            } else if (selectedCategory === 'Shopping') {
                subCategory = document.getElementById('shopping_type').value;
                quantity = parseFloat(document.getElementById('shopping_quantity').value);
            } else if (selectedCategory === 'Waste') {
                subCategory = document.getElementById('waste_type').value;
                quantity = parseFloat(document.getElementById('waste_weight').value);
            } else if (selectedCategory === 'Fuel') {
                subCategory = document.getElementById('fuel_type').value;
                quantity = parseFloat(document.getElementById('fuel_quantity').value);
            }
            
            // Query local Javascript lookup table mirroring MySQL emission factors
            let co2 = 0;
            if (carbonFactors[selectedCategory] && carbonFactors[selectedCategory][subCategory]) {
                const multiplier = carbonFactors[selectedCategory][subCategory].factor;
                co2 = multiplier * quantity;
            }
            
            // Update UI preview numbers
            document.getElementById('live-co2-val').innerText = co2.toFixed(2);
            
            // Update live color badge level
            const badge = document.getElementById('live-level-badge');
            badge.className = 'meter-label';
            
            let level = 'Low';
            
            // Simple threshold checks mirror server side
            if (selectedCategory === 'Transport') {
                level = co2 < 5 ? 'Low' : (co2 < 20 ? 'Medium' : 'High');
            } else if (selectedCategory === 'Electricity') {
                level = co2 < 25 ? 'Low' : (co2 < 100 ? 'Medium' : 'High');
            } else if (selectedCategory === 'Fuel') {
                level = co2 < 10 ? 'Low' : (co2 < 30 ? 'Medium' : 'High');
            } else {
                level = co2 < 5 ? 'Low' : (co2 < 15 ? 'Medium' : 'High');
            }
            
            badge.classList.add(level.toLowerCase());
            badge.innerText = `${level} Impact`;
        }

        function goToStep(step) {
            // Validate step traversal
            if (step === 2 && !selectedCategory) {
                showToast("Action Required", "Please select an activity category first.", "warning");
                return;
            }
            
            if (step === 3) {
                // Populate Step 3 Confirmation Text fields
                let quantityStr = '';
                let subCategory = '';
                
                if (selectedCategory === 'Transport') {
                    subCategory = document.getElementById('transport_mode').value;
                    quantityStr = `${document.getElementById('transport_distance').value} km`;
                } else if (selectedCategory === 'Electricity') {
                    subCategory = 'Grid Average';
                    quantityStr = `${document.getElementById('electricity_units').value} kWh`;
                } else if (selectedCategory === 'Food') {
                    subCategory = document.getElementById('food_type').value;
                    quantityStr = `${document.getElementById('food_quantity').value} kg`;
                } else if (selectedCategory === 'Shopping') {
                    subCategory = document.getElementById('shopping_type').value;
                    quantityStr = `${document.getElementById('shopping_quantity').value} items`;
                } else if (selectedCategory === 'Waste') {
                    subCategory = document.getElementById('waste_type').value;
                    quantityStr = `${document.getElementById('waste_weight').value} kg`;
                } else if (selectedCategory === 'Fuel') {
                    subCategory = document.getElementById('fuel_type').value;
                    const fuelType = document.getElementById('fuel_type').value;
                    quantityStr = `${document.getElementById('fuel_quantity').value} ${fuelType === 'CNG' ? 'kg' : 'Liters'}`;
                }
                
                const estCo2 = document.getElementById('live-co2-val').innerText;
                
                document.getElementById('confirm-category').innerText = selectedCategory;
                document.getElementById('confirm-subtype').innerText = subCategory;
                document.getElementById('confirm-qty').innerText = quantityStr;
                document.getElementById('confirm-co2').innerText = `${estCo2} kg CO₂`;
            }
            
            // Toggle active styles on stepper header
            document.querySelectorAll('.step-node').forEach((node, idx) => {
                const nodeStep = idx + 1;
                node.classList.remove('active', 'completed');
                
                if (nodeStep < step) {
                    node.classList.add('completed');
                } else if (nodeStep === step) {
                    node.classList.add('active');
                }
            });
            
            // Toggle display on pane wrappers
            document.querySelectorAll('.step-pane').forEach((pane, idx) => {
                pane.classList.remove('active');
                if ((idx + 1) === step) {
                    pane.classList.add('active');
                }
            });
            
            currentStep = step;
        }
    </script>
</body>
</html>
