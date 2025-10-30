//Search button loader direction and stay in same page if wrong email//
document.querySelector("form").addEventListener("submit", function(event) {
    event.preventDefault(); // stop normal submit

    let searchBtn = document.getElementById("searchBtn");
    let loader = document.getElementById("loader");
    let btnText = document.querySelector(".btn-text");
    let messageDiv = document.querySelector(".message");

    if (messageDiv) messageDiv.textContent = ""; // clear old messages

    // Show loader state
    loader.style.display = "inline-block";
    btnText.style.display = "none";
    searchBtn.classList.add("loading");
    searchBtn.disabled = true;

    let formData = new FormData(event.target);
    let newEmail = formData.get('email').toLowerCase();
    // Get previously saved email
    let savedEmail = localStorage.getItem("otpEmail");
    
    // If new email is different, clear old expiry time for old email
    if (savedEmail && savedEmail !== newEmail) {
        localStorage.removeItem("otpExpiryTime_" + savedEmail);
    }

    fetch("/bridge/user_auth/processes/process_find_account.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        // delay response handling so loader shows
        setTimeout(() => {
            if (data.success) {
            if (data.email && data.expiry) {
                const key = "otpExpiryTime_" + encodeURIComponent(data.email.toLowerCase());
            localStorage.setItem(key, data.expiry);
            localStorage.setItem("otpEmail", data.email.toLowerCase()); // store last used email
            }
            window.location.href = data.redirect;
        } else {
            messageDiv.textContent = data.message;
            messageDiv.style.color = "red";
        }

            // reset button state
            loader.style.display = "none";
            btnText.style.display = "inline";
            searchBtn.classList.remove("loading");
            searchBtn.disabled = false;
        }, 300); // adjust delay
    })
    .catch(err => {
        setTimeout(() => {
            messageDiv.textContent = "Something went wrong. Please try again.";
            messageDiv.style.color = "red";

            loader.style.display = "none";
            btnText.style.display = "inline";
            searchBtn.classList.remove("loading");
            searchBtn.disabled = false;
        }, 700);
        console.error(err);
    });
});

// Reset button state on back/forward navigation
// FindAccount.js

// Clear OTP timer whenever we return to FindAccount page 
window.addEventListener("pageshow", function () {
    // Get saved email + expiry from storage
    let savedEmail = localStorage.getItem("otpEmail");
    let expiry = localStorage.getItem("otpExpiryTime_" + encodeURIComponent(savedEmail || ""));

    if (!savedEmail || !expiry || Date.now() > expiry) {
        // Expired or no email → reset state
        localStorage.removeItem("otpEmail");
        if (savedEmail) {
            localStorage.removeItem("otpExpiryTime_" + encodeURIComponent(savedEmail));
        }
    }

    let searchBtn = document.getElementById("searchBtn");
    let loader = document.getElementById("loader");
    let btnText = document.querySelector(".btn-text");

    loader.style.display = "none";
    btnText.style.display = "inline";
    searchBtn.classList.remove("loading");
    searchBtn.disabled = false;
});
