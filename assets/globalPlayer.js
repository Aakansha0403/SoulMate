// assets/globalPlayer.js
(function() {
    let ytPlayer;
    let isYouTube = false;
    let updateInterval;

    document.addEventListener("DOMContentLoaded", function() {
        const audioPlayer = document.getElementById('global-audio-player');
        const playerContainer = document.getElementById('global-music-player');
        const playBtn = document.getElementById('global-play-btn');
        const songTitle = document.getElementById('global-song-title');
        const progressBar = document.getElementById('global-progress');
        const currentTimeEl = document.getElementById('global-current-time');
        const durationEl = document.getElementById('global-duration');
        const volumeSlider = document.getElementById('global-volume');
        const closeBtn = document.getElementById('global-player-close');

        if (!audioPlayer) return;

        // stop and hide functionality
        if (closeBtn) {
            closeBtn.addEventListener("click", () => {
                stopAll();
                playerContainer.style.display = "none";
                localStorage.removeItem("currentSong");
                localStorage.removeItem("currentYT");
            });
        }

        // Global stopping logic
        window.stopAll = () => {
            audioPlayer.pause();
            audioPlayer.currentTime = 0;
            if(ytPlayer && ytPlayer.pauseVideo) ytPlayer.pauseVideo();
            playBtn.innerText = "▶";
            clearInterval(updateInterval);
        };

        // UI SYNC: PROGRESS BAR
        progressBar.addEventListener("input", () => {
            const time = (progressBar.value / 100) * (isYouTube ? ytPlayer.getDuration() : audioPlayer.duration);
            if(isYouTube) ytPlayer.seekTo(time);
            else audioPlayer.currentTime = time;
        });

        // UI SYNC: VOLUME
        volumeSlider.addEventListener("input", () => {
            const vol = volumeSlider.value;
            audioPlayer.volume = vol;
            if(ytPlayer && ytPlayer.setVolume) ytPlayer.setVolume(vol * 100);
        });

        // PLAYBACK TOGGLE
        playBtn.addEventListener("click", () => {
            if(isYouTube) {
                if(ytPlayer.getPlayerState() === 1) ytPlayer.pauseVideo();
                else ytPlayer.playVideo();
            } else {
                if(audioPlayer.paused) audioPlayer.play();
                else audioPlayer.pause();
            }
        });

        // 🧠 EXPOSE GLOBAL API
        window.globalPlayer = {
            playLocal: function(path, title) {
                stopAll();
                isYouTube = false;
                audioPlayer.src = path;
                songTitle.innerText = title;
                playerContainer.style.display = "flex";
                audioPlayer.play();
                localStorage.setItem("currentSong", JSON.stringify({path, title, type:'local'}));
                startUIUpdate();
            },
            playYouTube: function(yid, title) {
                stopAll();
                isYouTube = true;
                songTitle.innerText = title;
                playerContainer.style.display = "flex";
                
                if (ytPlayer && ytPlayer.loadVideoById) {
                    ytPlayer.loadVideoById(yid);
                    ytPlayer.playVideo();
                } else {
                    // Fallback if API not ready
                    localStorage.setItem("pendingYT", yid);
                }
                localStorage.setItem("currentSong", JSON.stringify({yid, title, type:'yt'}));
                startUIUpdate();
            }
        };

        function startUIUpdate() {
            clearInterval(updateInterval);
            updateInterval = setInterval(() => {
                let cur, dur;
                if(isYouTube && ytPlayer && ytPlayer.getCurrentTime) {
                    cur = ytPlayer.getCurrentTime();
                    dur = ytPlayer.getDuration();
                    playBtn.innerText = (ytPlayer.getPlayerState() === 1) ? "⏸" : "▶";
                } else {
                    cur = audioPlayer.currentTime;
                    dur = audioPlayer.duration || 0;
                    playBtn.innerText = audioPlayer.paused ? "▶" : "⏸";
                }

                if(dur > 0) {
                    progressBar.value = (cur / dur) * 100;
                    currentTimeEl.innerText = formatTime(cur);
                    durationEl.innerText = formatTime(dur);
                }
            }, 500);
        }

        function formatTime(secs) {
            const m = Math.floor(secs / 60);
            const s = Math.floor(secs % 60);
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        }

        // AUTO-RESUME ON RELOAD
        const last = JSON.parse(localStorage.getItem("currentSong") || "null");
        if(last) {
            if(last.type === 'yt') setTimeout(() => window.globalPlayer.playYouTube(last.yid, last.title), 1000);
            else window.globalPlayer.playLocal(last.path, last.title);
        }
    });

    // YouTube API Callback
    window.onYouTubeIframeAPIReady = function() {
        ytPlayer = new YT.Player('youtube-player', {
            height: '0', width: '0',
            videoId: localStorage.getItem("pendingYT") || '',
            playerVars: { 'autoplay': 1, 'controls': 0 },
            events: {
                'onReady': (event) => {
                    const vol = document.getElementById('global-volume').value;
                    event.target.setVolume(vol * 100);
                }
            }
        });
    };
})();
