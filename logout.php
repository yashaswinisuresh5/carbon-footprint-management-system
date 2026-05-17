<?php
// logout.php
// Secure Session Termination - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// 1. Clear session variables
$_SESSION = array();

// 2. Terminate session cookie completely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy standard session
session_destroy();

// 4. Redirect to index homepage
header("Location: index.php");
exit;
