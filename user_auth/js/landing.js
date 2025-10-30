document.addEventListener("DOMContentLoaded", () => {
    const countdownEl = document.getElementById("countdown");
    if (!countdownEl) return; // only run if countdown exists

    const REDIRECT_URL = "login";
    const TIMER_DURATION = 10; // seconds
    const STORAGE_KEY = "timer_start_time";
    
    let targetTime;
    let timer = null;

    // Check if timer was already started
    const storedStartTime = localStorage.getItem(STORAGE_KEY);
    const now = Date.now();
    
    if (storedStartTime) {
        // Timer was already started, calculate remaining time
        const startTime = parseInt(storedStartTime);
        const elapsed = now - startTime;
        const remaining = (TIMER_DURATION * 1000) - elapsed;
        
        if (remaining > 0) {
            // Timer is still running, set target time based on remaining time
            targetTime = now + remaining;
        } else {
            // Timer has already expired, redirect immediately
            localStorage.removeItem(STORAGE_KEY);
            if (typeof NProgress !== "undefined") {
                NProgress.start();
                setTimeout(() => NProgress.set(0.7), 300);
                setTimeout(() => {
                    NProgress.done();
                    window.location.href = REDIRECT_URL;
                }, 1200);
            } else {
                window.location.href = REDIRECT_URL;
            }
            return;
        }
    } else {
        // First time, start new timer
        targetTime = now + TIMER_DURATION * 1000;
        localStorage.setItem(STORAGE_KEY, now.toString());
    }

    function updateCountdown() {
        const currentTime = Date.now();
        let timeLeft = Math.ceil((targetTime - currentTime) / 1000);

        if (timeLeft <= 0) {
            if (timer !== null) {
                clearInterval(timer);
                timer = null;
            }
            localStorage.removeItem(STORAGE_KEY); // Clean up storage
            countdownEl.textContent = 0;

            if (typeof NProgress !== "undefined") {
                NProgress.start();
                setTimeout(() => NProgress.set(0.7), 300);
                setTimeout(() => {
                    NProgress.done();
                    window.location.href = REDIRECT_URL;
                }, 1200);
            } else {
                window.location.href = REDIRECT_URL;
            }

            return;
        }

        countdownEl.textContent = timeLeft;
    }

    // ⚠️ Important: assign timer first, THEN run the first update
    timer = setInterval(updateCountdown, 1000);
    updateCountdown();
});



