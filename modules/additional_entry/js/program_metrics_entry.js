/******************* COMBOBOXES CUSTOM FUNCTIONALITY *******************/
let programOptions = {};
let yearLevelOptions = {};
let collegeOptions = [];
let mockSubjectOptions = [];

// Initial fetch of data from PHP
fetch("/bridge/populate_filter.php?module=additional_entry")
  .then(res => res.json())
  .then(data => {
    collegeOptions = data.collegeOptions;
    programOptions = data.programOptions;
    yearLevelOptions = data.yearLevelOptions;
    mockSubjectOptions = data.mockSubjectOptions;
  });

let selectedMetric = "";

/********************************* METRICS COMBOBOXES HANDLING ***********************************/

function handleMetricChange() {
  const metric = document.getElementById("metricSelect").value;
  const subMetricGroup = document.getElementById("subMetricGroup");
  const subMetricLabel = document.getElementById("subMetricLabel");
  const subMetricSelect = document.getElementById("subMetricSelect");
  const textboxGroup = document.getElementById("textboxGroup");
  const defaultTextbox = document.getElementById("defaultTextbox");

  // Reset form and clear validation errors when metric changes
  resetAdditionalEntryForm();

  selectedMetric = metric;
  subMetricSelect.onchange = null;
  subMetricSelect.innerHTML = "";
  subMetricGroup.style.display = "none";
  textboxGroup.style.display = "none";
  defaultTextbox.style.display = "none";
    // Hide save button until ready
    toggleSaveButtonVisibility(false, 'Program Metrics - initial state');

  // Clear all dynamic elements and their labels
    ["displayProgram"].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            const label = el.previousElementSibling;
            if (label && label.tagName === 'LABEL') {
                label.remove();
            }
            el.remove();
        }
    });

    // Helper to create option with value and label
    const createOption = (value, label) => {
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = label;
        return opt;
    };

    // Always add "Select"
    subMetricSelect.appendChild(createOption("Select", "Select"));

    // Static metric options
    /* NO OTHER METRICS YET ASIDE FROM MOCK SUBJECTS
    const staticOptions = {
        "TypeOfRating": ["Clinical", "Internship", "Practicum", "Add"],
        "Recognition": ["Dean's Lister", "Awards", "Top Performer", "Add"]
    };*/ 

    if (metric === "MockSubjects") { // BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS BOARD SUBJECTS
        subMetricLabel.textContent = "Select College:";
        populateColleges();

        subMetricSelect.onchange = () => {
            const selectedCollege = subMetricSelect.value;

            // Always hide additional details and save button if subMetric is not selected
            if (!selectedCollege || selectedCollege === "" || selectedCollege === "Select") {
                textboxGroup.style.display = "none";
                defaultTextbox.style.display = "none";
                toggleSaveButtonVisibility(false, 'Program Metrics - form not ready');
            }

            // Clear previous program select and its label
            const oldProgramSelect = document.getElementById("displayProgram");
            if (oldProgramSelect) {
                const label = oldProgramSelect.previousElementSibling;
                if (label && label.tagName === 'LABEL') {
                    label.remove();
                }
                oldProgramSelect.remove();
            }

            // Clear previous subject select and its label
            const oldSubjectSelect = document.getElementById("displaySubjects");
            if (oldSubjectSelect) {
                const label = oldSubjectSelect.previousElementSibling;
                if (label && label.tagName === 'LABEL') {
                    label.remove();
                }
                oldSubjectSelect.remove();
            }

            // Only add program select if a valid college is selected
            if (selectedCollege && selectedCollege !== "Select") {
                // Create program select
                const programSelect = document.createElement("select");
                programSelect.id = "displayProgram";
                // Add program label
                const programLabel = document.createElement("label");
                programLabel.textContent = "Select Program:";
                subMetricGroup.appendChild(programLabel);
                programSelect.appendChild(createOption("", "Select"));
                subMetricGroup.appendChild(programSelect);
                // Now populate options
                populatePrograms(selectedCollege);

                // Always hide additional details and save button if program is not selected
                programSelect.onchange = () => {
                    if (!programSelect.value || programSelect.value === "" || programSelect.value === "Select") {
                        textboxGroup.style.display = "none";
                        defaultTextbox.style.display = "none";
                        toggleSaveButtonVisibility(false, 'Program Metrics - form not ready');
                    }

                    const selectedProgram = programSelect.value;
                    // Clear previous subject select and its label
                    const oldSubjectSelect = document.getElementById("displaySubjects");
                    if (oldSubjectSelect) {
                        const label = oldSubjectSelect.previousElementSibling;
                        if (label && label.tagName === 'LABEL') {
                            label.remove();
                        }
                        oldSubjectSelect.remove();
                    }

                    // Only add subject select if a valid program is selected
                    if (selectedProgram && selectedProgram !== "Select") {
                        // Create subject select
                        const subjectSelect = document.createElement("select");
                        subjectSelect.id = "displaySubjects";
                        // Add subject label
                        const subjectLabel = document.createElement("label");
                        subjectLabel.textContent = "Select Subject:";
                        subMetricGroup.appendChild(subjectLabel);
                        subMetricGroup.appendChild(subjectSelect);

                        populateMockSubjects(selectedProgram);
                        subjectSelect.appendChild(createOption("AddMockSubject", "Add Mock Subject"));
                        subMetricGroup.appendChild(subjectSelect);

                        // Show additional details and save button only when all dropdowns are selected
                        subjectSelect.onchange = () => {
                            const selectedSubject = subjectSelect.value;
                            const subjectName = (mockSubjectOptions[selectedProgram]?.find(s => s.id == selectedSubject) || {}).name || "";
                            const subjectInput = document.getElementById("metricTextbox");
                            if (
                                subMetricSelect.value && subMetricSelect.value !== "" && subMetricSelect.value !== "Select" &&
                                programSelect.value && programSelect.value !== "" && programSelect.value !== "Select" &&
                                subjectSelect.value && subjectSelect.value !== "" && subjectSelect.value !== "Select"
                            ) {
                                textboxGroup.style.display = "block";
                                defaultTextbox.style.display = "block";
                                toggleSaveButtonVisibility(true, 'Program Metrics - additional details ready');
                                // Set up change detection for the newly shown form
                                setTimeout(() => {
                                    // Store original values and reset save buttons after form is populated
                                    if (typeof storeOriginalValuesAndReset === 'function') {
                                        storeOriginalValuesAndReset();
                                    }
                                    // Set up change detection
                                    if (typeof setupFormChangeDetection === 'function') {
                                        setupFormChangeDetection();
                                    }
                                }, 100);
                                // Pre-fill subject name in textbox if editing
                                if (selectedSubject !== "AddMockSubject") {
                                    subjectInput.value = subjectName;
                                    // Check if this subject is hidden and set checkbox accordingly
                                    checkAndSetHideCheckbox('mock_subject', selectedSubject);
                                } else {
                                    subjectInput.value = "";
                                }
                                // Set up real-time validation for Mock Subjects
                                setupMockSubjectsValidation();
                            } else {
                                textboxGroup.style.display = "none";
                                defaultTextbox.style.display = "none";
                                toggleSaveButtonVisibility(false, 'Program Metrics - form not ready');
                                subjectInput.value = "";
                            }
                        };
                    }
                };
            }
        };

        subMetricGroup.style.display = "block";
    }
}


function saveDataEntry() {
    const metric = document.getElementById("metricSelect").value;
    const data = new FormData();

    if (metric === "MockSubjects") {
        data.append("metric", metric); // Metric is used in PHP (submit_academic_profile_entry.php) to identify which metric is being saved
        data.append("program_id", document.getElementById("displayProgram")?.value || "");
        data.append("subject_id", document.getElementById("displaySubjects")?.value || "");
        data.append("subject_name", document.getElementById("metricTextbox")?.value || "");
        // Handle hide functionality
        const hideCheckbox = document.getElementById("hideFieldCheckbox");
        if (hideCheckbox) {
            data.append("is_hidden", hideCheckbox.checked ? "1" : "0");
        }
    } //add more depende sa mga either dagdag or naur

    fetch("/bridge/modules/additional_entry/processes/submit_program_metrics_entry.php", {
        method: "POST",
        body: data
    })
    .then(res => res.text())
    .then(response => {
        // Show toast notification
        showToast("Data has been successfully saved!");
        
        // Keep the modal open and button loading until page reloads
        // Reload page after toast duration
        setTimeout(() => {
            window.location.reload();
        }, 1000); // Toast duration
    })
    .catch(error => {
        console.error('Error saving data:', error);
        showToast("Error saving data. Please try again.");
        
        // Reset button state on error
        const saveButton = document.getElementById("confirmSave");
        const cancelButton = document.getElementById("cancelSave");
        saveButton.classList.remove("loading");
        saveButton.disabled = false;
        cancelButton.disabled = false;
    });
}


// open modal
  function openValidationModal() {
    document.getElementById("validationModal").classList.add("show");
  }

  // close modal
  document.getElementById("cancelSave").addEventListener("click", () => {
    const modal = document.getElementById("validationModal");
    modal.classList.add("closing");
    
    // Remove the modal after animation completes
    setTimeout(() => {
      modal.classList.remove("show", "closing");
    }, 500); // Match the animation duration
  });

  // confirm save
  document.getElementById("confirmSave").addEventListener("click", () => {
    const loader = document.getElementById("loader");
    const saveButton = document.getElementById("confirmSave");
    const cancelButton = document.getElementById("cancelSave");
    
    // Show loader and disable buttons with smooth transitions
    saveButton.classList.add("loading");
    saveButton.disabled = true;
    cancelButton.disabled = true;

    // Call your save function here
    saveDataEntry();

    // Keep the modal open and button loading until page reloads
    // The modal will only close when the page reloads
  });



function populateColleges() {
    const collegeSelect = document.getElementById("subMetricSelect");
    collegeSelect.innerHTML = '<option value="" disabled selected>Select</option>';
    // Clear previous options except the first one
    subMetricSelect.options.length = 1;
    collegeOptions.forEach(college => {
        const option = document.createElement("option");
        option.text = college.name;
        option.value = college.id;
        subMetricSelect.add(option);
    });
}

function populatePrograms(collegeId) {
    const programSelect = document.getElementById("displayProgram");
    programSelect.innerHTML = '<option value="" disabled selected>Select</option>';
    // Clear previous options except the first one
    if (programSelect) {
        if (programOptions[collegeId] && programOptions[collegeId].length > 0) {
            programOptions[collegeId].forEach(program => {
                // Only add programs that have valid data
                if (program && program.id && program.name) {
                    const option = document.createElement("option");
                    option.text = program.name;
                    option.value = program.id;
                    programSelect.add(option);
                }
            });
        } else {
            // If no programs exist for this college, add a message
            const option = document.createElement("option");
            option.text = "No programs available";
            option.value = "";
            option.disabled = true;
            programSelect.add(option);
        }
    }
}

function populateMockSubjects(programId) {
  const subjectSelect = document.getElementById("displaySubjects");
  subjectSelect.innerHTML = '<option value="" disabled selected>Select</option>';

  if (subjectSelect){
    if (mockSubjectOptions[programId]) {
        mockSubjectOptions[programId].forEach(subject => {
          const option = document.createElement("option");
          option.text = subject.name;
          option.value = subject.id;
          subjectSelect.add(option);
      });
    }
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

/******************* REAL-TIME VALIDATION FUNCTIONS *******************/

// Global validation function for duplicate data checking
async function validateDuplicateData(metric, data) {
    try {
        const formData = new FormData();
        formData.append('metric', metric);
        
        // Add data based on metric type
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        const response = await fetch('/bridge/modules/additional_entry/processes/validate_duplicate_data.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Validation error:', error);
        return { isValid: false, message: 'Validation error occurred' };
    }
}

// Create error message container
function createErrorContainer(containerId) {
    let errorContainer = document.getElementById(containerId);
    if (!errorContainer) {
        errorContainer = document.createElement("div");
        errorContainer.id = containerId;
        errorContainer.style.cssText = `
            margin-top: 15px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            color: #856404;
            font-size: 14px;
            display: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        document.getElementById("textboxGroup").appendChild(errorContainer);
    }
    return errorContainer;
}

// Show validation error
function showValidationError(containerId, message) {
    const errorContainer = createErrorContainer(containerId);
    errorContainer.innerHTML = message;
    errorContainer.style.display = "block";
    
    // Disable save button when there are validation errors
    const saveButtons = document.querySelectorAll('.entry-buttons button');
    saveButtons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
    });
}

// Hide validation error
function hideValidationError(containerId) {
    const errorContainer = document.getElementById(containerId);
    if (errorContainer) {
        errorContainer.style.display = "none";
    }
    
    // Let the change detection system handle save button state
}

// Mock Subjects validation
function setupMockSubjectsValidation() {
    const subjectInput = document.getElementById("metricTextbox");
    const programSelect = document.getElementById("displayProgram");
    const subjectSelect = document.getElementById("displaySubjects");
    
    if (!subjectInput || !programSelect || !subjectSelect) return;

    const validateMockSubjects = async () => {
        const subjectName = subjectInput.value.trim();
        const programId = programSelect.value;
        const subjectId = subjectSelect.value;
        
        if (subjectName && programId && subjectId) {
            const data = {
                program_id: programId,
                subject_name: subjectName,
                subject_id: subjectId
            };
            
            const result = await validateDuplicateData('MockSubjects', data);
            if (!result.isValid) {
                showValidationError('mockSubjectsError', result.message);
            } else {
                hideValidationError('mockSubjectsError');
            }
        } else {
            hideValidationError('mockSubjectsError');
        }
    };

    // Add event listeners
    subjectInput.addEventListener('input', validateMockSubjects);
    programSelect.addEventListener('change', validateMockSubjects);
    subjectSelect.addEventListener('change', validateMockSubjects);
}

/******************* HIDE CHECKBOX STATE MANAGEMENT *******************/

// Function to check if data is hidden and set checkbox accordingly
async function checkAndSetHideCheckbox(optionType, optionId) {
    try {
        const formData = new FormData();
        formData.append('option_type', optionType);
        formData.append('option_id', optionId);
        
        const response = await fetch('/bridge/modules/additional_entry/processes/check_hide_status.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        // Set checkbox state based on is_active status
        const hideCheckbox = document.getElementById("hideFieldCheckbox");
        if (hideCheckbox) {
            // If is_active is 0, data is hidden, so checkbox should be checked
            // If is_active is 1, data is visible, so checkbox should be unchecked
            hideCheckbox.checked = result.is_active === 0;
        }
    } catch (error) {
        console.error('Error checking hide status:', error);
    }
}

/******************* FORM RESET FUNCTION *******************/

// Reset additional entry form and clear all validation errors
function resetAdditionalEntryForm() {
    // Clear all validation error containers
    const errorContainerIds = [
        'mockSubjectsError'
    ];
    
    errorContainerIds.forEach(containerId => {
        hideValidationError(containerId);
    });
    
    // Clear any input values
    const metricTextbox = document.getElementById("metricTextbox");
    if (metricTextbox) {
        metricTextbox.value = "";
    }
    
    // Let the change detection system handle save button state
    
    // Clear any hide checkbox
    const hideCheckbox = document.getElementById("hideFieldCheckbox");
    if (hideCheckbox) {
        hideCheckbox.checked = false;
    }
}