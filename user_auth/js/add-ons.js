//UNUSED BUT JUST IN CASE NA MAY GAMITIN//

//////////for loading animation on next pages
document.addEventListener('DOMContentLoaded', function () {
    // Select all links with the "next-page" class (which wraps the buttons)
    const nextPageButtons = document.querySelectorAll(".next-page");
        
    // Add event listener for each button inside the links
    nextPageButtons.forEach(function(button) {
    button.addEventListener("click", function (e) {
    e.preventDefault(); // Prevent the default link behavior
        
    NProgress.start(); // Start the loading bar
        
    setTimeout(() => {
        NProgress.set(0.7); // Move the bar 70% quickly
    }, 300); // Adjust the speed here
        
    setTimeout(() => {
        NProgress.done(); // Complete the bar animation
        //window.location.href = button.href; // Redirect after the loading bar completes
		document.getElementById("loginSignup").submit();
    }, 1200); // Adjust the time here based on the animation duration
});
});
    
// Ensure the progress bar is fully complete when the page is loaded
window.onload = function () {
    NProgress.done();
};
});





//////////for loading animation on going back to prev. pages
document.addEventListener('DOMContentLoaded', function () {
    // Select all links with the "back-page" class
    const backPageLinks = document.querySelectorAll(".back-page");

    // Add event listener for each back link
    backPageLinks.forEach(function(link) {
        link.addEventListener("click", function (e) {
        e.preventDefault(); // Prevent default link behavior to show the loading animation

        NProgress.start(); // Start the loading bar animation

        setTimeout(() => {
            NProgress.set(0.7); // Move the bar to 70%
        }, 300); // Adjust timing here

        setTimeout(() => {
            NProgress.done(); // Finish the loading bar animation
            window.location.href = link.href; // Redirect after loading bar completes
        }, 1200); // Ensure the redirect happens after the animation completes
    });
});

// Ensure NProgress finishes when the page loads
window.onload = function () {
    NProgress.done();
};
});






///////////////////////Loading for when click search account on forget password
document.querySelector("form").addEventListener("submit", function(event) {
    event.preventDefault(); // stop immediate form submission first!
                
    let searchBtn = document.getElementById("searchBtn");
    let loader = document.getElementById("loader");
    let btnText = document.querySelector(".btn-text");
                
    loader.style.display = "inline-block"; // show loader
    btnText.style.display = "none"; // hide "Search" text
    searchBtn.classList.add("loading");
    searchBtn.disabled = true;
                
    // simulate processing
    setTimeout(function() {
        NProgress.start(); // Start NProgress when processing done
                
    setTimeout(function() {
        NProgress.done(); // Finish the NProgress
            
        event.target.submit(); // actually submit the form after loading
    }, 1200); // NProgress animation timing (same as your back/next pages)
                        
    }, 3000); // 3 seconds of fake processing
});
         




/////////For 6 textboxes OTP Code
function moveToNext(input, index) {
    let inputs = document.querySelectorAll('.otp-input');

    // If the value is not empty, move to next input field
    if (input.value && index < inputs.length - 1) {
        inputs[index + 1].focus();
    }
}

// Function to validate only numeric input and restrict non-numeric characters
function validateNumber(event) {
    let key = event.key;

    // Allow backspace, delete, and numbers (0-9) only
    if (key !== 'Backspace' && key !== 'Delete' && !/^\d$/.test(key)) {
        event.preventDefault();  // Prevent non-numeric input
    }
}

// Handle backspace
function handleBackspace(event, index) {
    let inputs = document.querySelectorAll('.otp-input');
    
    if (event.key === 'Backspace' && !inputs[index].value && index > 0) {
        inputs[index - 1].focus();
        inputs[index - 1].value = '';
    }
}

// Handle paste event
function handlePaste(event) {
    event.preventDefault();
    let inputs = document.querySelectorAll('.otp-input');
    let pastedData = event.clipboardData.getData('text').slice(0, 6);
    
    // Only proceed if pasted data is numeric
    if (/^\d+$/.test(pastedData)) {
        // Fill each input with the corresponding digit
        for (let i = 0; i < pastedData.length; i++) {
            if (i < inputs.length) {
                inputs[i].value = pastedData[i];
            }
        }
        // Focus the next empty input or the last input
        let nextEmptyIndex = pastedData.length;
        if (nextEmptyIndex < inputs.length) {
            inputs[nextEmptyIndex].focus();
        } else {
            inputs[inputs.length - 1].focus();
        }
    }
}

// Ensure sequential typing (prevent skipping ahead)
function enforceSequentialFocus(event, index) {
    let inputs = document.querySelectorAll('.otp-input');
    
    // Find the first empty box
    let firstEmptyIndex = Array.from(inputs).findIndex(input => input.value === "");

    // If user tries to focus ahead of the first empty input, block it
    if (index > firstEmptyIndex && firstEmptyIndex !== -1) {
        event.preventDefault();
        inputs[firstEmptyIndex].focus();
    }
}

// Add event listeners to all OTP inputs
document.addEventListener('DOMContentLoaded', function() {
    let inputs = document.querySelectorAll('.otp-input');
    
    inputs.forEach((input, index) => {
        // Add paste event listener to the first input only
        if (index === 0) {
            input.addEventListener('paste', handlePaste);
        }
        
        // Add backspace event listener to all inputs
        input.addEventListener('keydown', function(event) {
            handleBackspace(event, index);
        });

        // Enforce sequential typing rule
        input.addEventListener('focus', function(event) {
            enforceSequentialFocus(event, index);
        });
    });
});


/////////Timer with OTP
let timer;
const otpDuration = 120; // 2 minutes
const otpMessage = document.getElementById('otpMessage');
const timerDisplay = document.getElementById('timer');
const timerText = document.getElementById('timerText');
const resendBtn = document.getElementById('resendBtn');
const errorMessage = document.querySelector('.message');
const otpInputs = document.querySelectorAll('.otp-input');
const verifyLoader = document.getElementById('verifyLoader');

// mock OTP for demo
const correctOTP = "123456"; 

function startTimer() {
    let expiryTime = localStorage.getItem('otpExpiryTime');

    // If no expiry saved or already expired, create new one
    if (!expiryTime || Date.now() > expiryTime) {
        expiryTime = Date.now() + otpDuration * 1000;
        localStorage.setItem('otpExpiryTime', expiryTime);
    }

    runTimer();
}

function runTimer() {
    clearInterval(timer);
    let expiryTime = localStorage.getItem('otpExpiryTime');

    otpMessage.textContent = "We sent a 6-digit OTP to your email.";
    otpMessage.style.display = "block";
    timerText.style.display = "block";
    resendBtn.style.display = "none"; 
    errorMessage.style.display = "none";

    otpInputs.forEach(input => {
        input.disabled = false;
        input.value = "";
    });

    // ✅ Use Date.now() based calculation for accurate timing
    function updateTimer() {
        const now = Date.now();
        const timeLeft = Math.max(0, Math.floor((expiryTime - now) / 1000));
        
        if (timeLeft > 0) {
            updateTimerDisplay(timeLeft);
        } else {
            clearInterval(timer);
            timerDisplay.textContent = "00:00";
            otpMessage.style.display = "none";
            timerText.style.display = "none";
            localStorage.removeItem('otpExpiryTime');

            resendBtn.style.display = "block"; 
            otpInputs.forEach(input => input.disabled = true);
        }
    }

    // Start the timer
    timer = setInterval(updateTimer, 1000);
    updateTimer(); // Call immediately to show initial time
}

function updateTimerDisplay(seconds) {
    let minutes = Math.floor(seconds / 60);
    let remainingSeconds = seconds % 60;
    let formattedTime = `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
    timerDisplay.textContent = formattedTime;
}

function resendCode() {
    const loader = document.getElementById('loader');
    const btnText = resendBtn.querySelector('.btn-text');
    
    resendBtn.disabled = true;
    resendBtn.classList.add('loading');
    loader.style.display = "inline-block";
    btnText.style.display = "none";

    setTimeout(() => {
        startTimer();
        resendBtn.disabled = false;
        resendBtn.classList.remove('loading');
        loader.style.display = "none";
        btnText.style.display = "inline";
    }, 1000);
}

// OTP input handling
function moveToNext(elem, index) {
    if (elem.value.length === 1 && index < 5) {
        otpInputs[index + 1].focus();
    }
    if (index === 5 && elem.value.length === 1) {
        verifyOTP();
    }
}

function handleBackspace(event, index) {
    if (event.key === "Backspace" && !otpInputs[index].value && index > 0) {
        otpInputs[index - 1].focus();
    }
}

function validateNumber(event) {
    if (!/[0-9]/.test(event.key)) {
        event.preventDefault();
    }
}

// OTP verification with loader
function verifyOTP() {
    const enteredOTP = Array.from(otpInputs).map(input => input.value).join('');
    if (enteredOTP.length === 6) {
        errorMessage.style.display = "none";
        verifyLoader.style.display = "inline-block"; // ✅ show loading
        otpInputs.forEach(input => input.disabled = true);

        setTimeout(() => {
            if (enteredOTP === correctOTP) {
                window.location.href = "ChangePassword.php"; // ✅ proceed
            } else {
                errorMessage.style.display = "block"; // ❌ wrong otp
                verifyLoader.style.display = "none"; // hide loader
                otpInputs.forEach(input => input.disabled = false);
                otpInputs.forEach(input => input.value = ""); // clear
                otpInputs[0].focus();
            }
        }, 1500); // simulate API delay
    }
}

// On page load
document.addEventListener("DOMContentLoaded", function () {
    startTimer();
    resendBtn.style.display = "none"; // ✅ hide on load
    verifyLoader.style.display = "none"; // hide loader initially
});







////////////////////SIGNUP PASSWORD ////////////
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm-password');
const errorMsg = document.getElementById('error-message');

// Hide error message by default
if (errorMsg) {
    errorMsg.style.display = 'none';
}

function validatePasswordMatch() {
    if (password && confirmPassword) {
        if (password.value && confirmPassword.value) {
            if (password.value !== confirmPassword.value) {
                errorMsg.style.display = 'block';
            } else {
                errorMsg.style.display = 'none';
            }
        } else {
            errorMsg.style.display = 'none';
        }
    }
}

if (password && confirmPassword) {
    password.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
}

////////////////////CHANGE PASSWORD ////////////
const newPassword = document.getElementById('new-password');
const confirmNewPassword = document.getElementById('confirm-password');
const changePasswordMsg = document.querySelector('.message');

// Hide error message by default
if (changePasswordMsg) {
    changePasswordMsg.style.display = 'none';
}

function validateChangePasswordMatch() {
    if (newPassword && confirmNewPassword) {
        if (newPassword.value && confirmNewPassword.value) {
            if (newPassword.value !== confirmNewPassword.value) {
                changePasswordMsg.style.display = 'block';
            } else {
                changePasswordMsg.style.display = 'none';
            }
        } else {
            changePasswordMsg.style.display = 'none';
        }
    }
}

if (newPassword && confirmNewPassword) {
    newPassword.addEventListener('input', validateChangePasswordMatch);
    confirmNewPassword.addEventListener('input', validateChangePasswordMatch);
}

/////////////////////////////TERMS OF SERVICE
document.addEventListener("DOMContentLoaded", function () {
    const agreeCheckbox = document.getElementById('agreeCheckbox');
    const acceptButton = document.getElementById('acceptButton');
    const declineBtn = document.getElementById("declineButton");
    const loader = document.getElementById("loader");

    // Initially hide the "Accept" button and loader
    acceptButton.style.display = 'none';
    loader.style.display = 'none';

    // Show "Accept" button and hide "Decline" when checkbox is checked
    agreeCheckbox.addEventListener('change', function () {
        if (agreeCheckbox.checked) {
            acceptButton.style.display = 'inline-block';
            declineBtn.style.display = 'none';   // ✅ use declineBtn
        } else {
            acceptButton.style.display = 'none';
            declineBtn.style.display = 'inline-block'; // ✅ use declineBtn
        }
    });

    declineBtn.addEventListener("click", function () {
        // hide button & show loader
        declineBtn.style.display = "none";
        loader.style.display = "inline-block";

        // simulate loading, then redirect
        setTimeout(() => {
            window.location.href = "login.php"; // ✅ or change.php if you want
        }, 1500);
    });

    // Show modal on page load
    const termsModal = new bootstrap.Modal(document.getElementById('termsModal'), {
        backdrop: 'static',
        keyboard: false
    });
    termsModal.show();
});





/////////////////////////////DYNAMIC PROGRAM SELECTION
document.addEventListener('DOMContentLoaded', function() {
    const collegeSelect = document.getElementById('college');
    const positionSelect = document.getElementById('position');
    const programGroup = document.getElementById('programGroup');
    const programSelect = document.getElementById('program');

    // Program options for each college
    const programOptions = {
        medtech: [
            { value: 'bsmt', text: 'BS Medical Technology' },
            { value: 'bsrt', text: 'BS Radiologic Technology' }
        ]
        // Add more colleges and their programs here as needed
    };

    // Function to update program options
    function updateProgramOptions() {
        const selectedCollege = collegeSelect.value;
        const selectedPosition = positionSelect.value;

        // Clear existing options
        programSelect.innerHTML = '<option value="" disabled selected></option>';

        // Show/hide program selection based on conditions
        if (selectedPosition === 'head' && selectedCollege === 'medtech') {
            programGroup.style.display = 'block';
            programSelect.required = true;

            // Add program options
            programOptions.medtech.forEach(program => {
                const option = document.createElement('option');
                option.value = program.value;
                option.textContent = program.text;
                programSelect.appendChild(option);
            });
        } else {
            programGroup.style.display = 'none';
            programSelect.required = false;
        }
    }

    // Add event listeners
    collegeSelect.addEventListener('change', updateProgramOptions);
    positionSelect.addEventListener('change', updateProgramOptions);
});






////////////////////PASSWORD TOGGLE ////////////
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


