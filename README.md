# 🌿 EcoTrace — Carbon Footprint Management System

EcoTrace is a professional, database-driven, and highly polished **Carbon Footprint Management System** developed as an engineering DBMS mini-project. It operates on a real-world relational database model, integrating a dynamic, premium web dashboard that calculates, audits, and presents environmental impact analytics.

The system relies strictly on a secure, normalized MySQL backend and an elegant PHP controller layer, ensuring **100% database-driven calculation factors, dynamic real-time reporting, and zero dummy/hardcoded values.**

---

## 🚀 Key Features

### 👤 User Features
*   **Dynamic Analytics Dashboard**: Rich visualizations powered by Chart.js showing weekly carbon trends, dominant impact classifications, circular sustainability scores, and interactive KPI cards.
*   **3-Step Activity Logging Wizard**: Highly interactive form wizard with real-time client-side calculation previews, slide indicators, and step validation.
    *   *Supported Sectors*: **Transport** (Car, Bus, Train, Bike, Flight), **Electricity** (Grid Average), **Food** (Beef, Chicken, Fish, Vegetables), **Shopping** (Electronics, Clothing, Shoes), **Waste** (Plastic, Paper, Organic), and **Fuel** (Petrol, Diesel, LPG, CNG).
*   **Searchable Activity Logs**: Complete history page allowing users to search keywords and filter logs by category, emission impact level, raw input quantity, or CO₂ values in ascending/descending order.
*   **Eco-Friendly Recommendations**: A fully database-driven recommendation engine that matches user impact levels to custom eco-tips to help lower their carbon score.
*   **Dynamic Monthly Reports**: Automatically consolidates all logs into dynamic monthly/annual summaries and calculates user sustainability scores.
*   **Community Leaderboard**: Gamified social rankings page sorting users based on their active dynamic carbon mitigation scores.
*   **DBMS Conceptual Section**: A built-in educational corner detailing the underlying database schema, normalization forms, indexes, and entity-relationship models for presentation purposes.

### 👑 Administrative Features
*   **Master Administrative Portal**: Secure backend access for system auditors with a dedicated panel.
*   **Warriors Account Directory**: Manage, view, search, and delete registered user accounts.
*   **Master Activity Database Logs**: Audit and remove any recorded carbon activity across the entire platform.
*   **Dynamic Factor Multiplier Editor**: Live CRUD interface to update, insert, or delete emission coefficients in the `emission_factors` table, immediately modifying future global calculations in real-time.

---

## 🛠️ Technology Stack

*   **Frontend**: HTML5, Vanilla CSS3 (Custom-tailored glassmorphism, responsive grid layout, micro-animations, standard and dark modes), Modern Vanilla JavaScript, FontAwesome Icons.
*   **Data Visualization**: Chart.js (Line Chart and Doughnut Category Split).
*   **Backend**: Object-Oriented PHP 8.x.
*   **Database**: MySQL / MariaDB (Relational design, indexing, foreign keys, cascade deletions).

---

## 📊 Database Architecture

The system utilizes a 3rd Normal Form (3NF) relational schema consisting of the following primary tables:

1.  **`users`**: Contains credential hashes, demographics, location, and account dates.
2.  **`admin`**: Stores administrative credentials and profile data.
3.  **`emission_factors`**: The central data-driving table mapping categories and sub-categories to their exact CO₂ emission values per unit.
4.  **`activities`**: Stores user-logged carbon events tied to `users.user_id` via a foreign key with cascade deletion.
5.  **`recommendations`**: Houses eco-tips dynamically resolved by category and carbon level.
6.  **`reports`**: Stores aggregated monthly stats with a unique composite index constraint on `(user_id, report_month, report_year)` to prevent duplicate report generations.

---

## 🛠️ Quick Installation & Setup

### Prerequisites
*   [XAMPP for Windows](https://www.apachefriends.org/index.html) or equivalent local server stack (Apache, PHP, MySQL).
*   Git command line client.

### Step-by-Step Installation

1.  **Move to Server Root**:
    Clone or copy this entire project folder into your active local server root:
    ```bash
    C:\xampp\htdocs\finaldbmsproject1
    ```

2.  **Start Services**:
    Open the **XAMPP Control Panel** and start both **Apache Web Server** and **MySQL Database**.

3.  **Auto-Initialization (Self-Healing Connection)**:
    Simply open your web browser and navigate to:
    ```http
    http://localhost/finaldbmsproject1/index.php
    ```
    *The system features a self-healing bootstrapper! On your very first visit, the backend will automatically connect to your MySQL instance, generate the database `carbon_footprint_db`, structure all tables, establish primary/foreign key relations, build performance indexes, and pre-seed all standard real-world multipliers, demo accounts, and eco-tips automatically.*

---

## 🔑 Default Evaluation Accounts

For quick presentation and assessment, you can use the following pre-seeded credentials:

### Standard User Profile
*   **Email**: `demo@example.com`
*   **Password**: `demo123`

### System Administrator
*   **Username**: `admin`
*   **Password**: `admin123`

---

## 📈 DBMS Presentation Highlights
*   **Cascade Deletes**: If a user is deleted via the Admin interface, all associated carbon logs and monthly reports are immediately purged using active relational constraints.
*   **Indexes**: Custom performance indexes (`idx_activities_user_date`, `idx_activities_category`, `idx_reports_user_year_month`) are optimized to handle large datasets.
*   **Strict Security**: Uses PDO parameterized prepared statements across every SQL query, completely neutralizing SQL Injection vulnerabilities.
