<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
/* Universal Fixed Navigation Styling */
.universal-nav {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: rgba(0, 0, 0, 0.85); /* Proper Dark Theme Contrast */
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    z-index: 9999;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 40px;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
}

.nav-left {
    display: flex;
    align-items: center;
    gap: 25px;
}

/* History API Back Button Tool */
.nav-back-btn {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.15);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 500;
    font-family: inherit;
    transition: all 0.3s;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
.nav-back-btn:hover {
    background: rgba(255,255,255,0.15);
    border-color: #d475ff;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 117, 255, 0.3);
}

.nav-brand {
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(to right, #b452ff, #ff7eb3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-decoration: none;
    letter-spacing: 1px;
}

.nav-right {
    display: flex;
    gap: 25px;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
}

.nav-link {
    color: rgba(255,255,255,0.55);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
    padding-bottom: 3px;
}

/* Hover & Active Highlight Effects */
.nav-link:hover {
    color: #ff7eb3;
    text-shadow: 0 0 10px rgba(255, 126, 179, 0.3);
}

.nav-link.active {
    color: #d475ff;
    text-shadow: 0 0 15px rgba(212, 117, 255, 0.4);
}
.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background: #d475ff;
    border-radius: 2px;
}

.nav-logout {
    background: rgba(255, 71, 87, 0.1);
    color: #ff4757;
    padding: 6px 14px;
    border-radius: 8px;
    border: 1px solid rgba(255, 71, 87, 0.3);
}
.nav-logout:hover {
    background: rgba(255, 71, 87, 0.2);
    color: white;
    border-color: #ff4757;
}

/* Ensure no overlap logic applies to body natively */
body {
    padding-top: 85px !important;
}

/* Mobile Responsiveness Stack */
@media (max-width: 900px) {
    .universal-nav {
        flex-direction: column;
        padding: 15px 20px;
        gap: 15px;
    }
    .nav-left {
        width: 100%;
        justify-content: space-between;
    }
    .nav-right {
        justify-content: center;
        gap: 15px;
    }
    body {
        padding-top: 140px !important;
    }
}

@media (max-width: 480px) {
    .nav-right {
        gap: 10px;
    }
    .nav-link {
        font-size: 0.85rem;
    }
}
/* Language Selector Dropdown */
.nav-lang-select {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.8);
    padding: 8px 12px;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.9rem;
    cursor: pointer;
    outline: none;
    transition: all 0.3s;
}
.nav-lang-select:focus {
    border-color: #d475ff;
    background: rgba(255, 255, 255, 0.12);
}
.nav-lang-select option {
    background: #111;
    color: white;
}
</style>

<nav class="universal-nav">
    <div class="nav-left">
        <?php if($current_page !== 'dashboard.php'): ?>
            <button class="nav-back-btn" onclick="window.history.back()">← Back</button>
        <?php endif; ?>
        <a href="dashboard.php" class="nav-brand" style="display:flex; align-items:center;">
            <img src="assets/logo.png" alt="SoulMate Logo" height="35" style="margin-right: 10px; border-radius: 8px; box-shadow: 0 0 10px rgba(212, 117, 255, 0.4);">
            SoulMate
        </a>
    </div>
    
    <div class="nav-right">
        <!-- Multi-Language System Hook -->
        <select class="nav-lang-select" onchange="setSiteLanguage(this.value)" title="Choose Language 🌐">
            <option value="English" <?= ($_SESSION['user_language'] ?? 'English' == 'English') ? 'selected' : '' ?>>🌐 English</option>
            <option value="Hindi" <?= (($_SESSION['user_language'] ?? '') == 'Hindi') ? 'selected' : '' ?>>🌐 Hindi</option>
            <option value="Marathi" <?= (($_SESSION['user_language'] ?? '') == 'Marathi') ? 'selected' : '' ?>>🌐 Marathi</option>
        </select>
        
        <?php 
        // Fetch User Identity for Persistent Nav Presence
        $nav_pic = "https://ui-avatars.com/api/?name=".urlencode($_SESSION['name'] ?? 'U')."&background=random";
        if(isset($_SESSION['user_id'])) {
            require_once 'db.php'; // Local context check
            $u_id = $_SESSION['user_id'];
            $n_stmt = $conn->prepare("SELECT profile_image, avatar FROM users WHERE id = ?");
            $n_stmt->bind_param("i", $u_id);
            $n_stmt->execute();
            $n_stmt->bind_result($p_img, $p_av);
            if($n_stmt->fetch()) {
                if($p_img) $nav_pic = $p_img;
                elseif($p_av) $nav_pic = "assets/avatars/" . $p_av . ".png";
            }
            $n_stmt->close();
        }
        ?>
        <a href="profile.php" class="nav-link <?= ($current_page == 'profile.php') ? 'active' : '' ?>" style="display:flex; align-items:center; gap:8px;">
            <img src="<?= $nav_pic ?>" alt="Profile" style="width:30px; height:30px; border-radius:50%; border:1px solid rgba(255,255,255,0.2); object-fit:cover;">
            Profile
        </a>
        <a href="profile.php?action=logout" class="nav-link nav-logout">🚪 Logout</a>
    </div>
</nav>

<script>
async function setSiteLanguage(lang) {
    try {
        const response = await fetch('api_set_language.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ language: lang })
        });
        const result = await response.json();
        if (result.success) {
            window.location.reload(); // Refresh to apply language filtering
        }
    } catch (e) { console.error("Language Error:", e); }
}
</script>
