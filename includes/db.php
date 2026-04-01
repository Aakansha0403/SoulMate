<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "soulmate";

// 1. Establish initial connection WITHOUT database selection to prevent "Unknown Database" error
$conn = new mysqli($host, $user, $password);

// 2. Validate initial server connection
if ($conn->connect_error) {
    die("<div style='color:red; font-family:sans-serif; text-align:center; margin-top:50px;'><strong>Critical Error:</strong> Could not connect to MySQL server. Ensure XAMPP MySQL is running.<br>" . $conn->connect_error . "</div>");
}

// 3. Automatically generate the database if it is missing
$create_db_query = "CREATE DATABASE IF NOT EXISTS `$database`";
if (!$conn->query($create_db_query)) {
    die("<div style='color:red;'>Error generating database: " . $conn->error . "</div>");
}

// 4. Connect explicitly into the database 
if (!$conn->select_db($database)) {
    die("<div style='color:red;'>Error selecting database: " . $conn->error . "</div>");
}

// 5. Automatic Structural Maintenance: Validate and build all required tables natively
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        language VARCHAR(20) DEFAULT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        avatar VARCHAR(100) DEFAULT NULL
    )",
    "CREATE TABLE IF NOT EXISTS moods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        mood VARCHAR(50) NOT NULL,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS diary (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        mood VARCHAR(50),
        note TEXT NOT NULL,
        reason TEXT,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        points INT DEFAULT 0,
        level VARCHAR(50) DEFAULT 'Beginner 🌱',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS songs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) DEFAULT NULL,
        youtube_id VARCHAR(100) DEFAULT NULL,
        mood VARCHAR(50) NOT NULL,
        language VARCHAR(20) DEFAULT 'English',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS user_playlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        song_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_song_playlist (user_id, song_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS user_songs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        song_title VARCHAR(255) NOT NULL,
        song_path VARCHAR(500) NOT NULL,
        mood VARCHAR(50) NOT NULL,
        language VARCHAR(20) DEFAULT 'English',
        last_played TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY user_song_unique (user_id, song_path),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS community_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS post_reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction VARCHAR(50) NOT NULL,
        UNIQUE KEY user_post_unique (user_id, post_id),
        FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

// Execute arrays securely
foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        die("<div style='color:red;'>Error building table framework: " . $conn->error . "</div>");
    }
}

// 6. SCHEMA MIGRATION PATCH: Manually inject columns if table already existed without them
$migrations = [
    ['table' => 'users', 'column' => 'language', 'sql' => "ALTER TABLE users ADD language VARCHAR(20) DEFAULT 'Hindi'"],
    ['table' => 'users', 'column' => 'profile_image', 'sql' => "ALTER TABLE users ADD profile_image VARCHAR(255) DEFAULT NULL"],
    ['table' => 'users', 'column' => 'avatar', 'sql' => "ALTER TABLE users ADD avatar VARCHAR(100) DEFAULT NULL"],
    ['table' => 'songs', 'column' => 'language', 'sql' => "ALTER TABLE songs ADD language VARCHAR(20) DEFAULT 'English'"],
    ['table' => 'songs', 'column' => 'youtube_id', 'sql' => "ALTER TABLE songs ADD youtube_id VARCHAR(100) DEFAULT NULL"],
    ['table' => 'user_songs', 'column' => 'language', 'sql' => "ALTER TABLE user_songs ADD language VARCHAR(20) DEFAULT 'English'"]
];

foreach ($migrations as $m) {
    $check = $conn->query("SHOW COLUMNS FROM `{$m['table']}` LIKE '{$m['column']}'");
    if ($check->num_rows == 0) {
        $conn->query($m['sql']);
    }
}
?>
