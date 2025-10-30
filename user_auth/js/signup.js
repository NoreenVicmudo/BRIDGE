//TERMS OF SERVICE//
document.addEventListener("DOMContentLoaded", function () {
    const agreeCheckbox = document.getElementById('agreeCheckbox');
    const acceptButton = document.getElementById('acceptButton');
    const declineBtn = document.getElementById("declineButton");

    // Initially hide the "Accept" button and loader
    acceptButton.style.display = 'none';

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

        // simulate loading, then redirect
        setTimeout(() => {
            window.location.href = "login"; 
        }, 1500);
    });

    // Show modal on page load
    const termsModal = new bootstrap.Modal(document.getElementById('termsModal'), {
        backdrop: 'static',
        keyboard: false
    });
    termsModal.show();
});



//PASSWORD TOGGLE//
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

///DYNAMIC PROGRAM SELECTION//
document.addEventListener('DOMContentLoaded', function() {
    const collegeSelect = document.getElementById('college');
    const positionSelect = document.getElementById('position');
    const programGroup = document.getElementById('programGroup');
    const programSelect = document.getElementById('program');

    let collegeOptions = [];
    let programOptions = {};
    let positionOptions = [];

    fetch("/bridge/user_auth/processes/populate_filter_college.php")
    .then(res => res.json())
    .then(data => {
        collegeOptions = data.collegeOptions;
        programOptions = data.programOptions;
        positionOptions = data.positionOptions;

        populateColleges();
        updatePositionOptions(); // call with initial state
    });

    function populateColleges() {
        collegeSelect.innerHTML = '<option value="" disabled selected></option>';
        collegeOptions.forEach(c => {
            const option = document.createElement('option');
            option.value = c.id;
            option.textContent = c.name;
            collegeSelect.appendChild(option);
        });
    }

    function updatePositionOptions() {
        positionSelect.innerHTML = '<option value="" disabled selected></option>';
        positionOptions.forEach(p => {
            const option = document.createElement("option");
            option.value = p.id;
            option.textContent = p.name;
            positionSelect.appendChild(option);
        });
    }

    // Function to update program options
     // Function to update program options
    function updateProgramOptions() {
        const selectedCollege = parseInt(collegeSelect.value, 10);
        const selectedPosition = parseInt(positionSelect.value, 10);

        // Reset
        programSelect.innerHTML = '<option value="" disabled selected></option>';
        programGroup.style.display = "none";
        programSelect.required = false;

        // ✅ Show program options only if Program Head
        if (selectedPosition === 3) {
            programGroup.style.display = "block";
            programSelect.required = true;

            if (programOptions[selectedCollege] && programOptions[selectedCollege].length > 0) {
                programOptions[selectedCollege].forEach(prog => {
                    const option = document.createElement("option");
                    option.value = prog.id;
                    option.textContent = prog.name;
                    programSelect.appendChild(option);
                });
            } else {
                // If college has no programs, show a disabled "No programs" option
                const option = document.createElement("option");
                option.disabled = true;
                option.textContent = "No programs available";
                programSelect.appendChild(option);
            }
        }
    }

    // Add event listeners
    collegeSelect.addEventListener('change', updateProgramOptions);
    positionSelect.addEventListener('change', updateProgramOptions);
});


//CHARACTER COUNT FUNCTIONALITY//
document.addEventListener('DOMContentLoaded', function () {
    // Character count functionality for firstname, lastname, and username
    const inputs = [
        { input: 'firstname', counter: 'firstname-count', maxLength: 50 },
        { input: 'lastname', counter: 'lastname-count', maxLength: 50 },
        { input: 'username', counter: 'username-count', maxLength: 30 }
    ];

    inputs.forEach(({ input, counter, maxLength }) => {
        const inputElement = document.getElementById(input);
        const counterElement = document.getElementById(counter);

        if (inputElement && counterElement) {
            // Update counter on input
            inputElement.addEventListener('input', function() {
                const currentLength = this.value.length;
                counterElement.textContent = `${currentLength}/${maxLength}`;
                
                // Add warning class when approaching limit
                if (currentLength >= maxLength * 0.9) {
                    counterElement.classList.add('warning');
                } else {
                    counterElement.classList.remove('warning');
                }
                
                // Add max-reached class when at limit
                if (currentLength >= maxLength) {
                    counterElement.classList.add('max-reached');
                } else {
                    counterElement.classList.remove('max-reached');
                }
            });

            // Initialize counter
            counterElement.textContent = `0/${maxLength}`;
        }
    });
});

//Process signup//
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('signup');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const errorMsg = document.getElementById('error-message'); // your <div class="message" id="error-message">
    const submitBtn = document.getElementById("signupBtn");

    if (errorMsg) errorMsg.style.display = 'none';

    // ✅ Real-time password validation
    function validatePasswordRequirements(passwordValue) {
        const requirements = {
            length: passwordValue.length >= 8,
            spaces: !/\s/.test(passwordValue),
            uppercase: /[A-Z]/.test(passwordValue),
            lowercase: /[a-z]/.test(passwordValue),
            number: /\d/.test(passwordValue),
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(passwordValue)
        };

        // Update UI for each requirement
        document.getElementById('req-length').className = `requirement-item ${requirements.length ? 'valid' : 'invalid'}`;
        document.getElementById('req-spaces').className = `requirement-item ${requirements.spaces ? 'valid' : 'invalid'}`;
        document.getElementById('req-uppercase').className = `requirement-item ${requirements.uppercase ? 'valid' : 'invalid'}`;
        document.getElementById('req-lowercase').className = `requirement-item ${requirements.lowercase ? 'valid' : 'invalid'}`;
        document.getElementById('req-number').className = `requirement-item ${requirements.number ? 'valid' : 'invalid'}`;
        document.getElementById('req-special').className = `requirement-item ${requirements.special ? 'valid' : 'invalid'}`;

        return Object.values(requirements).every(req => req === true);
    }

    // ✅ Real-time validation on password input
    password.addEventListener('input', function() {
        const passwordValue = this.value;
        validatePasswordRequirements(passwordValue);
        
        // Hide error message when user types
        errorMsg.style.display = 'none';
    });

    // ✅ Hide error as soon as the user types again
    confirmPassword.addEventListener('input', () => {
        errorMsg.style.display = 'none';
    });
    
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const passwordValue = password.value.trim();
            const confirmValue = confirmPassword.value.trim();

            // ✅ Only check if all password requirements are met
            const allRequirementsMet = validatePasswordRequirements(passwordValue);
            if (!allRequirementsMet) {
                errorMsg.textContent = "Please meet all password requirements above.";
                errorMsg.style.display = 'block';
                return;
            }

            // ✅ Only check password match
            if (passwordValue !== confirmValue) {
                errorMsg.textContent = "Passwords do not match.";
                errorMsg.style.display = 'block';
                return;
            }

            errorMsg.style.display = 'none'; // hide error if all good

            // ✅ Add loading class
            submitBtn.classList.add("loading");
            submitBtn.disabled = true;

            // ✅ Collect all form data
            const formData = new FormData(form);

            fetch("/bridge/user_auth/processes/process_signup.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json()) // expect JSON response
            .then(result => {
                if (result.success) {
                    // Keep button disabled and loader visible until redirect
                    setTimeout(() => {
                        window.location.href = "signup-success";
                    }, 700); // adjust delay if needed
                } else {
                    // Show error and reset button state
                    errorMsg.textContent = result.message || "Signup failed.";
                    errorMsg.style.display = 'block';

                    // Reset
                    submitBtn.classList.remove("loading");
                    submitBtn.disabled = false;
                }
            })
            .catch(err => {
                errorMsg.textContent = "Error: " + err.message;
                errorMsg.style.display = 'block';

                // Reset
                submitBtn.classList.remove("loading");
                submitBtn.disabled = false;
            });
        });
    }
});
