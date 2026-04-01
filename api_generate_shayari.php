<?php
session_start();
require_once 'includes/ai_generator.php';

$mood = isset($_GET['mood']) ? $_GET['mood'] : 'happy';
$lang = $_SESSION['user_language'] ?? 'English';

$new_shayari = SoulMateAI::generate($mood, $lang);

echo json_encode(['shayari' => $new_shayari, 'language' => $lang]);
?>
