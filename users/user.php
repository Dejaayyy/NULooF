<?php
include("../data/db.php");
session_start();

if (!isset($_SESSION['user_info'])) {
    header("Location: ../users/index.php");
    exit;
}

$user_email = mysqli_real_escape_string($conn, $_SESSION['user_info']['email_add']);
$id_number = $_SESSION['user_info']['id_number'] ?? null;
$first_name = $_SESSION['user_info']['first_name'];
$last_name = $_SESSION['user_info']['last_name'];
$full_name = "$first_name $last_name";

if (!$id_number) {
    header("Location: ../index.php");
    exit;
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Modify SQL query based on filter selection
if ($filter === 'lost') {
    $home_query = "SELECT * FROM reports_table WHERE email_add='$user_email' AND ITEM_STATUS='lost' ORDER BY ITEM_DATE DESC";
} elseif ($filter === 'found') {
    $home_query = "SELECT * FROM reports_table WHERE email_add='$user_email' AND ITEM_STATUS='found' ORDER BY ITEM_DATE DESC";
} else {
    $home_query = "SELECT * FROM reports_table WHERE email_add='$user_email' ORDER BY ITEM_DATE DESC";
}

$home_result = mysqli_query($conn, $home_query) or die(mysqli_error($conn));


// Fetch all reports of the logged-in user
// $home_query = "SELECT * FROM reports_table WHERE email_add='$user_email'";
// $home_result = mysqli_query($conn, $home_query) or die(mysqli_error($conn));

// Fetch matched items
$matched_query = "SELECT
    a.ITEM_IMAGE AS lost_item_image,
    b.ITEM_IMAGE AS found_item_image,
    a.ITEM_NAME AS lost_item_name,
    b.ITEM_NAME AS found_item_name,
    a.report_id AS lost_report_id,
    b.report_id AS found_report_id,
    a.QR_CODE AS lost_qr_code,
    b.QR_CODE AS found_qr_code
FROM reports_table a
JOIN reports_table b ON a.matched_with = b.report_id
WHERE a.match_status = 'matched'
AND b.match_status = 'matched'
AND (
    (a.email_add = ? AND a.ITEM_STATUS = 'lost') OR
    (b.email_add = ? AND b.ITEM_STATUS = 'found')
)
ORDER BY a.report_id DESC";

$stmt = $conn->prepare($matched_query);
$stmt->bind_param("ss", $user_email, $user_email);
$stmt->execute();
$matched_result = $stmt->get_result();

mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet" href="../styles/user.css" />
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=BenchNine:wght@300;400;700&family=Encode+Sans+Condensed:wght@100;200;300;400;500;600;700;800;900&family=Homenaje&display=swap" rel="stylesheet">

    <link href="https://fonts.cdnfonts.com/css/haettenschweiler" rel="stylesheet">

    <link rel="icon" type="../bg/NU.png" href="../bg/NU.png">
    <title>User | NULooF Dashboard</title>
</head>

<body>
    <!-- LEFT SIDE CONTENT -->
    <div class="w3-sidebar w3-bar-block w3-collapse w3-card w3-animate-left" id="mySidebar" style="width:20%">
        <button class="w3-bar-item w3-button w3-large w3-hide-large" onclick="w3_close()">Close &times;</button>
        <div class="logo">
            <img style="width: 50px; height: 50px; margin-right: 10px;" src="../bg/NU.png" alt="Logo">
            <div class="logo-text">
                <span class="title">NU LOOF</span>
                <span class="subtitle">LOST AND FOUND MANAGEMENT SYSTEM</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="#home"><i class="fas fa-home"></i> HOME</a>

            <a href="#matcheditems"><i class="fas fa-check"></i> MATCHED ITEMS</a>
        </div>
    </div>

    <!-- RIGHT SIDE CONTENT -->
    <div class="w3-main" style="margin-left:20%">

        <!-- HEADER CONTENT -->
        <div class="w3-container-header">
            <div class="dropdown">
            <span><i class="fa fa-user"></i><?php echo $user_email; ?></span>
            <i class="fas fa-caret-down" id="dropdown-toggle"></i>
                <div class="dropdown-content">
                <a href="#" onclick="showLogoutModal(); return false;">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </a>
                </div>
            </div>
            
            <!-- HAMBURGER TOGGLE BUTTON -->
            <button class="w3-button w3-xlarge w3-hide-large" onclick="w3_open()">&#9776;</button>
        </div>

        <!-- HOME SECTION -->
        <section id="home">
            <!-- Top Content -->
            <div class="w3-cell-row">

                <!-- GREETINGS -->
                <div class="w3-cell">
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Hi, <?php echo $full_name; ?></h1>
                    <p style="font-size: larger;">Did you find something, or something is lost?</p>
                </div>

                <!-- REPORT ITEM BUTTON -->
                <div class="w3-cell" style="display: flex; justify-content: flex-end; align-items: center;">
                    <button onclick="document.getElementById('id01').style.display='block'"
                        class="create-report-btn">
                        <i class="fa fa-file-upload"></i> Create new report
                    </button>
                </div>

            </div>

            <!-- Container to align h2 and filter dropdown -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-family: Encode Sans Condensed; margin: 0;">Recent Report</h2>
                
                <div class="d-flex align-items-center">
                    <label for="reportFilter" class="me-2 fw-bold">Filter Reports:</label>
                    <select id="reportFilter" class="form-select w-auto" onchange="filterReports()">
                        <option value="all" <?php echo ($filter === 'all') ? 'selected' : ''; ?>>All Reports</option>
                        <option value="lost" <?php echo ($filter === 'lost') ? 'selected' : ''; ?>>Lost Reports</option>
                        <option value="found" <?php echo ($filter === 'found') ? 'selected' : ''; ?>>Found Reports</option>
                    </select>
                </div>
            </div>


            <!-- TABLE CONTENT -->
            <table id="recentReportsTable">
                <thead>
                    <tr>
                        <th>Ticket No. and QR Code</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Color</th>
                        <th>Brand</th>
                        <th>Floor and Room</th>
                        <th>Date and Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if (mysqli_num_rows($home_result) > 0) {
                            while ($row = mysqli_fetch_assoc($home_result)) {
                                echo "<tr>";

                                echo "<td>";
                                $qrCodePath = htmlspecialchars($row['QR_CODE']);
                                echo "<img src='$qrCodePath' height='100px' width='100px' alt='QR CODE'>";
                                echo "<br>";
                                echo htmlspecialchars($row['report_id']);
                                echo "</td>";

                                echo "<td>";
                                $imagePath = htmlspecialchars($row['ITEM_IMAGE']);
                                echo "<img src='$imagePath' height='100px' width='100px' alt='Image'>";
                                echo "<br>";
                                echo htmlspecialchars($row['ITEM_NAME']);
                                echo "</td>";

                                echo "<td>" . htmlspecialchars($row['ITEM_STATUS']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['ITEM_COLOR']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['ITEM_BRAND']) . "</td>";                                
                                echo "<td>" . htmlspecialchars($row['FLOOR_NUMBER']) . " Floor " . ", " . htmlspecialchars($row['ROOM_NUMBER']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['ITEM_DATE']) . " " . htmlspecialchars($row['ITEM_TIME']) . "</td>";
                                
                                echo "<td>
                                    <button class='w3-btn w3-blue w3-round' onclick='viewItemReport(" . json_encode($row) . ")'><i class='fa fa-eye'></i> View</button>
                                    <button class='w3-btn w3-light-green w3-round' onclick='printReport(" . $row['report_id'] . ")'><i class='fa fa-print'></i> Print</button>
                                </td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='11'>You have no recent report</td></tr>";
                        }
                    ?>
                </tbody>
            </table>

            <!-- VIEW ITEM MODAL -->
            <div id="viewItem" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom modal-fixed-size">
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('viewItem').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;</span>
                    </div>
                    <div class="w3-container" id="viewItemContent">
                        <!-- Content will be dynamically inserted here -->
                    </div>
                </div>
            </div>

        </section>

        <!-- MATCHED ITEM SECTION -->
        <section id="matcheditems">
            <!-- Top Content -->
            <div class="w3-cell-row" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="w3-cell" style="width: 30%;">
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Matched item history</h1>
                    <p style="font-size: larger;">This page consist of records of your reported matched items</p>
                </div>
                <div class="w3-cell" style="width: 70%; display: flex; justify-content: flex-end; align-items: center; gap: 20px;">
                    <button onclick="document.getElementById('id01').style.display='block'" class="w3-button w3-yellow w3-large">
                        <i class="fa fa-file-upload"></i>Report Item
                    </button>
                </div>
            </div>

            <!-- Table Content -->
            <h2 style="font-family: Encode Sans Condensed;">List of reported matched items</h2>
            <table id="recentReportsTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Ticket No. & QR Code (Lost Item)</th>
                        <th>Ticket No. & QR Code (Found Item)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($matched_result) > 0) {
                        while ($row = mysqli_fetch_assoc($matched_result)) {
                            echo "<tr>";

                            echo "<td>";
                            $itemImage = htmlspecialchars($row['lost_item_image']);
                            echo "<img src='$itemImage' height='100px' width='100px' alt='Image'>";
                            echo "<br>";
                            echo htmlspecialchars($row['lost_item_name']);
                            echo "</td>";

                            echo "<td>";
                            $qrCodePathLost = htmlspecialchars($row['lost_qr_code']);
                            echo "<img src='$qrCodePathLost' height='100px' width='100px' alt='QR CODE'>";
                            echo "<br>";
                            echo htmlspecialchars($row['lost_report_id']);
                            echo "</td>";

                            echo "<td>";
                            $qrCodePathFound = htmlspecialchars($row['found_qr_code']);
                            echo "<img src='$qrCodePathFound' height='100px' width='100px' alt='QR CODE'>";
                            echo "<br>";
                            echo htmlspecialchars($row['found_report_id']);
                            echo "</td>";

                            echo "<td>Matched</td>";
                            echo "<td><button class='view-btn w3-button w3-blue' onclick='viewMatchedItem(" . $row['lost_report_id'] . ")'><i class='fa fa-eye'></i> View</button></td>";

                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>You have no matched items</td></tr>";
                    }
                    ?>
                </tbody>

            </table>

            <!-- VIEW MATCHED ITEM MODAL -->
            <div id="viewMatch" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom">
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('viewMatch').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;</span>
                    </div>
                    <div class="w3-container" id="viewMatchContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
            </div>

        </section>

        <!-- CREATE REPORT FORM  -->
        <div class="w3-container">
            <div id="id01" class="w3-modal">
            <div class="w3-modal-content w3-card w3-animate-zoom">

                <!-- CANCEL BUTTON -->
                <div id="cancelButton" class="w3-center"><br>
                    <span
                        onclick="document.getElementById('id01').style.display='none'" class="w3-button w3-circle w3-xlarge w3-display-topright" style="background-color: transparent;">&times;
                    </span>
                </div>

                <!-- REPORT FORM -->
                <form action="form_handler.php" method="POST" id="reportForm" onsubmit="submitReport(event)" enctype="multipart/form-data" class="w3-container" style="font-family: 'Encode Sans Condensed';">
                    <h2 class="w3-padding" style="font-family: 'Encode Sans Condensed';">Report Item</h2>
                    <p class="w3-padding" style="font-family: 'Encode Sans Condensed';">Kindly fill up this form to provide the description of the item</p>

                    <!-- STATUS OF ITEM -->
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="itemImage"><b>Upload Image:</b></label>
                            <input type="file" id="itemImage" name="itemImage" accept="image/*" onchange="previewImage(event)">
                            <img id="itemImagePreview" height="100" width="100" style="display: none;">
                        </div>

                        <div class="w3-half" style="margin-bottom: 16px;">
                            <!-- STATUS OF ITEM -->
                            <label style="display: block; font-weight: bold;">Item Condition:</label>
                            <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 16px;">
                                <label>
                                    <input class="w3-radio" type="radio" name="status" value="lost" required>
                                    Lost
                                </label>
                                <label>
                                    <input class="w3-radio" type="radio" name="status" value="found" required>
                                    Found
                                </label>
                            </div>

                            <!-- HOLDING STATUS -->
                            <label style="display: block; font-weight: bold;">Item Status:</label>

                            <!-- HOLDING STATUS -->
                            <div id="foundHoldingStatus" style="display: none;">
                                <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                                    <label>
                                        <input class="w3-radio" type="radio" name="holding" value="Did not surrender yet">
                                        Did not surrender yet
                                    </label>
                                    <label>
                                        <input class="w3-radio" type="radio" name="holding" value="Surrendered at D.O Office">
                                        Surrendered at D.O. Office
                                    </label>
                                </div>
                            </div>

                            <!-- For Lost Items -->
                            <div id="lostHoldingStatus" style="display: none;" class="holding-options">
                                <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                                    <label>
                                        <input class="w3-radio" type="radio" name="holding" value="Not yet retrieved" id="notYetRetrieved">
                                        Not yet retrieved
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ITEM NAME AND CATEGORY -->
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="itemName"><b>Item Name:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="text" id="itemName" name="itemName" required>
                        </div>

                        <div class="w3-half">
                            <label for="itemCategory"><b>Item Category:</b></label>
                            <select class="w3-select" name="itemCategory" id="itemCategory" onchange="toggleInput(this, 'customCategoryInput')" required>
                                <option value="" disabled selected>Select Category</option>
                                <option value="Footwear">Footwear</option>
                                <option value="Clothes">Clothes</option>
                                <option value="Electronics/Gadgets">Electronics/Gadgets</option>
                                <option value="Accessories">Accessories</option>
                                <option value="Document">Documents</option>
                                <option value="School Items">School Items</option>
                                <option value="Household Items">Household Items</option>
                                <option value="Others">Others</option>
                            </select>
                            <input type="text" id="customCategoryInput" name="customCategory" class="w3-input" placeholder="Enter Category" style="display: none;">
                        </div>
                    </div>

                    <!-- ITEM COLOR -->
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="itemColor"><b>Item Color:</b></label>
                            <select class="w3-select" name="itemColor" id="itemColor" onchange="toggleInput(this, 'customColorInput')" required>
                                <option value="" disabled selected>Select Color</option>
                                <option value="Red">Red</option>
                                <option value="Orange">Orange</option>
                                <option value="Yellow">Yellow</option>
                                <option value="Green">Green</option>
                                <option value="Blue">Blue</option>
                                <option value="Violet">Violet</option>
                                <option value="Pink">Pink</option>
                                <option value="Gray">Gray</option>
                                <option value="Black">Black</option>
                                <option value="White">White</option>
                                <option value="Brown">Brown</option>
                                <option value="Others">Others</option>
                            </select>
                            <input type="text" id="customColorInput" name="customColor" class="w3-input" placeholder="Enter Color" style="display: none;">
                        </div>

                        <!-- ITEM BRAND -->
                        <div class="w3-half">
                            <label for="itemBrand"><b>Item Brand:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="text" id="itemBrand" name="itemBrand" required>
                        </div>
                    </div>

                    <!-- ITEM DESCRIPTION -->
                    <div class="w3-row-padding">
                        <div class="w3-col" style="width: 100%;">
                            <label for="description"><b>Item Description:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" id="description" name="description" required>
                        </div>
                    </div>

                    <!-- LABELS -->
                    <div class="w3-row-padding" style="margin-bottom: 0px;">
                        <!-- Where did the item lost/found? -->
                        <div class="w3-col s6" style="margin-bottom: 0px;">
                            <p style="font-weight:bold; font-family: 'Encode Sans Condensed'; margin-bottom: 0px;">Where did the item lost/found?</p>
                        </div>

                        <!-- When did the item lost/found? -->
                        <div class="w3-col s6" style="margin-bottom: 0px;">
                            <p style="font-weight:bold; font-family: 'Encode Sans Condensed'; margin-bottom: 0px;">When did the item lost/found?</p>
                        </div>
                    </div>

                    <!-- LOCATION, DATE, AND TIME -->
                    <div class="w3-row-padding">
                        <!-- FLOOR NUMBER -->
                        <div class="w3-col s3">
                            <label for="floorNo"><b>Floor No.:</b></label>
                            <select class="w3-select" onchange="locSelect()" name="floorNo" id="floorNo" required>
                                <option value="" disabled selected>Select Floor</option>
                                <option value="LG">LG</option>
                                <option value="UG">UG</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>

                        <!-- ROOM NUMBER -->
                        <div class="w3-col s3">
                            <label for="roomNo"><b>Room No.:</b></label>
                            <select class="w3-select" id="roomNo" name="roomNo" required onchange="checkOtherRoom()">
                                <option value="" disabled selected>Select Room</option>
                            </select>
                            <input type="text" id="otherRoomInput" name="otherRoom" class="w3-input" placeholder="Enter Room Name" style="display:none;">
                        </div>

                        <!-- DATE ITEM LOST -->
                        <div class="w3-col s3">
                            <label for="reportDate"><b>Date</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="date" id="reportDate" name="reportDate" required>
                        </div>

                        <!-- TIME ITEM LOST -->
                        <div class="w3-col s3">
                            <label for="reportTime"><b>Time</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="time" id="reportTime" name="reportTime" required>
                        </div>
                        
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="w3-bar w3-section w3-padding">
                        <input type="submit" name="submit" class="w3-btn w3-ripple w3-pale-green w3-round-large w3-padding w3-right" value="Submit">
                        
                        <button type="button"
                            onclick="closePopup()"
                            class="w3-btn w3-ripple w3-pale-red w3-round-large w3-padding w3-right"
                            style="margin-right: 10px;">
                            Cancel
                        </button>
                    </div>
                </form>

                <!-- LOADING SPINNER (SHOWS WHEN SUBMITTING) -->
                <div id="loadingSpinner" class="w3-center" style="display: none; padding: 10px;">
                    <i class="w3-spin fa fa-spinner fa-2x"></i>
                    <p style="font-family: 'Encode Sans Condensed'">Processing...</p>
                </div>

                <!-- SUCCESS MODAL -->
                <div id="successModal" class="w3-modal">
                    <div class="w3-modal-content w3-card w3-animate-zoom">
                        <div class="w3-container">
                            <span onclick="document.getElementById('successModal').style.display='none'" class="w3-button w3-circle w3-xlarge w3-display-topright">&times;</span>
                            <h2 class="w3-center" style="font-family: 'Encode Sans Condensed'">Success!</h2>
                            <p class="w3-center" style="font-family: 'Encode Sans Condensed'">Your report has been submitted successfully.</p>
                            <button class="w3-button w3-green w3-round-large w3-block" onclick="closeSuccessModal()">OK</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        </div>

        <!-- LOGOUT MODAL -->
        <div id="logoutModal" class="w3-modal" style="display: none;">
            <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">
                
                <!-- Modal Header -->
                <header class="w3-container w3-center" style="padding: 16px; font-size: 18px;">
                    <h2 style="margin: 0;">Confirm Logout</h2>
                </header>

                <!-- Modal Body -->
                <div class="w3-container w3-padding w3-center">
                    <p style="font-size: 16px; color: #444;">Are you sure you want to log out?</p>
                </div>

                <!-- Modal Footer -->
                <footer class="w3-container w3-padding w3-center" style="display: flex; gap: 10px; justify-content: center;">
                    <button class="w3-button w3-round w3-red w3-hover-dark-red" onclick="confirmLogout()" style="padding: 10px 20px;">Yes</button>
                    <button class="w3-button w3-round w3-gray w3-hover-dark-gray" onclick="closeLogoutModal()" style="padding: 10px 20px;">Cancel</button>
                </footer>
            </div>
        </div>


    </div>

    <script>
        function w3_open() {
            document.getElementById("mySidebar").style.display = "block";
        }

        function w3_close() {
            document.getElementById("mySidebar").style.display = "none";
        }

        // Get today's date in YYYY-MM-DD format
        const today = new Date().toISOString().split('T')[0];
    
        // Set the max attribute for the date input
        document.getElementById("reportDate").setAttribute("max", today);

        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'block';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmLogout() {
            window.location.href = "logout.php"; // Redirect to logout
        }

        function filterReports() {
            let filter = document.getElementById('reportFilter').value;
            window.location.href = "user.php?filter=" + filter;
        }

    </script>

    <script src="../scripts/users.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.3/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

</body>
</html>

