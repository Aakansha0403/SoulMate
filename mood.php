<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db.php';

// Auto-generate the moods table if it doesn't already exist in the database
// (This guarantees no DB structural errors for the mood functionality)
$createTableSQL = "CREATE TABLE IF NOT EXISTS moods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood VARCHAR(50) NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($createTableSQL);

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mood'])) {
    $mood = trim($_POST['mood']);
    $user_id = $_SESSION['user_id'];
    
    // Validate entry and prevent SQL injection using Prepared Statements
    $stmt = $conn->prepare("INSERT INTO moods (user_id, mood) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $mood);
    
    if ($stmt->execute()) {
        // Feature 8: Automatic XP Reward System
        $stmt->close();
        
        // 1. Ensure user has a rewards record
        $conn->query("INSERT IGNORE INTO rewards (user_id, points, level) VALUES ($user_id, 0, 'Beginner 🌱')");
        
        // 2. Add +3 XP for mood tracking
        $conn->query("UPDATE rewards SET points = points + 3 WHERE user_id = $user_id");
        
        // 3. Dynamic Level Recalculation
        $res = $conn->query("SELECT points FROM rewards WHERE user_id = $user_id");
        if ($row = $res->fetch_assoc()) {
            $new_points = $row['points'];
            $new_level = 'Beginner 🌱';
            if ($new_points >= 21 && $new_points <= 50) $new_level = 'Explorer 🌍';
            elseif ($new_points > 50) $new_level = 'Soul Master 🌟';
            
            $conn->query("UPDATE rewards SET level = '$new_level' WHERE user_id = $user_id");
        }

        // Redirect automatically to music.php after the mood is saved securely
        header("Location: music.php?mood=" . urlencode($mood));
        exit();
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - How are you feeling today?</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e1366, #4a0e4e);
            transition: background 0.8s ease; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden; /* Prevent strange scroll bars during hover logic */
            font-family: 'Poppins', sans-serif;
            color: white;
        }

        .mood-container {
            width: 100%;
            max-width: 900px;
            text-align: center;
            padding: 2rem;
            z-index: 2;
        }

        .mood-heading {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 600;
            margin-bottom: 3.5rem;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            animation: fadeInDown 1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            letter-spacing: 1px;
        }

        .mood-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1.5rem;
            animation: fadeInUp 1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .mood-btn {
            background: rgba(255, 255, 255, 0.08); /* Heavy transparency structure */
            backdrop-filter: blur(15px); /* Glassmorphism blur depth */
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px; /* Huge rounded corners */
            padding: 30px 15px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            color: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            -webkit-appearance: none;
            outline: none;
        }

        /* Hover Interaction Effects (Scale up immensely + create strong outer glow) */
        .mood-btn:hover {
            transform: scale(1.1) translateY(-10px);
            background: rgba(255, 255, 255, 0.15); /* Becomes thicker internally */
            border-color: rgba(255, 255, 255, 0.5); /* Rim lights up deeply */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }

        .mood-emoji {
            font-size: 3.8rem;
            filter: drop-shadow(0 0 10px rgba(255,255,255,0.3));
            transition: transform 0.3s ease;
        }

        .mood-btn:hover .mood-emoji {
            transform: scale(1.15);
        }

        .mood-label {
            font-size: 1.2rem;
            font-weight: 500;
            letter-spacing: 1px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        /* Unique card border-glow maps corresponding to the mood */
        .mood-btn[data-mood="happy"]:hover { box-shadow: 0 0 35px rgba(255, 235, 59, 0.5); border-color: rgba(255, 235, 59, 0.7); }
        .mood-btn[data-mood="sad"]:hover { box-shadow: 0 0 35px rgba(33, 150, 243, 0.5); border-color: rgba(33, 150, 243, 0.7); }
        .mood-btn[data-mood="romantic"]:hover { box-shadow: 0 0 35px rgba(233, 30, 99, 0.5); border-color: rgba(233, 30, 99, 0.7); }
        .mood-btn[data-mood="calm"]:hover { box-shadow: 0 0 35px rgba(76, 175, 80, 0.5); border-color: rgba(76, 175, 80, 0.7); }
        .mood-btn[data-mood="angry"]:hover { box-shadow: 0 0 35px rgba(244, 67, 54, 0.5); border-color: rgba(244, 67, 54, 0.7); }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
<div class="mood-container">
        <h1 class="mood-heading">How are you feeling today?</h1>

        <form action="mood.php" method="POST" id="moodForm">
            <!-- Hidden input that bridges the clicked item state to PHP -->
            <input type="hidden" name="mood" id="selectedMood" value="">
            
            <div class="mood-grid">
                <button type="button" class="mood-btn" data-mood="happy" onclick="submitMood('happy')">
                    <span class="mood-emoji">😊</span>
                    <span class="mood-label">Happy</span>
                </button>
                <button type="button" class="mood-btn" data-mood="sad" onclick="submitMood('sad')">
                    <span class="mood-emoji">😢</span>
                    <span class="mood-label">Sad</span>
                </button>
                <button type="button" class="mood-btn" data-mood="romantic" onclick="submitMood('romantic')">
                    <span class="mood-emoji">😍</span>
                    <span class="mood-label">Romantic</span>
                </button>
                <button type="button" class="mood-btn" data-mood="calm" onclick="submitMood('calm')">
                    <span class="mood-emoji">😌</span>
                    <span class="mood-label">Calm</span>
                </button>
                <button type="button" class="mood-btn" data-mood="angry" onclick="submitMood('angry')">
                    <span class="mood-emoji">😡</span>
                    <span class="mood-label">Angry</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Interface Scripting (Interactive Dynamic Backgrounds + Submit) -->
    <script>
        // Mapping background gradient colors individually for that "Wow" factor.
        const dynamicColors = {
            'happy': 'linear-gradient(135deg, #1e1366, #a17c0f)', // Gold / Yellow
            'sad': 'linear-gradient(135deg, #0e1e33, #153257)',   // Bleak Blue
            'romantic': 'linear-gradient(135deg, #380a1c, #9c144e)', // Deep Hot Pink
            'calm': 'linear-gradient(135deg, #0f4023, #1e7041)', // Nature Green
            'angry': 'linear-gradient(135deg, #420606, #a30e0e)'  // Hellish Red
        };

        const defaultGradient = 'linear-gradient(135deg, #1e1366, #4a0e4e)'; // Default Purple mapping
        const bodyTag = document.body;

        // Apply interactive hover bindings instantly to buttons
        document.querySelectorAll('.mood-btn').forEach(button => {
            button.addEventListener('mouseenter', () => {
                const triggerMood = button.getAttribute('data-mood');
                bodyTag.style.background = dynamicColors[triggerMood];
            });

            // Revert securely when unhovered
            button.addEventListener('mouseleave', () => {
                bodyTag.style.background = defaultGradient;
            });
        });

        // Intercept function on button click -> Submits form securely behind the scenes
        function submitMood(moodSelection) {
            document.getElementById('selectedMood').value = moodSelection;
            
            // Execute a cool fade/zoom-out departure sequence 
            const grid = document.querySelector('.mood-grid');
            grid.style.transform = 'scale(0.85)';
            grid.style.opacity = '0';
            grid.style.transition = 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            
            // Allow animation frame rendering prior to PHP execution jump
            setTimeout(() => {
                document.getElementById('moodForm').submit();
            }, 350);
        }
    </script>
<?php include 'includes/global_player.php'; ?>
</body>
</html>
