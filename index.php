<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoulMate - Where your soul meets the rhythm</title>
    <link rel="stylesheet" href="assets/landing.css">
</head>
<body>
    <!-- Background Elements -->
    <div class="glow-blob blob1"></div>
    <div class="glow-blob blob2"></div>

    <!-- Floating Icons -->
    <div class="floating-icons">
        <span class="icon i1">🎧</span>
        <span class="icon i2">🎵</span>
        <span class="icon i3">💖</span>
        <span class="icon i4">🌙</span>
    </div>

    <!-- Background Music (Muted by default until user toggles) -->
    <audio id="bgm" loop preload="auto">
        <!-- Sample royalty-free ambient source; optionally replace this later -->
        <source src="https://cdn.pixabay.com/audio/2022/10/25/audio_2c5cf3275c.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <div class="fade-in-container">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="nav-brand" style="display:flex; align-items:center;">
                <img src="assets/logo.png" alt="SoulMate Logo" height="40" style="margin-right: 10px; border-radius: 8px; box-shadow: 0 0 15px rgba(212, 117, 255, 0.4);">
                SoulMate
            </div>
            <div class="nav-links">
                <button id="music-toggle" class="music-btn">🔇 BGM Off</button>
                <a href="login.php" class="nav-btn">Login</a>
                <a href="signup.php" class="nav-btn signup-btn">Signup</a>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero">
            <h1 class="hero-title">SoulMate</h1>
            <p class="hero-tagline">Where your soul meets the rhythm</p>
            <a href="login.php" class="get-started-btn">Get Started</a>
        </section>
        
        <!-- Scroll Hint -->
        <div class="scroll-hint">
            <span>Scroll</span>
            <div class="arrow">↓</div>
        </div>
    </div>

    <script src="assets/landing.js"></script>
</body>
</html>
