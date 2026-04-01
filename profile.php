<?php
session_start();

// Strict session lock for logged-in users only
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Immediate interception for secure session destruction (Logout)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];
$status_msg = '';

// Edit Profile Logistics: Process Name Change locally to DB
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Process Name Change
    if (isset($_POST['update_name'])) {
        $new_name = trim($_POST['new_name']);
        if (!empty($new_name)) {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $user_id);
            if ($stmt->execute()) {
                $_SESSION['name'] = $new_name; 
                $status_msg = "<div class='success-msg'>Profile updated beautifully!</div>";
            }
            $stmt->close();
        }
    }

    // 2. Process Avatar Selection
    if (isset($_POST['select_avatar'])) {
        $avatar_choice = $_POST['avatar_name'];
        $stmt = $conn->prepare("UPDATE users SET avatar = ?, profile_image = NULL WHERE id = ?");
        $stmt->bind_param("si", $avatar_choice, $user_id);
        if ($stmt->execute()) {
            $status_msg = "<div class='success-msg'>New persona adopted! ✨</div>";
        }
        $stmt->close();
    }

    // 3. Process Profile Photo Upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'jpeg' => 'image/jpeg'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = $_FILES['profile_photo']['type'];
        $filesize = $_FILES['profile_photo']['size'];
    
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists(strtolower($ext), $allowed)) {
            $status_msg = "<div class='error-msg'>Only JPG and PNG files are allowed.</div>";
        } elseif ($filesize > 2 * 1024 * 1024) {
            $status_msg = "<div class='error-msg'>Image size must be less than 2MB.</div>";
        } else {
            // Securely generate upload framework if missing
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $new_filename = "profile_" . $user_id . "_" . time() . "." . $ext;
            $upload_path = $target_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE users SET profile_image = ?, avatar = NULL WHERE id = ?");
                $stmt->bind_param("si", $upload_path, $user_id);
                $stmt->execute();
                $stmt->close();
                $status_msg = "<div class='success-msg'>Photo uploaded successfully! 📸</div>";
            } else {
                $status_msg = "<div class='error-msg'>Could not move file to $target_dir. Ensure folder is writable.</div>";
            }
        }
    }
}

// Extract primary user credentials
$stmt = $conn->prepare("SELECT name, email, profile_image, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($db_name, $db_email, $db_image, $db_avatar);
$stmt->fetch();
$stmt->close();

// EXTRACT ACTIVITY STATS
// 1. Core Analytics: Total Mood Inputs
$total_moods = 0;
$check_moods = $conn->query("SHOW TABLES LIKE 'moods'");
if ($check_moods->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM moods WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($total_moods);
    $stmt->fetch();
    $stmt->close();
}

// 2. Core Analytics: Total Backend Diary Entries
$total_diary = 0;
$check_journals = $conn->query("SHOW TABLES LIKE 'diary'");
if ($check_journals->num_rows > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM diary WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($total_diary);
    $stmt->fetch();
    $stmt->close();
}

// 3. Core Analytics: Total Level Tracking Points
$points = 0;
$level = 'Beginner 🌱';
$check_rw = $conn->query("SHOW TABLES LIKE 'rewards'");
if ($check_rw->num_rows > 0) {
    $stmt = $conn->prepare("SELECT points, level FROM rewards WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if($stmt->fetch()) {
        // Exists mapping
    } else {
        $level = 'Beginner 🌱';
        $points = 0;
    }
    $stmt->close();
}

// EXTRACT EMOTIONAL INTELLIGENCE INSIGHTS (NEW ADVANCED FEATURE)
$sad_count = 0;
$total_week_moods = 0;
$insight_messages = [];

$check_moods = $conn->query("SHOW TABLES LIKE 'moods'");
if ($check_moods->num_rows > 0) {
    // Check sad frequency this week
    $stmt = $conn->prepare("SELECT COUNT(*) FROM moods WHERE user_id = ? AND mood = 'sad' AND date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($sad_count);
        $stmt->fetch();
        $stmt->close();
    }
}

// Generate dynamic emotional feedback arrays
if($sad_count >= 3) {
    $insight_messages[] = "Insight: You were sad {$sad_count} times this week. Take a deep breath or visit the Midnight realm.";
} elseif($sad_count > 0) {
    $insight_messages[] = "Insight: It's okay to feel sad sometimes. You logged it {$sad_count} time(s) recently. Keep pushing forward.";
}

if($total_diary >= 5) {
    $insight_messages[] = "Insight: Writing heals! You feel much better after documenting your thoughts regularly.";
} elseif($total_diary > 0) {
    $insight_messages[] = "Insight: You've written {$total_diary} entry(s). Users who track their thoughts typically show advanced emotional clarity!";
}

if(empty($insight_messages)) {
    $insight_messages[] = "We need more data! Log your feelings and journal entries to unlock deep subconscious AI insights.";
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Your Identity</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Modern Profile Background Structure */
        body {
            background: linear-gradient(135deg, #0f1025, #1d0738, #050b1c);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Ambient Glow Nodes Behind Content */
        .ambient-glow {
            position: fixed;
            width: 400px;
            height: 400px;
            background: #d475ff;
            border-radius: 50%;
            filter: blur(150px);
            opacity: 0.15;
            z-index: -1;
            animation: pulseBg 8s infinite alternate ease-in-out;
        }
        @keyframes pulseBg {
            0% { transform: scale(1) translate(0,0); }
            100% { transform: scale(1.2) translate(50px, -50px); }
        }

        /* Top Minimal Navigation */
        .header {
            width: 100%;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
            background: rgba(0,0,0,0.3);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            backdrop-filter: blur(15px);
        }
        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            background: linear-gradient(to right, #b452ff, #ff7eb3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        .header-links a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            margin-left: 20px;
            transition: color 0.3s;
            font-weight: 500;
        }
        .header-links a:hover { color: #d475ff; }

        /* Unified Centered Dashboard Grid */
        .profile-wrapper {
            max-width: 900px;
            width: 100%;
            padding: 40px 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            animation: fadeIn 1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Glassmorphism Structural Node */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 25px;
            padding: 35px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: transform 0.4s, border-color 0.4s;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255,255,255,0.15);
        }

        /* Identity Side */
        .identity-col {
            align-items: center;
            text-align: center;
        }
        .profile-container {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            margin: 0 auto;
            position: relative;
            padding: 5px;
            background: linear-gradient(45deg, #6a11cb, #ff7eb3);
            box-shadow: 0 0 40px rgba(212, 117, 255, 0.3);
        }
        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0f1025;
            background: #111;
        }
        .edit-pic-btn {
            position: absolute;
            bottom: 5px; right: 5px;
            background: #d475ff;
            width: 35px; height: 35px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            border: 2px solid #0f1025;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .edit-pic-btn:hover { transform: scale(1.1); background: #f0afff; }

        .persona-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 20px;
            width: 100%;
        }
        .avatar-option {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 12px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
            overflow: hidden;
            background: rgba(255,255,255,0.05);
        }
        .avatar-option img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-option:hover { transform: scale(1.05); background: rgba(255,255,255,0.1); }
        .avatar-option.selected { border-color: #d475ff; box-shadow: 0 0 15px rgba(212, 117, 255, 0.4); }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            margin-top: 15px;
            color: #ffffff;
        }
        .profile-email {
            font-size: 1rem;
            color: rgba(255,255,255,0.5);
            font-weight: 300;
            margin-bottom: 20px;
        }

        /* Dynamic Buttons */
        .btn-glow {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1.05rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
            text-decoration: none;
            display: inline-block;
            box-sizing: border-box;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-glow:hover {
            background: rgba(255,255,255,0.15);
            border-color: #d475ff;
            box-shadow: 0 0 20px rgba(212, 117, 255, 0.3);
            transform: scale(1.02);
        }

        .btn-logout {
            background: rgba(255, 65, 108, 0.1);
            border-color: rgba(255, 65, 108, 0.3);
            color: #ff7eb3;
        }
        .btn-logout:hover {
            background: rgba(255, 65, 108, 0.3);
            border-color: #ff416c;
            box-shadow: 0 0 25px rgba(255, 65, 108, 0.4);
            color: white;
        }

        /* Analytics Side */
        .stats-col h2 {
            font-size: 1.35rem;
            margin: 0 0 10px 0;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            width: 100%;
        }
        .stat-box {
            background: rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .stat-val {
            font-size: 1.8rem;
            font-weight: 700;
            color: #d475ff;
            text-shadow: 0 0 10px rgba(212, 117, 255, 0.3);
        }
        .stat-label {
            font-size: 0.85rem;
            font-weight: 400;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-box-large {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, rgba(106, 17, 203, 0.2), rgba(37, 117, 252, 0.1));
            border-color: rgba(106, 17, 203, 0.3);
        }

        /* Profile Editing Overlay/Form Formats */
        .edit-form { display: none; width: 100%; gap: 10px; flex-direction: column; animation: fadeIn 0.5s;}
        .edit-input {
            width: 100%; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);
            background: rgba(0,0,0,0.3); color: white; outline: none; box-sizing: border-box; font-family: inherit;
        }
        .edit-input:focus { border-color: #d475ff; }

        .success-msg, .error-msg {
            font-size: 0.9rem; padding: 10px; border-radius: 8px; width: 100%; box-sizing:border-box; text-align:center;
        }
        .success-msg { color: #4cd137; background: rgba(76, 209, 55, 0.1); border: 1px solid rgba(76, 209, 55, 0.2); }
        .error-msg { color: #ff4757; background: rgba(255, 71, 87, 0.1); border: 1px solid rgba(255, 71, 87, 0.2); }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .profile-wrapper { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
<div class="ambient-glow"></div>

    <!-- Interface Shell Navigation -->
    <div class="profile-wrapper">
        
        <!-- LEFT SCREEN: Identity Card -->
        <div class="glass-card identity-col" style="justify-content:center;">
            <div class="profile-container">
                <?php 
                    $final_pic = "https://ui-avatars.com/api/?name=".urlencode($db_name)."&background=random";
                    if ($db_image) $final_pic = $db_image;
                    elseif ($db_avatar) $final_pic = "assets/avatars/" . $db_avatar . ".png";
                ?>
                <img src="<?php echo $final_pic; ?>" class="profile-pic" alt="Profile">
                <label for="photo-upload" class="edit-pic-btn" title="Upload Photo">📸</label>
                <form id="photo-form" action="profile.php" method="POST" enctype="multipart/form-data" style="display:none;">
                    <input type="file" id="photo-upload" name="profile_photo" onchange="document.getElementById('photo-form').submit()">
                </form>
            </div>
            
            <div id="display-info" style="width:100%; display:flex; flex-direction:column; align-items:center;">
                <h2 class="profile-name"><?php echo htmlspecialchars($db_name); ?></h2>
                <p class="profile-email"><?php echo htmlspecialchars($db_email); ?></p>
            </div>

            <!-- Avatar Personality Grid -->
            <div style="width:100%;">
                <span style="font-size:0.85rem; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:1px;">Or Choose Your Persona</span>
                <form action="profile.php" method="POST" id="avatar-form" class="persona-grid">
                    <input type="hidden" name="avatar_name" id="avatar_name_input">
                    <input type="hidden" name="select_avatar" value="1">
                    
                    <?php for($i=1; $i<=4; $i++): ?>
                        <div class="avatar-option <?php echo ($db_avatar == "avatar$i") ? 'selected' : ''; ?>" onclick="submitAvatar('avatar<?php echo $i; ?>')">
                            <img src="assets/avatars/avatar<?php echo $i; ?>.png" alt="Avatar <?php echo $i; ?>">
                        </div>
                    <?php endfor; ?>
                </form>
            </div>

            <?php if($status_msg) echo $status_msg; ?>

            <!-- Optional Feature Request: Secure Profile Renaming -->
            <form class="edit-form" id="edit-form" action="profile.php" method="POST">
                <input type="text" name="new_name" class="edit-input" placeholder="Change display name" value="<?php echo htmlspecialchars($db_name); ?>" required>
                <button type="submit" name="update_name" class="btn-glow" style="background:#d475ff; border-color:#d475ff;">Save Name</button>
            </form>

            <div style="display:flex; flex-direction:column; gap:12px; width: 100%; margin-top:10px;">
                <button class="btn-glow" onclick="toggleEdit()">✏️ Edit Profile</button>
                <a href="profile.php?action=logout" class="btn-glow btn-logout">🚪 Logout Securely</a>
            </div>
        </div>

        <!-- RIGHT SCREEN: Extracted Behavioral Analytics -->
        <div class="glass-card stats-col">
            <h2>Your Activity Statistics</h2>
            
            <div class="stat-grid">
                
                <div class="stat-box">
                    <span class="stat-val"><?php echo $total_moods; ?></span>
                    <span class="stat-label">Moods Selected</span>
                </div>
                
                <div class="stat-box">
                    <span class="stat-val"><?php echo $total_diary; ?></span>
                    <span class="stat-label">Diary Entries</span>
                </div>

                <div class="stat-box">
                    <span class="stat-val" style="color: #4cd137; text-shadow: 0 0 10px rgba(76, 209, 55, 0.3);"><?php echo $points; ?></span>
                    <span class="stat-label">Total XP Points</span>
                </div>

                <div class="stat-box">
                    <span class="stat-val" style="font-size: 1.2rem; display:flex; align-items:center; height:100%;"><?php echo htmlspecialchars($level); ?></span>
                    <span class="stat-label">Current Tier</span>
                </div>

                <!-- Emotional Intelligence UI Extraction -->
                <div class="stat-box stat-box-large" style="gap:15px;">
                    <span style="font-weight:600; font-size:1.1rem; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:8px; display:inline-block; color:#ff7eb3;">
                        🧠 Subconscious Insights
                    </span>
                    <ul style="margin:0; padding-left:15px; color:rgba(255,255,255,0.85); font-size:0.95rem; line-height:1.7; display:flex; flex-direction:column; gap:8px;">
                        <?php foreach($insight_messages as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>
        </div>

    </div>

    <!-- Frontend Interactive Binding -->
    <script>
        function toggleEdit() {
            const form = document.getElementById('edit-form');
            if(form.style.display === 'flex') {
                form.style.display = 'none';
            } else {
                form.style.display = 'flex';
            }
        }

        function submitAvatar(name) {
            document.getElementById('avatar_name_input').value = name;
            document.getElementById('avatar-form').submit();
        }
    </script>
<?php include 'includes/global_player.php'; ?>
</body>
</html>
