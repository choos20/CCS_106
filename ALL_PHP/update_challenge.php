<?php
session_start();
header('Content-Type: application/json');

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'CCS_106_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['challenge_id']) || !isset($input['progress'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$challenge_id = (int)$input['challenge_id'];
$progress = (int)$input['progress'];

// Get challenge target
$stmt = $conn->prepare("SELECT target_matches FROM challenges WHERE id = ?");
$stmt->bind_param("i", $challenge_id);
$stmt->execute();
$result = $stmt->get_result();
$challenge = $result->fetch_assoc();
$stmt->close();

if (!$challenge) {
    echo json_encode(['success' => false, 'error' => 'Challenge not found']);
    exit;
}

$target = $challenge['target_matches'];
$completed = ($progress >= $target) ? 1 : 0;

// Insert or update user_challenges
$stmt = $conn->prepare("INSERT INTO user_challenges (user_id, challenge_id, progress, completed) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                        progress = VALUES(progress), 
                        completed = VALUES(completed),
                        completed_at = IF(VALUES(completed)=1 AND completed=0, NOW(), completed_at)");
$stmt->bind_param("iiii", $user_id, $challenge_id, $progress, $completed);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success, 'completed' => $completed]);