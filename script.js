// =============================================================
// script.js : used by index.php (booking) and edit.php (admin edit)
//
// Job 1: when the date changes, ask the server (slots.php) how many
//        free places each mechanic has on that date, and update the
//        mechanic list without reloading the page.
// Job 2: check the form fields before it is sent to the server.
// =============================================================

const form          = document.getElementById("appointmentForm");
const dateInput     = document.getElementById("appointment_date");
const mechanicList  = document.getElementById("mechanic_id");
const slotInfo      = document.getElementById("slotInfo");

// ---------- Job 1: live free places ----------

function updateMechanicList() {
    const date = dateInput.value;
    if (date === "") {
        return; // no date chosen yet
    }

    // edit.php puts the appointment id in data-exclude (index.php has none -> 0)
    const excludeId = mechanicList.dataset.exclude || 0;
    const url = "slots.php?date=" + encodeURIComponent(date) + "&exclude=" + excludeId;

    fetch(url)
        .then(function (response) {
            return response.json();
        })
        .then(function (mechanics) {
            if (mechanics.error) {
                return; // the server said the date is invalid
            }

            const selected = mechanicList.value; // remember the current choice

            // Rebuild the <option> list
            mechanicList.innerHTML = '<option value="">-- Select a mechanic --</option>';
            mechanics.forEach(function (m) {
                const option = document.createElement("option");
                option.value = m.id;
                if (m.free > 0) {
                    option.textContent = m.name + " — " + m.free + " of " + MAX_CARS + " places free";
                } else {
                    option.textContent = m.name + " — FULL";
                    option.disabled = true; // a full mechanic cannot be chosen
                }
                if (String(m.id) === selected && m.free > 0) {
                    option.selected = true;
                }
                mechanicList.appendChild(option);
            });

            slotInfo.textContent = "Free places shown for " + date + ".";
        })
        .catch(function () {
            slotInfo.textContent = "Could not load free places. Please try again.";
        });
}

dateInput.addEventListener("change", updateMechanicList);

// ---------- Job 2: form validation ----------

// Each rule: which field, how to test it, and the message if it fails.
// On edit.php only the date and mechanic fields exist, so the
// other rules are skipped automatically.
const rules = [
    { id: "name",        test: /^[A-Za-z .\-]{2,100}$/, message: "Enter your name (letters and spaces only)." },
    { id: "address",     test: /\S/,                    message: "Enter your address." },
    { id: "phone",       test: /^[0-9]{11}$/,           message: "Phone must be exactly 11 digits, e.g. 01712345678." },
    { id: "car_license", test: /^[A-Za-z0-9 \-]{2,50}$/, message: "Enter the car license number (letters, numbers, - and spaces)." },
    { id: "car_engine",  test: /^[0-9]{1,30}$/,         message: "Engine number must contain numbers only." },
    { id: "appointment_date", test: /^\d{4}-\d{2}-\d{2}$/, message: "Choose an appointment date." },
    { id: "mechanic_id", test: /^[0-9]+$/,              message: "Please select a mechanic from the list." }
];

form.addEventListener("submit", function (event) {
    let allOk = true;

    rules.forEach(function (rule) {
        const field = document.getElementById(rule.id);
        if (!field) {
            return; // this field is not on this page
        }
        const errorBox = document.getElementById("err-" + rule.id);
        const value = field.value.trim();

        if (rule.test.test(value)) {
            errorBox.textContent = "";
            field.classList.remove("invalid");
        } else {
            errorBox.textContent = rule.message;
            field.classList.add("invalid");
            allOk = false;
        }
    });

    // Extra check: the date must not be in the past.
    // PHP wrote today's date into the input's "min" attribute.
    if (dateInput.value !== "" && dateInput.value < dateInput.min) {
        document.getElementById("err-appointment_date").textContent = "The date cannot be in the past.";
        dateInput.classList.add("invalid");
        allOk = false;
    }

    if (!allOk) {
        event.preventDefault(); // stop the form from being sent
    }
});
