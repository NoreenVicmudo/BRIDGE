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

// ==== SHOWTAB ==== //
function showTab(tabName, event) {
    if (event) event.preventDefault(); // prevent link from jumping

    // Save the selected tab in localStorage
    localStorage.setItem("activeTab", tabName);

    // Remove "active" class from all sidebar links
    const links = document.querySelectorAll('.sidebar a');
    links.forEach(link => link.classList.remove('active'));

    // Add "active" to the clicked link
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    } else {
        // If triggered on load, find the correct link
        document.querySelector(`.sidebar a[onclick*="${tabName}"]`)?.classList.add("active");
    }

    // Hide all tab contents
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.style.display = 'none');

    // Show the selected tab
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) selectedTab.style.display = 'block';
}

// ==== ON PAGE LOAD ==== //
document.addEventListener("DOMContentLoaded", function () {
    // Check localStorage for last active tab
    const savedTab = localStorage.getItem("activeTab") || "profile";
    showTab(savedTab);
});

// ===== PROFILE LOGIC =====
let originalUsername = document.getElementById("username").value;
let originalPic = document.getElementById("profile-pic").src;
let savedPic = document.getElementById("profile-pic").src; // Keep track of the actual saved image

document.getElementById("username").addEventListener("input", function() {
  updateCharCount();
  toggleActionButtons();
});

// ===== CHARACTER COUNT LOGIC =====
function updateCharCount() {
  const usernameInput = document.getElementById("username");
  const charCountElement = document.getElementById("char-count");
  const currentLength = usernameInput.value.length;
  const maxLength = 30;
  
  // Update the character count display
  charCountElement.textContent = `${currentLength}/${maxLength}`;
  
  // Update styling based on character count
  charCountElement.classList.remove("warning", "error", "minimum");
  
  if (currentLength < 3) {
    charCountElement.classList.add("minimum");
  } else if (currentLength > maxLength * 0.8) { // 80% of max length (24 characters)
    charCountElement.classList.add("warning");
  }
  
  if (currentLength >= maxLength) {
    charCountElement.classList.add("error");
  }
}

// Initialize character count on page load
document.addEventListener("DOMContentLoaded", function() {
  updateCharCount();
});

document.getElementById("file-upload").addEventListener("change", e => {
  let file = e.target.files[0];
  if (file){
    const loader = document.getElementById("pic-loader");
    const pic = document.getElementById("profile-pic");
    const uploadIcon = document.querySelector(".upload-icon");

    // hide pic + icon, show loader
    pic.style.visibility = "hidden";
    uploadIcon.style.display = "none";
    loader.style.display = "block";

    // prepare formData for validation
    let formData = new FormData();
    formData.append("profile_pic", file);
    formData.append("validate_only", "1"); // flag for validation

    fetch("/bridge/modules/settings/processes/update_profile.php", {
      method: "POST",
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      setTimeout(() => {
        if (data.success) {
          // ✅ only update preview if server accepted
          const reader = new FileReader();
          reader.onload = ev => {
            pic.src = ev.target.result;
            pic.style.visibility = "visible";
            uploadIcon.style.display = "flex";
            loader.style.display = "none";
            toggleActionButtons();
          };
          reader.readAsDataURL(file);
        } else {
          // ❌ reject - revert to saved pic
          loader.style.display = "none";
          pic.src = savedPic; // Revert to the actual saved image
          pic.style.visibility = "visible";
          uploadIcon.style.display = "flex";
          showToast("Error: " + data.message);
          e.target.value = ""; // reset file input
          toggleActionButtons(); // Hide buttons since nothing changed
        }
      }, 1300)
    })
    .catch(err => {
      loader.style.display = "none";
      pic.src = savedPic; // Revert to the actual saved image
      pic.style.visibility = "visible";
      uploadIcon.style.display = "flex";
      console.error(err);
      showToast("An error occurred while checking the file.");
      toggleActionButtons(); // Hide buttons since nothing changed
    });
  }
});


// ===== USERNAME VALIDATION =====
function isValidUsername(username) {
  // Check minimum length and pattern: letters, numbers, underscore, dash (3-30 characters)
  const pattern = /^[a-zA-Z0-9_-]{3,30}$/;
  return pattern.test(username);
}

function toggleActionButtons() {
  let act = document.getElementById("profile-actions");
  const username = document.getElementById("username").value;
  const hasChanges = (username !== originalUsername || document.getElementById("profile-pic").src !== savedPic);
  const isValid = isValidUsername(username);
  
  // Only show action buttons if there are changes AND username is valid
  act.style.display = (hasChanges && isValid) ? "flex" : "none";
}

function revertChanges() { 
  document.getElementById("username").value = originalUsername; 
  document.getElementById("profile-pic").src = savedPic; 
  toggleActionButtons();
}

function saveChanges() {
  const saveBtn = document.querySelector(".btn-save");
  const cancelBtn = document.querySelector(".btn-cancel-profile-change");
  const username = document.getElementById("username").value;

  // Validate username before proceeding
  if (!isValidUsername(username)) {
    showToast("Username must be 3-30 characters and contain only letters, numbers, underscore, and dash");
    return;
  }

  // Show loader and hide button text
  saveBtn.classList.add("loading");
  
  // Add debugging
  console.log("Save button classes:", saveBtn.classList.toString());
  console.log("Save button disabled:", saveBtn.disabled);

  // Disable buttons with enhanced visual feedback
  saveBtn.disabled = true;
  
  // Direct style application as fallback
  saveBtn.style.opacity = '0.6';
  saveBtn.style.cursor = 'not-allowed';
  saveBtn.style.pointerEvents = 'none';
  
  if(cancelBtn) {
    cancelBtn.disabled = true;
    cancelBtn.style.pointerEvents = 'none'; // Additional safety
  }

  let formData = new FormData();
  formData.append("username", document.getElementById("username").value);

  let fileInput = document.getElementById("file-upload");
  if (fileInput.files.length > 0) {
    formData.append("profile_pic", fileInput.files[0]); // send image blob
  }

  fetch("/bridge/modules/settings/processes/update_profile.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Show toast
      showToast("Profile updated!");   

      setTimeout(() => {
        // Update original values so buttons hide again
        originalUsername = document.getElementById("username").value;
        originalPic = document.getElementById("profile-pic").src;
        savedPic = document.getElementById("profile-pic").src; // Update saved image reference

        // Reset loader & buttons
        saveBtn.classList.remove("loading");
        saveBtn.disabled = false;
        
        // Reset direct styles
        saveBtn.style.opacity = '';
        saveBtn.style.cursor = '';
        saveBtn.style.pointerEvents = '';
        
        if(cancelBtn) {
          cancelBtn.disabled = false;
          cancelBtn.style.pointerEvents = 'auto'; // Restore interactions
        }

        toggleActionButtons();

        location.reload();
      }, 1300);
      //setTimeout(() => location.reload(), 1000);
    } else {
      showToast("Error: " + data.message);

      // Reset loader & buttons
      saveBtn.classList.remove("loading");
      saveBtn.disabled = false;
      
      // Reset direct styles
      saveBtn.style.opacity = '';
      saveBtn.style.cursor = '';
      saveBtn.style.pointerEvents = '';
      
      if(cancelBtn) {
        cancelBtn.disabled = false;
        cancelBtn.style.pointerEvents = 'auto'; // Restore interactions
      }
    }
  })
  .catch(err => {
    console.error(err);
    showToast("An error occurred");

    // Reset loader & buttons
    saveBtn.classList.remove("loading");
    saveBtn.disabled = false;
    
    // Reset direct styles
    saveBtn.style.opacity = '';
    saveBtn.style.cursor = '';
    saveBtn.style.pointerEvents = '';
    
    if(cancelBtn) {
      cancelBtn.disabled = false;
      cancelBtn.style.pointerEvents = 'auto'; // Restore interactions
    }
  });
}





 // ===== CHANGE EMAIL AND PASSWORD =====
 // ===== BASIC HELPERS =====
function openModal(id){
  const el = document.getElementById(id);
  if(!el) return;
  el.style.display = "flex";
  if(id === "password-modal"){ initPasswordOtp(); } // auto-start pass OTP screen
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (!el) return;

  // Add the "closing" class to trigger fadeOut animation
  el.classList.add("closing");

  // Wait for the animation (0.5s) then fully hide
  setTimeout(() => {
    el.classList.remove("show", "closing");
    el.style.display = "none";

    // Reset things depending on modal type
    if (id === "email-modal") {
      resetOtp("email");
      const msgBox = document.querySelector('#email-step1 .message');
      if (msgBox) msgBox.style.display = "none"; // clear msg when closing
    }
    if (id === "password-modal") {
      resetOtp("pass");
    }
  }, 500); // match fadeOut duration
}


// ===== OTP SHARED CONFIG =====
const OTP_DURATION = 120; // seconds
const CORRECT_OTP = { email: "123456", pass: "654321" }; // demo OTPs
let timers = { email: null, pass: null };

// ===== INPUT GUARDS (reusable) =====
function validateNumber(event){
  const key = event.key;
  if(key !== 'Backspace' && key !== 'Delete' && !/^\d$/.test(key)) event.preventDefault();
}

function handleBackspace(event, index, type) {
  const inputs = getInputs(type);

  if (event.key === 'Backspace') {
    if (inputs[index].value) {
      // If current box has a value → clear it
      inputs[index].value = '';
    } else if (index > 0) {
      // If already empty → move focus back
      inputs[index - 1].focus();
      inputs[index - 1].value = '';
    }
  }
}

function lockInputs(type) {
  const inputs = getInputs(type);

  inputs.forEach(inp => {
    inp.disabled = false;   // 🔹 keep all enabled
    inp.value = "";         // clear old values
    inp.onfocus = null;     // remove forced focus handler
  });

  // still auto-focus first input so user can start typing
  if (inputs[0]) inputs[0].focus();
}

function moveToNext(input, index, type) {
  const inputs = getInputs(type);

  if (input.value) {
    if (index < inputs.length - 1) {
      // normal case: go forward
      inputs[index + 1].disabled = false;
      inputs[index + 1].focus();
    } else {
      // last box filled → run verify
      verifyOTP(type);
    }
  }

  // 🔹 NEW: check if *all* boxes filled after typing in any box
  const allFilled = inputs.every(i => i.value !== "");
  if (allFilled) {
    verifyOTP(type);
  }
}






function getInputs(type){
  const modal = document.getElementById(type === 'email' ? 'email-modal' : 'password-modal');
  return Array.from(modal.querySelectorAll('.otp-input')); // convert to array
}

function handlePaste(event, type) {
  event.preventDefault();
  const inputs = getInputs(type);
  let pastedData = event.clipboardData.getData('text').slice(0, 6);

  if (/^\d+$/.test(pastedData)) {
    inputs.forEach((inp, i) => inp.value = pastedData[i] || "");
    inputs[Math.min(pastedData.length, inputs.length - 1)].focus();

    if (pastedData.length === 6) verifyOTP(type);
  }
}


// ===== TIMER (per type) =====
function startTimer(type){
  const isEmail = type === 'email';
  const modal = document.getElementById(isEmail ? 'email-modal' : 'password-modal');
  const timerSpan = modal.querySelector(isEmail ? '#timer' : '#timerPass');
  const timerText = modal.querySelector(isEmail ? '#timerText' : '#timerPassText');
  const resendBtn = modal.querySelector(isEmail ? '#resendBtnEmail' : '#resendBtnPass');
  const inputs = getInputs(type); // ✅ get the OTP input fields
  const msg = modal.querySelector(isEmail ? '#otpMessage' : '#otpPassMessage');
  const errorBox = modal.querySelector(isEmail ? '#emailError' : '#passError');

  if (timerText) timerText.style.display = 'block';
  if (resendBtn) resendBtn.style.display = 'none';

  // ✅ Store expiry time instead of countdown
  const expiryTime = Date.now() + (OTP_DURATION * 1000);
  
  // 🔹 Show the initial 02:00 right away
  if (timerSpan) {
    const minutes = Math.floor(OTP_DURATION / 60);
    const seconds = OTP_DURATION % 60;
    timerSpan.textContent = `${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
  }

  // ✅ Use Date.now() based calculation for accurate timing
  function updateTimer() {
    const now = Date.now();
    const timeLeft = Math.max(0, Math.floor((expiryTime - now) / 1000));
    
    if (timeLeft > 0) {
      if (timerSpan) {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerSpan.textContent = `${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
      }
    } else {
      // Timer expired
      clearInterval(timers[type]);
      if (timerText) timerText.style.display = 'none';
      if (resendBtn) resendBtn.style.display = 'inline-block';     
      if(errorBox) errorBox.style.display = 'none';
      if(msg) msg.style.display = 'none';
      inputs.forEach(i => { i.disabled = true; i.value = ''; });

      showToast("OTP expired. Please resend to try again.");
    }
  }

  // Start the timer
  timers[type] = setInterval(updateTimer, 1000);
  updateTimer(); // Call immediately to show initial time
}

function resendCode(type){
  const isEmail = type === 'email';
  const modal = document.getElementById(isEmail ? 'email-modal' : 'password-modal');
  const btn = modal.querySelector(isEmail ? '#resendBtnEmail' : '#resendBtnPass');
  const cancelBtn = modal.querySelector(isEmail ? '.btn-cancel-resend-email' : '.btn-cancel-resend-pass');
  const msg = modal.querySelector(isEmail ? '#otpMessage' : '#otpPassMessage');
  const errorBox = modal.querySelector(isEmail ? '#emailError' : '#passError');
  const inputs = getInputs(type);

   // 🔹 activate loader via CSS class with fade effect
  if (btn) {
    btn.classList.add("loading");
    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.style.cursor = 'not-allowed';
    btn.style.pointerEvents = 'none';
  }
  if (cancelBtn) {
    cancelBtn.disabled = true;
    cancelBtn.style.opacity = '0.4';
    cancelBtn.style.cursor = 'not-allowed';
    cancelBtn.style.pointerEvents = 'none';
  }

  let payload = "type=" + type;
  if(isEmail){
    const email = document.getElementById('otp-email-display').innerText;
    payload += "&new_email=" + encodeURIComponent(email);
  }

  fetch('/bridge/modules/settings/processes/process_send_otp.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded' },
    body: payload
  })
  .then(r=>r.json())
  .then(res=>{
    if(res.success){
      inputs.forEach(i => { i.value = ""; i.disabled = false; });
      lockInputs(type);
      startTimer(type);

      if(msg){
        msg.innerText = "We sent a 6-digit OTP to your email.";
        msg.style.display = "block";
      }
      if(errorBox) errorBox.style.display = "none";

      showToast("A new OTP has been sent.");
    } else {
      showToast(res.msg || "Failed to resend OTP");
    }
  })
  .catch(err=>{
    console.error(err);
    showToast("Server error. Please try again.");
  })
  .finally(()=>{
    // 🔹 remove loader & re-enable
    if (btn) {
      btn.classList.remove("loading");
      btn.disabled = false;
      btn.style.opacity = '';
      btn.style.cursor = '';
      btn.style.pointerEvents = '';
    }
    if (cancelBtn) {
      cancelBtn.disabled = false;
      cancelBtn.style.opacity = '';
      cancelBtn.style.cursor = '';
      cancelBtn.style.pointerEvents = '';
    }
  });
}

// ===== VERIFY (per type) =====
function verifyOTP(type){
 
 const isEmail = type === 'email';
  const modal = document.getElementById(isEmail ? 'email-modal' : 'password-modal');
  const inputs = getInputs(type);
  const code = Array.from(inputs).map(i => i.value).join('');
  const loader = modal.querySelector(isEmail ? '#verifyLoaderEmail' : '#verifyLoaderPass');
  const errorBox = modal.querySelector(isEmail ? '#emailError' : '#passError');

  if(code.length !== 6) return;

  // 🔹 Show loader immediately
  if(loader) loader.style.display = 'inline-block';
  if(errorBox) errorBox.style.display = 'none';
  inputs.forEach(i => i.disabled = true);
  
  // Disable cancel button during verification
  const cancelBtn = modal.querySelector(isEmail ? '.btn-cancel-resend-email' : '.btn-cancel-resend-pass');
  if(cancelBtn) {
    cancelBtn.disabled = true;
    cancelBtn.style.opacity = '0.4';
    cancelBtn.style.cursor = 'not-allowed';
    cancelBtn.style.pointerEvents = 'none';
  }

  let payload = 'type='+type+'&otp='+code;
  if(type==='email'){
    const newEmail = document.getElementById('otp-email-display').innerText;
    payload += '&new_email='+encodeURIComponent(newEmail);
  }

  // 🔹 Small delay (300–500ms) to let loader render before fetch
  setTimeout(() => {
    fetch('/bridge/modules/settings/processes/process_verify_otp.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:payload
    })
    .then(r=>r.json())
    .then(res=>{
      if(loader) loader.style.display = 'none';

      if(res.success){
        if(type==='email'){
          showToast(res.msg);
          // Simple page reload after showing toast
          setTimeout(()=>{
            location.reload();
          }, 2000); // wait for toast to be visible before reload
        } else {
          clearInterval(timers['pass']);
          document.getElementById('pass-step1').style.display='none';
          document.getElementById('pass-step2').style.display='block';
        }
      } else {
        if(errorBox){
          errorBox.innerText = res.msg;
          errorBox.style.display = 'block';        
        }
        inputs.forEach(i=>{ i.value=''; i.disabled=false; });
        if(inputs[0]) inputs[0].focus();
        lockInputs(type); // 🔄 reset inputs to start over
        
        // Re-enable cancel button on error
        const cancelBtn = modal.querySelector(isEmail ? '.btn-cancel-resend-email' : '.btn-cancel-resend-pass');
        if(cancelBtn) {
          cancelBtn.disabled = false;
          cancelBtn.style.opacity = '';
          cancelBtn.style.cursor = '';
          cancelBtn.style.pointerEvents = '';
        }
      }
    })
    .catch(()=>{
      if(loader) loader.style.display = 'none';
      if(errorBox){
        errorBox.innerText = "An error occurred. Please try again.";
        errorBox.style.display = 'block';
      }
      inputs.forEach(i=> i.disabled=false);
      
      // Re-enable cancel button on error
      const cancelBtn = modal.querySelector(isEmail ? '.btn-cancel-resend-email' : '.btn-cancel-resend-pass');
      if(cancelBtn) {
        cancelBtn.disabled = false;
        cancelBtn.style.opacity = '';
        cancelBtn.style.cursor = '';
        cancelBtn.style.pointerEvents = '';
      }
    });
  }, 1000); // <-- delay so loader shows up
}

// ===== EMAIL FLOW =====
function startEmailOtp(){
  const confirmBtn = document.querySelector("#email-modal .btn-confirm");
  const cancelBtn = document.querySelector("#email-modal .btn-cancel-email-step1");

  const newEmail = document.getElementById('new-email').value;
  if(!newEmail){ alert("Enter a valid email"); return; }

  // activate loader + disable both with fade effect
  confirmBtn.classList.add("loading");
  confirmBtn.disabled = true;
  confirmBtn.style.opacity = '0.6';
  confirmBtn.style.cursor = 'not-allowed';
  confirmBtn.style.pointerEvents = 'none';
  
  if(cancelBtn) {
    cancelBtn.disabled = true;
    cancelBtn.style.opacity = '0.4';
    cancelBtn.style.cursor = 'not-allowed';
    cancelBtn.style.pointerEvents = 'none';
  }

  fetch('/bridge/modules/settings/processes/process_send_otp.php',{
    method: 'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'type=email&new_email='+encodeURIComponent(newEmail)
  })
  .then(r=>r.json())
.then(res=>{
  if(res.success){
    document.getElementById('otp-email-display').innerText = newEmail;
    document.getElementById('email-step1').style.display = 'none';
    document.getElementById('email-step2').style.display = 'block';
    lockInputs('email');
    startTimer('email');

    document.getElementById('otpMessage').innerText = "We sent a 6-digit OTP to your email.";
    document.getElementById('otpMessage').style.display = 'block';
  } else {
    // ❌ show error in step1 box or toast for attempt limit
    if(res.msg && res.msg.includes('Too many reset attempts')) {
      showToast(res.msg);
      // Close the modal since they can't proceed
      setTimeout(() => {
        closeModal('email-modal');
      }, 2000);
    } else {
      const msgBox = document.querySelector('#email-step1 .message');
      if(msgBox){
        msgBox.innerText = res.msg;
        msgBox.style.display = 'block';
      }
    }
  }
})
.catch(err=>{
    console.error(err);
    const msgBox = document.querySelector('#email-step1 .message');
    if(msgBox){
      msgBox.innerText = "Server error. Please try again.";
      msgBox.style.display = 'block';
    }
  })
  .finally(()=>{
    // reset loader and buttons
    confirmBtn.classList.remove("loading");
    confirmBtn.disabled = false;
    confirmBtn.style.opacity = '';
    confirmBtn.style.cursor = '';
    confirmBtn.style.pointerEvents = '';
    
    if(cancelBtn) {
      cancelBtn.disabled = false;
      cancelBtn.style.opacity = '';
      cancelBtn.style.cursor = '';
      cancelBtn.style.pointerEvents = '';
    }
  });
}

// ===== PASSWORD FLOW =====
function initPasswordOtp(){
  // Show OTP step (reset first)
  document.getElementById('pass-step2').style.display = 'none';
  document.getElementById('pass-step1').style.display = 'block';
  resetOtp('pass');

  // Show loading state
  const loadingState = document.getElementById('otpLoadingState');
  const otpContainer = document.querySelector('#pass-step1 .otp-container');
  
  if (loadingState) loadingState.style.display = 'flex';
  if (otpContainer) otpContainer.style.display = 'none';

  // Then, send OTP 
  fetch('/bridge/modules/settings/processes/process_send_otp.php',{
    method: 'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'type=pass'
  })
  .then(r=>r.json())
  .then(res=>{
    // Hide loading state
    if (loadingState) loadingState.style.display = 'none';
    if (otpContainer) otpContainer.style.display = 'flex';
    
    if(res.success){
      lockInputs('pass');
      startTimer('pass');

      const msg = document.getElementById('otpPassMessage');
      if(msg){
        msg.innerText = "We sent a 6-digit OTP to your email.";
        msg.style.display = "block";
      }
    } else {
      // Check if it's an attempt limit error
      if(res.msg && res.msg.includes('Too many reset attempts')) {
        showToast(res.msg);
        // Close the modal since they can't proceed
        setTimeout(() => {
          closeModal('password-modal');
        }, 2000);
      } else {
        const errorBox = document.getElementById('passError');
        if(errorBox){
          errorBox.innerText = res.msg;
          errorBox.style.display = 'block';
        }
      }
    }
  })
  .catch(()=>{
    // Hide loading state on error
    if (loadingState) loadingState.style.display = 'none';
    if (otpContainer) otpContainer.style.display = 'flex';
    
    const errorBox = document.getElementById('passError');
    if(errorBox){
      errorBox.innerText = "Server error while sending OTP.";
      errorBox.style.display = 'block';
    }
  });
}


function backToEmail(){
  resetOtp('email');
  const msgBox = document.querySelector('#email-step1 .message');
  if(msgBox) msgBox.style.display = 'none'; // clear msg
}
function resetOtp(type){
  clearInterval(timers[type]);
  const isEmail = type === 'email';
  const modal = document.getElementById(isEmail ? 'email-modal' : 'password-modal');
  const msg = modal.querySelector(isEmail ? '#otpMessage' : '#otpPassMessage');
  const timerText = modal.querySelector(isEmail ? '#timerText' : '#timerPassText');
  const resendBtn = modal.querySelector(isEmail ? '#resendBtnEmail' : '#resendBtnPass');
  const errorBox = modal.querySelector(isEmail ? '#emailError' : '#passError');
  const loader = modal.querySelector(isEmail ? '#verifyLoaderEmail' : '#verifyLoaderPass');
  const inputs = getInputs(type);

  if(msg) msg.style.display = 'none';
  if(timerText) timerText.style.display = 'none';
  if(resendBtn) resendBtn.style.display = 'none';
  if(errorBox) errorBox.style.display = 'none';
  if(loader) loader.style.display = 'none';
  inputs.forEach(i => { i.disabled = false; i.value = ''; });

  // Reset loading state for password modal
  if(!isEmail){
    const loadingState = document.getElementById('otpLoadingState');
    const otpContainer = document.querySelector('#pass-step1 .otp-container');
    if (loadingState) loadingState.style.display = 'none';
    if (otpContainer) otpContainer.style.display = 'none';
  }

  // Extra reset for email modal: go back to step1
  if(isEmail){
    document.getElementById('email-step1').style.display = 'block';
    document.getElementById('email-step2').style.display = 'none';
    document.getElementById('new-email').value = ''; //clear old email
    document.getElementById('otp-email-display').innerText = '';
    if(timerText) timerText.style.display = 'none'; // only hide for email modal
  }
}

function savePassword(){
  const saveBtn = document.querySelector(".btn-passsave");
  const cancelBtn = document.querySelector(".btn-cancel-save-pass");
  const oldPass = document.getElementById('old-password').value;
  const newPass = document.getElementById('new-password').value;
  const confirmPass = document.getElementById('confirm-password').value;
  const msg  = document.getElementById('changePassMsg');

  // Clear previous error
  if (msg) {
    msg.style.display = 'none';
    msg.innerText = '';
  }

  // ✅ Validate match
  if (newPass !== confirmPass) {
    msg.innerText = "New and Confirm Passwords do not match.";
    msg.style.display = 'block';
    return;
  }
  
  // ✅ Validate not same as current password
  if (newPass === oldPass) {
    msg.innerText = "New password cannot be the same as your current password.";
    msg.style.display = 'block';
    return;
  }

  // ✅ Validate strength
  if (newPass.length < 8) {
    msg.innerText = "Password must be at least 8 characters.";
    msg.style.display = 'block';
    return;
  }
  
  // Check for spaces
  if (/\s/.test(newPass)) {
    msg.innerText = "Password must not contain spaces.";
    msg.style.display = 'block';
    return;
  }
  
  // Check for uppercase letter
  if (!/[A-Z]/.test(newPass)) {
    msg.innerText = "Password must contain at least one uppercase letter.";
    msg.style.display = 'block';
    return;
  }
  
  // Check for lowercase letter
  if (!/[a-z]/.test(newPass)) {
    msg.innerText = "Password must contain at least one lowercase letter.";
    msg.style.display = 'block';
    return;
  }
  
  // Check for number
  if (!/\d/.test(newPass)) {
    msg.innerText = "Password must contain at least one number.";
    msg.style.display = 'block';
    return;
  }
  
  // Check for special character
  if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(newPass)) {
    msg.innerText = "Password must contain at least one special character.";
    msg.style.display = 'block';
    return;
  }

  // ✅ Show loader & disable button with fade effect
  saveBtn.classList.add("loading");
  saveBtn.disabled = true;
  saveBtn.style.opacity = '0.6';
  saveBtn.style.cursor = 'not-allowed';
  saveBtn.style.pointerEvents = 'none';
  
  if (cancelBtn) {
    cancelBtn.disabled = true;
    cancelBtn.style.opacity = '0.4';
    cancelBtn.style.cursor = 'not-allowed';
    cancelBtn.style.pointerEvents = 'none';
  }

  // ✅ If all good → proceed
  fetch('/bridge/modules/settings/processes/process_password_change.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'old=' + encodeURIComponent(oldPass) +
        '&new=' + encodeURIComponent(newPass) +
        '&confirm=' + encodeURIComponent(confirmPass)
  })
  .then(r=>r.json())
  .then(res=>{
    setTimeout(() => {
      if(res.success){
        // hide loader
        saveBtn.classList.remove("loading");
        saveBtn.disabled = false;
        saveBtn.style.opacity = '';
        saveBtn.style.cursor = '';
        saveBtn.style.pointerEvents = '';
        
        if (cancelBtn) {
          cancelBtn.disabled = false;
          cancelBtn.style.opacity = '';
          cancelBtn.style.cursor = '';
          cancelBtn.style.pointerEvents = '';
        }

        showToast(res.msg); 
        // Simple page reload after showing toast
        setTimeout(()=>{
          location.reload();
        }, 2000); // wait for toast to be visible before reload
      } else {
        // ❌ Backend error: show message, remove loader
        saveBtn.classList.remove("loading");
        saveBtn.disabled = false;
        saveBtn.style.opacity = '';
        saveBtn.style.cursor = '';
        saveBtn.style.pointerEvents = '';
        
        if (cancelBtn) {
          cancelBtn.disabled = false;
          cancelBtn.style.opacity = '';
          cancelBtn.style.cursor = '';
          cancelBtn.style.pointerEvents = '';
        }
        if (msg) {
          msg.innerText = res.msg || "Current password is incorrect.";
          msg.style.display = 'block';
        }
      }
    }, 2000);
  })
  .catch(err=>{
    saveBtn.classList.remove("loading");
    saveBtn.disabled = false;
    saveBtn.style.opacity = '';
    saveBtn.style.cursor = '';
    saveBtn.style.pointerEvents = '';
    
    if (cancelBtn) {
      cancelBtn.disabled = false;
      cancelBtn.style.opacity = '';
      cancelBtn.style.cursor = '';
      cancelBtn.style.pointerEvents = '';
    }
    if(msg){
      msg.innerText = "Server error. Please try again.";
      msg.style.display = 'block';
    }
  })
}


// ===== PASSWORD TOGGLE =====
function togglePassword(inputId, icon){
  const input = document.getElementById(inputId);
  if(!input) return;
  if(input.type === "password"){
    input.type = "text";
    icon.classList.remove("bi-eye-slash");
    icon.classList.add("bi-eye");
  } else {
    input.type = "password";
    icon.classList.remove("bi-eye");
    icon.classList.add("bi-eye-slash");
  }
}

// ===== INITIAL STATE =====
document.addEventListener('DOMContentLoaded', () => {
  // hide loaders & resend buttons initially
  const emailModal = document.getElementById('email-modal');
  const passModal = document.getElementById('password-modal');
  if(emailModal){
    const r1 = emailModal.querySelector('#resendBtnEmail');
    const l1 = emailModal.querySelector('#verifyLoaderEmail');
    if(r1) r1.style.display = 'none';
    if(l1) l1.style.display = 'none';
  }
  if(passModal){
    const r2 = passModal.querySelector('#resendBtnPass');
    const l2 = passModal.querySelector('#verifyLoaderPass');
    if(r2) r2.style.display = 'none';
    if(l2) l2.style.display = 'none';
  }

  // Clear error in email step-1 when typing in the new email field
  const newEmailInput = document.getElementById("new-email");
  const emailMsgBox = document.querySelector("#email-step1 .message");

  if (newEmailInput && emailMsgBox) {
    newEmailInput.addEventListener("input", () => {
      emailMsgBox.innerText = "";
      emailMsgBox.style.display = "none";
    });
  }

  document.querySelectorAll('.otp-input').forEach(inp => {
    inp.addEventListener('input', () => {
      const modal = inp.closest('.modal');
      if(!modal) return;
      const errorBox = modal.querySelector('#emailError, #passError');
      if(errorBox) errorBox.style.display = 'none';
    });
  });
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
         

// In text notification
function showMessage(targetId, msg) {
  const box = document.getElementById(targetId);
  if (!box) return;
  box.textContent = msg;
  box.style.display = "block";
}



////////// TOAST HELPER (Responsive) //////////
let toastTimeout = null;

function showToast(message) {
    let toast = document.getElementById("customToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "customToast";
        toast.style.position = "fixed";
        toast.style.top = "20px"; // ⬆️ top position
        toast.style.left = "50%";
        toast.style.transform = "translateX(-50%) translateY(-100px)"; // hidden above
        toast.style.background = "#60357a";
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

    // Clear any existing timeout
    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }

    // Reset toast state before showing
    toast.style.display = "block";
    toast.style.opacity = "0";
    toast.style.transform = "translateX(-50%) translateY(-100px)";
    toast.textContent = message;
    toast.lastShown = Date.now(); // Track when toast was shown

    // Trigger slide down
    requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateX(-50%) translateY(0)";
    });

    // Hide after 2s
    toastTimeout = setTimeout(() => {
        hideToast();
    }, 2000);
}

function hideToast() {
    const toast = document.getElementById("customToast");
    if (toast) {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
        
        // Force hide after animation completes
        setTimeout(() => {
            if (toast && toast.style.opacity === "0") {
                toast.style.display = "none";
            }
        }, 500); // Match the CSS transition duration
    }
    if (toastTimeout) {
        clearTimeout(toastTimeout);
        toastTimeout = null;
    }
}

// Handle page visibility changes to fix toast issues when switching tabs
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // When user returns to tab, check if there's a visible toast and clean it up
        const toast = document.getElementById("customToast");
        if (toast && toast.style.opacity === "1") {
            // If toast is visible when returning to tab, hide it after a short delay
            setTimeout(() => {
                hideToast();
            }, 500);
        }
    }
});

// Additional fallback: Handle window focus events
window.addEventListener('focus', function() {
    const toast = document.getElementById("customToast");
    if (toast && toast.style.opacity === "1") {
        // If toast is visible when window gains focus, hide it
        setTimeout(() => {
            hideToast();
        }, 300);
    }
});

// Additional fallback: Handle page load events
window.addEventListener('load', function() {
    const toast = document.getElementById("customToast");
    if (toast && toast.style.opacity === "1") {
        // If toast is visible on page load, hide it
        setTimeout(() => {
            hideToast();
        }, 200);
    }
});

// Additional fallback: Handle DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    const toast = document.getElementById("customToast");
    if (toast && toast.style.opacity === "1") {
        // If toast is visible when DOM is ready, hide it
        setTimeout(() => {
            hideToast();
        }, 200);
    }
});

// Aggressive fallback: Periodic check to force-hide stuck toasts
let toastCheckInterval = null;

function startToastCleanup() {
    if (toastCheckInterval) return; // Already running
    
    toastCheckInterval = setInterval(() => {
        const toast = document.getElementById("customToast");
        if (toast && toast.style.opacity === "1") {
            // Check if toast has been visible for more than 5 seconds
            const now = Date.now();
            if (!toast.lastShown) {
                toast.lastShown = now;
            } else if (now - toast.lastShown > 5000) {
                // Force hide toast that's been visible too long
                hideToast();
            }
        }
    }, 1000); // Check every second
}

function stopToastCleanup() {
    if (toastCheckInterval) {
        clearInterval(toastCheckInterval);
        toastCheckInterval = null;
    }
}

// Start cleanup when page loads
document.addEventListener('DOMContentLoaded', startToastCleanup);
window.addEventListener('load', startToastCleanup);
