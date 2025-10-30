/******************* COMBOBOXES CUSTOM FUNCTIONALITY *******************/
let programOptions = {};
let yearLevelOptions = {};
let collegeOptions = [];
let arrangementOptions = [];
let languageOptions = [];
let collegeForProgramsOptions = [];
let socioeconomicOptions = [];

// Initial fetch of data from PHP
fetch("/bridge/populate_filter.php?module=additional_entry")
  .then(res => res.json())
  .then(data => {
    collegeOptions = data.collegeOptions;
    programOptions = data.programOptions;
    yearLevelOptions = data.yearLevelOptions;
    arrangementOptions = data.arrangementOptions;
	languageOptions = data.languageOptions;
    collegeForProgramsOptions = data.collegeForProgramsOptions;
    socioeconomicOptions = data.socioeconomicOptions;
  });

let selectedMetric = "";

/******************* MAIN HANDLER FOR METRIC SELECTION *******************/
function handleMetricChange() {
    const metric = document.getElementById("metricSelect").value;
    const subMetricGroup = document.getElementById("subMetricGroup");
    const subMetricLabel = document.getElementById("subMetricLabel");
    const subMetricSelect = document.getElementById("subMetricSelect");
    const textboxGroup = document.getElementById("textboxGroup");
    const socioeconomicStatusInputs = document.getElementById("socioeconomicStatusInputs");
    const defaultTextbox = document.getElementById("defaultTextbox");

    // Reset form and clear validation errors when metric changes
    resetAdditionalEntryForm();

    selectedMetric = metric;
    subMetricSelect.onchange = null;
    subMetricSelect.innerHTML = "";  
    subMetricGroup.style.display = "none";
    textboxGroup.style.display = "none";
    socioeconomicStatusInputs.style.display = "none";
    defaultTextbox.style.display = "none";

    // Clear all dynamic elements and their labels
    ["displayProgram", "displayYearLevel", "displaySection", "displayCollegeForProgram"].forEach(id => {
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
    const staticOptions = {
        "CurrentLivingArrangement": ["Home", "Dorm", "Boarding House", "Add"],
        "LanguageSpoken": ["Filipino", "English", "Regional", "Others", "Add"]
    };

    if (metric === "SocioeconomicStatus") {
        textboxGroup.style.display = "block";
        socioeconomicStatusInputs.style.display = "block";
        document.getElementById("richMin").value = socioeconomicOptions.find(s => s.status === "RICH")?.minimum || "";
        document.getElementById("highIncomeMin").value = socioeconomicOptions.find(s => s.status === "HIGH INCOME")?.minimum || "";
        document.getElementById("highIncomeMax").value = socioeconomicOptions.find(s => s.status === "HIGH INCOME")?.maximum || "";
        document.getElementById("upperMiddleMin").value = socioeconomicOptions.find(s => s.status === "UPPER MIDDLE")?.minimum || "";
        document.getElementById("upperMiddleMax").value = socioeconomicOptions.find(s => s.status === "UPPER MIDDLE")?.maximum || "";
        document.getElementById("middleMin").value = socioeconomicOptions.find(s => s.status === "MIDDLE CLASS")?.minimum || "";
        document.getElementById("middleMax").value = socioeconomicOptions.find(s => s.status === "MIDDLE CLASS")?.maximum || "";
        document.getElementById("lowerMiddleMin").value = socioeconomicOptions.find(s => s.status === "LOWER MIDDLE")?.minimum || "";
        document.getElementById("lowerMiddleMax").value = socioeconomicOptions.find(s => s.status === "LOWER MIDDLE")?.maximum || "";
        document.getElementById("lowIncomeMin").value = socioeconomicOptions.find(s => s.status === "LOW INCOME")?.minimum || "";
        document.getElementById("lowIncomeMax").value = socioeconomicOptions.find(s => s.status === "LOW INCOME")?.maximum || "";
        document.getElementById("poorMax").value = socioeconomicOptions.find(s => s.status === "POOR")?.maximum || "";
        
        // Set up live validation for socioeconomic status
        setupSocioeconomicValidation();
        
        // Use unified save button visibility logic
        checkSaveButtonVisibility(metric);
        
        // For socioeconomic status, set up change detection
        setTimeout(() => {
            // Store original values and reset save buttons after form is populated
            if (typeof storeOriginalValuesAndReset === 'function') {
                storeOriginalValuesAndReset();
            }
            // Set up change detection for the socioeconomic status form
            if (typeof setupFormChangeDetection === 'function') {
                setupFormChangeDetection();
            }
        }, 300);
    } 
    else if (metric === "College") {
        subMetricLabel.textContent = "Select College:";
        populateColleges();
        subMetricSelect.appendChild(createOption("AddCollege","Add College"));
        // Hide additional details and save button initially
        textboxGroup.style.display = "none";
        defaultTextbox.style.display = "none";
        toggleSaveButtonVisibility(false, 'College metric - initial state');
        subMetricSelect.onchange = () => {
            const selectedCollegeId = subMetricSelect.value;
            const collegeName = (collegeOptions.find(c => c.id == selectedCollegeId) || {}).name || "";
            const collegeInput = document.getElementById("metricTextbox");
            if (!selectedCollegeId || selectedCollegeId === "" || selectedCollegeId === "Select") {
                textboxGroup.style.display = "none";
                defaultTextbox.style.display = "none";
                toggleSaveButtonVisibility(false, 'College not selected');
                collegeInput.value = "";
            } else {
                textboxGroup.style.display = "block";
                defaultTextbox.style.display = "block";
                toggleSaveButtonVisibility(true, 'College selected - additional details shown');
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
                // Pre-fill college name in textbox if editing
                if (selectedCollegeId !== "AddCollege") {
                    collegeInput.value = collegeName;
                    // Check if this college is hidden and set checkbox accordingly
                    checkAndSetHideCheckbox('college', selectedCollegeId);
                } else {
                    collegeInput.value = "";
                }
                // Set up real-time validation for College
                setupCollegeValidation();
            }
        };
        subMetricGroup.style.display = "block";
    }
    else if (metric === "Program") {
        // Step 1: Show Program dropdown first
        subMetricLabel.textContent = "Select Program:";
        // Clear and repopulate subMetricSelect      
        subMetricSelect.appendChild(createOption("", "Select"));
        subMetricSelect.innerHTML = '<option value="" disabled selected>Select</option>';
        // Add all programs as options (flattened)
        let allPrograms = [];
        Object.values(programOptions).forEach(arr => {
            arr.forEach(prog => allPrograms.push(prog));
        });
        allPrograms.forEach(prog => {
            subMetricSelect.appendChild(createOption(prog.id, prog.name));
        });
        subMetricSelect.appendChild(createOption("AddProgram", "Add Program"));

        // Remove any previous college fields
        ["displayCollegeForProgram", "programNameLabel"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.remove();
        });

        // Hide additional details and save button initially
        textboxGroup.style.display = "none";
        defaultTextbox.style.display = "none";
        toggleSaveButtonVisibility(false, 'Program metric - initial state');

        subMetricSelect.onchange = () => {
            // Remove any previous college fields
            ["displayCollegeForProgram", "programNameLabel"].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.remove();
            });

            // Hide everything by default
            textboxGroup.style.display = "none";
            defaultTextbox.style.display = "none";
            toggleSaveButtonVisibility(false, 'Program not selected');

            const selectedProgramId = subMetricSelect.value;
            if (!selectedProgramId || selectedProgramId === "" || selectedProgramId === "Select") {
                return;
            }

            // Step 2: Show College dropdown and use defaultTextbox for program name
            // Create college dropdown
            const collegeSelect = document.createElement("select");
            collegeSelect.id = "displayCollegeForProgram";
            const collegeLabel = document.createElement("label");
            collegeLabel.id = "programNameLabel";
            collegeLabel.textContent = "Select College:";
            subMetricGroup.appendChild(collegeLabel);
            
            // Check if user is a dean (level 1) or assistant (level 2) and pre-fill their college
            const session = window.userSession || {};
            const level = parseInt(session.level, 10);
            const userCollege = session.college;
            
            if ((level === 1 || level === 2) && userCollege) {
                // For deans and assistants, pre-fill and disable the college dropdown
                collegeSelect.innerHTML = '';
                const deanCollege = collegeForProgramsOptions.find(college => college.id == userCollege);
                if (deanCollege) {
                    collegeSelect.appendChild(createOption(deanCollege.id, deanCollege.name));
                    collegeSelect.value = deanCollege.id;
                    collegeSelect.disabled = true;
                    collegeSelect.style.backgroundColor = '#f8f9fa';
                    collegeSelect.style.cursor = 'not-allowed';
                }
            } else {
                // For admins, show all colleges
                collegeSelect.appendChild(createOption("", "Select"));
                collegeSelect.innerHTML = '<option value="" disabled selected>Select</option>';
                collegeForProgramsOptions.forEach(college => {
                    collegeSelect.appendChild(createOption(college.id, college.name));
                });
            }
            
            subMetricGroup.appendChild(collegeSelect);

            // Use the existing additional details textbox for program name
            textboxGroup.style.display = "block";
            defaultTextbox.style.display = "block";
            const programNameInput = document.getElementById("metricTextbox");
            programNameInput.placeholder = "Enter program name";

            // If editing, pre-fill college and program name
            if (selectedProgramId !== "AddProgram") {
                // Find the selected program's college
                let foundCollegeId = null;
                let foundProgramName = "";
                for (const [collegeId, progs] of Object.entries(programOptions)) {
                    const match = progs.find(p => p.id == selectedProgramId);
                    if (match) {
                        foundCollegeId = collegeId;
                        foundProgramName = match.name;
                        break;
                    }
                }
                if (foundCollegeId) collegeSelect.value = foundCollegeId;
                programNameInput.value = foundProgramName;
                // Check if this program is hidden and set checkbox accordingly
                checkAndSetHideCheckbox('program', selectedProgramId);
            } else {
                programNameInput.value = "";
            }

            // Show save button only when both are filled
            function checkShow() {
                // For deans, college is pre-filled, so only check program name
                // For admins, check both college and program name
                const hasCollege = collegeSelect.value && collegeSelect.value !== "" && collegeSelect.value !== "Select";
                const hasProgramName = programNameInput.value.trim() !== "";
                
                if (hasCollege && hasProgramName) {
                    toggleSaveButtonVisibility(true, 'Program - college and name filled');
                    // Set up real-time validation for Program
                    setupProgramValidation();
                } else {
                    toggleSaveButtonVisibility(false, 'Program - missing college or name');
                }
            }
            
            // Set up change detection for the Program form (only once)
            function setupProgramChangeDetection() {
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
            }
            
            // Only set up change listeners if college is not disabled (i.e., for admins)
            if (!collegeSelect.disabled) {
                collegeSelect.onchange = checkShow;
            }
            programNameInput.oninput = checkShow;
            checkShow();
            
            // Set up change detection only once when form is first created
            setupProgramChangeDetection();
        };

        subMetricGroup.style.display = "block";
        textboxGroup.style.display = "none";
        defaultTextbox.style.display = "none";
        document.querySelectorAll('.entry-buttons').forEach(btns => btns.style.display = 'none');
    } /*
    else if (metric === "Section") {
        subMetricLabel.textContent = "Select College:";
        Object.keys(programOptions).forEach(college => {
            subMetricSelect.appendChild(createOption(college));
        });

        subMetricSelect.onchange = () => {
            const selectedCollege = subMetricSelect.value;
            ["displayProgram", "displayYearLevel", "displaySection"].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.remove();
            });

            const programSelect = document.createElement("select");
            programSelect.id = "displayProgram";
            const programLabel = document.createElement("label");
            programLabel.textContent = "Select Program:";
            subMetricGroup.appendChild(programLabel);
            programSelect.appendChild(createOption("Select Program"));
            programOptions[selectedCollege].forEach(prog => {
                programSelect.appendChild(createOption(prog));
            });
            subMetricGroup.appendChild(programSelect);

            programSelect.onchange = () => {
                const yearSelect = document.createElement("select");
                yearSelect.id = "displayYearLevel";
                const yearLabel = document.createElement("label");
                yearLabel.textContent = "Select Year Level:";
                subMetricGroup.appendChild(yearLabel);
                yearSelect.appendChild(createOption("Select Year Level"));
                yearLevelOptions[programSelect.value].forEach(yr => {
                    yearSelect.appendChild(createOption(yr));
                });
                subMetricGroup.appendChild(yearSelect);

                yearSelect.onchange = () => {
                    const sectionSelect = document.createElement("select");
                    sectionSelect.id = "displaySection";
                    const sectionLabel = document.createElement("label");
                    sectionLabel.textContent = "Select Section:";
                    subMetricGroup.appendChild(sectionLabel);
                    sectionSelect.appendChild(createOption("Select Section"));
                    for (let i = 1; i <= 3; i++) {
                        sectionSelect.appendChild(createOption(`${yearSelect.value.split(" ")[0]} - ${i}`));
                    }
                    sectionSelect.appendChild(createOption("Add Section"));
                    subMetricGroup.appendChild(sectionSelect);
                };
            };
        };

        subMetricGroup.style.display = "block";
        textboxGroup.style.display = "block";
        defaultTextbox.style.display = "block";
    } */
     
    else if (metric === "CurrentLivingArrangement") {
        subMetricLabel.textContent = "Select Option:";
        populateArrangements();
        subMetricSelect.appendChild(createOption("AddLivingArrangement","Add Living Arrangement"));
        // Hide additional details and save button initially
        textboxGroup.style.display = "none";
        defaultTextbox.style.display = "none";
        toggleSaveButtonVisibility(false, 'Living Arrangement metric - initial state');
        subMetricSelect.onchange = () => {
            const selectedArrangementId = subMetricSelect.value;
            const arrangementName = (arrangementOptions.find(a => a.id == selectedArrangementId) || {}).name || "";
            const arrangementInput = document.getElementById("metricTextbox");
            if (!selectedArrangementId || selectedArrangementId === "" || selectedArrangementId === "Select") {
                textboxGroup.style.display = "none";
                defaultTextbox.style.display = "none";
                toggleSaveButtonVisibility(false, 'Living Arrangement not selected');
                arrangementInput.value = "";
            } else {
                textboxGroup.style.display = "block";
                defaultTextbox.style.display = "block";
                toggleSaveButtonVisibility(true, 'Living Arrangement selected - additional details shown');
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
                // Pre-fill arrangement name in textbox if editing
                if (selectedArrangementId !== "AddLivingArrangement") {
                    arrangementInput.value = arrangementName;
                    // Check if this arrangement is hidden and set checkbox accordingly
                    checkAndSetHideCheckbox('living_arrangement', selectedArrangementId);
                } else {
                    arrangementInput.value = "";
                }
                // Set up real-time validation for Living Arrangement
                setupLivingArrangementValidation();
            }
        };
        subMetricGroup.style.display = "block";
    }
    else if (metric === "LanguageSpoken") {
        subMetricLabel.textContent = "Select Option:";
        populateLanguages();
        subMetricSelect.appendChild(createOption("AddLanguage","Add Language"));
        // Hide additional details and save button initially
        textboxGroup.style.display = "none";
        defaultTextbox.style.display = "none";
        toggleSaveButtonVisibility(false, 'Language metric - initial state');
        subMetricSelect.onchange = () => {
            const selectedLanguageId = subMetricSelect.value;
            const languageName = (languageOptions.find(l => l.id == selectedLanguageId) || {}).name || "";
            const languageInput = document.getElementById("metricTextbox");
            if (!selectedLanguageId || selectedLanguageId === "" || selectedLanguageId === "Select") {
                textboxGroup.style.display = "none";
                defaultTextbox.style.display = "none";
                toggleSaveButtonVisibility(false, 'Language not selected');
                languageInput.value = "";
            } else {
                textboxGroup.style.display = "block";
                defaultTextbox.style.display = "block";
                toggleSaveButtonVisibility(true, 'Language selected - additional details shown');
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
                // Pre-fill language name in textbox if editing
                if (selectedLanguageId !== "AddLanguage") {
                    languageInput.value = languageName;
                    // Check if this language is hidden and set checkbox accordingly
                    checkAndSetHideCheckbox('language', selectedLanguageId);
                } else {
                    languageInput.value = "";
                }
                // Set up real-time validation for Language Spoken
                setupLanguageValidation();
            }
        };
        subMetricGroup.style.display = "block";
    }
}

/******************* SOCIOECONOMIC STATUS VALIDATION *******************/
// Global validation function
window.validateSocioeconomicRanges = function() {
    // Get all socioeconomic input fields
    const fields = {
        richMin: document.getElementById("richMin"),
        highIncomeMax: document.getElementById("highIncomeMax"),
        highIncomeMin: document.getElementById("highIncomeMin"),
        upperMiddleMax: document.getElementById("upperMiddleMax"),
        upperMiddleMin: document.getElementById("upperMiddleMin"),
        middleMax: document.getElementById("middleMax"),
        middleMin: document.getElementById("middleMin"),
        lowerMiddleMax: document.getElementById("lowerMiddleMax"),
        lowerMiddleMin: document.getElementById("lowerMiddleMin"),
        lowIncomeMax: document.getElementById("lowIncomeMax"),
        lowIncomeMin: document.getElementById("lowIncomeMin"),
        poorMax: document.getElementById("poorMax")
    };

    // Create error message container if it doesn't exist
    let errorContainer = document.getElementById("socioeconomicErrorContainer");
    if (!errorContainer) {
        errorContainer = document.createElement("div");
        errorContainer.id = "socioeconomicErrorContainer";
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
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            white-space: normal;
        `;
        document.getElementById("socioeconomicStatusInputs").appendChild(errorContainer);
    }

    let hasErrors = false;
    let errorMessages = [];
    
    // Clear previous error styling
    Object.values(fields).forEach(field => {
        if (field) {
            field.style.borderColor = "";
            field.style.borderWidth = "";
            field.classList.remove("error");
        }
    });

    // Get current values
    const values = {};
    Object.keys(fields).forEach(fieldId => {
        const field = fields[fieldId];
        values[fieldId] = field ? parseFloat(field.value) || 0 : 0;
    });
    
    // Check if all fields have values before validating
    const allFieldsFilled = Object.values(values).every(val => val > 0);
    if (!allFieldsFilled) {
        // If not all fields are filled, don't show validation errors
        errorContainer.style.display = "none";
        
        // Let the change detection system handle save button state
        return true;
    }

    // Validation rules based on hierarchy
    // richMin must be highest
    if (values.richMin > 0) {
            if (values.highIncomeMax > 0 && values.richMin <= values.highIncomeMax) {
                hasErrors = true;
                errorMessages.push("Rich must be higher than Maximum High Income");
                fields.richMin.classList.add("error");
            }
    }

    // highIncomeMax must be lower than richMin but higher than highIncomeMin
    if (values.highIncomeMax > 0) {
        if (values.richMin > 0 && values.highIncomeMax >= values.richMin) {
            hasErrors = true;
            errorMessages.push("Maximum High Income must be lower than Rich");
            fields.highIncomeMax.classList.add("error");
        }
        if (values.highIncomeMin > 0 && values.highIncomeMax <= values.highIncomeMin) {
            hasErrors = true;
            errorMessages.push("Maximum High Income must be higher than Minimum High Income");
            fields.highIncomeMax.classList.add("error");
        }
    }

    // highIncomeMin must be lower than highIncomeMax but higher than upperMiddleMax
    if (values.highIncomeMin > 0) {
        if (values.highIncomeMax > 0 && values.highIncomeMin >= values.highIncomeMax) {
            hasErrors = true;
            errorMessages.push("Minimum High Income must be lower than Maximum High Income");
            fields.highIncomeMin.classList.add("error");
        }
        if (values.upperMiddleMax > 0 && values.highIncomeMin <= values.upperMiddleMax) {
            hasErrors = true;
            errorMessages.push("Minimum High Income must be higher than Maximum Upper Middle");
            fields.highIncomeMin.classList.add("error");
        }
    }

    // upperMiddleMax must be lower than highIncomeMin but higher than upperMiddleMin
    if (values.upperMiddleMax > 0) {
        if (values.highIncomeMin > 0 && values.upperMiddleMax >= values.highIncomeMin) {
            hasErrors = true;
            errorMessages.push("Maximum Upper Middle must be lower than Minimum High Income");
            fields.upperMiddleMax.classList.add("error");
        }
        if (values.upperMiddleMin > 0 && values.upperMiddleMax <= values.upperMiddleMin) {
            hasErrors = true;
            errorMessages.push("Maximum Upper Middle must be higher than Minimum Upper Middle");
            fields.upperMiddleMax.classList.add("error");
        }
    }

    // upperMiddleMin must be lower than upperMiddleMax but higher than middleMax
    if (values.upperMiddleMin > 0) {
        if (values.upperMiddleMax > 0 && values.upperMiddleMin >= values.upperMiddleMax) {
            hasErrors = true;
            errorMessages.push("Minimum Upper Middle must be lower than Maximum Upper Middle");
            fields.upperMiddleMin.classList.add("error");
        }
        if (values.middleMax > 0 && values.upperMiddleMin <= values.middleMax) {
            hasErrors = true;
            errorMessages.push("Minimum Upper Middle must be higher than Maximum Middle Class");
            fields.upperMiddleMin.classList.add("error");
        }
    }

    // middleMax must be lower than upperMiddleMin but higher than middleMin
    if (values.middleMax > 0) {
        if (values.upperMiddleMin > 0 && values.middleMax >= values.upperMiddleMin) {
            hasErrors = true;
            errorMessages.push("Maximum Middle Class must be lower than Minimum Upper Middle");
            fields.middleMax.classList.add("error");
        }
        if (values.middleMin > 0 && values.middleMax <= values.middleMin) {
            hasErrors = true;
            errorMessages.push("Maximum Middle Class must be higher than Minimum Middle Class");
            fields.middleMax.classList.add("error");
        }
    }

    // middleMin must be lower than middleMax but higher than lowerMiddleMax
    if (values.middleMin > 0) {
        if (values.middleMax > 0 && values.middleMin >= values.middleMax) {
            hasErrors = true;
            errorMessages.push("Minimum Middle Class must be lower than Maximum Middle Class");
            fields.middleMin.classList.add("error");
        }
        if (values.lowerMiddleMax > 0 && values.middleMin <= values.lowerMiddleMax) {
            hasErrors = true;
            errorMessages.push("Minimum Middle Class must be higher than Maximum Lower Middle");
            fields.middleMin.classList.add("error");
        }
    }

    // lowerMiddleMax must be lower than middleMin but higher than lowerMiddleMin
    if (values.lowerMiddleMax > 0) {
        if (values.middleMin > 0 && values.lowerMiddleMax >= values.middleMin) {
            hasErrors = true;
            errorMessages.push("Maximum Lower Middle must be lower than Minimum Middle Class");
            fields.lowerMiddleMax.classList.add("error");
        }
        if (values.lowerMiddleMin > 0 && values.lowerMiddleMax <= values.lowerMiddleMin) {
            hasErrors = true;
            errorMessages.push("Maximum Lower Middle must be higher than Minimum Lower Middle");
            fields.lowerMiddleMax.classList.add("error");
        }
    }

    // lowerMiddleMin must be lower than lowerMiddleMax but higher than lowIncomeMax
    if (values.lowerMiddleMin > 0) {
        if (values.lowerMiddleMax > 0 && values.lowerMiddleMin >= values.lowerMiddleMax) {
            hasErrors = true;
            errorMessages.push("Minimum Lower Middle must be lower than Maximum Lower Middle");
            fields.lowerMiddleMin.classList.add("error");
        }
        if (values.lowIncomeMax > 0 && values.lowerMiddleMin <= values.lowIncomeMax) {
            hasErrors = true;
            errorMessages.push("Minimum Lower Middle must be higher than Maximum Low Income");
            fields.lowerMiddleMin.classList.add("error");
        }
    }

    // lowIncomeMax must be lower than lowerMiddleMin but higher than lowIncomeMin
    if (values.lowIncomeMax > 0) {
        if (values.lowerMiddleMin > 0 && values.lowIncomeMax >= values.lowerMiddleMin) {
            hasErrors = true;
            errorMessages.push("Maximum Low Income must be lower than Minimum Lower Middle");
            fields.lowIncomeMax.classList.add("error");
        }
        if (values.lowIncomeMin > 0 && values.lowIncomeMax <= values.lowIncomeMin) {
            hasErrors = true;
            errorMessages.push("Maximum Low Income must be higher than Minimum Low Income");
            fields.lowIncomeMax.classList.add("error");
        }
    }

    // lowIncomeMin must be lower than lowIncomeMax but higher than poorMax
    if (values.lowIncomeMin > 0) {
        if (values.lowIncomeMax > 0 && values.lowIncomeMin >= values.lowIncomeMax) {
            hasErrors = true;
            errorMessages.push("Minimum Low Income must be lower than Maximum Low Income");
            fields.lowIncomeMin.classList.add("error");
        }
        if (values.poorMax > 0 && values.lowIncomeMin <= values.poorMax) {
            hasErrors = true;
            errorMessages.push("Minimum Low Income must be higher than Poor");
            fields.lowIncomeMin.classList.add("error");
        }
    }

    // poorMax must be lowest
    if (values.poorMax > 0) {
        if (values.lowIncomeMin > 0 && values.poorMax >= values.lowIncomeMin) {
            hasErrors = true;
            errorMessages.push("Poor must be lower than Minimum Low Income");
            fields.poorMax.classList.add("error");
        }
    }

    // Display error messages
    if (hasErrors) {
        errorContainer.innerHTML = "<strong>Validation Errors:</strong><br>" + errorMessages.join("<br>");
        errorContainer.style.display = "block";
        
        // Disable save button when there are validation errors
        const saveButtons = document.querySelectorAll('.entry-buttons button');
        saveButtons.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
        });
    } else {
        errorContainer.style.display = "none";
        
        // Let the change detection system handle save button state
    }

    return !hasErrors;
};

function setupSocioeconomicValidation() {
    // Get all socioeconomic input fields
    const fields = {
        richMin: document.getElementById("richMin"),
        highIncomeMax: document.getElementById("highIncomeMax"),
        highIncomeMin: document.getElementById("highIncomeMin"),
        upperMiddleMax: document.getElementById("upperMiddleMax"),
        upperMiddleMin: document.getElementById("upperMiddleMin"),
        middleMax: document.getElementById("middleMax"),
        middleMin: document.getElementById("middleMin"),
        lowerMiddleMax: document.getElementById("lowerMiddleMax"),
        lowerMiddleMin: document.getElementById("lowerMiddleMin"),
        lowIncomeMax: document.getElementById("lowIncomeMax"),
        lowIncomeMin: document.getElementById("lowIncomeMin"),
        poorMax: document.getElementById("poorMax")
    };

    // Add number-only validation and live validation to each field
    Object.keys(fields).forEach(fieldId => {
        const field = fields[fieldId];
        if (field) {
            // Number-only input validation
            field.addEventListener("input", function(e) {
                // Remove any non-numeric characters
                let value = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = value;
                
                // Trigger validation with a small delay to allow for input processing
                setTimeout(() => {
                    validateSocioeconomicRanges();
                }, 100);
            });

            // Also validate on blur
            field.addEventListener("blur", function() {
                setTimeout(() => {
                    validateSocioeconomicRanges();
                }, 100);
            });
        }
    });
    
    // Run initial validation to set proper save button state
    setTimeout(() => {
        validateSocioeconomicRanges();
    }, 200);
}

function saveDataEntry() {
    const metric = document.getElementById("metricSelect").value;
    console.log("Metric being sent:", metric, "(type:", typeof metric, ", length:", metric.length, ")");
    const data = new FormData();

        if (metric === "SocioeconomicStatus") {
            // For socioeconomic status, allow saving even if validation has minor issues
            // Only block if there are critical validation errors
            if (typeof validateSocioeconomicRanges === 'function') {
                const isValid = validateSocioeconomicRanges();
                // Don't block saving for socioeconomic status - let the server handle validation
                console.log("Socioeconomic validation result:", isValid);
            }
            
            // Debug: Log all field values before sending
            console.log("Socioeconomic Status - Sending data:");
            console.log("rich_min:", document.getElementById("richMin").value);
            console.log("highIncome_min:", document.getElementById("highIncomeMin").value);
            console.log("highIncome_max:", document.getElementById("highIncomeMax").value);
            console.log("upperMiddle_min:", document.getElementById("upperMiddleMin").value);
            console.log("upperMiddle_max:", document.getElementById("upperMiddleMax").value);
            console.log("middleClass_min:", document.getElementById("middleMin").value);
            console.log("middleClass_max:", document.getElementById("middleMax").value);
            console.log("lowerMiddle_min:", document.getElementById("lowerMiddleMin").value);
            console.log("lowerMiddle_max:", document.getElementById("lowerMiddleMax").value);
            console.log("lowIncome_min:", document.getElementById("lowIncomeMin").value);
            console.log("lowIncome_max:", document.getElementById("lowIncomeMax").value);
            console.log("poor_max:", document.getElementById("poorMax").value);
            
            data.append("metric", metric);
            data.append("rich_min", document.getElementById("richMin").value);
            data.append("highIncome_min", document.getElementById("highIncomeMin").value);
            data.append("highIncome_max", document.getElementById("highIncomeMax").value);
            data.append("upperMiddle_min", document.getElementById("upperMiddleMin").value);
            data.append("upperMiddle_max", document.getElementById("upperMiddleMax").value);
            data.append("middleClass_min", document.getElementById("middleMin").value);
            data.append("middleClass_max", document.getElementById("middleMax").value);
            data.append("lowerMiddle_min", document.getElementById("lowerMiddleMin").value);
            data.append("lowerMiddle_max", document.getElementById("lowerMiddleMax").value);
            data.append("lowIncome_min", document.getElementById("lowIncomeMin").value);
            data.append("lowIncome_max", document.getElementById("lowIncomeMax").value);
            data.append("poor_max", document.getElementById("poorMax").value);
        }
    else if (metric === "College") { //task dea: College insertion/updating
        data.append("metric", metric);
        data.append("language_id", document.getElementById("subMetricSelect")?.value || "");
        data.append("language_input_name", document.getElementById("metricTextbox")?.value || "");
        // Handle hide functionality
        const hideCheckbox = document.getElementById("hideFieldCheckbox");
        if (hideCheckbox) {
            data.append("is_hidden", hideCheckbox.checked ? "1" : "0");
        }
    }
    else if (metric === "Program") { //task dea: Program insertion/updating with college validation
        data.append("metric", metric);
        data.append("language_id", document.getElementById("subMetricSelect")?.value || "");
        data.append("language_input_name", document.getElementById("metricTextbox")?.value || "");
        data.append("college_id", document.getElementById("displayCollegeForProgram")?.value || "");
        // Handle hide functionality
        const hideCheckbox = document.getElementById("hideFieldCheckbox");
        if (hideCheckbox) {
            data.append("is_hidden", hideCheckbox.checked ? "1" : "0");
        }
    }
    else if (metric === "CurrentLivingArrangement") { //task dea: Current living arrangement insertion/updating
        data.append("metric", metric);
        data.append("language_id", document.getElementById("subMetricSelect")?.value || "");
        data.append("language_input_name", document.getElementById("metricTextbox")?.value || "");
        // Handle hide functionality
        const hideCheckbox = document.getElementById("hideFieldCheckbox");
        if (hideCheckbox) {
            data.append("is_hidden", hideCheckbox.checked ? "1" : "0");
        }
    }
    else if (metric === "LanguageSpoken") { //task dea: Language spoken (already working)
        data.append("metric", metric); // Metric is used in PHP (submit_student_information_entry.php) to identify which metric is being saved
        data.append("language_id", document.getElementById("subMetricSelect")?.value || "");
        data.append("language_input_name", document.getElementById("metricTextbox")?.value || "");
        // Handle hide functionality
        const hideCheckbox = document.getElementById("hideFieldCheckbox");
        if (hideCheckbox) {
            data.append("is_hidden", hideCheckbox.checked ? "1" : "0");
        }
    }

    fetch("/bridge/modules/additional_entry/processes/submit_student_information_entry.php", {
        method: "POST",
        body: data
    })
    .then(res => {
        console.log("Response status:", res.status);
        console.log("Response headers:", res.headers);
        return res.text();
    })
    .then(response => {
        console.log("Server response:", response);
        
        // Check if response contains error
        if (response.includes("Error:")) {
            console.error("Server returned error:", response);
            showToast("Error: " + response);
            return;
        }
        
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
    const modal = document.getElementById("validationModal");
    if (modal) {
      modal.classList.add("show");
    } else {
      console.error("Validation modal not found!");
    }
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

function populateArrangements() {
    const arrangementSelect = document.getElementById("subMetricSelect");
    arrangementSelect.innerHTML = '<option value="" disabled selected>Select</option>';
    // Clear previous options except the first one
    subMetricSelect.options.length = 1;
    arrangementOptions.forEach(arrangement => {
        const option = document.createElement("option");
        // Add visual indicator for hidden items
        const displayName = arrangement.is_active === 0 ? `[HIDDEN] ${arrangement.name}` : arrangement.name;
        option.text = displayName;
        option.value = arrangement.id;
        // Style hidden items differently
        if (arrangement.is_active === 0) {
            option.style.color = '#999';
            option.style.fontStyle = 'italic';
        }
        subMetricSelect.add(option);
    });
}

function populateLanguages() {
    const languageSelect = document.getElementById("subMetricSelect");
    languageSelect.innerHTML = '<option value="" disabled selected>Select</option>';
    // Clear previous options except the first one
    subMetricSelect.options.length = 1;
    languageOptions.forEach(language => {
        const option = document.createElement("option");
        // Add visual indicator for hidden items
        const displayName = language.is_active === 0 ? `[HIDDEN] ${language.name}` : language.name;
        option.text = displayName;
        option.value = language.id;
        // Style hidden items differently
        if (language.is_active === 0) {
            option.style.color = '#999';
            option.style.fontStyle = 'italic';
        }
        subMetricSelect.add(option);
    });
}

// Function to check and set hide checkbox for hidden items
function checkAndSetHideCheckbox(optionType, optionId) {
    const hideCheckbox = document.getElementById('hideCheckbox');
    if (!hideCheckbox) return;
    
    // Find the item in the appropriate options array
    let item = null;
    if (optionType === 'living_arrangement') {
        item = arrangementOptions.find(a => a.id == optionId);
    } else if (optionType === 'language') {
        item = languageOptions.find(l => l.id == optionId);
    }
    
    if (item) {
        // Set checkbox to checked if item is hidden (is_active === 0)
        hideCheckbox.checked = item.is_active === 0;
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
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            white-space: normal;
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

// College validation
function setupCollegeValidation() {
    const collegeInput = document.getElementById("metricTextbox");
    const collegeSelect = document.getElementById("subMetricSelect");
    
    if (!collegeInput || !collegeSelect) return;

    const validateCollege = async () => {
        const collegeName = collegeInput.value.trim();
        const collegeId = collegeSelect.value;
        
        if (collegeName && collegeId) {
            const data = {
                college_name: collegeName,
                college_id: collegeId
            };
            
            const result = await validateDuplicateData('College', data);
            if (!result.isValid) {
                showValidationError('collegeError', result.message);
            } else {
                hideValidationError('collegeError');
            }
        } else {
            hideValidationError('collegeError');
        }
    };

    // Add event listeners
    collegeInput.addEventListener('input', validateCollege);
    collegeSelect.addEventListener('change', validateCollege);
}

// Program validation
function setupProgramValidation() {
    const programInput = document.getElementById("metricTextbox");
    const collegeSelect = document.getElementById("displayCollegeForProgram");
    const programSelect = document.getElementById("subMetricSelect");
    
    if (!programInput || !collegeSelect || !programSelect) return;

    const validateProgram = async () => {
        const programName = programInput.value.trim();
        const collegeId = collegeSelect.value;
        const programId = programSelect.value;
        
        // Only validate if we have all required fields
        if (programName && collegeId && programId && collegeId !== "Select") {
            const data = {
                program_name: programName,
                college_id: collegeId,
                program_id: programId
            };
            
            const result = await validateDuplicateData('Program', data);
            if (!result.isValid) {
                showValidationError('programError', result.message);
            } else {
                hideValidationError('programError');
            }
        } else {
            hideValidationError('programError');
        }
    };

    // Add event listeners
    programInput.addEventListener('input', validateProgram);
    
    // Only add change listener for college if it's not disabled (i.e., for admins)
    if (!collegeSelect.disabled) {
        collegeSelect.addEventListener('change', validateProgram);
    }
    
    if (programSelect) {
        programSelect.addEventListener('change', validateProgram);
    }
}

// Living Arrangement validation
function setupLivingArrangementValidation() {
    const arrangementInput = document.getElementById("metricTextbox");
    const arrangementSelect = document.getElementById("subMetricSelect");
    
    if (!arrangementInput || !arrangementSelect) return;

    const validateArrangement = async () => {
        const arrangementName = arrangementInput.value.trim();
        const arrangementId = arrangementSelect.value;
        
        if (arrangementName && arrangementId) {
            const data = {
                arrangement_name: arrangementName,
                arrangement_id: arrangementId
            };
            
            const result = await validateDuplicateData('CurrentLivingArrangement', data);
            if (!result.isValid) {
                showValidationError('arrangementError', result.message);
            } else {
                hideValidationError('arrangementError');
            }
        } else {
            hideValidationError('arrangementError');
        }
    };

    // Add event listeners
    arrangementInput.addEventListener('input', validateArrangement);
    arrangementSelect.addEventListener('change', validateArrangement);
}

// Language Spoken validation
function setupLanguageValidation() {
    const languageInput = document.getElementById("metricTextbox");
    const languageSelect = document.getElementById("subMetricSelect");
    
    if (!languageInput || !languageSelect) return;

    const validateLanguage = async () => {
        const languageName = languageInput.value.trim();
        const languageId = languageSelect.value;
        
        if (languageName && languageId) {
            const data = {
                language_name: languageName,
                language_id: languageId
            };
            
            const result = await validateDuplicateData('LanguageSpoken', data);
            if (!result.isValid) {
                showValidationError('languageError', result.message);
            } else {
                hideValidationError('languageError');
            }
        } else {
            hideValidationError('languageError');
        }
    };

    // Add event listeners
    languageInput.addEventListener('input', validateLanguage);
    languageSelect.addEventListener('change', validateLanguage);
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
        'collegeError',
        'programError',
        'arrangementError',
        'languageError'
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