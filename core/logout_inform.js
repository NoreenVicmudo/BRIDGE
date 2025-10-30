document.addEventListener("DOMContentLoaded", function() {
    function checkForceRelogin() {
        fetch("/bridge/core/get_force_relogin.php")
            .then(res => res.json())
            .then(data => {
                if (data.success && data.forceRelogin) {
                    // Show toast
                    logoutToast("Your account has been updated by an admin. You will be redirected to login in 10 seconds.");
                    
                    // Clear session and redirect after 10 seconds
                    setTimeout(() => {
                        // Clear the session by calling logout endpoint
                        fetch("/bridge/core/logout.php", { method: "POST" })
                            .then(() => {
                                window.location.href = "/bridge/login";
                            })
                            .catch(() => {
                                // If logout fails, still redirect to login
                                window.location.href = "/bridge/login";
                            });
                    }, 10000);
                }
            })
            .catch(err => console.error("Error checking force relogin:", err));
    }

    // Run immediately
    checkForceRelogin();

    // Auto-refresh every 3s (optional)
    setInterval(checkForceRelogin, 10000);
});

////////// TOAST HELPER (Responsive) //////////
function logoutToast(message) {
    let toast = document.getElementById("logoutInformToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "logoutInformToast";
        toast.style.position = "fixed";
        toast.style.top = "20px";
        toast.style.left = "50%";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
        toast.style.background = "#dc3545";
        toast.style.color = "#fff";
        toast.style.padding = "12px 20px";
        toast.style.borderRadius = "8px";
        toast.style.zIndex = "99999";
        toast.style.maxWidth = "90%";
        toast.style.wordWrap = "break-word";
        toast.style.textAlign = "center";
        toast.style.fontSize = "clamp(14px, 2vw, 18px)";
        toast.style.opacity = "0";
        toast.style.transition = "all 0.5s ease";
        document.body.appendChild(toast);
    }
    
    toast.textContent = message;

    requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateX(-50%) translateY(0)";
    });

    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
    }, 10000);
}