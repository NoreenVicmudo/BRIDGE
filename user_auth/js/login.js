//Loader with ajax (only for login php)//
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById("login");
    const messageDiv = document.querySelector(".message");
    const loginBtn = document.getElementById("loginBtn");
    const loader = document.getElementById("loader");
    const btnText = loginBtn.querySelector(".btn-text");

    // AJAX login handler (for the login button)
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        // Show loader
        loginBtn.classList.add('loading');
        loader.style.display = "inline-block";
        btnText.style.display = "none";
        loginBtn.disabled = true;

        fetch("/bridge/user_auth/processes/process_login.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            setTimeout(() => {
                loginBtn.classList.remove('loading');
                loader.style.display = "none";
                btnText.style.display = "inline";
                loginBtn.disabled = false;

                if (data.success) {
                    window.location.href = "home";
                } else if (data.redirect) {
                    // Redirect to landing_account_pending
                    window.location.href = data.redirect;
                } else {
                    messageDiv.textContent = data.message;
                    messageDiv.classList.add("text-danger", "mt-2");
                }
            }, 700);

        })
        .catch(err => {
            loginBtn.classList.remove('loading');
            loader.style.display = "none";
            btnText.style.display = "inline";
            loginBtn.disabled = false;
            console.error(err);
        });
    });

    // Hide error when user types again
    document.getElementById("email").addEventListener("input", () => messageDiv.textContent = "");
    document.getElementById("password").addEventListener("input", () => messageDiv.textContent = "");

    // Loader for next-page links (ONLY <a> tags)
    document.querySelectorAll('a.next-page').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            NProgress.start();
            setTimeout(() => NProgress.set(0.7), 300);
            setTimeout(() => {
                NProgress.done();
                window.location.href = link.href;
            }, 1200);
        });
    });
});


//Password toggle so that user can check their password more than once//
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


