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

    fetch("/bridge/user_auth/processes/process_signup_email.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        // delay response handling so loader shows
        setTimeout(() => {
            if (data.success) {
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
        }, 700); // adjust delay
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