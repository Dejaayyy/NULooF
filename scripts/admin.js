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
});

// DATE AND TIME FUNCTION
function updateDateTime() {
    var currentDate = new Date();

    var dateOptions = { month: 'long', day: 'numeric', year: 'numeric' };
    var formattedDate = currentDate.toLocaleDateString('en-US', dateOptions);

    var weekdayOptions = { weekday: 'long' };
    var formattedDay = currentDate.toLocaleDateString('en-US', weekdayOptions);

    var timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
    var formattedTime = currentDate.toLocaleTimeString('en-US', timeOptions);

    var fullFormattedDateTime = formattedDate + '\n' + formattedDay + '\n' + formattedTime;

    document.getElementById("currentDateTime").textContent = fullFormattedDateTime;
}

setInterval(updateDateTime, 1000);
updateDateTime();

// LOGOUT DROPDOWN FUNCTION
document.getElementById("dropdown-toggle").addEventListener("click", function () {
    const dropdown = this.parentElement;
    dropdown.classList.toggle("active");
});

// DEMOACC FUNCTION
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

// OPEN POPUP FUNCTION
function openPopup() {
    document.getElementById("id01").style.display = "block";
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('reportDate').value = today;
}

// CLOSE POPUP FUNCTION
function closePopup() {
    document.getElementById("id01").style.display = "none";
}


// Filter function for tables
function filterTable(category, value, tableId) {
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header
        const row = tr[i];
        let match = false;

        switch(category) {
            case 'ITEM_CATEGORY':
                const itemCategory = row.querySelector("td:nth-child(4)").innerText;
                match = itemCategory.toLowerCase().includes(value.toLowerCase());
                break;
            case 'ITEM_COLOR':
                const itemColor = row.querySelector("td:nth-child(5)").innerText;
                match = itemColor.toLowerCase().includes(value.toLowerCase());
                break;
            case 'FLOOR_NUMBER':
                const floorNumber = row.querySelector("td:nth-child(6)").innerText;
                match = floorNumber.toLowerCase().includes(value.toLowerCase());
                break;
            case 'ROOM_NUMBER':
                const roomNumber = row.querySelector("td:nth-child(6)").innerText;
                match = roomNumber.toLowerCase().includes(value.toLowerCase());
                break;
        }

        row.style.display = match ? "" : "none";
    }
}

// FILTER BUTTON FUNCTION
function myFunction(tableId) {
    const dropdown = document.getElementById("myDropdown");
    dropdown.innerHTML = `
        <div class="filter-category">
            <a href="#" onclick="showFilterOptions('ITEM_CATEGORY', '${tableId}')">Category</a>
            <a href="#" onclick="showFilterOptions('ITEM_COLOR', '${tableId}')">Color</a>
            <a href="#" onclick="showFilterOptions('FLOOR_NUMBER', '${tableId}')">Floor</a>
            <a href="#" onclick="showFilterOptions('ROOM_NUMBER', '${tableId}')">Room</a>
        </div>
    `;
    dropdown.classList.toggle("show");
}

// FILTER DUNCTION
function filterFunction() {
    const filter = input.value.toUpperCase();
    const dropdown = document.getElementById("myDropdown");
    const items = dropdown.getElementsByTagName("a");

    for (let i = 0; i < items.length; i++) {
        const txtValue = items[i].textContent || items[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}

function showFilterOptions(field, tableId) {
    debugTableStructure(tableId);
    fetch(`../data/get_filter_options.php?field=${field}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            const dropdown = document.getElementById("myDropdown");
            let optionsHtml = `
                <div class="filter-options">
                    <div class="filter-header">
                        <button onclick="myFunction('${tableId}')" class="back-button">←</button>
                        <span>Select ${field.replace('_', ' ').toLowerCase()}</span>
                    </div>
            `;

            data.options.forEach(option => {
                optionsHtml += `<a href="#" onclick="applyFilter('${field}', '${option}', '${tableId}')">${option}</a>`;
            });

            optionsHtml += '</div>';
            dropdown.innerHTML = optionsHtml;
        })
        .catch(error => console.error('Error:', error));
}

function applyFilter(field, value, tableId) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName("tr");

    // Skip header row
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        let match = false;

        // Get all cell content, including nested elements
        const cellText = row.textContent || row.innerText;

        // Case insensitive search
        if (cellText.toLowerCase().includes(value.toLowerCase())) {
            row.style.display = "";
            match = true;
        }

        if (!match) {
            row.style.display = "none";
        }
    }

    document.getElementById("myDropdown").classList.remove("show");
}

function getColumnIndexForField(field) {
    // Map database fields to table column indices based on the actual table structure
    const columnMap = {
        'ITEM_CATEGORY': 1, // Index where category appears in the table
        'ITEM_COLOR': 2,    // Index where color appears in the table
        'FLOOR_NUMBER': 3,  // Index where floor appears in the table
        'ROOM_NUMBER': 3    // Index where room appears in the table
    };
    return columnMap[field] || 0;
}

function debugTableStructure(tableId) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error(`Table with ID ${tableId} not found`);
        return;
    }

    const headerRow = table.querySelector('thead tr');
    if (headerRow) {
        const headers = headerRow.cells;
        console.log('Table headers:');
        for (let i = 0; i < headers.length; i++) {
            console.log(`Column ${i}: ${headers[i].textContent.trim()}`);
        }
    }

    const firstDataRow = table.querySelector('tbody tr');
    if (firstDataRow) {
        const cells = firstDataRow.cells;
        console.log('First row data:');
        for (let i = 0; i < cells.length; i++) {
            console.log(`Column ${i}: ${cells[i].textContent.trim()}`);
        }
    }
}

function generateReport(tableId, reportName) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('Table not found!');
        return;
    }

    // Identify "Action" column index
    let actionColumnIndex = -1;
    const headers = table.querySelectorAll('thead th');
    headers.forEach((th, index) => {
        if (th.textContent.trim().toLowerCase() === 'action') {
            actionColumnIndex = index;
        }
    });

    // Hide "Action" column before generating report
    const rows = table.querySelectorAll('tr');
    let hiddenCells = [];

    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        if (actionColumnIndex !== -1 && cells[actionColumnIndex]) {
            hiddenCells.push(cells[actionColumnIndex]);
            cells[actionColumnIndex].style.display = 'none';
        }
    });

    // Convert all QR Code SVGs into canvas elements (for better html2canvas support)
    const qrCodes = table.querySelectorAll('svg'); // Assuming QR codes are in SVG format
    const qrCanvasMap = new Map();

    qrCodes.forEach((svg, index) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const svgData = new XMLSerializer().serializeToString(svg);
        const img = new Image();

        img.src = 'data:image/svg+xml;base64,' + btoa(svgData);
        img.onload = () => {
            canvas.width = svg.clientWidth;
            canvas.height = svg.clientHeight;
            ctx.drawImage(img, 0, 0);
            qrCanvasMap.set(svg, canvas);
            svg.replaceWith(canvas);
        };
    });

    // Add loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.innerHTML = `
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.5);
        z-index: 1000;">
        <i class="fas fa-spinner fa-spin"></i> Generating report...
        </div>`;
    document.body.appendChild(loadingDiv);

    // Wait for QR code replacements before capturing
    setTimeout(() => {
        html2canvas(table, {
            scale: 2,
            backgroundColor: '#ffffff',
            logging: false,
            useCORS: true // Ensures images load correctly
        }).then(canvas => {
            // Restore "Action" column visibility
            hiddenCells.forEach(cell => {
                cell.style.display = '';
            });

            // Restore QR Codes (replace canvas with original SVGs)
            qrCanvasMap.forEach((canvas, svg) => {
                canvas.replaceWith(svg);
            });

            // Remove loading indicator
            document.body.removeChild(loadingDiv);

            // Create and trigger download
            const link = document.createElement('a');
            link.download = `${reportName}_${new Date().toISOString().slice(0,10)}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }).catch(err => {
            console.error('Error generating report:', err);
            document.body.removeChild(loadingDiv);
            alert('Error generating report. Please try again.');
        });
    }, 1000); // Short delay to ensure QR codes are replaced
}




function filterlostFunction() {
    const filter = input.value.toUpperCase();
    const dropdown = document.getElementById("lostDropdown");
    const items = dropdown.getElementsByTagName("a");

    for (let i = 0; i < items.length; i++) {
        const txtValue = items[i].textContent || items[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}

// FILTER BUTTON FUNCTION FOR LOST SECTION
function myLostFunction(tableId) {
    const dropdown = document.getElementById("lostDropdown");
    dropdown.innerHTML = `
        <div class="filter-category">
            <a href="#" onclick="showLostFilterOptions('ITEM_CATEGORY', '${tableId}')">Category</a>
            <a href="#" onclick="showLostFilterOptions('ITEM_COLOR', '${tableId}')">Color</a>
            <a href="#" onclick="showLostFilterOptions('FLOOR_NUMBER', '${tableId}')">Floor</a>
            <a href="#" onclick="showLostFilterOptions('ROOM_NUMBER', '${tableId}')">Room</a>
        </div>
    `;
    dropdown.classList.toggle("show");
}

function showLostFilterOptions(field, tableId) {
    debugTableStructure(tableId);
    fetch(`../data/get_filter_options.php?field=${field}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            const dropdown = document.getElementById("lostDropdown");
            let optionsHtml = `
                <div class="filter-options">
                    <div class="filter-header">
                        <button onclick="myLostFunction('${tableId}')" class="back-button">←</button>
                        <span>Select ${field.replace('_', ' ').toLowerCase()}</span>
                    </div>
            `;

            data.options.forEach(option => {
                optionsHtml += `<a href="#" onclick="applyLostFilter('${field}', '${option}', '${tableId}')">${option}</a>`;
            });

            optionsHtml += '</div>';
            dropdown.innerHTML = optionsHtml;
        })
        .catch(error => console.error('Error:', error));
}

// GENERATE REPORT FOR LOST SECTION
function generateLostReport(tableId, reportName) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('Table not found!');
        return;
    }

    // Identify "Action" column index
    let actionColumnIndex = -1;
    const headers = table.querySelectorAll('thead th');
    headers.forEach((th, index) => {
        if (th.textContent.trim().toLowerCase() === 'action') {
            actionColumnIndex = index;
        }
    });

    // Hide "Action" column before generating report
    const rows = table.querySelectorAll('tr');
    let hiddenCells = [];

    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        if (actionColumnIndex !== -1 && cells[actionColumnIndex]) {
            hiddenCells.push(cells[actionColumnIndex]);
            cells[actionColumnIndex].style.display = 'none';
        }
    });

    // Convert all QR Code SVGs into canvas elements (for proper html2canvas capture)
    const qrCodes = table.querySelectorAll('svg');
    const qrCanvasMap = new Map();

    qrCodes.forEach((svg, index) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const svgData = new XMLSerializer().serializeToString(svg);
        const img = new Image();

        img.src = 'data:image/svg+xml;base64,' + btoa(svgData);
        img.onload = () => {
            canvas.width = svg.clientWidth;
            canvas.height = svg.clientHeight;
            ctx.drawImage(img, 0, 0);
            qrCanvasMap.set(svg, canvas);
            svg.replaceWith(canvas);
        };
    });

    // Add loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.innerHTML = `
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
        background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.5);
        z-index: 1000;">
        <i class="fas fa-spinner fa-spin"></i> Generating report...
        </div>`;
    document.body.appendChild(loadingDiv);

    // Wait for QR code replacements before capturing
    setTimeout(() => {
        html2canvas(table, {
            scale: 2,
            backgroundColor: '#ffffff',
            logging: false,
            useCORS: true
        }).then(canvas => {
            // Restore "Action" column visibility
            hiddenCells.forEach(cell => {
                cell.style.display = '';
            });

            // Restore QR Codes (replace canvas with original SVGs)
            qrCanvasMap.forEach((canvas, svg) => {
                canvas.replaceWith(svg);
            });

            // Remove loading indicator
            document.body.removeChild(loadingDiv);

            // Create and trigger download
            const link = document.createElement('a');
            link.download = `${reportName}_${new Date().toISOString().slice(0,10)}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }).catch(err => {
            console.error('Error generating report:', err);
            document.body.removeChild(loadingDiv);
            alert('Error generating report. Please try again.');
        });
    }, 1000); // Short delay to ensure QR codes are replaced
}


function applyLostFilter(field, value, tableId) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName("tr");

    // Skip header row
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        let match = false;

        // Get all cell content, including nested elements
        const cellText = row.textContent || row.innerText;

        // Case insensitive search
        if (cellText.toLowerCase().includes(value.toLowerCase())) {
            row.style.display = "";
            match = true;
        }

        if (!match) {
            row.style.display = "none";
        }
    }

    document.getElementById("lostDropdown").classList.remove("show");
}

function getLostColumnIndexForField(field) {
    // Map database fields to table column indices based on the actual table structure
    const columnMap = {
        'ITEM_CATEGORY': 1, // Index where category appears in the table
        'ITEM_COLOR': 2,    // Index where color appears in the table
        'FLOOR_NUMBER': 3,  // Index where floor appears in the table
        'ROOM_NUMBER': 3    // Index where room appears in the table
    };
    return columnMap[field] || 0;
}

function debugLostTableStructure(tableId) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error(`Table with ID ${tableId} not found`);
        return;
    }

    const headerRow = table.querySelector('thead tr');
    if (headerRow) {
        const headers = headerRow.cells;
        console.log('Table headers:');
        for (let i = 0; i < headers.length; i++) {
            console.log(`Column ${i}: ${headers[i].textContent.trim()}`);
        }
    }

    const firstDataRow = table.querySelector('tbody tr');
    if (firstDataRow) {
        const cells = firstDataRow.cells;
        console.log('First row data:');
        for (let i = 0; i < cells.length; i++) {
            console.log(`Column ${i}: ${cells[i].textContent.trim()}`);
        }
    }
}


// HISTORY DROPDOWN FUNCTION
const historyButton = document.getElementById("history");
historyButton.addEventListener("click", (e) => {
    const historyDropdown = document.getElementById("historyDropdown");
    e.preventDefault();
    historyDropdown.classList.toggle("active-dropdown");
});

// MATCHES BUTTON FUNCTION
const matchesButton = document.getElementById("matches");
matchesButton.addEventListener("click", (e) => {
    const matchesDropdown = document.getElementById("matchesDropdown");
    e.preventDefault();
    matchesDropdown.classList.toggle("active-dropdown");
});


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
    const surrenderedAtDORadio = document.getElementById("SudderenderedAtDO");
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
                surrenderedAtDORadio.checked = true;
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

    // Hide the form and cancel button, show the loading spinner
    form.style.display = "none";
    loadingSpinner.style.display = "block";
    cancelButton.style.display = "none";

    fetch('../data/form_handler_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        loadingSpinner.style.display = "none";
        if (data.includes("successfully")) {
            document.getElementById('successModal').style.display = 'block';
        } else {
            form.style.display = "block";
            cancelButton.style.display = "block";
            alert("Error: " + data);
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



// EDIT REPORT FUNCTION
function editReport(report) {
    document.getElementById("editReportModal").style.display = "block";

    document.getElementById('editItemId').value = report.report_id;
    document.getElementById('editItemImage').value = '';
    document.getElementById('itemImageEdit').src = report.ITEM_IMAGE;
    document.getElementById('itemImageEdit').style.display = 'block';
    document.getElementById('editStatusLost').checked = report.ITEM_STATUS.toLowerCase() === 'lost';
    document.getElementById('editStatusFound').checked = report.ITEM_STATUS.toLowerCase() === 'found';

    // Set the holding status based on the report data
    if (report.HOLDING_STATUS === 'Surrendered at D.O Office') {
        document.getElementById('editHstatusSurrendered').style.display = 'block';
        document.querySelector('#editHstatusSurrendered input[name="holding"]').checked = true;
    } else if (report.HOLDING_STATUS === 'Not yet retrieved') {
        document.getElementById('editHstatusUnclaimed').style.display = 'block';
        document.querySelector('#editHstatusUnclaimed input[name="holding"]').checked = true;
    }

    document.getElementById('editItemName').value = report.ITEM_NAME;
    document.getElementById('editItemCategory').value = report.ITEM_CATEGORY;
    document.getElementById('editItemColor').value = report.ITEM_COLOR;
    document.getElementById('editItemBrand').value = report.ITEM_BRAND;
    document.getElementById('editDescription').value = report.ITEM_DESCRIPTION;
    document.getElementById('editFloorNo').value = report.FLOOR_NUMBER;
    document.getElementById('editRoomNo').value = report.ROOM_NUMBER;
    document.getElementById('editReportDate').value = report.ITEM_DATE;
    document.getElementById('editReportTime').value = report.ITEM_TIME;
    document.getElementById('editUserEmail').value = report.email_add;

    // Trigger change event for status inputs to update holding status visibility
    const statusInputs = document.querySelectorAll('input[name="status"]');
    statusInputs.forEach(input => {
        if (input.value === report.ITEM_STATUS) {
            input.dispatchEvent(new Event('change'));
        }
    });

    console.log('Edit Report initialized with data:', report);
} 

// EDIT HOLDING STATUS FUNCTION
    const editHoldingStatus = () => {
    const statusInputs = document.querySelectorAll('input[name="status"]');
    const editfoundHoldingStatus = document.getElementById('editHstatusSurrendered');
    const editunclaimedHoldingStatus = document.getElementById('editHstatusUnclaimed');
    const storageRoomDiv = document.getElementById('storageRoomDiv');

    if (!statusInputs.length || !editfoundHoldingStatus || !editunclaimedHoldingStatus || !storageRoomDiv) {
        console.warn('Missing elements for editHoldingStatus function.');
        return;
    }

    statusInputs.forEach(input => {
        input.addEventListener('change', function () {
            if (this.value === 'found') {
                editfoundHoldingStatus.style.display = 'block';
                editunclaimedHoldingStatus.style.display = 'none';
                storageRoomDiv.style.display = 'block';
            } else {
                editfoundHoldingStatus.style.display = 'none';
                editunclaimedHoldingStatus.style.display = 'block';
                storageRoomDiv.style.display = 'none';
            }

            // Clear any previously selected holding status radio buttons
            document.querySelectorAll('input[name="holding"]').forEach(radio => {
                radio.checked = true;
            });

            console.log('Holding status updated for:', this.value);
        });
    });
};

// Run the function after the DOM has loaded
document.addEventListener("DOMContentLoaded", editHoldingStatus);

// EDIT IMAGE PREVIEW FUNCTION
function editImage(event) {
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('itemImageEdit').src = e.target.result;
        document.getElementById('itemImageEdit').style.display = 'block';
    };
    reader.readAsDataURL(event.target.files[0]);
}

// EDIT FLOOR AND ROOM NUMBER FUNCTION
function editlocSelect() {
    var selectRoom = document.getElementById('editRoomNo');
    var selectFloor = document.getElementById('editFloorNo').value;
    var otherRoomInput = document.getElementById('editotherRoomInput');

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
            option.value = room;
            option.textContent = room;
            selectRoom.appendChild(option);
        });
    }

    // Hide the "Other" input box initially
    otherRoomInput.style.display = 'none';
    otherRoomInput.value = ''; // Reset value
}

// Show input box if "Other" is selected
function editcheckOtherRoom() {
    var selectRoom = document.getElementById('editRoomNo');
    var otherRoomInput = document.getElementById('editotherRoomInput');

    if (selectRoom.value === "Other") {
        otherRoomInput.style.display = 'block';
        otherRoomInput.required = true;
    } else {
        otherRoomInput.style.display = 'none';
        otherRoomInput.required = false;
    }
}


// SUBMIT EDIT FORM FUNCTION
function submitEditForm(event) {
    event.preventDefault();

    const form = document.getElementById("editForm");
    const editloadingSpinner = document.getElementById("editloadingSpinner");
    const editcancelButton = document.getElementById("editcancelButton");

    const formData = new FormData(event.target);
    formData.append('submit', 'true');

    const fileInput = document.getElementById('editItemImage');
    if (fileInput.files.length === 0) {
        const existingImagePath = document.getElementById('itemImageEdit').src;
        formData.append('existingImage', existingImagePath);
    }

    form.style.display = "none";
    editloadingSpinner.style.display = "block";
    editcancelButton.style.display = "none";

    fetch('../data/edit_form_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {

        editloadingSpinner.style.display = "none";

        if (data.success) {
            document.getElementById('editsuccessModal').style.display = 'block';
        } else {
            form.style.display = "block";
            editcancelButton.style.display = "block";
            alert("Error: " + data.message);
        }
    })
    .catch(error => {
        editloadingSpinner.style.display = "none";
        form.style.display = "block";
        editcancelButton.style.display = "block";
        console.error('Error:', error);
        alert('An error occurred while submitting the report');
    });
}

// CLOSE SUCCESS MODAL
function closeEditSuccessModal() {
    document.getElementById('editsuccessModal').style.display = 'none';
    document.getElementById('editReportModal').style.display = 'none';
}



// VERFIY FOUND ITEM FUNCTION
let selectedReport = null;
function verifyReport(report) {
    console.log("Selected Report:", report); // Debugging log
    selectedReport = report;
    document.getElementById("verifyModal").style.display = "block";
}

// CONFIRM VERIFICATION FUNCTION
function confirmVerification() {
    let storageRoom = document.getElementById("storageRoom").value.trim();

    if (!selectedReport) {
        console.error("No report selected for verification.");
        alert("Error: No report selected.");
        return;
    }

    if (storageRoom === "") {
        alert("Please select a storage room.");
        return;
    }

    fetch("../data/update_verification.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `report_id=${selectedReport.report_id}&holding_status=Surrendered&storage_location=${encodeURIComponent(storageRoom)}`
    })
    .then(response => response.text())  // Change to .text() for debugging
    .then(text => {
        console.log("Raw Response:", text);  // Debugging step
        let data;
        try {
            data = JSON.parse(text);  // Convert to JSON
        } catch (error) {
            console.error("JSON Parse Error:", error);
            return;
        }

        if (data.status === "success") {
            updateVerifiedTable(selectedReport, storageRoom, data.updated_qr);
            document.getElementById("verifyModal").style.display = "none";
        } else {
            console.error("Error updating verification:", data.message);
            alert("Verification failed: " + data.message);
        }
    })
    .catch(error => {
        console.error("Fetch Error:", error);
        alert("Network error. Please try again.");
    });
}

// Function to update the verified table after confirmation
function updateVerifiedTable(report, storageRoom, qrCode) {
    let tableRows = document.querySelectorAll("#unverifiedrecentReportsTable tbody tr");
    
    tableRows.forEach(row => {
        let reportIdCell = row.cells[0]; // First column contains the report ID
        if (reportIdCell.innerText.includes(report.report_id)) {
            row.remove(); // Remove the row from the unverified table
        }
    });

    let foundHistoryTable = document.querySelector("#foundhistory tbody");

    if (foundHistoryTable) {
        let newRow = foundHistoryTable.insertRow();
        newRow.innerHTML = `
            <td>
                <img src='${qrCode}' height='100px' width='100px' alt='Updated QR CODE'>
                <br>${report.report_id}
            </td>
            <td>
                <img src='${report.ITEM_IMAGE}' height='100px' width='100px' alt='Item Image'>
                <br>${report.ITEM_NAME}
            </td>
            <td>${report.ITEM_STATUS}</td>
            <td>Surrendered</td>
            <td>${report.ITEM_DATE} ${report.ITEM_TIME}</td>
            <td>${report.email_add}</td>
            <td><span class='w3-text-green'><strong>Verified</strong></span></td>
            <td>
                <div class='button-container'>
                    <button class='w3-btn w3-blue w3-round' onclick='viewFoundReport(${JSON.stringify(report)})'><i class='fa fa-eye'></i> View</button>
                    <button class='w3-btn w3-orange w3-round' onclick='editReport(${JSON.stringify(report)})'><i class='fa fa-edit'></i> Edit</button>
                    <button class='w3-btn w3-light-green w3-round' onclick='printReport(${report.report_id})'><i class='fa fa-print'></i> Print</button>
                    <button class='w3-btn w3-red w3-round' onclick='archiveReport(${report.report_id})'><i class='fa fa-archive'></i> Archive</button>
                </div>
            </td>
        `;
    }
}



// UNVERIFIED EMAIL NOTIF FUNCTION
function notifyReport(report, button) {
    if (!button) return;

    // Store the original button content
    let originalContent = button.innerHTML;

    // Show loading animation inside the button
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Sending...`;
    button.disabled = true;

    // Prepare data to send to the PHP script
    let reportData = {
        report_id: report.report_id,
        email_add: report.email_add,
        item_name: report.ITEM_NAME
    };

    // Send an AJAX request to notify the user via email
    fetch("../data/send_notification.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(reportData)
    })
    .then(response => response.json())
    .then(data => {
        let message = (data.status === "success") 
            ? "Notification sent successfully." 
            : "Failed to send notification.";

        // Update the modal content
        document.getElementById("notifyMessage").innerText = message;
        document.getElementById("notifyModal").style.display = "block";
    })
    .catch(error => {
        // Handle network errors
        document.getElementById("notifyMessage").innerText = "Error sending notification.";
        document.getElementById("notifyModal").style.display = "block";
    })
    .finally(() => {
        // Restore the button to its original state
        button.innerHTML = originalContent;
        button.disabled = false;
    });
}



// VIEW FOUND REPORT MODAL FUNCTION
function viewFoundReport(report) {
    // Get the modal content element
    const modalContent = document.getElementById('viewFoundContent');

    // Populate the modal with the report details
    modalContent.innerHTML = `
        <h2 class="w3-padding" style="font-family: 'Encode Sans Condensed';">Report Found Item Details</h2>
        <div class="view-info-container">

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
                    <p> ${report.ITEM_COLOR}</p>
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
                    <p style="margin-bottom: 0px;"><strong>Location where the item found:</strong></p>
                </div>

                <div class="w3-col s6">
                    <p style="margin-bottom: 0px;"><strong>Date and Time when did the found:</strong></p>
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

            <div class="w3-row-padding">
                <div class="w3-half">
                    <label><strong>Storage Room Located:</strong></label>
                    <p>${report.STORAGE_LOCATION}</p>
                </div>
            </div>

        </div>
    `;

    // Display the modal
    document.getElementById('viewFound').style.display = 'block';
}

// VIEW LOST REPORT MODAL FUNCTION
function viewLostReport(report) {
    // Get the modal content element
    const modalContent = document.getElementById('viewLostContent');

    // Populate the modal with the report details
    modalContent.innerHTML = `
        <h2 class="w3-padding" style="font-family: 'Encode Sans Condensed';">Report Found Item Details</h2>
        <div class="view-info-container">

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
                    <p> ${report.ITEM_COLOR}</p>
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
                    <p style="margin-bottom: 0px;"><strong>Location where the item lost:</strong></p>
                </div>

                <div class="w3-col s6">
                    <p style="margin-bottom: 0px;"><strong>Date and Time when did the lost:</strong></p>
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
                    <label><strong>Date Lost:</strong></label>
                    <p>${report.ITEM_DATE}</p>
                </div>   
                
                <div class="w3-col s3">
                    <label><strong>Time Lost:</strong></label>
                    <p>${report.ITEM_TIME}</p>
                </div>  
            </div>

            

        </div>
    `;

    // Display the modal
    document.getElementById('viewLost').style.display = 'block';
}

// PRINT REPORT
function printReport(reportId) {
    // Open the report in a new tab
    const reportWindow = window.open(`../data/details.php?report_id=${reportId}`, '_blank');
    reportWindow.focus();
    reportWindow.print();
}



// ARCHIVE FUNCTION
let archiveReportId;
function archiveReport(reportId) {
    archiveReportId = reportId;
    document.getElementById('archiveModal').style.display = 'block';
}

function confirmArchive() {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../data/archive_report.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            console.log("Response: ", xhr.responseText); // Debugging
            if (xhr.responseText.trim() === "success") {
                // Find and remove the row from the table
                let row = document.getElementById("report-row-" + archiveReportId);
                if (row) {
                    row.remove();
                } else {
                    console.warn("Row not found for report ID: " + archiveReportId);
                }
                // Reload the archive section
                reloadArchiveSection();
                closeArchiveModal();
                openSuccessModal();
            } else {
                closeArchiveModal();
                openErrorModal();
            }
        }
    };
    xhr.send("report_id=" + archiveReportId);
}

function closeArchiveModal() {
    document.getElementById('archiveModal').style.display = 'none';
}


// RETRIEVE FUNCTION
let retrieveReportId;
function retrieveReport(reportId) {
    retrieveReportId = reportId;
    document.getElementById('retrieveModal').style.display = 'block';
}

function confirmRetrieve() {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../data/retrieve_report.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            if (xhr.responseText === "success") {
                // Remove the row from the table
                let row = document.getElementById("report-row-" + retrieveReportId);
                if (row) {
                    row.remove();
                } else {
                    console.warn("Row not found for report ID: " + retrieveReportId);
                }
                // Reload the archive section
                reloadArchiveSection();
                closeRetrieveModal();
                openSuccessModal();
            } else {
                closeRetrieveModal();
                openErrorModal();
            }
        }
    };
    xhr.send("report_id=" + retrieveReportId);
}

function closeRetrieveModal() {
    document.getElementById('retrieveModal').style.display = 'none';
}

// ARCHIVE TABLE FUNCTION
function reloadArchiveSection() {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "../data/fetch_archive_section.php", true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("reportTableBody").innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}

// VIEW ARCHIVE REPORT
function viewArchiveReport(reportId) {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "../data/fetch_archive_report.php?report_id=" + reportId, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);

            if (response.success) {
                var report = response.report;

                // Get the modal content element
                const modalContent = document.getElementById('viewArchiveContent');

                // Populate the modal with the report details
                modalContent.innerHTML = `
                    <h2 class="w3-padding" style="font-family: 'Encode Sans Condensed';">Report Archive Item Details</h2>
                    <div class="view-info-container">

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
                                <p style="margin-bottom: 0px;">Location where the item was lost/found:</p>
                            </div>

                            <div class="w3-col s6">
                                <p style="margin-bottom: 0px;">Date and Time when the item was lost/found:</p>
                            </div>
                        </div>

                        <div class="w3-row-padding">
                            <div class="w3-col s3">
                                <label><strong>Floor Number:</strong></label>
                                <p>Floor ${report.FLOOR_NUMBER}</p>
                            </div>

                            <div class="w3-col s3">
                                <label><strong>Room Number:</strong></label>
                                <p>Room ${report.ROOM_NUMBER}</p>
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
                document.getElementById('viewArchive').style.display = 'block';
            } else {
                alert("Error: " + response.error);
            }
        }
    };
    xhr.send();
}




// POSSIBLE MATCHES VIEW REPORT
function viewReport(reportId) {
    fetch('../data/fetch_found_items.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ 'report_id': reportId })
    })
    .then(response => response.json())
    .then(data => {
        console.log("Fetched data:", data);

        let viewPossibleContent = document.getElementById('viewPossibleContent');
        viewPossibleContent.innerHTML = '';

        let userInfo = data.userInfo;
        let foundItems = data.foundItems;

        let content = `
            <h2 style="font-family: Encode Sans Condensed;">Reported Item Info</h2>
            <h3 style="font-family: Encode Sans Condensed;">Lost Item reported information:</h3>

            <div class="item-info-container">
                <p>Item Photo: <br> <img src="${userInfo.ITEM_IMAGE}" height="100px" width="100px" alt="Found Item"></p>
                <p>Item Name: ${userInfo.ITEM_NAME}</p>
                <p>Item Category: ${userInfo.ITEM_CATEGORY}</p>
                <p>Item Color: ${userInfo.ITEM_COLOR}</p>
                <p>Item Brand: ${userInfo.ITEM_BRAND}</p>
                <p>Floor No: ${userInfo.FLOOR_NUMBER}</p>
                <p>Room No: ${userInfo.ROOM_NUMBER}</p>
                <p>Report Date: ${userInfo.ITEM_DATE}</p>
                <p>Report Time: ${userInfo.ITEM_TIME}</p>
                <p>Reported By: ${userInfo.email_add}</p>
                <p class="description">Description: ${userInfo.ITEM_DESCRIPTION}</p>
            </div>

            <h3 style="font-family: Encode Sans Condensed;">Possible found items to match:</h3>
        `;

        if (foundItems.length === 0) {
            content += '<p>No found items available</p>';
        } else {
            content += `
            <div class="found-items-wrapper">
                ${foundItems.map(item => `
                <div style="display: flex;">
                    <div class="found-item-container">
                        <p>Item Photo: <br> <img src="${item.ITEM_IMAGE}" height="100px" width="100px" alt="Found Item"></p>
                        <p>Item Name: ${item.ITEM_NAME}</p>
                        <p>Item Category: ${item.ITEM_CATEGORY}</p>
                        <p>Item Color: ${item.ITEM_COLOR}</p>
                        <p>Item Brand: ${item.ITEM_BRAND}</p>
                        <p>Floor No: ${item.FLOOR_NUMBER}</p>
                        <p>Room No: ${item.ROOM_NUMBER}</p>
                        <p>Report Date: ${item.ITEM_DATE}</p>
                        <p>Report Time: ${item.ITEM_TIME}</p>
                        <p>Reported By: ${item.REPORTED_BY}</p>
                        <p class="description">Description: ${item.ITEM_DESCRIPTION}</p>
                    </div>

                    <div class="action-buttons-container">
                        <button class="action-button match-button" data-item='${JSON.stringify(item)}' data-report-id="${reportId}">Match</button>
                    </div>
                </div>`).join('')}
            </div>`;
        }

        viewPossibleContent.innerHTML = content;

        // Add event listeners for "Match" buttons
        document.querySelectorAll('.match-button').forEach(button => {
            button.addEventListener('click', function() {
                const item = JSON.parse(button.getAttribute('data-item'));
                const foundReportId = item.report_id;
                const lostReportId = button.getAttribute('data-report-id');

                console.log("Matching items:", { lostReportId, foundReportId });

                // Show loading animation
                button.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Matching...`;
                button.disabled = true;

                // Make AJAX call
                fetch('../data/update_match_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 'lost_report_id': lostReportId, 'found_report_id': foundReportId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMatchSuccessModal(); // Show modal instead of alert
                        document.getElementById('viewPossibleModal').style.display = 'none';

                        // Remove the matched report from the table
                        let matchedRow = document.querySelector(`#reportTableBody tr[data-report-id="${lostReportId}"]`);
                        if (matchedRow) {
                            matchedRow.remove();
                        }
                    } else {
                        alert('Error matching items: ' + data.error);
                    }
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    // Restore button state
                    button.innerHTML = 'Match';
                    button.disabled = false;
                });
            });
        });

        // Show modal
        document.getElementById('viewPossibleModal').style.display = 'block';
    })
    .catch(error => console.error('Error:', error));
}



// VIEW MODEL FOR MATCHED ITEMS
function viewMatchedItem(reportId) {
    fetch('../data/fetch_matched_items.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({ 'report_id': reportId })
    })
    .then(response => response.json())
    .then(response => {
        console.log("Response from server:", response); // Debugging line
        if (response.success) {
            const data = response.data;
            console.log("Data to display:", data); // Debugging line
            let content = `
                <h2 style="font-family: Encode Sans Condensed;">Matched Items Information</h2>
                <div class="matched-items-container">
                    <div class="lost-item-info">
                        <h3>Lost Item Details</h3>
                        <div class="item-details">
                            <p><strong>Item Photo:</strong> <img src="${data.lost_item_image || 'default.png'}" height="100" width="100" alt="Lost Item Image"></p>
                            <p><strong>QR Code:</strong> <img src="${data.lost_qr_code || 'default.png'}" height="100" width="100" alt="Lost QR Code"></p>
                            <p><strong>Reported by:</strong> ${data.reporter1_email_add || 'N/A'}</p>
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
                        <h3>Found Item Details</h3>
                        <div class="item-details">
                            <p><strong>Item Photo:</strong> <img src="${data.found_item_image || 'default.png'}" height="100" width="100" alt="Found Item Image"></p>
                            <p><strong>QR Code:</strong> <img src="${data.found_qr_code || 'default.png'}" height="100" width="100" alt="Found QR Code"></p>
                            <p><strong>Reported by:</strong> ${data.reporter2_email_add || 'N/A'}</p>
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
        } else {
            alert(response.error || "No matched items found.");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Failed to fetch matched items. Please try again.");
    });
}




// VERIFY LOST ITEM PROCESS
let selectedLostReport = null;
let selectedVerifyButton = null;
function verifyProcess(report, button) {
    selectedLostReport = report;
    selectedVerifyButton = button; // Store the clicked verify button
    document.getElementById("verifyProcessModal").style.display = "block";
}

function claimedLostItem() {
    if (!selectedLostReport || !selectedVerifyButton) {
        console.error("No report selected for verification.");
        return;
    }

    let yesButton = document.querySelector("#verifyProcessModal .w3-green");

    // Show loading animation inside the button
    let originalText = yesButton.innerHTML;
    yesButton.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Verifying...`;
    yesButton.disabled = true;

    fetch("../data/update_lost_status.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `report_id=${selectedLostReport.lost_report_id}&matched_with=${selectedLostReport.found_report_id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            // Update table row status
            // let row = selectedVerifyButton.closest("tr");
            // if (row) {
            //     row.cells[3].innerHTML = "<span class='w3-text-green'><strong>Matched</strong></span>";
            //     row.cells[4].innerHTML = "<span class='w3-text-green'><strong>Retrieved</strong></span>";
            // } else {
            //     console.error("Row not found for updating.");
            // }

            // Remove Verify button after confirmation
            selectedVerifyButton.remove();

            // Close modal
            document.getElementById("verifyProcessModal").style.display = "none";

            // Show success modal
            document.getElementById("verifySuccessModal").style.display = "block";
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
    })
    .finally(() => {
        // Restore button state
        yesButton.innerHTML = originalText;
        yesButton.disabled = false;
    });
}

function closeVerifySuccessModal() {
    document.getElementById("verifySuccessModal").style.display = "none";
}



// EMAIL NOTIF FOR LOST ITEM OWNER
function notifyLostUser(report, button) {
    if (!button) return;

    // Store the original button content
    let originalContent = button.innerHTML;

    // Show loading animation inside the button
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Sending...`;
    button.disabled = true;

    // Prepare data to send to the PHP script
    let reportData = {
        report_id: report.lost_report_id,  
        email_add: report.lost_email_add,  // Use correct field name
        item_name: report.lost_item_name  
    };
    
    // Send an AJAX request to notify the user via email
    fetch("../data/send_lost_notif.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(reportData)
    })
    .then(response => response.json())
    .then(data => {
        let message = (data.status === "success") 
            ? "Notification sent successfully." 
            : "Failed to send notification.";

        // Update the modal content
        document.getElementById("notifyLostMessage").innerText = message;
        document.getElementById("notifyLostUserModal").style.display = "block";
    })
    .catch(error => {
        // Handle network errors
        document.getElementById("notifyLostMessage").innerText = "Error sending notification.";
        document.getElementById("notifyLostUserModal").style.display = "block";
    })
    .finally(() => {
        // Restore the button to its original state
        button.innerHTML = originalContent;
        button.disabled = false;
    });
}



// REMATCH ITEM REPORT
function rematchReport(lostReportId, foundReportId, button) {
    // Store report IDs in the modal's button
    let confirmButton = document.querySelector("#rematchReportModal .w3-red");
    confirmButton.setAttribute("data-lost-report-id", lostReportId);
    confirmButton.setAttribute("data-found-report-id", foundReportId);

    // Show confirmation modal
    document.getElementById("rematchReportModal").style.display = "block";
}

function confirmRematch() {
    let confirmButton = document.querySelector("#rematchReportModal .w3-red");
    let lostReportId = confirmButton.getAttribute("data-lost-report-id");
    let foundReportId = confirmButton.getAttribute("data-found-report-id");

    if (!lostReportId || !foundReportId) {
        console.error("Missing lostReportId or foundReportId.");
        return;
    }

    console.log("Re-matching items:", { lostReportId, foundReportId });

    // Show loading animation inside the button
    let confirmButtonText = confirmButton.innerHTML;
    confirmButton.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Re-Matching...`;
    confirmButton.disabled = true;

    // Send AJAX request
    fetch("../data/rematch_status.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ lost_report_id: lostReportId, found_report_id: foundReportId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success modal
            document.getElementById("rematchSuccessModal").style.display = "block";
            document.getElementById("rematchReportModal").style.display = "none";

            // Properly remove the row from the table
            let matchedRow = document.querySelector(`tr[data-lost-id="${lostReportId}"][data-found-id="${foundReportId}"]`);
            if (matchedRow) {
                matchedRow.remove();
                console.log("Row removed successfully.");
            } else {
                console.error("Failed to find and remove the row.");
            }

        } else {
            alert("Error: " + (data.error || "Unknown error"));
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred while processing the rematch.");
    })
    .finally(() => {
        // Restore button state
        confirmButton.innerHTML = confirmButtonText;
        confirmButton.disabled = false;
    });
}

// Close success modal
function closeReMatchSuccessModal() {
    document.getElementById("rematchSuccessModal").style.display = "none";
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
    
    if (table.firstChild) {
        table.insertBefore(row, table.firstChild);
    } else {
        table.appendChild(row);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    loadArchivedUsers();
    
    document.querySelectorAll(".archive-btn").forEach(button => {
        button.addEventListener("click", function () {
            const row = this.closest("tr");
            const userId = row.cells[0].textContent.trim();
            archiveUser(row, userId);
        });
    });
});

function archiveUser(row, userId) {
    row.style.display = "none";
    let archivedUsers = JSON.parse(localStorage.getItem("archivedUsers")) || [];
    if (!archivedUsers.includes(userId)) {
        archivedUsers.push(userId);
    }
    localStorage.setItem("archivedUsers", JSON.stringify(archivedUsers));
}

function loadArchivedUsers() {
    let archivedUsers = JSON.parse(localStorage.getItem("archivedUsers")) || [];
    document.querySelectorAll("#usersrecentReportsTable tbody tr").forEach(row => {
        const userId = row.cells[0].textContent.trim();
        if (archivedUsers.includes(userId)) {
            row.style.display = "none";
        }
    });
}

function unarchiveUsers() {
    localStorage.removeItem("archivedUsers");
    document.querySelectorAll("#usersrecentReportsTable tbody tr").forEach(row => {
        row.style.display = "";
    });
}

document.addEventListener("DOMContentLoaded", function () {
    // Function to archive (hide) users
    document.querySelectorAll(".archive-btn").forEach(button => {
        button.addEventListener("click", function () {
            let row = this.closest("tr"); // Get the row
            let userId = this.getAttribute("data-id"); // Get user ID
            
            row.style.display = "none"; // Hide row

            // Store in localStorage for persistence
            let archivedUsers = JSON.parse(localStorage.getItem("archivedUsers")) || [];
            if (!archivedUsers.includes(userId)) {
                archivedUsers.push(userId);
                localStorage.setItem("archivedUsers", JSON.stringify(archivedUsers));
            }
        });
    });

    // Function to restore archived users
    window.unarchiveUsers = function () {
        let archivedUsers = JSON.parse(localStorage.getItem("archivedUsers")) || [];
        
        document.querySelectorAll("table tbody tr").forEach(row => {
            let userId = row.querySelector(".archive-btn")?.getAttribute("data-id");

            if (archivedUsers.includes(userId)) {
                row.style.display = ""; // Show row
            }
        });

        localStorage.removeItem("archivedUsers"); // Clear storage after restoring
    };

    // Hide archived users on page load
    let archivedUsers = JSON.parse(localStorage.getItem("archivedUsers")) || [];
    document.querySelectorAll("table tbody tr").forEach(row => {
        let userId = row.querySelector(".archive-btn")?.getAttribute("data-id");

        if (archivedUsers.includes(userId)) {
            row.style.display = "none"; // Hide archived users
        }
    });
});
