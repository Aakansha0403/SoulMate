<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['language'])) {
    $lang = $data['language'];
    
    // Save to user database record
    $stmt = $conn->prepare("UPDATE users SET language = ? WHERE id = ?");
    $stmt->bind_param("si", $lang, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user_language'] = $lang;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'No language provided']);
}
?>
