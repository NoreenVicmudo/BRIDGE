//Process passwords//
const form = document.getElementById('password_change');
const newPassword = document.getElementById('new-password');
const confirmNewPassword = document.getElementById('confirm-password');
const changePasswordMsg = document.querySelector('.message');

const submitBtn = document.getElementById("change-passwordBtn"); // your button
const loader = document.getElementById("loader");       // loader inside button
const btnText = submitBtn.querySelector(".btn-text");

// Hide error message by default
if (changePasswordMsg) changePasswordMsg.style.display = 'none';

// Submit handler with validation + AJAX
if (form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate password strength
        const passwordValue = newPassword.value;

        if (passwordValue.length < 8) {
            changePasswordMsg.textContent = "Password must be at least 8 characters.";
            changePasswordMsg.style.display = 'block';
            return;
        }
        
        // Check for spaces
        if (/\s/.test(passwordValue)) {
            changePasswordMsg.textContent = "Password must not contain spaces.";
            changePasswordMsg.style.display = 'block';
            return;
        }
        
        // Check for uppercase letter
        if (!/[A-Z]/.test(passwordValue)) {
            changePasswordMsg.textContent = "Password must contain at least one uppercase letter.";
            changePasswordMsg.style.display = 'block';
            return;
        }
        
        // Check for lowercase letter
        if (!/[a-z]/.test(passwordValue)) {
            changePasswordMsg.textContent = "Password must contain at least one lowercase letter.";
            changePasswordMsg.style.display = 'block';
            return;
        }
        
        // Check for number
        if (!/\d/.test(passwordValue)) {
            changePasswordMsg.textContent = "Password must contain at least one number.";
            changePasswordMsg.style.display = 'block';
            return;
        }
        
        // Check for special character
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(passwordValue)) {
            changePasswordMsg.textContent = "Password must contain at least one special character.";
            changePasswordMsg.style.display = 'block';
            return;
        }

        // Validate match
        if (passwordValue !== confirmNewPassword.value) {
            changePasswordMsg.textContent = "Passwords do not match.";
            changePasswordMsg.style.display = 'block';
            return;
        }

        changePasswordMsg.style.display = 'none'; // Hide errors

        // Show loading 
        submitBtn.classList.add('loading');
        loader.style.display = "inline-block";
        btnText.style.display = "none";
        submitBtn.disabled = true;

        const formData = new FormData(form);
        fetch("/bridge/user_auth/processes/process_change_password.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.text())
        .then(result => {
            setTimeout(() => {
                // Hide loader
                submitBtn.classList.remove('loading');
                loader.style.display = "none";
                btnText.style.display = "inline";
                submitBtn.disabled = false;

                if (result.includes("success")) {
                    showToast("Password changed successfully!");
                    setTimeout(() => window.location.href = "login", 2000);
                } else {
                    showToast("Password update failed!");
                }
            }, 700);
        })
        .catch(err => {
            // Hide loader on error
            submitBtn.classList.remove('loading');
            loader.style.display = "none";
            btnText.style.display = "inline";
            submitBtn.disabled = false;

            showToast("Error: " + err.message);
        });
    });
}


////////// PASSWORD TOGGLE //////////
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    }
}


////////// TOAST HELPER (Responsive) //////////
function showToast(message) {
    let toast = document.getElementById("customToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "customToast";
        toast.style.position = "fixed";
        toast.style.top = "20px"; // ⬆️ top position
        toast.style.left = "50%";
        toast.style.transform = "translateX(-50%) translateY(-100px)"; // hidden above
        toast.style.background = "#5c297c";
        toast.style.color = "#fff";
        toast.style.padding = "12px 20px"; // smaller padding
        toast.style.borderRadius = "8px";
        toast.style.zIndex = "99999";
        toast.style.maxWidth = "90%"; // ✅ responsive width
        toast.style.wordWrap = "break-word"; // ✅ wrap long text
        toast.style.textAlign = "center";
        toast.style.fontSize = "clamp(14px, 2vw, 18px)"; // ✅ responsive font size
        toast.style.opacity = "0";
        toast.style.transition = "all 0.5s ease";
        document.body.appendChild(toast);
    }

    toast.textContent = message;

    // Trigger slide down
    requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateX(-50%) translateY(0)";
    });

    // Hide after 2s
    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
    }, 2000);
}

