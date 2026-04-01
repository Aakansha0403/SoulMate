<?php
session_start();

// Strict session logic lock-out
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

// Automatically structure Database back-end for Mini Journal functionality
$conn->query("CREATE TABLE IF NOT EXISTS journals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Secure logic intercept for processing journal entries
$journal_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['journal_note'])) {
    $note = trim($_POST['journal_note']);
    
    if (!empty($note)) {
        // Prevent generic SQL vulnerabilities
        $stmt = $conn->prepare("INSERT INTO journals (user_id, note) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $note);
        
        if ($stmt->execute()) {
            $journal_msg = "<span style='color: #a8cbf0; letter-spacing: 1px; font-style:italic;'>Thought safely released to the stars ✨</span>";
            
            // Feature 8: Automatic XP Rewards (+5 for Midnight)
            $conn->query("INSERT IGNORE INTO rewards (user_id, points, level) VALUES ($user_id, 0, 'Beginner 🌱')");
            $conn->query("UPDATE rewards SET points = points + 5 WHERE user_id = $user_id");
            
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
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Midnight Mode</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Midnight Base UI Theme */
        body {
            background: radial-gradient(circle at bottom, #010411 0%, #000000 100%);
            color: #e0e0e0;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        /* Ambient Dynamic Background Engine */
        .stars-canvas {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            pointer-events: none;
            background: transparent;
        }

        /* Subtle Top Navigation */
        .nav-minimal {
            width: 100%;
            padding: 25px 6%;
            display: flex;
            justify-content: space-between;
            box-sizing: border-box;
            z-index: 10;
        }
        .nav-minimal a {
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.4s;
            font-weight: 300;
        }
        .nav-minimal a:hover {
            color: rgba(255, 255, 255, 1);
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
        }

        /* Core Structure Stack */
        .midnight-container {
            max-width: 800px;
            width: 100%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 55px;
            z-index: 1;
            text-align: center;
            animation: slowFadeIn 2s ease-in-out;
        }
        
        @keyframes slowFadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Highly Intelligent CSS Timing Breathing Exercise */
        .breathing-wrapper {
            position: relative;
            width: 280px;
            height: 280px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px auto;
        }
        .circle {
            position: absolute;
            width: 75%;
            height: 75%;
            border-radius: 50%;
            background: rgba(28, 62, 102, 0.15);
            box-shadow: 0 0 50px rgba(66, 135, 245, 0.1), inset 0 0 40px rgba(66, 135, 245, 0.05);
            border: 1px solid rgba(66, 135, 245, 0.1);
            transition: transform 4s ease-in-out, background 4s ease-in-out, box-shadow 4s ease-in-out, border-color 4s ease-in-out;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .circle-text {
            font-size: 1.8rem;
            font-weight: 300;
            letter-spacing: 3px;
            color: #a8cbf0;
            text-shadow: 0 0 20px rgba(168, 203, 240, 0.6);
            transition: opacity 0.5s ease;
        }

        /* Precise JS Trigger Classes for Breathing Logic */
        .inhale { 
            transform: scale(1.4); 
            background: rgba(66, 135, 245, 0.08); 
            box-shadow: 0 0 80px rgba(66, 135, 245, 0.3); 
            border-color: rgba(66, 135, 245, 0.4); 
        }
        .hold { 
            transform: scale(1.4); 
            background: rgba(66, 135, 245, 0.05); 
            box-shadow: 0 0 60px rgba(66, 135, 245, 0.2); 
            border-color: rgba(66, 135, 245, 0.2);
        }
        .exhale { 
            transform: scale(1); 
            background: rgba(28, 62, 102, 0.1); 
            box-shadow: 0 0 30px rgba(66, 135, 245, 0.1); 
            border-color: rgba(66, 135, 245, 0.1);
        }

        /* Affirmations / Quotes Ecosystem */
        .text-display {
            font-size: 1.3rem;
            font-style: italic;
            font-weight: 300;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.65);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 1.5s ease-in-out;
            max-width: 600px;
            line-height: 1.6;
        }

        /* Sound Control Board */
        .sound-board {
            display: flex;
            gap: 25px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .sound-btn {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 12px 30px;
            border-radius: 40px;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 1.05rem;
            font-weight: 300;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .sound-btn:hover {
            color: rgba(255, 255, 255, 0.8);
            border-color: rgba(255, 255, 255, 0.2);
        }
        .sound-btn.active {
            background: rgba(255, 255, 255, 0.08);
            color: white;
            border-color: rgba(168, 203, 240, 0.4);
            box-shadow: 0 0 20px rgba(168, 203, 240, 0.15);
        }

        /* Mini Sub-Conscious Journal */
        .journal-section {
            width: 100%;
            max-width: 550px;
            background: rgba(255, 255, 255, 0.015);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 30px;
            box-sizing: border-box;
            transition: transform 0.3s;
        }
        .journal-section:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        .journal-section h3 {
            font-weight: 300;
            margin-top: 0;
            margin-bottom: 20px;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 1.05rem;
        }
        .journal-textarea {
            width: 100%;
            background: transparent;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255,255,255,0.9);
            font-family: 'Poppins', sans-serif;
            font-size: 1.05rem;
            padding: 10px 0;
            resize: none;
            outline: none;
            min-height: 45px;
            transition: border-color 0.4s;
            font-weight: 300;
        }
        .journal-textarea:focus { border-color: rgba(168, 203, 240, 0.5); }
        .journal-btn {
            background: transparent;
            border: 1px solid rgba(168, 203, 240, 0.3);
            color: #a8cbf0;
            padding: 10px 25px;
            border-radius: 30px;
            font-family: 'Poppins', sans-serif;
            font-weight: 300;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .journal-btn:hover { 
            background: rgba(168, 203, 240, 0.1);
            border-color: #a8cbf0;
            box-shadow: 0 0 15px rgba(168,203,240, 0.2); 
        }

        @media (max-width: 768px) {
            .sound-board { flex-direction: column; width: 100%; }
            .breathing-wrapper { width: 220px; height: 220px; }
            .circle-text { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
<!-- Render Interactive HTML5 Canvas mapped via Javascript below -->
    <canvas id="starsCanvas" class="stars-canvas"></canvas>

    <!-- Global Embedded Ambient Acoustic Media Node -->
    <audio id="bg-sound" loop preload="auto"></audio>

    <nav class="nav-minimal">
        <a href="music.php">← Back to Dashboard</a>
        <span style="color: rgba(255,255,255,0.2); letter-spacing: 3px; font-weight:300;">MIDNIGHT</span>
    </nav>

    <div class="midnight-container">
        
        <!-- Deep Breathing Ecosystem -->
        <div class="breathing-wrapper">
            <div class="circle" id="breathe-circle">
                <span class="circle-text" id="breathe-text">Relax</span>
            </div>
        </div>

        <!-- Dynamic Output Container For Quotes -->
        <div class="text-display" id="affirmation-display">
            Breathe deeply...
        </div>

        <!-- Acoustic Controls -->
        <div class="sound-board">
            <!-- Utilizing strictly local media caches as specifically requested -->
            <button class="sound-btn" onclick="playSound('rain.mp3', this)">🌧️ Rain</button>
            <button class="sound-btn" onclick="playSound('ocean.mp3', this)">🌊 Ocean</button>
            <button class="sound-btn" onclick="playSound('ambient.mp3', this)">✨ Soft Ambient</button>
            
            <!-- Optional Requested Volume Slider -->
            <div style="width: 100%; display: flex; flex-direction:column; align-items: center; margin-top: 10px; gap:8px;">
                <span style="font-size:0.8rem; color: rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:1px;">Global Ambient Volume</span>
                <input type="range" id="volume-control" min="0" max="1" step="0.05" value="0.4" style="width: 250px; cursor:pointer;">
            </div>
        </div>

        <!-- Database-Supported Journaling Field -->
        <div class="journal-section">
            <h3>Release your thoughts safely...</h3>
            <?php if($journal_msg) echo "<div style='margin-bottom:15px;'>$journal_msg</div>"; ?>
            <form action="midnight.php" method="POST">
                <textarea name="journal_note" class="journal-textarea" placeholder="What's on your mind? Everything here is entirely private..." required></textarea>
                <button type="submit" class="journal-btn">Release thought</button>
            </form>
        </div>

    </div>

    <!-- Frontend Interactive Engineering -->
    <script>
        // -------------------------------------
        // Interactive Dynamic Star Canvas Logic
        // -------------------------------------
        const canvas = document.getElementById('starsCanvas');
        const ctx = canvas.getContext('2d');
        let viewW = canvas.width = window.innerWidth;
        let viewH = canvas.height = window.innerHeight;

        // Auto-scale dynamically based on screen orientation
        window.addEventListener('resize', () => {
            viewW = canvas.width = window.innerWidth;
            viewH = canvas.height = window.innerHeight;
        });

        // Initialize Star Map Arrays
        const systemStars = [];
        for(let i=0; i<200; i++) {
            systemStars.push({
                x: Math.random() * viewW,
                y: Math.random() * viewH,
                radius: Math.random() * 1.5,
                alpha: Math.random(),
                // Micro-shift speeds mimicking physical atmosphere twitch
                dAlpha: (Math.random() * 0.01) - 0.005 
            });
        }

        // Output loop rendering engine
        function renderStars() {
            ctx.clearRect(0, 0, viewW, viewH);
            ctx.fillStyle = '#ffffff';
            
            systemStars.forEach(star => {
                star.alpha += star.dAlpha;
                if(star.alpha <= 0 || star.alpha >= 1) star.dAlpha *= -1; // Reverse when hitting max brightness
                
                ctx.globalAlpha = Math.abs(star.alpha) * 0.5; // Keeping it incredibly subtle
                ctx.beginPath();
                ctx.arc(star.x, star.y, star.radius, 0, Math.PI*2);
                ctx.fill();
            });
            ctx.globalAlpha = 1;
            requestAnimationFrame(renderStars);
        }
        renderStars();


        // -------------------------------------
        // Core Breathing Animation Mechanism
        // -------------------------------------
        const breathingCircle = document.getElementById('breathe-circle');
        const textIndicator = document.getElementById('breathe-text');
        
        function systemBreatheProcess() {
            // STEP 1: Execute Inhale (Wait ~0.3s for text fading, scale upwards 4s length)
            textIndicator.style.opacity = '0';
            setTimeout(() => {
                textIndicator.innerText = 'Inhale';
                textIndicator.style.opacity = '1';
                
                breathingCircle.className = 'circle inhale';
                breathingCircle.style.transitionDuration = '4s';
            }, 500);

            // STEP 2: Hold Breath (Execute exactly at 4.5 sec limit)
            setTimeout(() => {
                textIndicator.style.opacity = '0';
                setTimeout(() => {
                    textIndicator.innerText = 'Hold';
                    textIndicator.style.opacity = '1';
                    
                    breathingCircle.className = 'circle hold';
                    breathingCircle.style.transitionDuration = '2s';
                }, 500);
            }, 4500);

            // STEP 3: Execute Exhale (Execute exactly at 6.5 sec limit, shrink length 4s)
            setTimeout(() => {
                textIndicator.style.opacity = '0';
                setTimeout(() => {
                    textIndicator.innerText = 'Exhale';
                    textIndicator.style.opacity = '1';
                    
                    breathingCircle.className = 'circle exhale';
                    breathingCircle.style.transitionDuration = '4s';
                }, 500);
            }, 7000);
        }

        // Initialize immediately upon page generation, set 11s internal loop duration for perfect breathing gaps
        setTimeout(() => {
            systemBreatheProcess();
            setInterval(systemBreatheProcess, 11500); 
        }, 1000);


        // -------------------------------------
        // Shifting Mindset Affirmations Array
        // -------------------------------------
        const safeAffirmations = [
            "You are safe here.",
            "Everthing is going to be alright.",
            "Let go of everything you can't control.",
            "Rest your mind. You have done more than enough today.",
            "You are strong and completely worthy of peace.",
            "\"Peace is the result of retraining your mind to process life as it is.\"",
            "\"Calmness is the core cradle of inner power.\"",
            "It is completely okay to pause."
        ];
        
        const stringDisplay = document.getElementById('affirmation-display');
        let arrayIndex = 0;

        // Loop Affirmations every 9 seconds, applying smooth CSS opacity transitions
        setInterval(() => {
            stringDisplay.style.opacity = '0';
            setTimeout(() => {
                arrayIndex = (arrayIndex + 1) % safeAffirmations.length;
                stringDisplay.innerText = safeAffirmations[arrayIndex];
                stringDisplay.style.opacity = '1';
            }, 1500);
        }, 9000);


        // -------------------------------------
        // Exclusive Single-Channel Ambient Audio Routing
        // -------------------------------------
        const singleSoundPlayer = document.getElementById('bg-sound');
        const volumeSlider = document.getElementById('volume-control');
        
        // Initialize structural slider volume logic 
        singleSoundPlayer.volume = 0.4;
        
        // Continuous Live Interaction Slider Mapping
        volumeSlider.addEventListener('input', function() {
            singleSoundPlayer.volume = this.value;
        });

        // Track active node state
        let activeSoundBtn = null;

        function playSound(fileUrl, specificButton) {
            // Requirement 1: Force stop on previous sound structurally instantly
            singleSoundPlayer.pause();
            singleSoundPlayer.currentTime = 0; // Strip time logic back to base

            // Reset visual indicator across all arrays instantly
            const allSoundBtns = document.querySelectorAll('.sound-btn');
            allSoundBtns.forEach(btn => btn.classList.remove('active'));

            // Play/Pause toggling capability 
            if (activeSoundBtn === specificButton) {
                // Audio stays physically paused via previous execution, user toggled off!
                activeSoundBtn = null; 
                return;
            }

            // Assign pure strict source URL exactly formatted via Instruction string concatenation
            singleSoundPlayer.src = "sounds/" + fileUrl;
            singleSoundPlayer.play().catch(e => console.log(e));
            
            specificButton.classList.add('active');
            activeSoundBtn = specificButton; // Retain specific session target lock
        }

        // Feature 7: One-Tap Calm Extractor Route
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto_calm') === 'true') {
            setTimeout(() => {
                const ambientSoundBtn = document.querySelectorAll('.sound-btn')[2]; // Soft Ambient Target
                if (ambientSoundBtn) ambientSoundBtn.click();
            }, 800); // 800ms offset preventing browser autoplay caching blocks
        }
    </script>
<?php include 'includes/global_player.php'; ?>
</body>
</html>
