<?php
$conn = new mysqli("localhost", "root", "", "CCS_106_db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
