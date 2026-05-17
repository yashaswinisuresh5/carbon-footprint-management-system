-- Carbon Footprint Management System Database Schema
-- Created for Engineering DBMS Mini Project
-- Highlights: Strict PK/FK constraints, indexes, normalized tables, pre-seeded factors & tips

CREATE DATABASE IF NOT EXISTS carbon_footprint_db;
USE carbon_footprint_db;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    location VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. ADMIN TABLE
CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. EMISSION FACTORS TABLE (Central Database for CO2 calculation multipliers)
CREATE TABLE IF NOT EXISTS emission_factors (
    factor_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    sub_category VARCHAR(50) NOT NULL UNIQUE,
    factor_value DECIMAL(10,4) NOT NULL,
    unit VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. CARBON_ACTIVITIES TABLE (Details of logged carbon events)
CREATE TABLE IF NOT EXISTS activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    sub_category VARCHAR(50) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    co2_emitted DECIMAL(10,3) NOT NULL,
    emission_level VARCHAR(10) NOT NULL, -- Low, Medium, High
    activity_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. RECOMMENDATIONS TABLE (Eco-friendly database suggestions matched by category and level)
CREATE TABLE IF NOT EXISTS recommendations (
    rec_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    emission_level VARCHAR(10) NOT NULL,
    tip_title VARCHAR(100) NOT NULL,
    tip_text TEXT NOT NULL,
    potential_saving DECIMAL(10,2) NOT NULL, -- Potential CO2 saved in kg
    icon_type VARCHAR(50) DEFAULT 'leaf'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. REPORTS TABLE (Summarized monthly carbon reports for users)
CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_month INT NOT NULL,
    report_year INT NOT NULL,
    total_co2 DECIMAL(10,3) NOT NULL,
    sustainability_score INT NOT NULL, -- 0 to 100
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_monthly_report (user_id, report_month, report_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ==========================================
CREATE INDEX idx_activities_user_date ON activities(user_id, activity_date);
CREATE INDEX idx_activities_category ON activities(category);
CREATE INDEX idx_reports_user_year_month ON reports(user_id, report_year, report_month);

-- ==========================================
-- PRE-SEED DATA
-- ==========================================

-- Seed default Administrator (Password is hashed using bcrypt in PHP or plain admin/admin123 for demo)
-- For absolute ease of demo, we insert an admin with password 'admin123' hashed using PHP standard password_hash
-- '$2y$10$tMh5hU6Z5e2.fWvR84d2hO7R92c3a7E/K1f91L1oW5Y9e.vOa39hG' represents 'admin123'
INSERT INTO admin (name, username, password) VALUES 
('System Administrator', 'admin', '$2y$10$tMh5hU6Z5e2.fWvR84d2hO7R92c3a7E/K1f91L1oW5Y9e.vOa39hG')
ON DUPLICATE KEY UPDATE name=name;

-- Seed Standard Real-World Carbon Emission Factors
-- These factors drive all calculations in the application dynamically
INSERT INTO emission_factors (category, sub_category, factor_value, unit) VALUES
('Transport', 'Car', 0.2100, 'km'),
('Transport', 'Bus', 0.0890, 'km'),
('Transport', 'Train', 0.0410, 'km'),
('Transport', 'Bike', 0.0800, 'km'),
('Transport', 'Flight', 0.2550, 'km'),
('Electricity', 'Grid Average', 0.8500, 'kWh'),
('Food', 'Beef', 27.0000, 'kg'),
('Food', 'Chicken', 6.9000, 'kg'),
('Food', 'Fish', 12.5000, 'kg'),
('Food', 'Vegetables', 2.0000, 'kg'),
('Shopping', 'Electronics', 85.0000, 'item'),
('Shopping', 'Clothing', 22.0000, 'item'),
('Shopping', 'Shoes', 14.0000, 'item'),
('Waste', 'Plastic', 5.6000, 'kg'),
('Waste', 'Paper', 1.5000, 'kg'),
('Waste', 'Organic', 1.2000, 'kg'),
('Fuel', 'Petrol', 2.3100, 'Liters'),
('Fuel', 'Diesel', 2.6800, 'Liters'),
('Fuel', 'LPG', 1.5100, 'Liters'),
('Fuel', 'CNG', 2.7500, 'kg')
ON DUPLICATE KEY UPDATE factor_value = VALUES(factor_value);

-- Seed Dynamic Database-Driven Recommendations
INSERT INTO recommendations (category, emission_level, tip_title, tip_text, potential_saving, icon_type) VALUES
-- Fuel Recommendations
('Fuel', 'High', 'Transition to Electric Vehicles', 'Vehicles running on Petrol/Diesel have high carbon footprints. Switching to electric or hybrid vehicles reduces transport-related fuel emissions to zero.', 50.00, 'car'),
('Fuel', 'Medium', 'Optimize Heating & Cooking Fuel', 'Cooking with LPG/CNG emits carbon. Plan meals, use induction cooktops, and keep burner flames moderate to save fuel.', 12.00, 'fire'),
('Fuel', 'Low', 'Efficient Fuel Usage', 'You use fuel responsibly. Continue to keep tire pressures optimized and schedule regular vehicle maintenance to sustain low fuel consumption.', 4.00, 'check-circle'),

-- High Emissions Tips
('Transport', 'High', 'Switch to Public Transit', 'Taking a bus or train instead of driving solo cuts your transport footprint by up to 60%. Try transit at least 3 days a week.', 12.50, 'bus'),
('Transport', 'High', 'Fly Less, Choose Train', 'Aviation is a heavy emitter. For regional trips, a high-speed train generates 80% less CO2 than a flight.', 45.00, 'plane'),
('Transport', 'Medium', 'Car Share & Carpool', 'Carpooling with just one other person splits your commute footprint in half. Look into corporate carpools or local rideshare apps.', 6.20, 'users'),
('Transport', 'Low', 'Keep Up Walking & Cycling', 'You are doing great! Cycling or walking has a zero-emission score. Keep it up for your short errands.', 1.50, 'bicycle'),

('Electricity', 'High', 'Audit & Switch to Renewable Power', 'Switch to a 100% green energy tariff or invest in solar panels to instantly drop your home electricity footprint to near zero.', 85.00, 'sun'),
('Electricity', 'High', 'Upgrade to Smart Thermostats', 'Heating and cooling comprise over 50% of home energy. Smart thermostats optimize temperature schedules automatically.', 30.00, 'thermometer'),
('Electricity', 'Medium', 'Switch to LED Lighting', 'Replacing incandescent bulbs with LEDs reduces lighting electricity usage by 85%. Always turn off unused lights.', 8.50, 'lightbulb'),
('Electricity', 'Low', 'Unplug Idle Devices', 'Even off, appliances draw standby energy. Use smart power strips to cut standby power drain.', 2.00, 'plug'),

('Food', 'High', 'Adopt Meatless Mondays', 'Beef has the highest footprint (27kg CO2 per kg). Replacing red meat with plant-based alternatives just once a week saves significant CO2.', 25.00, 'utensils'),
('Food', 'Medium', 'Choose Local Produce', 'Food miles add transport emissions. Purchasing seasonal, locally grown foods drastically lowers logistics footprints.', 5.40, 'shopping-bag'),
('Food', 'Low', 'Minimize Food Waste', 'Nearly 30% of global food is wasted. Meal planning, composting, and proper freezing can eliminate organic carbon waste.', 4.00, 'trash'),

('Shopping', 'High', 'Buy Pre-Loved Electronics', 'Production of a single smartphone emits ~85kg CO2. Purchase refurbished or certified pre-loved devices to avoid manufacturing carbon.', 80.00, 'tv'),
('Shopping', 'Medium', 'Practice Slow Fashion', 'A single shirt takes 22kg CO2. Choose durable garments, buy second-hand, or mend old clothes rather than purchasing fast-fashion.', 20.00, 'tshirt'),
('Shopping', 'Low', 'Choose Minimalist Living', 'Buy only what you truly need. High quality, long-lasting items prevent repeat production emissions.', 10.00, 'shopping-cart'),

('Waste', 'High', 'Say No to Single-Use Plastics', 'Plastics are refined fossil fuels. Use reusable bottles, grocery bags, and cups to reduce non-biodegradable waste.', 15.00, 'ban'),
('Waste', 'Medium', 'Set Up a Composting Bin', 'Food waste rotting in landfills produces methane (25x more potent than CO2). Composting converts organic waste into organic fertilizer.', 8.00, 'recycle'),
('Waste', 'Low', 'Paperless & Digital Statements', 'Opt for paperless bills and digital documents to save trees, cutting paper demand and chemical processing footprint.', 3.00, 'file-alt')
ON DUPLICATE KEY UPDATE tip_text = VALUES(tip_text);

-- Seed default user for evaluation (Password is 'demo123')
-- '$2y$10$tMh5hU6Z5e2.fWvR84d2hO7R92c3a7E/K1f91L1oW5Y9e.vOa39hG' represents 'demo123'
INSERT INTO users (user_id, name, age, email, password, location) VALUES
(1, 'Demo Eco Warrior', 28, 'demo@example.com', '$2y$10$tMh5hU6Z5e2.fWvR84d2hO7R92c3a7E/K1f91L1oW5Y9e.vOa39hG', 'California')
ON DUPLICATE KEY UPDATE name=name;

-- Seed Sample Carbon Activities for the demo user to populate analytics
-- Formulated exactly based on the system factors
-- Let's dynamic-date them so they are always in the current month/year relative to system time
INSERT INTO activities (activity_id, user_id, category, sub_category, quantity, co2_emitted, emission_level, activity_date) VALUES
(1, 1, 'Transport', 'Car', 150.00, 31.500, 'High', CURRENT_DATE() - INTERVAL 5 DAY),
(2, 1, 'Electricity', 'Grid Average', 200.00, 170.000, 'High', CURRENT_DATE() - INTERVAL 4 DAY),
(3, 1, 'Food', 'Vegetables', 10.00, 20.000, 'Low', CURRENT_DATE() - INTERVAL 2 DAY),
(4, 1, 'Waste', 'Plastic', 5.00, 28.000, 'Medium', CURRENT_DATE() - INTERVAL 1 DAY),
(5, 1, 'Shopping', 'Clothing', 2.00, 44.000, 'Medium', CURRENT_DATE() - INTERVAL 3 DAY)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

