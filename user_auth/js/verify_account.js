// ===== OTP INPUT HANDLING =====
function moveToNext(input, index) {
    let inputs = document.querySelectorAll('.otp-input');
    if (input.value && index < inputs.length - 1) {
        inputs[index + 1].focus();
    }
    if (index === inputs.length - 1 && input.value) {
        verifyOTP();
    }
}

function validateNumber(event) {
    if (!/^\d$/.test(event.key) && event.key !== "Backspace" && event.key !== "Delete") {
        event.preventDefault();
    }
}

function handleBackspace(event, index) {
    let inputs = document.querySelectorAll('.otp-input');
    if (event.key === "Backspace" && !inputs[index].value && index > 0) {
        inputs[index - 1].focus();
        inputs[index - 1].value = "";
    }
}

function handlePaste(event) {
    event.preventDefault();
    let inputs = document.querySelectorAll('.otp-input');
    let pastedData = event.clipboardData.getData('text').slice(0, 6);

    if (/^\d+$/.test(pastedData)) {
        inputs.forEach((inp, i) => inp.value = pastedData[i] || "");
        inputs[Math.min(pastedData.length, inputs.length - 1)].focus();

        if (pastedData.length === 6) verifyOTP();
    }
}

// ===== TIMER + RESEND =====
let timer;
const otpMessage = document.getElementById('otpMessage');
const timerDisplay = document.getElementById('timer');
const timerText = document.getElementById('timerText');
const resendBtn = document.getElementById('resendBtn');
const errorMessage = document.querySelector('.message');
const otpInputs = document.querySelectorAll('.otp-input');
const verifyLoader = document.getElementById('verifyLoader');

function checkOtpStatus(token, callback) {
    fetch("/bridge/user_auth/processes/process_check_otp.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "token=" + encodeURIComponent(token)
    })
    .then(res => res.json())
    .then(data => callback(data.valid, data.expiry))
    .catch(() => callback(false, null));
}

function startTimer(expiryTime) {
    clearInterval(timer);

    otpMessage.textContent = "We sent a 6-digit OTP to your email.";
    otpMessage.style.display = "block";
    timerText.style.display = "block";
    resendBtn.style.display = "none";
    errorMessage.style.display = "none";

    otpInputs.forEach(input => { input.disabled = false; input.value = ""; });

    // ✅ Use Date.now() based calculation for accurate timing
    function updateTimer() {
        const now = Date.now();
        const timeLeft = Math.max(0, Math.floor((expiryTime - now) / 1000));
        
        if (timeLeft > 0) {
            updateTimerDisplay(timeLeft);
        } else {
            clearInterval(timer);
            showResendState();
        }
    }

    // Start the timer
    timer = setInterval(updateTimer, 1000);
    updateTimer(); // Call immediately to show initial time
}

function updateTimerDisplay(seconds) {
    let minutes = Math.floor(seconds / 60);
    let remainingSeconds = seconds % 60;
    timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

function showResendState() {
    otpMessage.style.display = "none";
    timerText.style.display = "none";
    resendBtn.style.display = "block";
    timerDisplay.textContent = "00:00";
    otpInputs.forEach(input => { input.value = ""; input.disabled = true; });
}

function resendCode() {
    const token = document.querySelector('input[name="token"]').value;
    
    // Add fade out and disable effects
    resendBtn.classList.add('fade-out', 'disabled');
    resendBtn.disabled = true;
    
    // Add loading state after fade out
    setTimeout(() => {
        resendBtn.classList.add('loading');
    }, 300);

    fetch("/bridge/user_auth/processes/process_resend_code.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "token=" + encodeURIComponent(token)
    })
    .then(res => res.json())
    .then(data => {
        // Remove all states and restore button
        resendBtn.classList.remove('loading', 'fade-out', 'disabled');
        resendBtn.disabled = false;
        
        if (data.success && data.expiry) {
            startTimer(data.expiry);
            otpInputs.forEach(inp => inp.value = "");
            otpInputs[0].focus();
        } else {
            errorMessage.textContent = data.message || "Something went wrong.";
            errorMessage.style.display = "block";
        }
    })
    .catch(() => {
        // Remove all states and restore button
        resendBtn.classList.remove('loading', 'fade-out', 'disabled');
        resendBtn.disabled = false;
        errorMessage.textContent = "Server error. Try again.";
        errorMessage.style.display = "block";
    });
}

// ===== VERIFY OTP =====
function verifyOTP() {
    const enteredOTP = Array.from(otpInputs).map(input => input.value).join('');
    const token = document.querySelector('input[name="token"]').value;

    if (enteredOTP.length === 6) {
        verifyLoader.style.display = "inline-block";
        errorMessage.style.display = "none";

        fetch("/bridge/user_auth/processes/process_verify_account.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "token=" + encodeURIComponent(token) + "&code=" + encodeURIComponent(enteredOTP)
        })
        .then(res => res.json())
        .then(data => {
            setTimeout(() => {
                verifyLoader.style.display = "none";
                if (data.success) {
                    // Use the redirect URL from server response (contains new token)
                    window.location.href = data.redirect;
                } else if (data.message === "OTP expired") {
                    errorMessage.textContent = "OTP expired. Please resend.";
                    errorMessage.style.display = "block";
                    showResendState();
                } else {
                    errorMessage.textContent = "Invalid OTP. Try again.";
                    errorMessage.style.display = "block";
                    otpInputs.forEach(inp => inp.value = "");
                    otpInputs[0].focus();
                }
            }, 1200);
        })
        .catch(() => {
            verifyLoader.style.display = "none";
            errorMessage.textContent = "Server error. Try again.";
            errorMessage.style.display = "block";
        });
    }
}

// ===== INIT =====
document.addEventListener("DOMContentLoaded", function () {
    const token = document.querySelector('input[name="token"]').value;

    resendBtn.style.display = "none";
    verifyLoader.style.display = "none";

    // Ask backend for OTP expiry
    checkOtpStatus(token, function(isValid, expiry) {
        if (isValid) startTimer(expiry);
        else showResendState();
    });

    // Add events to inputs
    otpInputs.forEach((input, index) => {
        input.addEventListener("input", () => moveToNext(input, index));
        input.addEventListener("keydown", (e) => handleBackspace(e, index));
        input.addEventListener("keypress", validateNumber);
        if (index === 0) input.addEventListener("paste", handlePaste);
    });

    // ✅ Extra: auto-submit when all OTP boxes are filled
    otpInputs.forEach(input => {
        input.addEventListener("input", () => {
            const allFilled = [...otpInputs].every(inp => inp.value.trim() !== "");
            if (allFilled) verifyOTP();
        });
    });

    // ✅ Extra: hide error when user types again
    otpInputs.forEach(input => {
        input.addEventListener("input", () => {
            if (errorMessage) {
                errorMessage.style.display = "none";
                errorMessage.textContent = "";
            }
        });
    });
});
