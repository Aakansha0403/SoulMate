<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$name = $_SESSION['name'] ?? 'Soul';

require_once 'includes/db.php';
$user_id = $_SESSION['user_id'];

// Check if user language is already set
$lang_stmt = $conn->prepare("SELECT language FROM users WHERE id = ?");
$lang_stmt->bind_param("i", $user_id);
$lang_stmt->execute();
$lang_res = $lang_stmt->get_result();
$row = $lang_res->fetch_assoc();
$u_lang = $row['language'] ?? null; 
$_SESSION['user_language'] = $u_lang ?? 'Hindi'; // Primary Backend Fallback logic
$lang_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Navigation Board</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #090614, #191238, #000000);
            color: white; 
            font-family: 'Poppins', sans-serif; 
            min-height: 100vh; 
            margin: 0; 
            
            /* Center Layout Flexbox logic globally */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            box-sizing: border-box;
        }

        /* Constrains all elements directly mapping into a vertical column */
        .dashboard-container {
            width: 100%;
            max-width: 480px; 
            display: flex;
            flex-direction: column;
            gap: 35px; /* Clean spacing between Top, Actions, and List Modules */
            animation: fadeIn 1.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* 1. HEADER SECTION (TOP) */
        .header-section {
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .main-title {
            font-size: 3.8rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(to right, #b452ff, #ff7eb3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(180, 82, 255, 0.2);
            letter-spacing: 1px;
        }
        .tagline {
            font-size: 1.15rem;
            color: rgba(255, 255, 255, 0.55); /* Soft formatting */
            font-weight: 300;
            margin: 0;
            letter-spacing: 0.5px;
        }

        /* 2. USER ACTION SECTION */
        .user-actions {
            display: flex;
            justify-content: space-between; /* Secure Spacing Profile vs Logout */
            align-items: center;
            padding: 18px 25px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            backdrop-filter: blur(15px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .action-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .action-link:hover {
            color: #d475ff;
            text-shadow: 0 0 10px rgba(212, 117, 255, 0.4);
        }
        .action-logout:hover {
            color: #ff4757;
            text-shadow: 0 0 10px rgba(255, 71, 87, 0.4);
        }

        /* 3. MAIN MENU SECTION */
        .menu-list {
            display: flex;
            flex-direction: column; /* Locks strict vertical layout */
            gap: 18px; 
        }

        /* Dedicated Layout Math */
        .menu-card {
            width: 100%;
            padding: 22px 25px; /* Deep Padding for High-End Glass effect */
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 18px; /* Smooth Corners */
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 25px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .menu-card:hover {
            transform: scale(1.03) translateY(-3px); /* Scale up and pop out visually */
            background: rgba(255, 255, 255, 0.08); /* Brighter glass on interaction */
            border-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 15px 40px rgba(180, 82, 255, 0.15);
        }

        .menu-icon {
            font-size: 2.2rem;
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.1));
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .menu-card:hover .menu-icon {
            transform: scale(1.15) rotate(5deg);
        }

        .menu-title {
            font-size: 1.25rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* RESPONSIVE DESIGN NATIVELY */
        @media (max-width: 480px) {
            .dashboard-container {
                gap: 25px;
            }
            .main-title {
                font-size: 2.8rem;
            }
            .tagline {
                font-size: 0.95rem;
            }
            .user-actions {
                padding: 15px 20px;
            }
            .action-link {
                font-size: 0.95rem;
            }
            .menu-card {
                padding: 18px 20px;
                gap: 15px;
            }
            .menu-icon {
                font-size: 1.8rem;
            }
            .menu-title {
                font-size: 1.15rem;
            }
        }
        /* Language Popup Modal */
        .language-modal {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(25px);
            z-index: 20000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .language-modal.show {
            opacity: 1;
            pointer-events: auto;
        }
        .lang-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 40px;
            border-radius: 30px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
        }
        .lang-opt-container {
            display: grid;
            gap: 15px;
            margin-top: 30px;
        }
        .lang-option {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            padding: 15px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .lang-option:hover {
            background: rgba(212, 117, 255, 0.2);
            border-color: #d475ff;
            transform: scale(1.03);
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
<div class="dashboard-container">
        
        <!-- HEADER SECTION (TOP) -->
        <div class="header-section">
            <h1 class="main-title">Welcome, <?php echo htmlspecialchars($name); ?></h1>
            <p class="tagline">Where your soul meets the rhythm</p>
            
            <!-- NEW: One-Tap Premium Calm Utility -->
            <a href="midnight.php?auto_calm=true" style="margin-top:20px; text-decoration:none;">
                <button class="menu-icon" style="background: linear-gradient(45deg, #1cb5e0, #000046); color: white; border: none; padding: 15px 35px; border-radius: 40px; font-size: 1.15rem; font-family: 'Poppins', sans-serif; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 5px 20px rgba(28, 181, 224, 0.4); transition: transform 0.3s;">
                    ✨ Calm Me Now
                </button>
            </a>
        </div>

        <!-- MAIN MENU SECTION -->
        <div class="menu-list">
            <a href="community.php" class="menu-card" style="background: rgba(255,126,179,0.1); border-color: rgba(255,126,179,0.3);">
                <div class="menu-icon">🌍</div>
                <div class="menu-title" style="color:#ff7eb3;">Soul Community</div>
            </a>
            <a href="mood.php" class="menu-card">
                <div class="menu-icon">🎭</div>
                <div class="menu-title">Mood Selection</div>
            </a>
            
            <a href="music.php" class="menu-card">
                <div class="menu-icon">🎵</div>
                <div class="menu-title">Music & Shayari</div>
            </a>
            
            <a href="diary.php" class="menu-card">
                <div class="menu-icon">📓</div>
                <div class="menu-title">My Diary</div>
            </a>
            
            <a href="midnight.php" class="menu-card">
                <div class="menu-icon">🌙</div>
                <div class="menu-title">Midnight Mode</div>
            </a>
            
            <a href="rewards.php" class="menu-card">
                <div class="menu-icon">🎁</div>
                <div class="menu-title">Rewards</div>
            </a>
        </div>

    </div>

    <!-- Multi-Language System Initialization Popup -->
    <?php if ($u_lang === null): ?>
    <div class="language-modal" id="langModal">
        <div class="lang-card">
            <h2 style="margin-top: 0; font-weight: 500; font-size: 1.6rem; color: #d475ff;">Choose Your Language 💬</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.95rem;">Select your preferred language for a personalized SoulMate experience.</p>
            
            <div class="lang-opt-container">
                <button class="lang-option" onclick="setSiteLanguage('English')">🌐 English</button>
                <button class="lang-option" onclick="setSiteLanguage('Hindi')">🇮🇳 Hindi</button>
                <button class="lang-option" onclick="setSiteLanguage('Marathi')">🚩 Marathi</button>
            </div>
            <p style="margin-top: 25px; font-size: 0.8rem; color: rgba(255,255,255,0.4);">* You can change this anytime from the navigation bar.</p>
        </div>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.getElementById('langModal').classList.add('show');
            }, 800);
        });
    </script>
    <?php endif; ?>

<?php include 'includes/global_player.php'; ?>
</body>
</html>
