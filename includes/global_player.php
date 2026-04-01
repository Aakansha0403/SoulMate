<?php
// includes/global_player.php
?>
<style>
/* PREMIUM SPOTIFY-STYLE PERSISTENT GLOBAL BOTTOM PLAYER */
#global-music-player {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(10, 10, 15, 0.95);
    backdrop-filter: blur(25px);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    z-index: 10000;
    display: none; 
    flex-direction: column;
    align-items: center;
    padding: 10px 30px;
    box-sizing: border-box;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.6);
    animation: slideUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.global-player-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    max-width: 1200px;
}

#global-song-title {
    color: white;
    font-weight: 500;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 25%;
    display: flex;
    align-items: center;
    gap: 10px;
}
#global-song-title::before {
    content: '🎶';
    font-size: 1.2rem;
}

/* Central Controls */
.player-controls {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 45%;
    gap: 5px;
}

.control-buttons {
    display: flex;
    align-items: center;
    gap: 20px;
}
.ctrl-btn {
    background: transparent;
    border: none;
    color: rgba(255,255,255,0.7);
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
}
.ctrl-btn:hover { color: white; transform: scale(1.1); }
#global-play-btn {
    width: 40px; height: 40px;
    background: white; color: black;
    border-radius: 50%;
    display: flex; justify-content: center; align-items: center;
    font-size: 1.2rem;
    padding-left: 2px; /* optical center */
}
#global-play-btn:hover { transform: scale(1.05); box-shadow: 0 0 15px rgba(255,255,255,0.4); }

/* Progress Bar */
.progress-container {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.6);
    font-family: monospace;
}
.progress-bar {
    -webkit-appearance: none;
    width: 100%;
    height: 4px;
    background: rgba(255,255,255,0.2);
    border-radius: 5px;
    outline: none;
    cursor: pointer;
    transition: height 0.2s;
}
.progress-bar::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 12px; height: 12px;
    border-radius: 50%;
    background: #fff;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s;
}
.progress-container:hover .progress-bar::-webkit-slider-thumb { opacity: 1; }
.progress-container:hover .progress-bar { height: 6px; }

/* Volume Context */
.volume-control {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 25%;
    justify-content: flex-end;
}
.volume-control input {
    width: 100px;
    -webkit-appearance: none;
    height: 4px;
    background: rgba(255,255,255,0.2);
    border-radius: 5px;
    outline: none;
    cursor: pointer;
}
.volume-control input::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: white;
}

    #global-music-player:hover #global-player-close { opacity: 1; }
    
    #global-player-close {
        position: absolute;
        top: 10px;
        right: 15px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.6);
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.3s;
        z-index: 10001;
        opacity: 0.4;
    }
    #global-player-close:hover {
        background: rgba(255, 51, 51, 0.2);
        color: #ff4d4d;
        border-color: #ff4d4d;
        transform: rotate(90deg) scale(1.1);
        opacity: 1;
    }

    @keyframes slideUp {
        from { bottom: -120px; opacity: 0; }
        to { bottom: 0; opacity: 1; }
    }

    @media (max-width: 768px) {
        #global-music-player { padding: 15px 15px; }
        .global-player-content { flex-wrap: wrap; justify-content: center; gap: 10px;}
        #global-song-title { max-width: 100%; display:none; }
        .player-controls { width: 100%; }
        .volume-control { display: none; }
        #global-player-close { top: 5px; right: 5px; }
    }
    </style>

    <div id="global-music-player">
        <button id="global-player-close" title="Close Player & Stop Music">❌</button>
        <div class="global-player-content">
            <span id="global-song-title">Select a track</span>
            
            <div class="player-controls">
                <div class="control-buttons">
                    <button class="ctrl-btn" id="global-prev-btn" title="Previous">⏮</button>
                    <button class="ctrl-btn" id="global-play-btn" title="Play/Pause">▶</button>
                    <button class="ctrl-btn" id="global-next-btn" title="Next">⏭</button>
                </div>
                <div class="progress-container">
                    <span id="global-current-time">0:00</span>
                    <input type="range" class="progress-bar" id="global-progress" value="0" min="0" max="100" step="0.1">
                    <span id="global-duration">0:00</span>
                </div>
            </div>

            <div class="volume-control">
                <span style="font-size:1.2rem;">🔊</span>
                <input type="range" id="global-volume" min="0" max="1" step="0.05" value="0.5">
            </div>
            
            <!-- Background Audio Node entirely hidden from view visually -->
            <audio id="global-audio-player" preload="metadata"></audio>
            
            <!-- CLOUD MEDIA SYNC LAYER -->
            <div id="youtube-player" style="display:none;"></div>
        </div>
    </div>

<script src="https://www.youtube.com/iframe_api"></script>
<script src="assets/globalPlayer.js"></script>
