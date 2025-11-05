/******************* COLLEGE SELECTION FUNCTIONALITY *******************/
let collegeForProgramsOptions = [];
let originalLogo = null; // Store original logo src
let originalColor = "#5c297c"; // Store original color
let originalEmail = ""; // Store original email
let toastTimeout = null;

// Toast function (similar to settings)
function showToast(message) {
    let toast = document.getElementById("customToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "customToast";
        toast.style.position = "fixed";
        toast.style.top = "20px";
        toast.style.left = "50%";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
        toast.style.background = "#60357a";
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

    if (toastTimeout) {
        clearTimeout(toastTimeout);
    }

    toast.style.display = "block";
    toast.style.opacity = "0";
    toast.style.transform = "translateX(-50%) translateY(-100px)";
    toast.textContent = message;

    requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateX(-50%) translateY(0)";
    });

    toastTimeout = setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(-50%) translateY(-100px)";
        setTimeout(() => {
            if (toast && toast.style.opacity === "0") {
                toast.style.display = "none";
            }
        }, 500);
    }, 2000);
}

// Initial fetch of data from PHP
fetch("/bridge/populate_filter.php?module=additional_entry")
  .then(res => res.json())
  .then(data => {
    collegeForProgramsOptions = data.collegeForProgramsOptions;
    // Populate colleges after data is loaded
    populateColleges();
  })
  .catch(error => {
    console.error("Error fetching college data:", error);
  });

/******************* POPULATE COLLEGE DROPDOWN *******************/
function populateColleges() {
    const collegeSelect = document.getElementById("metricSelect");
    if (!collegeSelect) {
        console.error("College select element not found");
        return;
    }

    // Clear existing options
    collegeSelect.innerHTML = '<option value="" disabled selected>Select</option>';

    // Check user session
    const session = window.userSession || {};
    const level = parseInt(session.level, 10);
    const userCollege = session.college;

    // For deans (level 1) and administrative assistants (level 2), preselect their college
    if ((level === 1 || level === 2) && userCollege) {
        // Clear the "Select" option first
        collegeSelect.innerHTML = '';
        const deanCollege = collegeForProgramsOptions.find(college => college.id == userCollege);
        if (deanCollege) {
            // Show only their college, just like in academic_profile_entry and program_metrics_entry
            const option = document.createElement("option");
            option.value = deanCollege.id;
            option.textContent = deanCollege.name;
            collegeSelect.appendChild(option);
            collegeSelect.value = deanCollege.id;
            collegeSelect.disabled = true;
            collegeSelect.style.backgroundColor = '#f8f9fa';
            collegeSelect.style.cursor = 'not-allowed';
            
            // Trigger change event to load college data and show inputs
            setTimeout(() => {
                collegeSelect.dispatchEvent(new Event('change'));
            }, 100);
        }
    } else if (level === 0) {
        // For admins (level 0), show all colleges
        collegeForProgramsOptions.forEach(college => {
            const option = document.createElement("option");
            option.value = college.id;
            option.textContent = college.name;
            collegeSelect.appendChild(option);
        });
    }

    // Set up change event listener
    collegeSelect.addEventListener('change', handleCollegeChange);
}

/******************* HANDLE COLLEGE SELECTION CHANGE *******************/
function handleCollegeChange() {
    const collegeSelect = document.getElementById("metricSelect");
    const entryButtons = document.querySelector(".entry-buttons");
    const formatInputs = document.getElementById("documentFormatInputs");
    
    if (!collegeSelect || !collegeSelect.value || collegeSelect.value === "") {
        if (entryButtons) {
            entryButtons.style.display = "none";
        }
        if (formatInputs) {
            formatInputs.style.display = "none";
        }
        return;
    }

    const collegeId = collegeSelect.value;
    
    // Show inputs and save button when college is selected
    if (formatInputs) {
        formatInputs.style.display = "block";
    }
    if (entryButtons) {
        entryButtons.style.display = "block";
    }

    // Load existing college data
    loadCollegeData(collegeId);
    
    // Initially disable save button (will enable when changes are detected)
    setTimeout(() => {
        checkSaveButtonState();
    }, 300);
}

/******************* LOAD EXISTING COLLEGE DATA *******************/
function loadCollegeData(collegeId) {
    // Fetch existing college data
    fetch(`/bridge/modules/additional_entry/processes/get_college_format.php?college_id=${collegeId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const logoPreview = document.getElementById("logoPreview");
                const removeLogoBtn = document.getElementById("removeLogoBtn");
                const colorPicker = document.getElementById("colorPicker");
                const colorHexInput = document.getElementById("colorHexInput");
                const emailInput = document.getElementById("collegeEmail");
                
                // Load logo if exists - now using logo_path instead of base64
                if (data.logo_path && logoPreview) {
                    logoPreview.src = data.logo_path;
                    originalLogo = data.logo_path;
                    // Don't show remove button initially - only show if user modifies the logo
                    if (removeLogoBtn) {
                        removeLogoBtn.style.display = "none";
                    }
                } else if (logoPreview) {
                    logoPreview.src = "assets/img/blank.png";
                    originalLogo = "assets/img/blank.png";
                    if (removeLogoBtn) {
                        removeLogoBtn.style.display = "none";
                    }
                }
                
                // Load color
                if (data.color) {
                    originalColor = data.color;
                    if (colorPicker) colorPicker.value = data.color;
                    if (colorHexInput) colorHexInput.value = data.color.toUpperCase();
                } else {
                    originalColor = "#5c297c";
                    if (colorPicker) colorPicker.value = "#5c297c";
                    if (colorHexInput) colorHexInput.value = "#5C297C";
                }
                
                // Load email
                if (data.email) {
                    originalEmail = data.email.trim();
                    if (emailInput) emailInput.value = data.email;
                } else {
                    originalEmail = "";
                    if (emailInput) emailInput.value = "";
                }
                
                // Reset save button state after data is loaded
                setTimeout(() => {
                    checkSaveButtonState();
                }, 100);
            }
        })
        .catch(error => {
            console.error("Error loading college data:", error);
        });
}

/******************* CHECK SAVE BUTTON STATE *******************/
function checkSaveButtonState() {
    const saveButton = document.querySelector(".entry-buttons button");
    if (!saveButton) return;

    const logoPreview = document.getElementById("logoPreview");
    const logoUpload = document.getElementById("logoUpload");
    const colorHexInput = document.getElementById("colorHexInput");
    const collegeEmail = document.getElementById("collegeEmail");

    // Always get fresh values from DOM to ensure accuracy
    const hexValue = colorHexInput ? colorHexInput.value.trim() : "";
    const currentEmail = collegeEmail ? collegeEmail.value.trim() : "";
    
    // Hex code is invalid if: empty, only "#", or incomplete (less than 7 chars)
    const isEmptyOrInvalidHex = !hexValue || hexValue === "#" || hexValue.length < 7;

    // Check for changes - must be done carefully to detect actual modifications
    let logoChanged = false;
    
    // Check if a new file was uploaded (highest priority check)
    if (logoUpload && logoUpload.files.length > 0) {
        // New file selected - this means user uploaded a new logo
        logoChanged = true;
    } else if (logoPreview) {
        // Check if logo src changed from original
        const currentSrc = logoPreview.src || "";
        const originalSrc = originalLogo || "";
        
        // Handle different URL types:
        // 1. Data URLs (from FileReader) vs file paths - always different
        // 2. Both are file paths - compare normalized paths
        // 3. Both are blank.png - same
        const isDataUrl = (src) => src.startsWith("data:image/");
        const isBlank = (src) => src.includes("blank.png");
        
        const currentIsDataUrl = isDataUrl(currentSrc);
        const originalIsDataUrl = isDataUrl(originalSrc);
        const currentIsBlank = isBlank(currentSrc);
        const originalIsBlank = isBlank(originalSrc);
        
        // If one is data URL and other is not, they're different (logo changed)
        if (currentIsDataUrl !== originalIsDataUrl) {
            logoChanged = true;
        }
        // If both are blank, they're the same (no change)
        else if (currentIsBlank && originalIsBlank) {
            logoChanged = false;
        }
        // If both are file paths (not data URLs), compare normalized paths
        else if (!currentIsDataUrl && !originalIsDataUrl) {
            // Normalize URLs for comparison - extract just the filename/path part
            const normalizeSrc = (src) => {
                if (!src) return "";
                // Extract filename from URL or path
                const urlMatch = src.match(/([^\/]+\.(png|jpg|jpeg|gif|webp))|blank\.png/i);
                return urlMatch ? urlMatch[0].toLowerCase() : src;
            };
            
            const currentNormalized = normalizeSrc(currentSrc);
            const originalNormalized = normalizeSrc(originalSrc);
            
            // Logo changed if normalized paths are different
            if (currentNormalized !== originalNormalized) {
                logoChanged = true;
            }
        }
        // If both are data URLs, compare directly (they should be different if changed)
        else if (currentIsDataUrl && originalIsDataUrl) {
            if (currentSrc !== originalSrc) {
                logoChanged = true;
            }
        }
    }

    // Check if color changed from original (normalize both to uppercase for comparison)
    const currentColor = hexValue.toUpperCase();
    const normalizedOriginalColor = (originalColor || "#5c297c").trim().toUpperCase();
    
    // Color changed logic:
    // 1. Must be a complete valid hex code (7 chars) to be considered changed
    // 2. Must be different from original
    // 3. If reverted to original, colorChanged = false
    // 4. If empty or just "#", colorChanged = false (will be handled below)
    let colorChanged = false;
    if (!isEmptyOrInvalidHex && currentColor.length === 7) {
        colorChanged = currentColor !== normalizedOriginalColor;
    }

    // Check if email changed from original
    const normalizedOriginalEmail = (originalEmail || "").trim();
    
    // Email changed logic:
    // 1. Must not be empty
    // 2. Must be different from original
    // 3. If reverted to original or empty, emailChanged = false
    let emailChanged = false;
    if (currentEmail !== "" && currentEmail !== normalizedOriginalEmail) {
        emailChanged = true;
    }

    // Determine if save should be enabled
    // ENABLE CONDITIONS (ALL must be true):
    // 1. User has made changes (logoChanged OR colorChanged OR emailChanged)
    // 2. Hex code is valid (not empty, not just "#", complete 7 chars)
    // 3. College email is not empty
    const hasChanges = logoChanged || colorChanged || emailChanged;
    const hasValidHex = !isEmptyOrInvalidHex && hexValue.length === 7;
    const hasValidEmail = currentEmail !== "";
    
    // Button should be enabled ONLY if all conditions are met:
    // - There are changes AND hex is valid AND email is not empty
    const shouldEnable = hasChanges && hasValidHex && hasValidEmail;
    
    // DISABLE CONDITIONS (if ANY is true):
    // 1. No changes made (!hasChanges)
    // 2. Hex code is invalid (empty, only "#", or incomplete)
    // 3. College email textbox is empty
    const shouldDisable = !shouldEnable;

    saveButton.disabled = shouldDisable;
    saveButton.style.opacity = shouldDisable ? '0.5' : '1';
    saveButton.style.cursor = shouldDisable ? 'not-allowed' : 'pointer';
}

/******************* INITIALIZE DOCUMENT FORMAT INPUTS *******************/
function initializeDocumentFormatInputs() {
    // Logo upload handler
    const logoPreviewWrapper = document.querySelector(".logo-preview-wrapper");
    const logoUpload = document.getElementById("logoUpload");
    const logoPreview = document.getElementById("logoPreview");
    const removeLogoBtn = document.getElementById("removeLogoBtn");

    if (logoPreviewWrapper && logoUpload) {
        // Click on preview wrapper to trigger file input
        logoPreviewWrapper.addEventListener("click", () => {
            logoUpload.click();
        });

        // Handle file selection with validation (like settings)
        logoUpload.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                // Validate file type - only accept JPG and PNG
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    showToast("Please select a JPG or PNG image file.");
                    logoUpload.value = "";
                    return;
                }

                // Validate with server (like settings)
                const formData = new FormData();
                formData.append("logo", file);
                formData.append("validate_only", "1");

                fetch("/bridge/modules/additional_entry/processes/submit_document_format.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Preview image
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            if (logoPreview) {
                                logoPreview.src = event.target.result;
                            }
                            // Show remove button only when logo is modified (new file uploaded)
                            if (removeLogoBtn) {
                                removeLogoBtn.style.display = "inline-flex";
                            }
                            // File upload is confirmed - enable save button
                            // Use setTimeout to ensure file input is updated and logo preview is set
                            setTimeout(() => {
                                checkSaveButtonState();
                            }, 150);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        showToast(data.message || "Error: Image size must be less than 2MB.");
                        logoUpload.value = "";
                        if (logoPreview) {
                            logoPreview.src = originalLogo || "assets/img/blank.png";
                        }
                        if (removeLogoBtn) {
                            removeLogoBtn.style.display = originalLogo && originalLogo !== "assets/img/blank.png" ? "inline-flex" : "none";
                        }
                        checkSaveButtonState();
                    }
                })
                .catch(error => {
                    console.error("Validation error:", error);
                    showToast("An error occurred while validating the image.");
                    logoUpload.value = "";
                    if (logoPreview) {
                        logoPreview.src = originalLogo || "assets/img/blank.png";
                    }
                    checkSaveButtonState();
                });
            }
        });
    }

    // Remove logo button
    if (removeLogoBtn) {
        removeLogoBtn.addEventListener("click", () => {
            if (logoPreview) {
                logoPreview.src = originalLogo || "assets/img/blank.png";
            }
            if (logoUpload) {
                logoUpload.value = "";
            }
            
            // Hide remove button if logo is reverted to original
            // Use the same normalization logic as checkSaveButtonState
            setTimeout(() => {
                const currentSrc = logoPreview ? logoPreview.src || "" : "";
                const originalSrc = originalLogo || "";
                
                // Handle different URL types for comparison
                const isDataUrl = (src) => src.startsWith("data:image/");
                const isBlank = (src) => src.includes("blank.png");
                
                const currentIsDataUrl = isDataUrl(currentSrc);
                const originalIsDataUrl = isDataUrl(originalSrc);
                const currentIsBlank = isBlank(currentSrc);
                const originalIsBlank = isBlank(originalSrc);
                
                let isRevertedToOriginal = false;
                
                // If both are blank, they're the same
                if (currentIsBlank && originalIsBlank) {
                    isRevertedToOriginal = true;
                }
                // If both are file paths (not data URLs), compare normalized paths
                else if (!currentIsDataUrl && !originalIsDataUrl) {
                    // Normalize URLs for comparison - extract just the filename/path part
                    const normalizeSrc = (src) => {
                        if (!src) return "";
                        // Extract filename from URL or path
                        const urlMatch = src.match(/([^\/]+\.(png|jpg|jpeg|gif|webp))|blank\.png/i);
                        return urlMatch ? urlMatch[0].toLowerCase() : src;
                    };
                    
                    const currentNormalized = normalizeSrc(currentSrc);
                    const originalNormalized = normalizeSrc(originalSrc);
                    
                    isRevertedToOriginal = currentNormalized === originalNormalized;
                }
                // If both are data URLs, compare directly
                else if (currentIsDataUrl && originalIsDataUrl) {
                    isRevertedToOriginal = currentSrc === originalSrc;
                }
                // If one is data URL and other is not, they're different (not reverted)
                else {
                    isRevertedToOriginal = false;
                }
                
                if (isRevertedToOriginal) {
                    removeLogoBtn.style.display = "none";
                }
            }, 50);
            
            checkSaveButtonState();
        });
    }

    // Color picker and hex input synchronization
    const colorPicker = document.getElementById("colorPicker");
    const colorHexInput = document.getElementById("colorHexInput");

    // Color picker changes hex input
    if (colorPicker) {
        colorPicker.addEventListener("input", (e) => {
            const color = e.target.value;
            if (colorHexInput) {
                colorHexInput.value = color.toUpperCase();
            }
            // Immediately check save button state after color picker change
            setTimeout(() => {
                checkSaveButtonState();
            }, 0);
        });
    }

    // Hex input changes color picker
    if (colorHexInput) {
        // Prevent removing # from start
        colorHexInput.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && colorHexInput.selectionStart === 1 && colorHexInput.value.startsWith("#")) {
                e.preventDefault();
            }
            if (e.key === "Delete" && colorHexInput.selectionStart === 0 && colorHexInput.value.startsWith("#")) {
                e.preventDefault();
            }
        });

        colorHexInput.addEventListener("input", (e) => {
            let value = e.target.value;
            
            // Ensure # at start
            if (!value.startsWith("#")) {
                value = "#" + value.replace(/^#+/, "");
            }
            
            // Limit to 7 characters (# + 6 hex digits)
            if (value.length > 7) {
                value = value.substring(0, 7);
            }
            
            // Validate hex color pattern (allow partial input while typing)
            const hexPattern = /^#[0-9A-Fa-f]{0,6}$/;
            if (hexPattern.test(value)) {
                if (colorPicker && value.length === 7) {
                    colorPicker.value = value;
                }
                colorHexInput.value = value.toUpperCase();
            } else {
                // Revert to last valid value
                const lastValid = colorHexInput.getAttribute("data-last-valid") || "#";
                colorHexInput.value = lastValid;
                value = lastValid;
            }
            
            // Store last valid value
            if (hexPattern.test(value)) {
                colorHexInput.setAttribute("data-last-valid", value);
            }
            
            // Immediately check save button state - especially important when value is just "#"
            setTimeout(() => {
                checkSaveButtonState();
            }, 0);
        });
        
        // Also listen to keyup to catch when backspace/delete leaves only #
        colorHexInput.addEventListener("keyup", (e) => {
            // Immediately check state after keyup, especially for deletion
            setTimeout(() => {
                checkSaveButtonState();
            }, 0);
        });
        
        // Also listen to blur to ensure state is checked when focus leaves
        colorHexInput.addEventListener("blur", () => {
            setTimeout(() => {
                checkSaveButtonState();
            }, 0);
        });
    }

    // Email input - use multiple events to catch all modifications
    const collegeEmail = document.getElementById("collegeEmail");
    if (collegeEmail) {
        // Use input event for real-time detection of character changes
        collegeEmail.addEventListener("input", () => {
            setTimeout(() => {
                checkSaveButtonState();
            }, 0);
        });
        // Also use change event as backup
        collegeEmail.addEventListener("change", () => {
            setTimeout(() => {
                checkSaveButtonState();
            }, 0);
        });
        // Use keyup for additional coverage when typing/deleting
        collegeEmail.addEventListener("keyup", () => {
            setTimeout(() => {
                checkSaveButtonState();
            }, 0);
        });
        // Use blur to ensure state is checked when focus leaves
        collegeEmail.addEventListener("blur", () => {
            setTimeout(() => {
                checkSaveButtonState();
            }, 0);
        });
    }
}

/******************* OPEN VALIDATION MODAL *******************/
function openValidationModal() {
    const modal = document.getElementById("validationModal");
    if (modal) {
        modal.classList.add("show");
    }
}

// Close modal handlers
document.addEventListener('DOMContentLoaded', () => {
    // Cancel button
    const cancelBtn = document.getElementById("cancelSave");
    if (cancelBtn) {
        cancelBtn.addEventListener("click", () => {
            const modal = document.getElementById("validationModal");
            if (modal) {
                modal.classList.remove("show");
            }
        });
    }

    // Click outside modal to close
    const modal = document.getElementById("validationModal");
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.remove("show");
            }
        });
    }

    // Confirm save button
    const confirmBtn = document.getElementById("confirmSave");
    if (confirmBtn) {
        confirmBtn.addEventListener("click", () => {
            const loader = document.getElementById("loader");
            const saveButton = document.getElementById("confirmSave");
            const cancelButton = document.getElementById("cancelSave");
            
            // Show loader and disable buttons
            if (saveButton) {
                saveButton.classList.add("loading");
                saveButton.disabled = true;
            }
            if (cancelButton) {
                cancelButton.disabled = true;
            }

            // Call save function
            saveDocumentFormat();
        });
    }

    // If data is already loaded, populate immediately
    if (collegeForProgramsOptions.length > 0) {
        populateColleges();
    }
    
    // Initialize document format inputs
    initializeDocumentFormatInputs();
});

/******************* SAVE DOCUMENT FORMAT *******************/
function saveDocumentFormat() {
    const collegeSelect = document.getElementById("metricSelect");
    const logoUpload = document.getElementById("logoUpload");
    const colorPicker = document.getElementById("colorPicker");
    const collegeEmail = document.getElementById("collegeEmail");

    if (!collegeSelect || !collegeSelect.value) {
        alert("Please select a college.");
        return;
    }

    const collegeId = collegeSelect.value;
    const color = colorPicker ? colorPicker.value : "#5c297c";
    const email = collegeEmail ? collegeEmail.value.trim() : "";

    // Create FormData
    const formData = new FormData();
    formData.append("college_id", collegeId);
    formData.append("color", color);
    formData.append("email", email);

    // Add logo if a new file was selected
    if (logoUpload && logoUpload.files.length > 0) {
        formData.append("logo", logoUpload.files[0]);
    } else {
        // Check if logo should be removed (logo is set to default)
        const logoPreview = document.getElementById("logoPreview");
        if (logoPreview && logoPreview.src.includes("blank.png")) {
            formData.append("remove_logo", "1");
        }
    }

    // Send to server
    fetch("/bridge/modules/additional_entry/processes/submit_document_format.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Document format has been successfully saved!");
            
            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast("Error: " + (data.message || "Failed to save document format."));
            
            // Reset button state on error
            const saveButton = document.getElementById("confirmSave");
            const cancelButton = document.getElementById("cancelSave");
            if (saveButton) {
                saveButton.classList.remove("loading");
                saveButton.disabled = false;
            }
            if (cancelButton) {
                cancelButton.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error saving document format:', error);
        showToast("Error saving document format. Please try again.");
        
        // Reset button state on error
        const saveButton = document.getElementById("confirmSave");
        const cancelButton = document.getElementById("cancelSave");
        if (saveButton) {
            saveButton.classList.remove("loading");
            saveButton.disabled = false;
        }
        if (cancelButton) {
            cancelButton.disabled = false;
        }
    });
}
