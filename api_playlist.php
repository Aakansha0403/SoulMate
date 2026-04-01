<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';
$song_id = intval($data['song_id'] ?? 0);

if ($action === 'add') {
    $stmt = $conn->prepare("INSERT IGNORE INTO user_playlists (user_id, song_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $song_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} elseif ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM user_playlists WHERE user_id = ? AND song_id = ?");
    $stmt->bind_param("ii", $user_id, $song_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
