document.addEventListener('DOMContentLoaded', () => {
    const bgm = document.getElementById('bgm');
    const musicToggle = document.getElementById('music-toggle');
    let isPlaying = false;

    if (musicToggle && bgm) {
        // Set volume to a gentle, ambient level
        bgm.volume = 0.2;

        musicToggle.addEventListener('click', () => {
            if (isPlaying) {
                bgm.pause();
                musicToggle.innerHTML = '🔇 BGM Off';
            } else {
                let playPromise = bgm.play();
                if (playPromise !== undefined) {
                    playPromise.then(() => {
                        musicToggle.innerHTML = '🔊 BGM On';
                    }).catch(error => {
                        console.log("Audio playback was blocked or failed:", error);
                    });
                }
            }
            isPlaying = !isPlaying;
        });
    }
});
