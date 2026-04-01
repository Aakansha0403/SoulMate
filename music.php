<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db.php';
require_once 'includes/ai_generator.php';
$user_id = $_SESSION['user_id'];

// 1. Fetch User Language & Mood Context
$lang_stmt = $conn->prepare("SELECT language FROM users WHERE id = ?");
$lang_stmt->bind_param("i", $user_id);
$lang_stmt->execute();
$lang_stmt->bind_result($user_lang);
$lang_stmt->fetch();
$user_lang = $user_lang ?? 'Hindi';
$lang_stmt->close();
$_SESSION['user_language'] = $user_lang;

$latest_mood = 'happy';
$m_stmt = $conn->prepare("SELECT mood FROM moods WHERE user_id = ? ORDER BY date DESC LIMIT 1");
$m_stmt->bind_param("i", $user_id);
$m_stmt->execute();
$m_stmt->bind_result($db_mood);
if($m_stmt->fetch()) $latest_mood = $db_mood;
$m_stmt->close();

$current_mood = $_GET['mood'] ?? $latest_mood;
$search_query = $_GET['search'] ?? '';

// 2. Fetch AI-Generated Emotional Context
$ai_shayari = SoulMateAI::generate($current_mood, $user_lang);

// 3. CORE MUSIC ENGINE: YouTube Recommendation Logic (Fixing Auto-Load)
$songs = [];
// First Pass: Match Mood AND Language
$stmt = $conn->prepare("SELECT id, title, youtube_id, mood, language FROM songs WHERE mood = ? AND language = ? ORDER BY RAND() LIMIT 5");
$stmt->bind_param("ss", $current_mood, $user_lang);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $songs[] = $row;
$stmt->close();

// Second Pass FALLBACK: If no exact linguistic match, ignore language to provide content
if(empty($songs) && empty($search_query)) {
    $stmt = $conn->prepare("SELECT id, title, youtube_id, mood, language FROM songs WHERE mood = ? ORDER BY RAND() LIMIT 5");
    $stmt->bind_param("s", $current_mood);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $songs[] = $row;
    $stmt->close();
}

// Search Override
if(!empty($search_query)) {
    $songs = []; // Clear for fresh search
    $s_stmt = $conn->prepare("SELECT id, title, youtube_id, mood, language FROM songs WHERE title LIKE ? OR mood LIKE ?");
    $s_term = "%$search_query%";
    $s_stmt->bind_param("ss", $s_term, $s_term);
    $s_stmt->execute();
    $s_res = $s_stmt->get_result();
    while($row = $s_res->fetch_assoc()) $songs[] = $row;
    $s_stmt->close();
}

// 4. RANDOM RECOMMENDATIONS: ORDER BY RAND()
$recommendations = [];
$rec_stmt = $conn->query("SELECT id, title, youtube_id, mood, language FROM songs ORDER BY RAND() LIMIT 6");
while($r = $rec_stmt->fetch_assoc()) {
    $recommendations[] = $r;
}

// 5. FETCH USER PLAYLIST
$playlist_ids = [];
$p_stmt = $conn->prepare("SELECT song_id FROM user_playlists WHERE user_id = ?");
$p_stmt->bind_param("i", $user_id);
$p_stmt->execute();
$p_res = $p_stmt->get_result();
while($p = $p_res->fetch_assoc()) $playlist_ids[] = $p['song_id'];
$p_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Cloud Music Engine</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #090614, #191238, #000000);
            color: white; font-family: 'Poppins', sans-serif; min-height: 100vh; margin: 0; padding-bottom: 120px;
        }
        .music-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        
        /* Unified Search & Mood Controls */
        .controls-grid { display: flex; gap: 20px; align-items: center; margin-bottom: 30px; flex-wrap: wrap; }
        .search-box { flex: 1; min-width: 300px; display: flex; gap: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 10px 15px; border-radius: 12px; }
        .search-box input { background: transparent; border: none; color: white; width: 100%; outline: none; }
        .search-box button { background: #d475ff; border: none; color: white; padding: 5px 15px; border-radius: 8px; cursor: pointer; }

        .mood-tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; }
        .mood-tab { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 8px 20px; border-radius: 20px; color: rgba(255,255,255,0.6); text-decoration: none; transition: all 0.3s; white-space: nowrap; }
        .mood-tab:hover, .mood-tab.active { background: #d475ff; color: white; border-color: #d475ff; box-shadow: 0 0 15px rgba(212, 117, 255, 0.4); }

        /* Main Content Grid */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }

        /* Song Card List */
        .song-list { display: flex; flex-direction: column; gap: 15px; }
        .song-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 15px 20px; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
        .song-card:hover { background: rgba(255,255,255,0.08); transform: scale(1.01); }
        .song-info { display: flex; align-items: center; gap: 20px; flex:1; cursor:pointer;}
        .yt-thumb { width: 50px; height: 50px; border-radius: 8px; background: #222; overflow: hidden; }
        .yt-thumb img { width: 100%; height: 100%; object-fit: cover; }
        
        .song-meta h3 { margin: 0; font-size: 1.1rem; font-weight: 500; }
        .song-meta p { display:flex; gap:10px; margin: 5px 0 0 0; font-size: 0.8rem; color: rgba(255,255,255,0.4); }
        .lang-badge { background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 4px; color: #d475ff; }

        .song-actions { display: flex; gap: 15px; align-items: center; }
        .btn-playlist { background: transparent; border: none; color: white; font-size: 1.5rem; cursor: pointer; transition: 0.3s; }
        .btn-playlist.active { color: #ff7eb3; }

        /* AI Shayari Section */
        .ai-glass-box { background: rgba(212, 117, 255, 0.05); border: 1px solid rgba(212, 117, 255, 0.15); border-left: 5px solid #d475ff; padding: 25px; border-radius: 20px; margin-bottom: 30px; }
        .ai-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: #d475ff; margin-bottom: 15px; display: block; }
        .ai-content { font-size: 1.25rem; font-style: italic; line-height: 1.6; }

        /* Recommendation Side */
        .rec-card { margin-top: 20px; }
        .rec-item { display: flex; gap: 15px; margin-bottom: 15px; align-items: center; cursor: pointer; opacity: 0.7; transition: 0.3s; }
        .rec-item:hover { opacity: 1; transform: translateX(5px); }
        .rec-item img { width: 40px; height: 40px; border-radius: 6px; }

        @media (max-width: 900px) {
            .content-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="music-container">
        
        <div class="ai-glass-box">
            <span class="ai-title">🧠 Emotional Context Generation</span>
            <div class="ai-content" id="ai-shayari-box"><?php echo htmlspecialchars($ai_shayari); ?></div>
        </div>

        <div class="controls-grid">
            <div class="mood-tabs">
                <a href="music.php?mood=happy" class="mood-tab <?php echo ($current_mood == 'happy') ? 'active' : ''; ?>">😊 Happy</a>
                <a href="music.php?mood=sad" class="mood-tab <?php echo ($current_mood == 'sad') ? 'active' : ''; ?>">😔 Sad</a>
                <a href="music.php?mood=calm" class="mood-tab <?php echo ($current_mood == 'calm') ? 'active' : ''; ?>">🧘 Calm</a>
                <a href="music.php?mood=romantic" class="mood-tab <?php echo ($current_mood == 'romantic') ? 'active' : ''; ?>">💖 Romantic</a>
                <a href="music.php?mood=angry" class="mood-tab <?php echo ($current_mood == 'angry') ? 'active' : ''; ?>">🔥 Angry</a>
            </div>
            <form class="search-box" action="music.php" method="GET">
                <input type="text" name="search" placeholder="Search for artists, songs..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="content-grid">
            <div class="song-list">
                <h2 style="font-weight: 500; font-size: 1.4rem; margin-bottom: 20px;">
                    <?php echo !empty($search_query) ? "Search Results: ".htmlspecialchars($search_query) : "Recommended for ".ucfirst($current_mood)." Mood"; ?>
                </h2>
                
                <?php if(empty($songs)): ?>
                    <p style="opacity:0.5; padding:40px; text-align:center; background:rgba(255,255,255,0.02); border-radius:20px;">No songs found for this filter. Try another mood or search.</p>
                <?php endif; ?>

                <?php foreach($songs as $song): ?>
                    <div class="song-card">
                        <div class="song-info" onclick="playSong('<?php echo $song['youtube_id']; ?>', '<?php echo htmlspecialchars(addslashes($song['title'])); ?>')">
                            <div class="yt-thumb">
                                <img src="https://img.youtube.com/vi/<?php echo $song['youtube_id']; ?>/default.jpg" alt="thumbnail">
                            </div>
                            <div class="song-meta">
                                <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                                <p>
                                    <span class="lang-badge"><?php echo $song['language']; ?></span> 
                                    • Mood: <?php echo ucfirst($song['mood']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="song-actions">
                            <button class="btn-playlist <?php echo in_array($song['id'], $playlist_ids) ? 'active' : ''; ?>" 
                                    onclick="togglePlaylist(<?php echo $song['id']; ?>, this)" title="Add to Playlist">
                                <?php echo in_array($song['id'], $playlist_ids) ? '♥' : '♡'; ?>
                            </button>
                            <button class="btn-playlist" onclick="playSong('<?php echo $song['youtube_id']; ?>', '<?php echo htmlspecialchars(addslashes($song['title'])); ?>')" title="Play">▶</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="recommendations">
                <h2 style="font-weight: 500; font-size: 1.2rem; margin-bottom: 20px; color: #ff7eb3;">✨ Discover More</h2>
                <div class="glass-card" style="padding:20px; background:rgba(255,255,255,0.02);">
                    <?php foreach($recommendations as $rec): ?>
                        <div class="rec-item" onclick="playSong('<?php echo $rec['youtube_id']; ?>', '<?php echo htmlspecialchars(addslashes($rec['title'])); ?>')">
                            <img src="https://img.youtube.com/vi/<?php echo $rec['youtube_id']; ?>/default.jpg" alt="rec">
                            <div style="flex:1;">
                                <div style="font-size:0.95rem; margin-bottom:3px;"><?php echo htmlspecialchars($rec['title']); ?></div>
                                <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);"><?php echo $rec['language']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        function playSong(yid, title) {
            // Trigger the Global YouTube Sync Player
            if(window.globalPlayer && typeof window.globalPlayer.playYouTube === 'function') {
                window.globalPlayer.playYouTube(yid, title);
            }
        }

        async function togglePlaylist(songId, btn) {
            const isActive = btn.classList.contains('active');
            const action = isActive ? 'remove' : 'add';
            
            try {
                const response = await fetch('api_playlist.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: action, song_id: songId })
                });
                const result = await response.json();
                if(result.success) {
                    btn.classList.toggle('active');
                    btn.innerText = isActive ? '♡' : '♥';
                }
            } catch(e) { console.error("Playlist Errr:", e); }
        }
    </script>

    <?php include 'includes/global_player.php'; ?>
</body>
</html>
