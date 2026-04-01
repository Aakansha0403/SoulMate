<?php
session_start();

// Strict session logic lock-out
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

// Automatically build Rewards table if missing
$conn->query("CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    points INT DEFAULT 0,
    level VARCHAR(50) DEFAULT 'Beginner',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Ensure the current user has a record initialized
$stmt = $conn->prepare("SELECT points, level FROM rewards WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $insert_stmt = $conn->prepare("INSERT INTO rewards (user_id, points, level) VALUES (?, 0, 'Beginner 🌱')");
    $insert_stmt->bind_param("i", $user_id);
    $insert_stmt->execute();
    $insert_stmt->close();
    $points = 0;
} else {
    $row = $result->fetch_assoc();
    $points = $row['points'];
}
$stmt->close();

// Interactive Feature: Process Simulator Point Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $added = 0;
    if ($_POST['action'] === 'daily') $added = 5;
    if ($_POST['action'] === 'mood') $added = 3;
    if ($_POST['action'] === 'diary') $added = 10;
    if ($_POST['action'] === 'midnight') $added = 5;

    if ($added > 0) {
        $points += $added;
        
        // Calculate dynamic level scaling
        $level_name = 'Beginner 🌱';
        if ($points >= 21 && $points <= 50) {
            $level_name = 'Explorer 🌍';
        } elseif ($points > 50) {
            $level_name = 'Soul Master 🌟';
        }

        $update_stmt = $conn->prepare("UPDATE rewards SET points = ?, level = ? WHERE user_id = ?");
        $update_stmt->bind_param("isi", $points, $level_name, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Smooth Redirect to prevent standard POST formulation reloading errors & trigger confetti
        header("Location: rewards.php?earned=" . $added);
        exit();
    }
}

// Logic Mapping for UI Math representation
$current_level = 'Beginner 🌱';
$next_level = 'Explorer 🌍';
$max_points = 20;
$min_points = 0;

if ($points >= 21 && $points <= 50) {
    $current_level = 'Explorer 🌍';
    $next_level = 'Soul Master 🌟';
    $max_points = 50;
    $min_points = 21;
} elseif ($points > 50) {
    $current_level = 'Soul Master 🌟';
    $next_level = 'MAX';
    $max_points = 100; 
    $min_points = 51;
}

// Dynamic Progress Bar Scaling Logic
$progress_percent = 100; // Default max jump
if ($points <= 100 && $max_points != $min_points) {
    $progress_percent = (($points - $min_points) / ($max_points - $min_points + 1)) * 100;
} elseif ($points > 100) {
    $progress_percent = 100; // Cap visual length at 100%
}

// Rewards Mapping Logic
$rewards_config = [
    ['name' => 'New Visual Themes', 'cost' => 15, 'icon' => '🎨'],
    ['name' => 'Premium Shayari Packs', 'cost' => 30, 'icon' => '📜'],
    ['name' => 'Calm Sound Packs', 'cost' => 60, 'icon' => '🎵'],
    ['name' => 'Golden Special Badges', 'cost' => 90, 'icon' => '🏅']
];

// Achievements Mapping Logic
$achievements = [
    ['name' => 'First Login', 'desc' => 'Joined the SoulMate rhythm', 'unlocked' => ($points >= 5), 'icon' => '👋'],
    ['name' => 'Calm Mind', 'desc' => 'Utilized midnight mode', 'unlocked' => ($points >= 15), 'icon' => '🌙'],
    ['name' => 'Inner Voice', 'desc' => 'Released a personal diary entry securely', 'unlocked' => ($points >= 25), 'icon' => '📖']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Ecosystem Rewards</title>
    <!-- Lightweight Canvas Confetti Script for the Drop In Animations -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Base Architecture mapped strictly to modern Dark Aesthetics */
        body {
            background: linear-gradient(135deg, #090b14, #1c0e2a, #0d1222);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* Top Minimalistic Header */
        .rewards-header {
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.4);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
        }
        .rewards-header a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: color 0.3s;
        }
        .rewards-header a:hover { color: #d475ff; }

        /* Structural Core */
        .dashboard-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            width: 100%;
            box-sizing: border-box;
            animation: fadeIn 1.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Reusable Heavy Glass Effect Card */
        .glass-panel {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            transition: transform 0.4s, box-shadow 0.4s;
        }
        .glass-panel:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.15);
        }

        /* Level Scaling Identity & Progress Engine */
        .level-identity {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .level-title {
            font-size: 2rem;
            font-weight: 600;
            background: linear-gradient(to right, #6a11cb, #d475ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 15px rgba(212, 117, 255, 0.3);
        }
        .points-badge {
            background: rgba(212, 117, 255, 0.1);
            border: 1px solid rgba(212, 117, 255, 0.3);
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: 500;
            color: #d475ff;
            box-shadow: 0 0 15px rgba(212, 117, 255, 0.2);
        }

        .progress-track {
            width: 100%;
            height: 15px;
            background: rgba(0,0,0,0.5);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6a11cb, #ff416c);
            width: 0%; /* Fills inside JS animation dynamically */
            border-radius: 20px;
            transition: width 1.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 0 20px #ff416c;
        }
        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
        }

        /* Two-Column Lower Architecture Grid */
        .split-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 500;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.9);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }

        /* Expandable Modular Unlock Nodes */
        .rewards-list, .achievements-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .reward-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.2);
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.03);
            transition: all 0.3s;
        }
        .reward-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.1rem;
        }
        
        /* Interactive dynamic status UI */
        .unlocked-status {
            color: #4cd137;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            text-shadow: 0 0 10px rgba(76, 209, 55, 0.4);
        }
        .locked-status {
            color: rgba(255,255,255,0.3);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Individual Badges & Achievements */
        .achievement-card {
            display: flex;
            align-items: center;
            gap: 20px;
            background: rgba(255,255,255,0.02);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.05);
            opacity: 0.5; /* Lock state */
            transition: opacity 0.5s, transform 0.3s, background 0.3s;
        }
        .achievement-card.earned {
            opacity: 1; /* Unlocked state */
            background: rgba(212, 117, 255, 0.05);
            border-color: rgba(212, 117, 255, 0.2);
            box-shadow: inset 0 0 20px rgba(212, 117, 255, 0.05);
        }
        .achievement-icon { font-size: 2.5rem; filter: grayscale(100%); transition: filter 0.5s; }
        .earned .achievement-icon { filter: grayscale(0%); drop-shadow(0 0 10px rgba(255,255,255,0.3)); }
        
        .achievement-details h4 { margin: 0 0 5px 0; font-weight: 500; font-size: 1.1rem; }
        .achievement-details p { margin: 0; font-size: 0.9rem; color: rgba(255,255,255,0.5); }

        /* Point Simulator Form Panel */
        .simulator-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .sim-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        .sim-btn:hover { background: rgba(106, 17, 203, 0.4); border-color: #6a11cb; transform:scale(1.05); }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
<!-- Hidden element strictly carrying the PHP generated progress dynamically over to Javascript -->
    <div id="progress-target" data-percent="<?php echo $progress_percent; ?>" style="display:none;"></div>

    <div class="dashboard-container">
        
        <!-- TOP TIER METRICS -->
        <div class="glass-panel">
            <div class="level-identity">
                <div>
                    <span style="color: rgba(255,255,255,0.6); font-size: 0.9rem; text-transform: uppercase;">Current Level</span>
                    <div class="level-title"><?php echo htmlspecialchars($current_level); ?></div>
                </div>
                <div class="points-badge">
                    <?php echo $points; ?> XP
                </div>
            </div>

            <!-- Highly Fluid Progress Visualization Container -->
            <div class="progress-track">
                <div class="progress-fill" id="progress-bar"></div>
            </div>
            <div class="progress-labels">
                <span>Start</span>
                <span>Towards: <?php echo htmlspecialchars($next_level); ?></span>
            </div>

            <!-- Interactive Logic Hooks to display physical increments -->
            <div style="margin-top:25px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                <span style="font-size: 0.9rem; color: rgba(255,255,255,0.5);">Simulate internal app activity for points:</span>
                <form action="rewards.php" method="POST" class="simulator-form">
                    <button type="submit" name="action" value="daily" class="sim-btn">Login (+5)</button>
                    <button type="submit" name="action" value="mood" class="sim-btn">Select Mood (+3)</button>
                    <button type="submit" name="action" value="midnight" class="sim-btn">Midnight Mode (+5)</button>
                    <button type="submit" name="action" value="diary" class="sim-btn">Write Diary (+10)</button>
                </form>
            </div>
        </div>

        <div class="split-grid">
            <!-- REWARDS SYSTEM ENGINE -->
            <div class="glass-panel">
                <div class="section-title">Unlockable Cosmetics & Features</div>
                <div class="rewards-list">
                    <?php foreach($rewards_config as $reward): ?>
                        <div class="reward-item">
                            <div class="reward-info">
                                <span><?php echo $reward['icon']; ?></span>
                                <span><?php echo htmlspecialchars($reward['name']); ?></span>
                            </div>
                            
                            <!-- Intelligence logic analyzing PHP points vs requirements -->
                            <?php if($points >= $reward['cost']): ?>
                                <div class="unlocked-status">🔓 Unlocked</div>
                            <?php else: ?>
                                <div class="locked-status">🔒 <?php echo $reward['cost']; ?> XP Req</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ACHIEVEMENTS BADGE ENGINE -->
            <div class="glass-panel">
                <div class="section-title">Earned Achievements</div>
                <div class="achievements-list">
                    <?php foreach($achievements as $ach): ?>
                        <div class="achievement-card <?php echo $ach['unlocked'] ? 'earned' : ''; ?>">
                            <div class="achievement-icon"><?php echo $ach['icon']; ?></div>
                            <div class="achievement-details">
                                <h4><?php echo htmlspecialchars($ach['name']); ?></h4>
                                <p><?php echo htmlspecialchars($ach['desc']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Read target from PHP logic injection
            const rawTarget = document.getElementById('progress-target').getAttribute('data-percent');
            const bar = document.getElementById('progress-bar');
            
            // Allow CSS to structurally mount before animating to force smoothness 
            setTimeout(() => {
                bar.style.width = rawTarget + '%';
            }, 300);

            // Automatic Confetti Explosion purely if they just earned a point (Read via URL formatting hook)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('earned')) {
                const earnedNum = urlParams.get('earned');
                launchConfetti();
                
                // Strip the exact URL afterwards cleanly to prevent refresh hoarding
                window.history.replaceState({}, document.title, "rewards.php");
            }
        });

        function launchConfetti() {
            var duration = 2.5 * 1000;
            var animationEnd = Date.now() + duration;
            var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            var interval = setInterval(function() {
                var timeLeft = animationEnd - Date.now();
                if (timeLeft <= 0) return clearInterval(interval);

                var particleCount = 50 * (timeLeft / duration);
                
                // Explode uniquely styled confetti objects natively
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
            }, 250);
        }
    </script>
<?php include 'includes/global_player.php'; ?>
</body>
</html>
