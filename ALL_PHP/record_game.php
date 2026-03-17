<?php
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Database connection
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'CCS_106_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$score = intval($_POST['score'] ?? 0);
$moves = intval($_POST['moves'] ?? 0);
$time_taken = intval($_POST['time_taken'] ?? 0);

// Insert game session
$stmt = $conn->prepare("INSERT INTO game_sessions (user_id, score, moves, time_taken) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiii", $user_id, $score, $moves, $time_taken);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record game']);
}

$stmt->close();
$conn->close();
?>