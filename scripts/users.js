// SECTIONS FUNCTIONS
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('section'); // Get all section elements
    const links = document.querySelectorAll('.nav-links a'); // Get all navigation links
    let sectionHistory = []; // Stack to track visited sections

    // Function to display a section and track history
    const showSection = (target) => {
        if (!target) return;
        sections.forEach(section => section.style.display = 'none');
        target.style.display = 'block';
        sectionHistory.push(target);
    };

    // Initially show the home section
    const homeSection = document.querySelector('#home');
    sections.forEach(section => section.style.display = 'none');
    if (homeSection) {
        homeSection.style.display = 'block';
        sectionHistory.push(homeSection);
    }

    // Sidebar link functionality (to switch sections)
    links.forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);

            if (target) {
                showSection(target); 
            }
        });
    });

    // Optional: Back button functionality (if you want to go back through visited sections)
    const backButtons = document.querySelectorAll('.back-button');
    backButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (sectionHistory.length > 1) {
                sectionHistory.pop();
                const previousSection = sectionHistory[sectionHistory.length - 1];
                sections.forEach(section => section.style.display = 'none');
                previousSection.style.display = 'block';
            }
        });
    });

    document.getElementById("dropdown-toggle").addEventListener("click", function() {
        const dropdown = this.parentElement;
        dropdown.classList.toggle("active");
    });

    const historyButton = document.getElementById("history");
    const historyDropdown = document.getElementById("historyDropdown");

    // Toggle dropdown when clicking "HISTORY"
    historyButton.addEventListener("click", (e) => {
        e.preventDefault();
        historyDropdown.classList.toggle("active-dropdown");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
        if (!historyButton.contains(e.target) && !historyDropdown.contains(e.target)) {
            historyDropdown.classList.remove("active-dropdown");
        }
    });

    const menuButtons = document.querySelectorAll(".menu-button");

    menuButtons.forEach(button => {
        button.addEventListener("click", function(event) {
            menuButtons.forEach(btn => {
                btn.style.backgroundColor = "";
                btn.style.color = "";
                const icons = btn.getElementsByTagName("i");
                for (let i = 0; i < icons.length; i++) {
                    icons[i].style.color = "";
                }
            });

            this.style.backgroundColor = "#30408D";
            this.style.color = "white";
            const icons = this.getElementsByTagName("i");
            for (let i = 0; i < icons.length; i++) {
                icons[i].style.color = "white";
            }

            const href = this.getAttribute("href");
            if (href) {
                window.location.href = href;
            }
        });
    });
});


function myAccFunc() {
    var x = document.getElementById("demoAcc");
    var btn = x.previousElementSibling;

    if (x.className.indexOf("w3-show") == -1) {
        x.className += " w3-show";
        btn.style.backgroundColor = "#30408D";
        btn.style.color = "white";
        var icons = btn.getElementsByTagName("i");
        for (var i = 0; i < icons.length; i++) {
            icons[i].style.color = "white";
        }
    } else {
        x.className = x.className.replace(" w3-show", "");
        btn.style.backgroundColor = "";
        btn.style.color = "";
        var icons = btn.getElementsByTagName("i");
        for (var i = 0; i < icons.length; i++) {
            icons[i].style.color = "";
        }
    }
}

// OPEN POP UP FUNCTION
function openPopup() {
    document.getElementById("id01").style.display = "block";
    document.getElementById('reportDate').value = today;
}

// CLOSE POP UP FUNCTION
function closePopup() {
    document.getElementById("id01").style.display = "none";
}

// // IMAGE PREVIEW FUNCTION
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('itemImagePreview').src = e.target.result;
        document.getElementById('itemImagePreview').style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
}

// ITEM STATUS FUNCTIONALITY
document.addEventListener("DOMContentLoaded", function () {
    const statusRadios = document.getElementsByName("status");
    const lostHoldingStatus = document.getElementById("lostHoldingStatus");
    const foundHoldingStatus = document.getElementById("foundHoldingStatus");
    const notYetRetrievedRadio = document.getElementById("notYetRetrieved");

    statusRadios.forEach(radio => {
        radio.addEventListener("change", function () {
            if (this.value === "lost") {
                lostHoldingStatus.style.display = "block";
                foundHoldingStatus.style.display = "none";
                notYetRetrievedRadio.checked = true;
            } else if (this.value === "found") {
                lostHoldingStatus.style.display = "none";
                foundHoldingStatus.style.display = "block";
            }
        });
    });
});

// FLOOR AND ROOM NUMBER FUNCTION
function locSelect() {
    var selectRoom = document.getElementById('roomNo');
    var selectFloor = document.getElementById('floorNo').value;
    var otherRoomInput = document.getElementById('otherRoomInput');

    var selectRooms = {
        "LG": ["Bulldog Exchange", "Guard Post", "Lobby", "Outside", "Storage", "Other"],
        "UG": ["Near Staicase", "Lobby", "Storage", "Other"],
        "1": ["Canteen", "Elevator", "Library", "PE Area", "Restroom", "Staircase", "Other"],
        "2": ["201", "202", "203", "204", "205", "Other"],
        "3": ["301", "302", "303", "304", "305", "Other"],
        "4": ["401", "402", "403", "404", "405", "Other"],
        "5": ["501", "502", "503", "504", "505", "Other"]
    };

    // Clear previous options
    selectRoom.innerHTML = '<option value="" disabled selected>Select Room</option>';

    // Populate new options based on selected floor
    if (selectRooms[selectFloor]) {
        selectRooms[selectFloor].forEach(room => {
            var option = document.createElement("option");
            option.value = room; // Store value as is
            option.textContent = room;
            selectRoom.appendChild(option);
        });
    }

    // Hide the "Other" input box initially
    otherRoomInput.style.display = 'none';
    otherRoomInput.value = ''; // Reset value
}

// Show input box if "Other" is selected
function checkOtherRoom() {
    var selectRoom = document.getElementById('roomNo');
    var otherRoomInput = document.getElementById('otherRoomInput');

    if (selectRoom.value === "Other") {
        otherRoomInput.style.display = 'block';
        otherRoomInput.required = true;
    } else {
        otherRoomInput.style.display = 'none';
        otherRoomInput.required = false;
    }
}

// ITEM COLOR
function toggleInput(selectElement, inputId) {
    var inputField = document.getElementById(inputId);
    if (selectElement.value === "Others") {
        inputField.style.display = "block";
        inputField.required = true; // Make input required if 'Others' is selected
    } else {
        inputField.style.display = "none";
        inputField.required = false;
        inputField.value = ""; // Clear input field if not used
    }
}

// SUBMIT REPORT FUNCTION
function submitReport(event) {
    event.preventDefault();

    const form = document.getElementById("reportForm");
    const loadingSpinner = document.getElementById("loadingSpinner");
    const cancelButton = document.getElementById("cancelButton");

    const formData = new FormData(event.target);
    formData.append('submit', 'true');

    form.style.display = "none";
    loadingSpinner.style.display = "block";
    cancelButton.style.display = "none";

    fetch('../data/form_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Hide loading spinner
        loadingSpinner.style.display = "none";

        if (data.includes("successfully")) {
            document.getElementById('successModal').style.display = 'block';
        } else {
            form.style.display = "block";
            cancelButton.style.display = "block";
            alert(data);
        }
    })
    .catch(error => {
        loadingSpinner.style.display = "none";
        form.style.display = "block";
        cancelButton.style.display = "block";
        console.error('Error:', error);
        alert('An error occurred while submitting the report');
    });
}

// CLOSE SUCCESS MODAL
function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
    closePopup(); // Close report form
    window.location.reload(); // Refresh page
}


// VIEW ITEM REPORT MODAL FUNCTION
function viewItemReport(report) {
    // Get the modal content element
    const modalContent = document.getElementById('viewItemContent');

    // Populate the modal with the report details
    modalContent.innerHTML = `
        <h2 class="w3-padding" style="font-family: 'Encode Sans Condensed';">Report Item Details</h2>
        <div class="item-info-container">

            <!-- QR Code and Image -->
            <div class="w3-row-padding">
                <div class="w3-half">
                    <p><strong>QR CODE:</strong></p>
                    <img src="${report.QR_CODE}" height="100px" width="100px" alt="QR Code">
                </div>

                <div class="w3-half">
                    <p><strong>Item Photo:</strong></p>
                    <img src="${report.ITEM_IMAGE}" height="100px" width="100px" alt="Item Image">
                </div>
            </div>

            <!-- Status Section -->
            <div class="w3-row-padding">
                <div class="w3-half">
                    <label><strong>Item Status:</strong></label>
                    <p>${report.ITEM_STATUS}</p>
                </div>

                <div class="w3-half">
                    <label><strong>Item Holding Status:</strong></label>
                    <p>${report.HOLDING_STATUS}</p>
                </div>
            </div>

            <!-- Item Name and Category -->
            <div class="w3-row-padding">
                <div class="w3-half">
                    <label><strong>Item Name:</strong></label>
                    <p>${report.ITEM_NAME}</p>
                </div>
                <div class="w3-half">
                    <label><strong>Item Category:</strong></label>
                    <p>${report.ITEM_CATEGORY}</p>
                </div>
            </div>

            <!-- Item Color and Brand -->
            <div class="w3-row-padding">
                <div class="w3-half">
                    <label><strong>Item Color:</strong></label>
                    <p>${report.ITEM_COLOR}</p>
                </div>
                <div class="w3-half">
                    <label><strong>Item Brand:</strong></label>
                    <p>${report.ITEM_BRAND}</p>
                </div>
            </div>

            <!-- Item Description -->
            <div class="w3-row-padding">
                <div class="w3-col" style="width: 100%;">
                    <label><strong>Item Description:</strong></label>
                    <p>${report.ITEM_DESCRIPTION}</p>
                </div>
            </div>

            <!-- Location and Date/Time -->
            <div class="w3-row-padding">
                <div class="w3-col s6">
                    <p style="margin-bottom: 0px;"><strong>Location where the item lost/found:</strong></p>
                </div>

                <div class="w3-col s6">
                    <p style="margin-bottom: 0px;"><strong>Date and Time when did the lost/found:</strong></p>
                </div>
            </div>

            <div class="w3-row-padding">
                <div class="w3-col s3">
                    <label><strong>Floor Number:</strong></label>
                    <p>${report.FLOOR_NUMBER} Floor</p>
                </div>

                <div class="w3-col s3">
                    <label><strong>Room Number:</strong></label>
                    <p>${report.ROOM_NUMBER}</p>
                </div>

                <div class="w3-col s3">
                    <label><strong>Date Found:</strong></label>
                    <p>${report.ITEM_DATE}</p>
                </div>

                <div class="w3-col s3">
                    <label><strong>Time Found:</strong></label>
                    <p>${report.ITEM_TIME}</p>
                </div>
            </div>

        </div>
    `;

    // Display the modal
    document.getElementById('viewItem').style.display = 'block';
}


// PRINT REPORT
function printReport(reportId) {
    // Open the report in a new tab
    const reportWindow = window.open(`../data/details.php?report_id=${reportId}`, '_blank');
    reportWindow.focus();
    reportWindow.print();
}



// VIEW MATCHED ITEMS REPORT FUNCTION
function viewMatchedItem(reportId) {
    fetch('../data/fetch_matched_items.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'report_id': reportId
        })
    })
    .then(response => response.json())
    .then(response => {
        if(response.success) {
            const data = response.data;
            let content = `
                <h2 style="font-family: Encode Sans Condensed;">Matched Items Information</h2>
                <div class="matched-items-container">

                    <div class="lost-item-info">
                        <h3 style="font-family: Encode Sans Condensed;">Lost Item Details</h3>
                        <div class="item-details">
                            <p><strong>Item Photo:</strong> <img src="${data.lost_item_image}" height="100px" width="100px" alt="Found Item QR Code"></p>
                            <p><strong>QR Code:</strong> <img src="${data.lost_qr_code}" height="100px" width="100px" alt="Found Item QR Code"></p>
                            <p><strong>Reported by:</strong> ${data.reporter1_email_add}</p>
                            <p><strong>Item Name:</strong> ${data.lost_item_name}</p>
                            <p><strong>Category:</strong> ${data.ITEM_CATEGORY}</p>
                            <p><strong>Color:</strong> ${data.ITEM_COLOR}</p>
                            <p><strong>Brand:</strong> ${data.ITEM_BRAND}</p>
                            <p><strong>Location:</strong> Floor ${data.FLOOR_NUMBER}, Room ${data.ROOM_NUMBER}</p>
                            <p><strong>Report Date:</strong> ${data.ITEM_DATE}</p>
                            <p><strong>Description:</strong> ${data.ITEM_DESCRIPTION}</p>
                        </div>
                    </div>

                    <div class="found-item-info">
                        <h3 style="font-family: Encode Sans Condensed;">Found Item Details</h3>
                        <div class="item-details">
                            <p><strong>Item Photo:</strong> <img src="${data.found_item_image}" height="100px" width="100px" alt="Found Item QR Code"></p>
                            <p><strong>QR Code:</strong> <img src="${data.found_qr_code}" height="100px" width="100px" alt="Found Item QR Code"></p>
                            <p><strong>Reported by:</strong> ${data.reporter2_email_add}</p>
                            <p><strong>Item Name:</strong> ${data.found_item_name}</p>
                            <p><strong>Category:</strong> ${data.paired_category}</p>
                            <p><strong>Color:</strong> ${data.paired_color}</p>
                            <p><strong>Brand:</strong> ${data.paired_brand}</p>
                            <p><strong>Location:</strong> Floor ${data.paired_floor}, Room ${data.paired_room}</p>
                            <p><strong>Report Date:</strong> ${data.paired_date}</p>
                            <p><strong>Description:</strong> ${data.paired_description}</p>
                        </div>
                    </div>

                </div>`;

            document.getElementById('viewMatchContent').innerHTML = content;
            document.getElementById('viewMatch').style.display = 'block';
        }
    })
    .catch(error => console.error('Error:', error));
}



// UPDATE TABLE (RECENT REPORT) FUNCTION
function updateTable() {
    const table = document.querySelector('#recentReportsTable tbody');
    if (!table) return;

    // Get form elements
    const formElements = {
        itemImage: document.getElementById('itemImage'),
        itemName: document.getElementById('itemName'),
        itemCategory: document.getElementById('itemCategory'),
        itemColor: document.getElementById('itemColor'),
        itemBrand: document.getElementById('itemBrand'),
        description: document.getElementById('description'),
        floorNo: document.getElementById('floorNo'),
        roomNo: document.getElementById('roomNo'),
        reportDate: document.getElementById('reportDate'),
        reportTime: document.getElementById('reportTime'),
        status: document.querySelector('input[name="status"]:checked')
    };

    // Check if all elements exist
    for (const [key, element] of Object.entries(formElements)) {
        if (!element) {
            console.log(`Missing element: ${key}`);
            return;
        }
    }

    const imagePath = formElements.itemImage.value;

    const row = document.createElement('tr');
    row.innerHTML = `
        <td>-</td>
        <td><img src="${imagePath}" height="100px" width="100px" alt="Item Image"></td>
        <td>${formElements.itemName.value}</td>
        <td>${formElements.itemCategory.value}</td>
        <td>${formElements.itemColor.value}</td>
        <td>${formElements.itemBrand.value}</td>
        <td>${formElements.description.value}</td>
        <td>Floor ${formElements.floorNo.value}, Room ${formElements.roomNo.value}</td>
        <td>${formElements.reportDate.value}, ${formElements.reportTime.value}</td>
        <td>${formElements.status.value}</td>
        <td><button class="w3-btn w3-blue w3-round">Edit</button></td>
    `;

    console.log("Updating table with new row:", row);

    if (table.firstChild) {
        table.insertBefore(row, table.firstChild);
    } else {
        table.appendChild(row);
    }
}