<?php
declare(strict_types=1);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "solestride_db";

function get_db_connection(): mysqli
{
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;

    $conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

    if (!$conn) {
        error_log("DB connection failed: " . mysqli_connect_error());
        die("Connection failed. Check server logs.");
    }

    mysqli_set_charset($conn, "utf8mb4");

    return $conn;
}

$conn = get_db_connection();