<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'includes/db.php';

// Ensure table exists for tracking user listening history
$conn->query("CREATE TABLE IF NOT EXISTS user_songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_title VARCHAR(255) NOT NULL,
    song_path VARCHAR(500) NOT NULL,
    mood VARCHAR(50) NOT NULL,
    last_played TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_song_unique (user_id, song_path),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['song_title']) && isset($data['song_path']) && isset($data['mood'])) {
    $user_id = $_SESSION['user_id'];
    $title = $data['song_title'];
    $path = $data['song_path'];
    $mood = $data['mood'];
    $lang = $data['language'] ?? 'English';

    // 1. Insert or update last_played in history
    $stmt = $conn->prepare("INSERT INTO user_songs (user_id, song_title, song_path, mood, language) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE last_played = CURRENT_TIMESTAMP, language = VALUES(language)");
    $stmt->bind_param("issss", $user_id, $title, $path, $mood, $lang);
    $stmt->execute();
    $stmt->close();

    // 2. SMART AUTO-DETECTION: Update user language if not set
    $check_stmt = $conn->prepare("SELECT language FROM users WHERE id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $res = $check_stmt->get_result();
    $current_lang = ($res->fetch_assoc())['language'] ?? null;
    $check_stmt->close();

    if ($current_lang === null) {
        $upd_stmt = $conn->prepare("UPDATE users SET language = ? WHERE id = ?");
        $upd_stmt->bind_param("si", $lang, $user_id);
        $upd_stmt->execute();
        $upd_stmt->close();
        $_SESSION['user_language'] = $lang;
    }

    echo json_encode(['success' => true, 'detected_msg' => 'We created this based on your vibe 🎧']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
}
?>
