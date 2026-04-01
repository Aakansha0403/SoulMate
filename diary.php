<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

// Automatically build 'diary' table as requested backend framework
$conn->query("CREATE TABLE IF NOT EXISTS diary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood VARCHAR(50),
    note TEXT NOT NULL,
    reason TEXT,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$msg = '';
// Secure Insert System Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note']) && isset($_POST['mood'])) {
    $mood = trim($_POST['mood']);
    $note = trim($_POST['note']);
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    if (!empty($note) && !empty($mood)) {
        // Prevent SQL Injection natively
        $stmt = $conn->prepare("INSERT INTO diary (user_id, mood, note, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $mood, $note, $reason);
        
        if ($stmt->execute()) {
            $msg = "<div class='success-msg'>✓ Thought securely buried onto the database.</div>";
            
            // Feature 8: Automatic XP Rewards (+10 for Diary)
            $conn->query("INSERT IGNORE INTO rewards (user_id, points, level) VALUES ($user_id, 0, 'Beginner 🌱')");
            $conn->query("UPDATE rewards SET points = points + 10 WHERE user_id = $user_id");
            
            // Dynamic Recalculation
            $res = $conn->query("SELECT points FROM rewards WHERE user_id = $user_id");
            if ($row = $res->fetch_assoc()) {
                $p = $row['points']; $lvl = 'Beginner 🌱';
                if ($p >= 21 && $p <= 50) $lvl = 'Explorer 🌍';
                elseif ($p > 50) $lvl = 'Soul Master 🌟';
                $conn->query("UPDATE rewards SET level = '$lvl' WHERE user_id = $user_id");
            }
        }
        $stmt->close();
    } else {
        $msg = "<div class='error-msg'>✖ Mood and Note fields are required.</div>";
    }
}

// Memory Execution Fetch
$entries = [];
$stmt = $conn->prepare("SELECT mood, note, reason, date FROM diary WHERE user_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $entries[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Centralized Diary</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e1366, #4a0e4e);
            color: white;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        /* Unified Top Bar */
        .header {
            width: 100%; padding: 20px 5%; display: flex; justify-content: space-between; align-items: center;
            box-sizing: border-box; background: rgba(0,0,0,0.3); border-bottom: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(15px);
        }
        .header h1 { margin: 0; font-size: 1.5rem; background: linear-gradient(to right, #b452ff, #ff7eb3); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .header a { color: rgba(255,255,255,0.6); text-decoration: none; transition: color 0.3s; }
        .header a:hover { color: #d475ff; }

        .dashboard-container {
            max-width: 1000px; width: 100%; padding: 50px 20px; display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; box-sizing: border-box;
            animation: fadeIn 1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Responsive Architecture Box */
        .glass-panel {
            background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px; padding: 35px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            display:flex; flex-direction:column;
        }
        
        /* Modern Inputs */
        .edit-input { width: 100%; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: white; outline: none; box-sizing: border-box; font-family: inherit; margin-bottom:20px; font-size: 1.05rem; }
        .edit-input:focus { border-color: #d475ff; background: rgba(0,0,0,0.4); }
        .edit-textarea { width: 100%; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: rgba(255,255,255,0.9); outline: none; box-sizing: border-box; font-family: inherit; min-height: 250px; resize:none; margin-bottom:20px; line-height: 1.6; font-size: 1.05rem;}
        .edit-textarea:focus { border-color: #d475ff; background: rgba(0,0,0,0.4); }

        .btn-glow { width: 100%; padding: 15px; border: none; border-radius: 12px; font-family: inherit; font-size: 1.1rem; font-weight: 500; cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); color: white; background: linear-gradient(45deg, #6a11cb, #d475ff); box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4); }
        .btn-glow:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 10px 25px rgba(106, 17, 203, 0.6); }

        .success-msg { color: #4cd137; margin-bottom: 20px; font-size:0.95rem; }
        .error-msg { color: #ff4757; margin-bottom: 20px; font-size:0.95rem; }

        /* Timeline Fetch Frame */
        .entry-list { display: flex; flex-direction: column; gap: 20px; max-height: 550px; overflow-y: auto; padding-right:15px; }
        .entry-card { background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.05); padding: 25px; border-radius: 15px; transition: background 0.3s, transform 0.3s; }
        .entry-card:hover { background: rgba(255,255,255,0.05); transform: translateY(-2px); }
        .entry-card h3 { margin: 0 0 10px 0; font-size: 1.3rem; color: #ff7eb3; text-shadow: 0 0 10px rgba(255,126,179,0.3); }
        .entry-card p { margin: 0 0 15px 0; color: rgba(255,255,255,0.7); line-height: 1.7; font-size:0.95rem; font-style: italic; }
        .entry-date { font-size: 0.85rem; color: rgba(255,255,255,0.4); letter-spacing:1px; text-transform: uppercase;}

        .entry-list::-webkit-scrollbar { width: 8px; }
        .entry-list::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 10px; }
        .entry-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        .entry-list::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.4); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { .dashboard-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
<div class="dashboard-container">
        <!-- Interactive Composer -->
        <div class="glass-panel">
            <h2 style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px; margin-bottom:25px; font-weight:500;">Write Your Soul</h2>
            <?php echo $msg; ?>
            <form action="diary.php" method="POST" style="display:flex; flex-direction:column; flex-grow:1;">
                <input type="text" name="mood" class="edit-input" placeholder="Current mood (e.g., Happy, Calm)..." required>
                <input type="text" name="reason" class="edit-input" placeholder="Why are you feeling this way? (Optional)">
                <textarea name="note" class="edit-textarea" placeholder="Dump everything directly into the secure database..." required></textarea>
                <button type="submit" class="btn-glow" style="margin-top: auto;">Commit Entry</button>
            </form>
        </div>

        <!-- Rendered History Log -->
        <div class="glass-panel">
            <h2 style="margin-top:0; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px; margin-bottom:25px; font-weight:500;">Timeline Repository</h2>
            <?php if(empty($entries)): ?>
                <div style="flex-grow:1; display:flex; align-items:center; justify-content:center; flex-direction:column; opacity:0.5;">
                    <span style="font-size:3rem; margin-bottom:15px;">📓</span>
                    <p style="font-style:italic; text-align:center;">Your timeline is completely empty. Create your first secure offline record.</p>
                </div>
            <?php else: ?>
                <div class="entry-list">
                    <?php foreach($entries as $entry): ?>
                        <div class="entry-card">
                            <h3>Feeling: <span style="color:#fff;"><?php echo htmlspecialchars($entry['mood']); ?></span></h3>
                            <?php if(!empty($entry['reason'])): ?>
                                <div style="color:rgba(255,255,255,0.7); font-size:0.9rem; margin-bottom:8px; font-weight:500;">
                                    Reason: <span style="color:#d475ff;"><?php echo htmlspecialchars($entry['reason']); ?></span>
                                </div>
                            <?php endif; ?>
                            <p>"<?php echo nl2br(htmlspecialchars($entry['note'])); ?>"</p>
                            <span class="entry-date"><?php echo date('F j, Y | g:i a', strtotime($entry['date'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php include 'includes/global_player.php'; ?>
</body>
</html>
