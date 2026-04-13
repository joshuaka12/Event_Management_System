<?php
/**
 * config/db.php
 * Database connection using MySQLi.
 * Edit the constants below to match your local MySQL setup.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ← your MySQL username
define('DB_PASS', '');            // ← your MySQL password
define('DB_NAME', 'campus_ems');

/**
 * Returns a MySQLi connection object.
 * Terminates with an error message if the connection fails.
 */
function db_connect(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        // In production, log this error rather than exposing it.
        die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
                <h2>Database Connection Failed</h2>
                <p>' . htmlspecialchars($conn->connect_error) . '</p>
                <p>Please check your <code>config/db.php</code> credentials.</p>
             </div>');
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
