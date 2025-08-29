<?php
// Simple MySQLi connection setup
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'houserentalv2DB');

// Create database connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection - don't expose internals to users
if ($mysqli->connect_errno) {
    error_log('Database connection failed: ' . $mysqli->connect_error);
    die('Database connection failed. Please try again later.');
}

$mysqli->set_charset('utf8mb4');

function db_query($sql) {
    global $mysqli;
    $result = $mysqli->query($sql);
    if (!$result) {
        error_log('Query failed: ' . $mysqli->error . ' | SQL: ' . $sql);
        die('Database query failed. Please try again later.');
    }
    return $result;
}

function db_fetch_array($result) {
    return $result->fetch_assoc();
}

register_shutdown_function(function() {
    global $mysqli;
    if ($mysqli) {
        $mysqli->close();
    }
});
?>