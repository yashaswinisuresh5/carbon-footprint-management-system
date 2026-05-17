<?php
// config/db.php
// Database Connection Configuration utilizing PDO with Auto-Initialization

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carbon_footprint_db');

$pdo = null;

try {
    // Attempt standard connection to the pre-existing database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // If the database does not exist (Error Code 1049) or another database issue occurs, attempt auto-creation
    try {
        // Connect to MySQL server without database specification
        $bootstrap_pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Path to schema.sql
        $schema_file = dirname(__DIR__) . '/database/schema.sql';
        
        if (file_exists($schema_file)) {
            $schema_sql = file_get_contents($schema_file);
            
            // Execute the schema statements to create the database and seed tables
            $bootstrap_pdo->exec($schema_sql);
            
            // Re-attempt connecting to the newly created database
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } else {
            // If schema file cannot be found, output critical error
            throw new Exception("Database does not exist, and schema.sql file is missing at: " . $schema_file);
        }
    } catch (PDOException $bootstrap_error) {
        // Output clean connection instructions for the user if MySQL server is offline
        die("<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 100px; padding: 20px; background-color: #fce8e6; border-radius: 8px; max-width: 600px; margin-left: auto; margin-right: auto; border: 1px solid #ea4335;'>
                <h2 style='color: #c5221f; margin-bottom: 10px;'>MySQL Database Offline</h2>
                <p style='color: #3c4043; font-size: 16px; line-height: 1.6;'>
                    The Carbon Footprint Management System could not connect to your MySQL database.<br><br>
                    <strong>Please make sure XAMPP is running and the MySQL service is started.</strong>
                </p>
                <div style='margin-top: 20px; text-align: left; background: #fff; padding: 15px; border-radius: 5px; border: 1px solid #dadce0;'>
                    <strong>Error details:</strong> <code style='color: #d93025; word-break: break-all;'>" . htmlspecialchars($bootstrap_error->getMessage()) . "</code>
                </div>
                <button onclick='window.location.reload()' style='margin-top: 20px; padding: 10px 20px; background: #137333; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>Retry Connection</button>
             </div>");
    } catch (Exception $ex) {
        die("System Initialization Failure: " . $ex->getMessage());
    }
}

/**
 * Helper function to determine standard CO2 emission based on DB factors
 * This is database-driven calculation!
 */
function calculateCO2($category, $sub_category, $quantity, $pdo) {
    $stmt = $pdo->prepare("SELECT factor_value FROM emission_factors WHERE category = ? AND sub_category = ?");
    $stmt->execute([$category, $sub_category]);
    $factor = $stmt->fetchColumn();
    
    if ($factor === false) {
        return 0; // Unknown combination
    }
    
    return floatval($factor) * floatval($quantity);
}

/**
 * Helper to classify emission level
 */
function getEmissionLevel($category, $co2) {
    // Context-sensitive thresholding
    switch ($category) {
        case 'Transport':
            if ($co2 < 5) return 'Low';
            if ($co2 < 20) return 'Medium';
            return 'High';
        case 'Electricity':
            if ($co2 < 25) return 'Low';
            if ($co2 < 100) return 'Medium';
            return 'High';
        case 'Food':
            if ($co2 < 5) return 'Low';
            if ($co2 < 20) return 'Medium';
            return 'High';
        case 'Shopping':
            if ($co2 < 15) return 'Low';
            if ($co2 < 60) return 'Medium';
            return 'High';
        case 'Waste':
            if ($co2 < 5) return 'Low';
            if ($co2 < 15) return 'Medium';
            return 'High';
        case 'Fuel':
            if ($co2 < 10) return 'Low';
            if ($co2 < 30) return 'Medium';
            return 'High';
        default:
            if ($co2 < 10) return 'Low';
            if ($co2 < 40) return 'Medium';
            return 'High';
    }
}
?>
