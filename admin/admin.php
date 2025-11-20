<?php
session_start();
include('../data/db.php');

// Check if the admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../users/index.php");
    exit();
}

// Function to archive old reports
function archiveOldReports($conn) {
    // Calculate the date 2 years ago from today
    $exceedReport = date('Y-m-d', strtotime('-1 years'));

    // Update reports older than 2 years to 'archived'
    $archiveQuery = "UPDATE reports_table
                     SET status = 'archived'
                     WHERE ITEM_DATE < ?
                     AND status != 'archived'";

    if ($stmt = mysqli_prepare($conn, $archiveQuery)) {
        mysqli_stmt_bind_param($stmt, 's', $exceedReport);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing archive query: " . mysqli_error($conn));
    }
}

// Archive old reports
archiveOldReports($conn);

// Initialize counts and variables
$reports_exist = false;
$foundCount = 0;
$lostCount = 0;

$reportsPerPage = 4;

// Get the current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);  // Prevents going below page 1

// Calculate OFFSET
$offset = ($page - 1) * $reportsPerPage;

// Get total reports count
$totalReportsQuery = "SELECT COUNT(*) AS total FROM reports_table WHERE status != 'archived' AND match_status != 'matched'";
$totalReportsResult = mysqli_query($conn, $totalReportsQuery);
$totalReportsRow = mysqli_fetch_assoc($totalReportsResult);
$totalReports = $totalReportsRow['total'];

// Calculate total pages
$totalPages = max(ceil($totalReports / $reportsPerPage), 1);  // Ensure at least 1 page

// Fetch paginated reports
$recentReportsQuery = "SELECT 
            r.report_id, r.QR_CODE, r.ITEM_IMAGE, r.ITEM_NAME, r.ITEM_STATUS, r.HOLDING_STATUS,
            CONCAT(COALESCE(r.ITEM_DATE, ''), ' ', COALESCE(r.ITEM_TIME, '')) AS combined_datetime,
            COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
    FROM reports_table r
    LEFT JOIN user_info u ON r.email_add = u.email_add
    WHERE r.status != 'archived'
    AND r.match_status != 'matched'
    ORDER BY r.ITEM_DATE DESC, r.ITEM_TIME DESC
    LIMIT $reportsPerPage OFFSET $offset";

$recentReports = [];
if ($recent_result = mysqli_query($conn, $recentReportsQuery)) {
    while ($row = mysqli_fetch_assoc($recent_result)) {
        $recentReports[] = $row;
    }
    mysqli_free_result($recent_result);
}

// If fetchTable=true, return only table rows
if (isset($_GET['fetchTable'])) {
    foreach ($recentReports as $report): ?>
        <tr>
            <td><?php echo htmlspecialchars($report['report_id']); ?></td>
            <td><img src="<?php echo htmlspecialchars($report['QR_CODE']); ?>" height="100px" width="100px" alt="QR CODE"></td>
            <td><img src="<?php echo htmlspecialchars($report['ITEM_IMAGE']); ?>" height="100px" width="100px" alt="Item Image"></td>
            <td><?php echo htmlspecialchars($report['ITEM_NAME']); ?></td>
            <td><?php echo htmlspecialchars($report['ITEM_STATUS']); ?></td>
            <td><?php echo htmlspecialchars($report['HOLDING_STATUS']); ?></td>
            <td><?php echo htmlspecialchars($report['combined_datetime']); ?></td>
            <td><?php echo htmlspecialchars($report['email_add']); ?></td>
        </tr>
    <?php endforeach;
    exit;
}

// FOUND HISTORY QUERY
$foundQuery = "SELECT COUNT(*) AS count FROM reports_table 
               WHERE ITEM_STATUS = 'found' 
               AND status != 'archived' 
               AND VERIFIED_STATUS = 1
               AND match_status != 'matched'";

if ($found_result = mysqli_query($conn, $foundQuery)) {
    $row = mysqli_fetch_assoc($found_result);
    $foundCount = $row['count'];
    mysqli_free_result($found_result);
} else {
    echo "Error: " . mysqli_error($conn);
}

// FETCH FOUND REPORTS
$found_query = "SELECT r.report_id, r.QR_CODE, r.ITEM_IMAGE, r.ITEM_NAME, r.ITEM_STATUS, 
                       r.HOLDING_STATUS, r.ITEM_DATE, r.ITEM_TIME, r.FLOOR_NUMBER, r.ROOM_NUMBER, 
                       r.ITEM_CATEGORY, r.ITEM_COLOR, r.ITEM_BRAND, r.ITEM_DESCRIPTION, 
                       r.VERIFIED_STATUS, r.STORAGE_LOCATION, 
                       COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                FROM reports_table r
                LEFT JOIN user_info u ON r.email_add = u.email_add
                WHERE r.ITEM_STATUS = 'found' 
                AND r.status != 'archived' 
                AND r.VERIFIED_STATUS = 1
                AND r.match_status != 'matched'";

$found_result = $conn->query($found_query);

$foundItems = [];
if ($found_result->num_rows > 0) {
    while ($row = $found_result->fetch_assoc()) {
        $foundItems[] = $row;
    }
} else {
    $foundItems = [];
}


// LOST HISTORY QUERY
$lostQuery = "SELECT COUNT(*) AS count FROM reports_table 
              WHERE ITEM_STATUS = 'lost' 
              AND status != 'archived' 
              AND match_status != 'matched'";

if ($lost_result = mysqli_query($conn, $lostQuery)) {
    $row = mysqli_fetch_assoc($lost_result);
    $lostCount = $row['count'];
    mysqli_free_result($lost_result);
} else {
    echo "Error: " . mysqli_error($conn);
}

// FETCH LOST REPORTS
$lost_query = "SELECT r.report_id, r.QR_CODE, r.ITEM_IMAGE, r.ITEM_NAME, r.ITEM_STATUS, r.HOLDING_STATUS, 
                      r.ITEM_DATE, r.ITEM_TIME, r.FLOOR_NUMBER, r.ROOM_NUMBER, r.ITEM_CATEGORY, 
                      r.ITEM_COLOR, r.ITEM_BRAND, r.ITEM_DESCRIPTION, 
                      COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
               FROM reports_table r
               LEFT JOIN user_info u ON r.email_add = u.email_add
               WHERE r.ITEM_STATUS = 'lost' 
               AND r.status != 'archived' 
               AND r.match_status != 'matched'";


$lost_result = $conn->query($lost_query);

// Store fetched data
$lostItems = [];
if ($lost_result->num_rows > 0) {
    while ($row = $lost_result->fetch_assoc()) {
        $lostItems[] = $row;
    }
} else {
    $lostItems = [];  // No lost items found
}


// UNVERIFIED QUERY: Fetch only unverified reports count
$unverifiedQuery = "SELECT COUNT(*) AS count FROM reports_table 
                    WHERE ITEM_STATUS = 'found' 
                    AND status != 'archived' 
                    AND VERIFIED_STATUS = 0";

$unverifiedCount = 0; // Default to 0 in case no results
if ($result = $conn->query($unverifiedQuery)) {
    $row = $result->fetch_assoc();
    $unverifiedCount = $row['count'];
    $result->free();
}

// UNVERIFIED REPORTS QUERY
$unverified_query = "SELECT r.report_id, r.QR_CODE, r.ITEM_IMAGE, r.ITEM_NAME, r.ITEM_STATUS, r.HOLDING_STATUS, 
                            r.ITEM_DATE, r.ITEM_TIME, r.FLOOR_NUMBER, r.ROOM_NUMBER, r.ITEM_CATEGORY, 
                            r.ITEM_COLOR, r.ITEM_BRAND, r.ITEM_DESCRIPTION, r.VERIFIED_STATUS,
                            COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                     FROM reports_table r
                     LEFT JOIN user_info u ON r.email_add = u.email_add
                     WHERE r.ITEM_STATUS = 'found' 
                     AND r.status != 'archived' 
                     AND r.VERIFIED_STATUS = 0";


$unverified_result = $conn->query($unverified_query);
$unverifiedItems = [];
if ($unverified_result->num_rows > 0) {
    while ($row = $unverified_result->fetch_assoc()) {
        $unverifiedItems[] = $row;
    }
}

// POSSIBLE MATCH QUERY
$possible_match = "SELECT r.report_id,
                          r.QR_CODE,
                          r.ITEM_IMAGE,
                          r.ITEM_NAME,
                          r.ITEM_STATUS,
                          r.HOLDING_STATUS,
                          r.ITEM_DATE,
                          r.ITEM_TIME,
                          r.FLOOR_NUMBER,
                          r.ROOM_NUMBER,
                          r.ITEM_CATEGORY,
                          r.ITEM_COLOR,
                          r.ITEM_BRAND,
                          r.ITEM_DESCRIPTION,
                          COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                   FROM reports_table r
                   LEFT JOIN user_info u ON r.email_add = u.email_add
                   WHERE r.ITEM_STATUS = 'lost' 
                   AND r.status != 'archived'
                   AND r.match_status != 'matched'";


$possibleItems = [];
if ($possible_stmt = mysqli_prepare($conn, $possible_match)) {
    mysqli_stmt_execute($possible_stmt);
    $possible_result = mysqli_stmt_get_result($possible_stmt);
    while ($row = mysqli_fetch_assoc($possible_result)) {
        $possibleItems[] = $row;
    }
    mysqli_stmt_close($possible_stmt);
} else {
    error_log("Error: " . mysqli_error($conn));
}

// MATCHED ITEMS QUERY
$matched_query = "SELECT
        CASE WHEN a.ITEM_STATUS = 'lost' THEN a.ITEM_IMAGE ELSE b.ITEM_IMAGE END AS lost_item_image,
        CASE WHEN a.ITEM_STATUS = 'lost' THEN a.ITEM_NAME ELSE b.ITEM_NAME END AS lost_item_name,
        CASE WHEN a.ITEM_STATUS = 'lost' THEN a.report_id ELSE b.report_id END AS lost_report_id,
        CASE WHEN a.ITEM_STATUS = 'lost' THEN a.QR_CODE ELSE b.QR_CODE END AS lost_qr_code,
        CASE WHEN a.ITEM_STATUS = 'lost' THEN a.HOLDING_STATUS ELSE b.HOLDING_STATUS END AS lost_holding_status,
        CASE
            WHEN a.ITEM_STATUS = 'lost' THEN COALESCE(ua.email_add, a.non_user_email, a.email_add)
            ELSE COALESCE(ub.email_add, b.non_user_email, b.email_add)
        END AS lost_email_add,
        CASE WHEN a.ITEM_STATUS = 'lost' THEN a.match_status ELSE b.match_status END AS lost_match_status,

        CASE WHEN a.ITEM_STATUS = 'found' THEN a.ITEM_IMAGE ELSE b.ITEM_IMAGE END AS found_item_image,
        CASE WHEN a.ITEM_STATUS = 'found' THEN a.ITEM_NAME ELSE b.ITEM_NAME END AS found_item_name,
        CASE WHEN a.ITEM_STATUS = 'found' THEN a.report_id ELSE b.report_id END AS found_report_id,
        CASE WHEN a.ITEM_STATUS = 'found' THEN a.QR_CODE ELSE b.QR_CODE END AS found_qr_code,
        CASE
            WHEN a.ITEM_STATUS = 'found' THEN COALESCE(ub.email_add, b.non_user_email, b.email_add)
            ELSE COALESCE(ua.email_add, a.non_user_email, a.email_add)
        END AS found_email_add
    FROM reports_table a
    JOIN reports_table b ON a.matched_with = b.report_id
    LEFT JOIN user_info ua ON a.email_add = ua.email_add
    LEFT JOIN user_info ub ON b.email_add = ub.email_add
    WHERE (a.match_status = 'matched' OR b.match_status = 'matched')
    AND a.report_id < b.report_id
    AND a.status != 'archived' AND b.status != 'archived'
";

$matchedItems = [];
if ($matched_stmt = mysqli_prepare($conn, $matched_query)) {
    mysqli_stmt_execute($matched_stmt);
    $matched_result = mysqli_stmt_get_result($matched_stmt);
    while ($row = mysqli_fetch_assoc($matched_result)) {
        $matchedItems[] = $row;
    }
    mysqli_stmt_close($matched_stmt);
} else {
    error_log("Error: " . mysqli_error($conn));
}

// ARCHIVED ITEMS QUERY
$archived_query = "SELECT r.report_id, 
                          r.QR_CODE, 
                          r.ITEM_IMAGE, 
                          r.ITEM_NAME, 
                          r.ITEM_STATUS, 
                          r.HOLDING_STATUS,
                          COALESCE(u.email_add, r.non_user_email, r.email_add) AS email_add
                   FROM reports_table r
                   LEFT JOIN user_info u ON r.email_add = u.email_add
                   WHERE r.status = 'archived'";

$archivedItems = [];
if ($archived_stmt = mysqli_prepare($conn, $archived_query)) {
    mysqli_stmt_execute($archived_stmt);
    $archived_result = mysqli_stmt_get_result($archived_stmt);
    while ($row = mysqli_fetch_assoc($archived_result)) {
        $archivedItems[] = $row;
    }
    mysqli_stmt_close($archived_stmt);
} else {
    error_log("Error: " . mysqli_error($conn));
}

// USERS LIST QUERY
$user_query = "SELECT u.id_number, u.first_name, u.middle_name, u.last_name, u.email_add, u.created_at,
    COUNT(r.report_id) as report_count
    FROM user_info u
    LEFT JOIN reports_table r ON u.email_add = r.email_add
    GROUP BY u.id_number";

$users = [];
if ($user_stmt = mysqli_prepare($conn, $user_query)) {
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    while ($row = mysqli_fetch_assoc($user_result)) {
        $users[] = $row;
    }
    mysqli_stmt_close($user_stmt);
} else {
    error_log("Error: " . mysqli_error($conn));
}

// Debugging: Ensure users are fetched
if (empty($users)) {
    error_log("No users found in database.");
}

// Close the database connection
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/2.6.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <link rel="stylesheet" href="../styles/admin.css" />
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=BenchNine:wght@300;400;700&family=Encode+Sans+Condensed:wght@100;200;300;400;500;600;700;800;900&family=Homenaje&display=swap" rel="stylesheet">

    <link href="https://fonts.cdnfonts.com/css/haettenschweiler" rel="stylesheet">

    <link rel="icon" type="../bg/NU.png" href="../bg/NU.png">
    <title>Admin | NULooF Dashboard</title>
</head>

<body>

    <!-- LEFT SIDE CONTENT-->
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

            <a href="#" id="history">
                <i class="fas fa-history"></i> REPORTS <i class="fas fa-caret-down" style="margin-left:auto;"></i>
            </a>

            <div class="dropdown-container" id="historyDropdown">
                <a href="#foundhistory"><i class="fa-solid fa-file-circle-check"></i> FOUND ITEMS</a>
                <a href="#losthistory"><i class="fa-solid fa-file-circle-question"></i> LOST ITEMS</a>
            </div>

            <a href="#" id="matches">
                <i class="fa-solid fa-boxes-stacked"></i> ITEMS <i class="fas fa-caret-down" style="margin-left:auto;"></i>
            </a>

            <div class="dropdown-container" id="matchesDropdown">
                <a href="#unverifieditem"><i class="fas fa-check"></i> PENDING VERIFICATION</a>
                <a href="#possiblematch"><i class="fas fa-search"></i> POSSIBLE MATCH ITEMS</a>
                <a href="#matcheditems"><i class="fas fa-box"></i> MATCHED ITEMS</a>
            </div>

            <a href="#users"><i class="fas fa-users"></i> USERS </a>

            <a href="#archives"><i class="fas fa-archive"></i> ARCHIVES </a>

        </div>

    </div>

    <!-- RIGHT SIDE CONTENT -->
    <div class="w3-main" style="margin-left:20%">
        
        <!-- HEADER CONTENT -->
        <div class="w3-container-header">
            <h2 id="currentDateTime" class="date-time"></h2>

            <div class="dropdown">
                <span><i class="fa fa-user"></i>Admin</span>
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

        <!-- DASHBOARD SECTION-->
        <section id="home">
            <div class="w3-cell">
                <h2 style="font-family: 'Encode Sans Condensed'; font-weight: bold; margin: 0%;">Welcome Admin</h2>
            </div>

            <!-- TALLY REPORTS -->
            <div class="card-container">
                <!-- FOUND ITEMS TALLY -->
                <div class="card w3-green">
                    <h2 style="font-size: x-large; font-family: 'Encode Sans Condensed'; font-weight: 600; margin-left: 15px;">Reported Found Items</h2>
                    <div class="card-body">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($foundCount); ?></span>
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <button onclick="showSection('foundhistory')" class="card-button">View more
                        <i class="fas fa-arrow-circle-right"></i>
                    </button>
                </div>

                <!-- UNVERIFIED ITEMS TALLY -->
                <div class="card w3-blue">
                    <h2 style="font-size: x-large; font-family: 'Encode Sans Condensed'; font-weight: 600; margin-left: 15px;">Pending Verification Items</h2>
                    <div class="card-body">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($unverifiedCount); ?></span>
                        <i class="fas fa-certificate"></i>
                    </div>
                    <button onclick="showSection('unverifieditem')" class="card-button">View more
                        <i class="fas fa-arrow-circle-right"></i>
                    </button>
                </div>

                <!-- LOST ITEMS TALLY -->
                <div class="card w3-pink">
                    <h2 style="font-size: x-large; font-family: 'Encode Sans Condensed'; font-weight: 600; margin-left: 15px;">Reported Lost Items</h2>
                    <div class="card-body">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($lostCount); ?></span>
                        <i class="fa-solid fa-magnifying-glass-location"></i>
                    </div>
                    <button onclick="showSection('losthistory')" class="card-button">View more
                        <i class="fas fa-arrow-circle-right"></i>
                    </button>
                </div>
                
                <!-- POSSIBLE MATCH ITEMS TALLY -->
                <div class="card w3-orange">
                    <h2 style="font-size: x-large; font-family: 'Encode Sans Condensed'; font-weight: 600; margin-left: 15px;">Possible Match Items</h2>
                    <div class="card-body">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars(count($possibleItems)); ?></span>
                        <i class="fas fa-search"></i>
                    </div>
                    <button onclick="showSection('possiblematch')" class="card-button">View more
                        <i class="fas fa-arrow-circle-right"></i>
                    </button>
                </div>
            </div>

            <!-- Refresh Button -->
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-family: Encode Sans Condensed;">Recent Entry Reports:</h2>

                <div class="d-flex align-items-center">
                    <button onclick="refreshRecentTable()" class="w3-btn w3-light-blue w3-round">
                        <i class="fas fa-sync-alt"></i> Refresh Table
                    </button>
                </div>
            </div>

            <!-- RECENT REPORTS TABLE -->
            <table id="homerecentReportsTable">
                <thead>
                    <tr>
                        <th>Ticket No.</th>
                        <th>QR CODE</th>
                        <th>Photo</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Holding Status</th>
                        <th>Date and Time</th>
                        <th>Reported by</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentReports)): ?>
                        <?php foreach ($recentReports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                                <td><img src="<?php echo htmlspecialchars($report['QR_CODE']); ?>" height="100px" width="100px" alt="QR CODE"></td>
                                <td><img src="<?php echo htmlspecialchars($report['ITEM_IMAGE']); ?>" height="100px" width="100px" alt="Item Image"></td>
                                <td><?php echo htmlspecialchars($report['ITEM_NAME']); ?></td>
                                <td><?php echo htmlspecialchars($report['ITEM_STATUS']); ?></td>
                                <td><?php echo htmlspecialchars($report['HOLDING_STATUS']); ?></td>
                                <td><?php echo htmlspecialchars($report['combined_datetime']); ?></td>
                                <td><?php echo htmlspecialchars($report['email_add']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">You have no recent reports</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Buttons -->
            <div class="footer">
                <button id="prevButton" class="w3-button w3-blue" onclick="loadPage(<?php echo ($page - 1); ?>)"><</button>
                <button id="nextButton" class="w3-button w3-blue" onclick="loadPage(<?php echo ($page + 1); ?>)" style="margin-left: 10px;">></button>
            </div>
        </section>

        <!-- FOUND HISTORY SECTION -->
        <section id="foundhistory">
            <!-- TOP CONTENT -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <div>
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Found Item Reports</h1>
                    <p style="font-size: larger;">Reports / <span style="color: #1e90ff; text-decoration: underline;">Found Item</span></p>
                </div>
                
                <!-- BUTTONS -->
                <div style="display: flex; gap: 10px;">
                    <button onclick="document.getElementById('id01').style.display='block'" class="create-report-btn">
                        <i class="fa fa-file-upload"></i> Create new report
                    </button>

                    <form class="search">
                        <input type="text" placeholder="Search item or ticket number" name="search">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>

                    <div class="dropdown">
                        <button class="filter-btn" onclick="myFunction('foundrecentReportsTable')">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <div id="myDropdown" class="dropdown-content"></div>
                    </div>
                </div>
            </div>

            <!-- Refresh Button -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-family: Encode Sans Condensed; margin: 0;">List of reported found items</h2>

                <div class="d-flex align-items-center">
                    <!-- Generate Report Button -->
                    <button onclick="generateReport('foundrecentReportsTable', 'found_items_report')" class="w3-btn w3-green w3-round" style="margin-right: 10px;">
                        <i class="fas fa-file-excel"></i> Generate Report
                    </button>
                    
                    <!-- Refresh Button -->
                    <button onclick="refreshFoundTable()" class="w3-btn w3-light-blue w3-round">
                        <i class="fas fa-sync-alt"></i> Refresh Table
                    </button>
                </div>
            </div>

            <table id="foundrecentReportsTable">
                <thead>
                    <tr>
                        <th>Ticket No. and QR Code</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Holding Status</th>
                        <th>Category</th>
                        <th>Color</th>
                        <th>Reported by</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if (empty($foundItems)) {
                            echo "<tr><td colspan='9'>No found reports available</td></tr>";
                        } else {
                            foreach ($foundItems as $found_report) {
                                echo "<tr id='report-row-" . htmlspecialchars($found_report['report_id']) . "'>";
                                echo "<td>";
                                $qrCodeFound = htmlspecialchars($found_report['QR_CODE']);
                                echo "<img src='$qrCodeFound' height='100px' width='100px' alt='QR CODE'>";
                                echo "<br>";
                                echo htmlspecialchars($found_report['report_id']);
                                echo "</td>";

                                echo "<td>";
                                $imageFound = htmlspecialchars($found_report['ITEM_IMAGE']);
                                echo "<img src='$imageFound' height='100px' width='100px' alt='Item Image'>";
                                echo "<br>";
                                echo htmlspecialchars($found_report['ITEM_NAME']);
                                echo "</td>";

                                echo "<td>" . htmlspecialchars($found_report['ITEM_STATUS']) . "</td>";
                                echo "<td>" . htmlspecialchars($found_report['HOLDING_STATUS']) . "</td>";
                                echo "<td>" . htmlspecialchars($found_report['ITEM_CATEGORY']) . "</td>";
                                echo "<td>" . htmlspecialchars($found_report['ITEM_COLOR']) . "</td>";
                                echo "<td>" . htmlspecialchars($found_report['email_add']) . "</td>";
                                echo "<td><span class='w3-text-green'><strong>Verified</strong></span></td>";
                                echo "<td>
                                        <div class='button-container'>
                                            <button class='w3-btn w3-blue w3-round' onclick='viewFoundReport(" . json_encode($found_report) . ")'><i class='fa fa-eye'></i> View</button>
                                            <button class='w3-btn w3-orange w3-round' onclick='editReport(" . json_encode($found_report) . ")'><i class='fa fa-edit'></i> Edit</button>
                                            <button class='w3-btn w3-light-green w3-round' onclick='printReport(" . $found_report['report_id'] . ")'><i class='fa fa-print'></i> Print</button>
                                            <button class='w3-btn w3-red w3-round' onclick='archiveReport(" . $found_report['report_id'] . ")'><i class='fa fa-archive'></i> Archive</button>
                                        </div>
                                    </td>";
                                echo "</tr>";
                            }
                        }
                    ?>
                </tbody>
            </table>

            <!-- VIEW FOUND REPORT MODAL -->
            <div id="viewFound" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom modal-fixed-size">
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('viewFound').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;</span>
                    </div>
                    <div class="w3-container" id="viewFoundContent">
                        <!-- Content will be dynamically inserted here -->
                    </div>
                </div>
            </div>

        </section>

        <!-- LOST HISTORY SECTION -->
        <section id="losthistory">
            <!-- TOP CONTENT -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <div>
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Lost Item Reports</h1>
                    <p style="font-size: larger;">Reports / <span style="color: #1e90ff; text-decoration: underline;">Lost Item</span></p>
                </div>
                
                <!-- BUTTONS -->
                <div style="display: flex; gap: 10px;">
                    <button onclick="document.getElementById('id01').style.display='block'" class="create-report-btn">
                        <i class="fa fa-file-upload"></i> Create new report
                    </button>

                    <form class="search">
                        <input type="text" placeholder="Search item or ticket number" name="search">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>

                    <div class="dropdown">
                        <button type="button" class="filter-btn" onclick="myLostFunction('lostrecentReportsTable')">
                        <i class="fas fa-filter"></i> Filter
                        </button>
                        <div id="lostDropdown" class="dropdown-content"></div>
                    </div>
                </div>
            </div>

            <!-- Refresh Button -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-family: Encode Sans Condensed; margin: 0;">List of reported lost items</h2>

                <div class="d-flex align-items-center">
                    <!-- Generate Report Button -->
                    <button onclick="generateLostReport('lostrecentReportsTable', 'lost_items_report')" class="w3-btn w3-green w3-round" style="margin-right: 10px;">
                        <i class="fas fa-file-excel"></i> Generate Report
                    </button>

                    <button onclick="refreshLostTable()" class="w3-btn w3-light-blue w3-round">
                        <i class="fas fa-sync-alt"></i> Refresh Table
                    </button>
                </div>
            </div>
                        
            <table id="lostrecentReportsTable">
                <thead>
                    <tr>
                        <th>Ticket No. and QR Code</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Color</th>
                        <th>Category</th>
                        <th>Location Lost</th>
                        <th>Reported by</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if (empty($lostItems)) {
                            echo "<tr><td colspan='8'>No lost reports available</td></tr>";
                        } else {
                            foreach ($lostItems as $lost_report) {
                                echo "<tr id='report-row-" . htmlspecialchars($lost_report['report_id']) . "'>";
                                echo "<td>";
                                $qrCodeLost = htmlspecialchars(string: $lost_report['QR_CODE']);
                                echo "<img src='$qrCodeLost' height='100px' width='100px' alt='QR CODE'>";
                                echo "<br>";
                                echo htmlspecialchars($lost_report['report_id']);
                                echo "</td>";

                                echo "<td>";
                                $imageLost = htmlspecialchars($lost_report['ITEM_IMAGE']);
                                echo "<img src='$imageLost' height='100px' width='100px' alt='Item Image'>";
                                echo "<br>";
                                echo htmlspecialchars($lost_report['ITEM_NAME']);
                                echo "</td>";

                                echo "<td>" . htmlspecialchars($lost_report['ITEM_STATUS']) . "</td>";
                                echo "<td>" . htmlspecialchars($lost_report['ITEM_COLOR']) . "</td>";
                                echo "<td>" . htmlspecialchars($lost_report['ITEM_CATEGORY'])  . "</td>";
                                echo "<td>" . htmlspecialchars($found_report['ITEM_DATE']) . " " . htmlspecialchars($found_report['ITEM_TIME']) . "</td>";
                                echo "<td>" . htmlspecialchars($lost_report['email_add']) .  "</td>";
                                echo "<td>
                                        <div class='button-container'>
                                            <button class='w3-btn w3-blue w3-round' onclick='viewLostReport(" . json_encode($lost_report) . ")'><i class='fa fa-eye'></i> View</button>
                                            <button class='w3-btn w3-orange w3-round' onclick='editReport(" . json_encode($lost_report) . ")'><i class='fa fa-edit'></i> Edit</button>
                                            <button class='w3-btn w3-light-green w3-round' onclick='printReport(" . $lost_report['report_id'] . ")'><i class='fa fa-print'></i> Print</button>
                                            <button class='w3-btn w3-red w3-round' onclick='archiveReport(" . $lost_report['report_id'] . ")'><i class='fa fa-archive'></i> Archive</button>
                                        </div>
                                    </td>";
                                echo "</tr>";
                            }
                        }
                    ?>
                </tbody>
            </table>

            <!-- VIEW LOST REPORT MODAL -->
            <div id="viewLost" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom modal-fixed-size">
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('viewLost').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;</span>
                    </div>
                    <div class="w3-container" id="viewLostContent">
                        <!-- Content will be dynamically inserted here -->
                    </div>
                </div>
            </div>

        </section>

        <!-- UNVERIFIED ITEMS SECTION -->
        <section id="unverifieditem">
            <!-- TOP CONTENT -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <div>
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Unverified Found Item Reports</h1>
                    <p style="font-size: larger;">Reports / <span style="color: #1e90ff; text-decoration: underline;">Pending Verification Found Items</span></p>
                </div>
                
                <!-- BUTTONS -->
                <div style="display: flex; gap: 10px;">
                    <form class="search">
                        <input type="text" placeholder="Search item or ticket number" name="search">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <!-- Refresh Button -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-family: Encode Sans Condensed;">List of pending verification found items</h2>

                <div class="d-flex align-items-center">
                    <button onclick="refreshUnverifiedTable()" class="w3-btn w3-light-blue w3-round">
                        <i class="fas fa-sync-alt"></i> Refresh Table
                    </button>
                </div>
            </div>

            <!-- TABLE CONTENT -->
            <table id="unverifiedrecentReportsTable">
                <thead>
                    <tr>
                        <th>Ticket No. and QR Code</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Holding Status</th>
                        <th>Date and Time</th>
                        <th>Reported by</th>
                        <th>Remarks</th>
                        <!-- <th>Action</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if (empty($unverifiedItems)) {
                            echo "<tr><td colspan='8'>No found reports available</td></tr>";
                        } else {
                            foreach ($unverifiedItems as $unverified_report) {
                                echo "<tr>";
                                echo "<td>";
                                $qrCodeFound = htmlspecialchars($unverified_report['QR_CODE']);
                                echo "<img src='$qrCodeFound' height='100px' width='100px' alt='QR CODE'>";
                                echo "<br>";
                                echo htmlspecialchars($unverified_report['report_id']);
                                echo "</td>";

                                echo "<td>";
                                $imageFound = htmlspecialchars($unverified_report['ITEM_IMAGE']);
                                echo "<img src='$imageFound' height='100px' width='100px' alt='Item Image'>";
                                echo "<br>";
                                echo htmlspecialchars($unverified_report['ITEM_NAME']);
                                echo "</td>";

                                echo "<td>" . htmlspecialchars($unverified_report['ITEM_STATUS']) . "</td>";
                                echo "<td>" . htmlspecialchars($unverified_report['HOLDING_STATUS']) . "</td>";
                                echo "<td>" . htmlspecialchars($unverified_report['ITEM_DATE']) . " " . htmlspecialchars($unverified_report['ITEM_TIME']) . "</td>";
                                echo "<td>" . htmlspecialchars($unverified_report['email_add']) . "</td>";

                                echo "<td>
                                        <button class='w3-btn w3-green w3-round' onclick='verifyReport(" . json_encode($unverified_report) . ")'>
                                            <i class='fa fa-check'></i> Verify
                                        </button>
                                        <br> <br>
                                        <button class='w3-btn w3-blue w3-round' onclick='notifyReport(" . json_encode($unverified_report) . ", this)'>
                                            <i class='fa fa-bell'></i> Notify
                                        </button>
                                    </td>";
                                echo "</tr>";
                            }
                        }
                    ?>
                </tbody>
            </table>

            <!-- VERIFY FOUND REPORT MODAL -->
            <div id="verifyModal" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 500px;">
                    
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('verifyModal').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;
                        </span>
                    </div>

                    <div class="w3-container" style="padding-bottom: 20px;">
                        <h2 style="font-family: Encode Sans Condensed;">Verify Item Surrender</h2>
                        <p>Has this item been surrendered to the Discipline Office?</p>
                        <label for="storageRoom">Select Storage Room:</label>
                        <select id="storageRoom" class="w3-select">
                            <option value=""disabled selected>Select Room</option>
                            <option value="D.O. Office">D.O. Office</option>
                            <option value="Storage Room">Storage Room</option>
                        </select>
                        <br><br>
                        <button class="w3-btn w3-green w3-round" onclick="confirmVerification()">Confirm</button>
                        <button class="w3-btn w3-red w3-round" onclick="document.getElementById('verifyModal').style.display='none'">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- NOTIFICATION STATUS MODAL -->
            <div id="notifyModal" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 500px;">
                    
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('notifyModal').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;
                        </span>
                    </div>

                    <div class="w3-container">
                        <h2 style="font-family: Encode Sans Condensed;">Email Notification Status</h2>
                        <p id="notifyMessage"></p> <!-- This will be updated dynamically -->
                        <button class="w3-btn w3-green w3-round" style="margin-bottom: 20px;" onclick="document.getElementById('notifyModal').style.display='none'">OK</button>
                    </div>
                </div>
            </div>


        </section>

        <!-- POSSIBLE MATCH SECTION -->
        <section id="possiblematch">
            <!-- TOP CONTENT -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <div>
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Possible Match Items Reports</h1>
                    <p style="font-size: larger;">Items / <span style="color: #1e90ff; text-decoration: underline;">Possible Match Items</span></p>
                </div>
                
                <!-- BUTTONS -->
                <div style="display: flex; gap: 10px;">
                    <form class="search">
                        <input type="text" placeholder="Search item or ticket number" name="search">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <!-- Refresh Button -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-family: Encode Sans Condensed;">List of possible matched items</h2>

                <div class="d-flex align-items-center">
                    <button onclick="refreshPossibleTable()" class="w3-btn w3-light-blue w3-round">
                        <i class="fas fa-sync-alt"></i> Refresh Table
                    </button>
                </div>
            </div>

            <table id="possiblerecentReportsTable">
                <thead>
                    <tr>
                        <th>Ticket No.</th>
                        <th>QR CODE</th>
                        <th>Photo</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Holding Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody">
                    <?php
                        if (empty($possibleItems)) {
                            echo "<tr><td colspan='7'>No lost reports available</td></tr>";
                        } else {
                            foreach ($possibleItems as $possible_report) {
                                echo "<tr data-report-id='{$possible_report['report_id']}'>"; // Added data-report-id attribute
                                echo "<td>{$possible_report['report_id']}</td>";

                                $qrCodePath = htmlspecialchars($possible_report['QR_CODE']);
                                echo "<td><img src='$qrCodePath' height='100px' width='100px' alt='QR CODE'></td>";

                                $imagePath = htmlspecialchars($possible_report['ITEM_IMAGE']);
                                echo "<td><img src='$imagePath' height='100px' width='100px' alt='Item Image'></td>";

                                echo "<td>{$possible_report['ITEM_NAME']}</td>";
                                echo "<td>{$possible_report['ITEM_STATUS']}</td>";
                                echo "<td>{$possible_report['HOLDING_STATUS']}</td>";
                                echo "<td>
                                        <button class='w3-btn w3-blue w3-round' onclick='viewReport({$possible_report['report_id']})'><i class='fa fa-check'></i> Check</button>
                                        <button class='w3-btn w3-light-green w3-round' onclick='printReport({$possible_report['report_id']})'><i class='fa fa-print'></i> Print</button>
                                    </td>";
                                echo "</tr>";
                            }
                        }
                    ?>
                </tbody>
            </table>

            <!-- VIEW POSSIBLE MODAL -->
            <div id="viewPossibleModal" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom">
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('viewPossibleModal').style.display='none'"
                        class="w3-button w3-circle w3-xlarge w3-display-topright"
                        style="background-color: transparent;">&times;</span>
                    </div>
                    <div class="w3-container" id="viewPossibleContent">
                        <input type="text" id="report_id" placeholder="Enter Report ID" oninput="generateQR()">
                        <div id="qrcode"></div>
                    </div>
                </div>
            </div>

            <!-- MATCH SUCCESS MODAL -->
            <div id="matchSuccessModal" class="w3-modal" style="display: none;">
                <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">
                    <!-- Modal Header -->
                    <header class="w3-container w3-center" style="padding: 16px; font-size: 18px;">
                        <h2 style="margin: 0; font-family: Encode Sans Condensed;">Match Successful</h2>
                    </header>
                    <!-- Modal Body -->
                    <div class="w3-container w3-padding w3-center">
                        <p style="font-size: 16px; color: #444;">Items matched successfully! Notifications sent.</p>
                    </div>
                    <!-- Modal Footer -->
                    <footer class="w3-container w3-padding w3-center" style="display: flex; gap: 10px; justify-content: center;">
                        <button class="w3-button w3-round w3-blue w3-hover-dark-blue" onclick="closeMatchSuccessModal()" style="padding: 10px 20px;">OK</button>
                    </footer>
                </div>
            </div>
            
        </section>

        <!-- MATCHED ITEM SECTION -->
        <section id="matcheditems">
            <!-- TOP CONTENT -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <div>
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Matched Items Reports</h1>
                    <p style="font-size: larger;">Items / <span style="color: #1e90ff; text-decoration: underline;">Matched Items</span></p>
                </div>
                
                <!-- BUTTONS -->
                <div style="display: flex; gap: 10px;">
                    <form class="search">
                        <input type="text" placeholder="Search item or ticket number" name="search">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <!-- Refresh Button -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-family: Encode Sans Condensed;">Matched Items</h2>

                <div class="d-flex align-items-center">
                    <button onclick="refreshMatchedTable()" class="w3-btn w3-light-blue w3-round">
                        <i class="fas fa-sync-alt"></i> Refresh Table
                    </button>
                </div>
            </div>

            <!-- TABLE CONTENT -->
            <table id="matchedrecentReportsTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Ticket No. & QR Code (Lost Item)</th>
                        <th>Ticket No. & QR Code (Found Item)</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($matchedItems)) {
                        foreach ($matchedItems as $matched_row) {
                            echo "<tr data-lost-id='" . htmlspecialchars($matched_row['lost_report_id']) . "' data-found-id='" . htmlspecialchars($matched_row['found_report_id']) . "'>";
                            echo "<td>";
                            echo "<img src='" . htmlspecialchars($matched_row['lost_item_image']) . "' height='100px' width='100px' alt='Item Image'>";
                            echo "<br>" . htmlspecialchars($matched_row['lost_item_name']);
                            echo "</td>";

                            echo "<td>";
                            echo "<img src='" . htmlspecialchars($matched_row['lost_qr_code']) . "' height='100px' width='100px' alt='QR CODE'>";
                            echo "<br>Ticket No: " . htmlspecialchars($matched_row['lost_report_id']);
                            echo "</td>";

                            echo "<td>";
                            echo "<img src='" . htmlspecialchars($matched_row['found_qr_code']) . "' height='100px' width='100px' alt='QR CODE'>";
                            echo "<br>Ticket No: " . htmlspecialchars($matched_row['found_report_id']);
                            echo "</td>";

                            echo "<td>" . htmlspecialchars($matched_row['lost_match_status']) . "</td>";

                            echo "<td>" . htmlspecialchars($matched_row['lost_holding_status']) . "</td>";
                            
                            echo "<td>
                                    <div class='button-container'>
                                        <button class='w3-btn w3-blue w3-round' onclick='viewMatchedItem(" . $matched_row['lost_report_id'] . ", " . $matched_row['found_report_id'] . ")'>
                                            <i class='fa fa-eye'></i> View
                                        </button>

                                        <button class='w3-btn w3-green w3-round' onclick='verifyProcess(" . json_encode($matched_row) . ", this)'>
                                            <i class='fa fa-check'></i> Verify
                                        </button>

                                        <button class='w3-btn w3-orange w3-round' onclick='notifyLostUser(" . json_encode($matched_row) . ", this)'>
                                            <i class='fa fa-bell'></i> Notify
                                        </button>

                                        <button class='w3-btn w3-red w3-round' onclick='rematchReport(" . $matched_row['lost_report_id'] . ", " . $matched_row['found_report_id'] . ", this)'>
                                            <i class='fa fa-undo'></i> Re-match
                                        </button>
                                    </div>
                                </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>You have no matched items</td></tr>";
                    }
                    ?>
                </tbody>

            </table>

            <!-- MATCHED ITEM MODAL -->
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

            <!-- VERIFY LOST REPORT MODAL -->
            <div id="verifyProcessModal" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 500px;">
                    
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('verifyProcessModal').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;
                        </span>
                    </div>

                    <div class="w3-container" style="padding-bottom: 15px;">
                        <h2 style="font-family: Encode Sans Condensed;">Verify Item Retrieval</h2>
                        <p style="font-size: medium;">Has the owner of the lost item retrieved the item at the Discipline Office?</p>
                        <button class="w3-btn w3-green w3-round" onclick="claimedLostItem()">Yes</button>
                        <button class="w3-btn w3-red w3-round" onclick="document.getElementById('verifyProcessModal').style.display='none'">No</button>
                    </div>
                </div>
            </div>

            <!-- VERIFY SUCCESS MODAL -->
            <div id="verifySuccessModal" class="w3-modal" style="display: none;">
                <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">
                    <!-- Modal Header -->
                    <header class="w3-container w3-center" style="padding: 16px; font-size: 18px;">
                        <h2 style="margin: 0;">Verification Successful</h2>
                    </header>
                    <!-- Modal Body -->
                    <div class="w3-container w3-padding w3-center">
                        <p style="font-size: 16px; color: #444;">The item has been successfully verified as retrieved!</p>
                    </div>
                    <!-- Modal Footer -->
                    <footer class="w3-container w3-padding w3-center" style="display: flex; gap: 10px; justify-content: center;">
                        <button class="w3-button w3-round w3-blue w3-hover-dark-blue" onclick="closeVerifySuccessModal()" style="padding: 10px 20px;">OK</button>
                    </footer>
                </div>
            </div>


            <!-- NOTIFICATION STATUS MODAL -->
            <div id="notifyLostUserModal" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 500px;">
                    
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('notifyLostUserModal').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;
                        </span>
                    </div>

                    <div class="w3-container">
                        <h2 style="font-family: Encode Sans Condensed;">Email Notification Status</h2>
                        <p id="notifyLostMessage"></p> <!-- This will be updated dynamically -->
                        <button class="w3-btn w3-green w3-round" style="margin-bottom: 30px;" 
                            onclick="document.getElementById('notifyLostUserModal').style.display='none'">
                            OK
                        </button>
                    </div>
                </div>
            </div>

            <!-- REMATCH MODAL -->
            <div id="rematchReportModal" class="w3-modal" style="display: none;">
                <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">
                    <!-- Modal Header -->
                    <header class="w3-container w3-center" style="padding: 16px; font-size: 18px;">
                        <h2 style="margin: 0; font-family: Encode Sans Condensed;">Confirm Rematch Item</h2>
                    </header>
                    <!-- Modal Body -->
                    <div class="w3-container w3-padding w3-center">
                        <p style="font-size: 16px; color: #444;">Are you sure you want to rematch this item?</p>
                    </div>
                    <!-- Modal Footer -->
                    <footer class="w3-container w3-padding w3-center" style="display: flex; gap: 10px; justify-content: center;">
                        <button class="w3-button w3-round w3-red w3-hover-dark-red" onclick="confirmRematch()" style="padding: 10px 20px;">Yes</button>
                        <button class="w3-button w3-round w3-gray w3-hover-dark-gray" onclick="document.getElementById('rematchReportModal').style.display='none'" style="padding: 10px 20px;">Cancel</button>
                    </footer>
                </div>
            </div>

            <!-- REMATCH SUCCESS MODAL -->
            <div id="rematchSuccessModal" class="w3-modal" style="display: none;">
                <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">
                    <!-- Modal Header -->
                    <header class="w3-container w3-center" style="padding: 16px; font-size: 18px;">
                        <h2 style="margin: 0;">Re-Match Successful</h2>
                    </header>
                    <!-- Modal Body -->
                    <div class="w3-container w3-padding w3-center">
                        <p style="font-size: 16px; color: #444;">Items are rematched successfully! Notifications are sent to the reporters.</p>
                    </div>
                    <!-- Modal Footer -->
                    <footer class="w3-container w3-padding w3-center" style="display: flex; gap: 10px; justify-content: center;">
                        <button class="w3-button w3-round w3-blue w3-hover-dark-blue" onclick="closeReMatchSuccessModal()" style="padding: 10px 20px;">OK</button>
                    </footer>
                </div>
            </div>


        </section>

        <!-- USER LIST SECTION -->
        <section id="users">
             <!-- TOP CONTENT -->
             <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <div>
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Registered Users</h1>
                    <p style="font-size: larger;">Home / <span style="color: #1e90ff; text-decoration: underline;">Users</span></p>
                </div>
                
                <!-- BUTTONS -->
                <div style="display: flex; gap: 10px;">
                    <form class="search">
                        <input type="text" placeholder="Search item or ticket number" name="search">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <!-- Refresh Button -->
            <!-- Button Container -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-family: Encode Sans Condensed;">List of users</h2>

                <div style="display: flex; gap: 10px;">
                    <button onclick="refreshUsersTable()" class="w3-btn w3-light-blue w3-round">
                        <i class="fas fa-sync-alt"></i> Refresh Table
                    </button>

                    <button onclick="unarchiveUsers()" class="w3-btn w3-green w3-round">
                        <i class="fas fa-eye"></i> Show Archived Users
                    </button>
                </div>
            </div>



            <!-- TABLE CONTENT -->
            <table id="usersrecentReportsTable">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>No. of reports</th>
                        <th>Date joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email_add']); ?></td>
                                <td><?php echo htmlspecialchars($row['report_count']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <!-- <button class="w3-btn w3-blue w3-round"><i class='fa fa-eye'></i> View</button> -->
                                    <button class="w3-btn w3-red w3-round archive-btn" data-id="<?php echo $row['id_number']; ?>">
                                        <i class='fa fa-archive'></i> Archive
                                    </button>                                
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">You have no users yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- ARCHIVE SECTION -->
        <section id="archives">
            <!-- TOP CONTENT -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <div>
                    <h1 style="font-family: 'Encode Sans Condensed'; font-weight: bold;">Archive Reports</h1>
                    <p style="font-size: larger;">Items / <span style="color: #1e90ff; text-decoration: underline;">Archive Items</span></p>
                </div>
                
                <!-- BUTTONS -->
                <div style="display: flex; gap: 10px;">
                
                    <form class="search">
                        <input type="text" placeholder="Search item or ticket number" name="search">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>

                    <div class="dropdown">
                        <button class="filter-btn" onclick="myFunction()">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <div id="myDropdown" class="dropdown-content">
                            <a href="#category">Category</a>
                            <a href="#color">Color</a>
                            <a href="#floor">Floor</a>
                            <a href="#room">Room</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refresh Button -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="font-family: Encode Sans Condensed;">Archive Items</h2>

                <div class="d-flex align-items-center">
                    <button onclick="refreshArchiveTable()" class="w3-btn w3-light-blue w3-round">
                        <i class="fas fa-sync-alt"></i> Refresh Table
                    </button>
                </div>
            </div>

            <!-- TABLE CONTENT -->
            <table id="archiverecentReportsTable">
                <thead>
                    <tr>
                        <th>Ticket No.</th>
                        <th>QR CODE</th>
                        <th>Photo</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Holding Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody">
                    <?php
                        if (!empty($archivedItems)) {
                            foreach ($archivedItems as $archived_report) {
                                echo "<tr id='report-row-" . htmlspecialchars($archived_report['report_id']) . "'>";

                                // echo "<tr>";
                                echo "<td>{$archived_report['report_id']}</td>";

                                $qrCodePath = htmlspecialchars($archived_report['QR_CODE']);
                                echo "<td><img src='$qrCodePath' height='100px' width='100px' alt='QR CODE'></td>";

                                $imagePath = htmlspecialchars($archived_report['ITEM_IMAGE']);
                                echo "<td><img src='$imagePath' height='100px' width='100px' alt='Item Image'></td>";

                                echo "<td>{$archived_report['ITEM_NAME']}</td>";
                                echo "<td>{$archived_report['ITEM_STATUS']}</td>";
                                echo "<td>{$archived_report['HOLDING_STATUS']}</td>";
                                echo "<td>
                                        <button class='w3-btn w3-blue w3-round' onclick='viewArchiveReport({$archived_report['report_id']})'><i class='fa fa-eye'></i> View</button>
                                        <button class='w3-btn w3-gray w3-round' onclick='retrieveReport({$archived_report['report_id']})'><i class='fa fa-undo'></i> Retrieve</button>
                                        <button class='w3-btn w3-light-green w3-round' onclick='printReport({$archived_report['report_id']})'><i class='fa fa-print'></i> Print</button>
                                    </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No archive items available</td></tr>";
                        }
                    ?>
                </tbody>
            </table>

            <!-- VIEW ARCHIVE ITEM MODAL -->
            <div id="viewArchive" class="w3-modal">
                <div class="w3-modal-content w3-card w3-animate-zoom">
                    <div class="w3-center"><br>
                        <span onclick="document.getElementById('viewArchive').style.display='none'"
                            class="w3-button w3-circle w3-xlarge w3-display-topright"
                            style="background-color: transparent;">&times;</span>
                    </div>
                    <div class="w3-container" id="viewArchiveContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
            </div>
            
        </section>

        <!-- CREATE REPORT FORM -->
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
                <form action="form_handler_admin.php" method="POST" id="reportForm" onsubmit="submitReport(event)" enctype="multipart/form-data" class="w3-container" style="font-family: 'Encode Sans Condensed';">
                    <h2 class="w3-padding" style="font-family: 'Encode Sans Condensed';">Report Item</h2>
                    <p class="w3-padding" style="font-family: 'Encode Sans Condensed';">Kindly fill up this form to provide the description of the item</p>

                    <!-- STATUS OF ITEM -->
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="itemImage"><b>Upload Image</b>:</label>
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
                                        <input class="w3-radio" type="radio" name="holding" value="Surrendered at D.O Office" id="SudderenderedAtDO" checked>
                                        Surrendered at D.O. Office
                                    </label>
                                </div>
                            </div>

                            <!-- For Lost Items -->
                            <div id="lostHoldingStatus" style="display: none;" class="holding-options">
                                <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                                    <label>
                                        <input class="w3-radio" type="radio" name="holding" value="Not yet retrieved" id="notyetRetrieved" checked>
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

                    <!-- ITEM COLOR AND BRAND -->
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

                    <!-- NON-USER EMAIL ADD AND STORAGE ROOM -->
                    <div class="w3-row-padding">
                        <!-- NON-USER EMAIL -->
                        <div class="w3-half">
                            <label for=""><b>Email Address:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="text" id="nonUserEmail" name="nonUserEmail" required>
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
                    <p>Processing...</p>
                </div>

                <!-- SUCCESS MODAL -->
                <div id="successModal" class="w3-modal">
                    <div class="w3-modal-content w3-card w3-animate-zoom">
                        <div class="w3-container">
                            <span onclick="document.getElementById('successModal').style.display='none'" class="w3-button w3-circle w3-xlarge w3-display-topright">&times;</span>
                            <h2 class="w3-center">Success!</h2>
                            <p class="w3-center">Your report has been submitted successfully.</p>
                            <button class="w3-button w3-green w3-round-large w3-block" onclick="closeSuccessModal()">OK</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        </div>

        <!-- EDIT REPORT FORM -->
        <div id="editReportModal" class="w3-modal">
            <div class="w3-modal-content w3-card w3-animate-zoom">

                <!-- CANCEL BUTTON -->
                <div id="editcancelButton" class="w3-center"><br>
                    <span
                        onclick="document.getElementById('editReportModal').style.display='none'" class="w3-button w3-circle w3-xlarge w3-display-topright" style="background-color: transparent;">&times;
                    </span>
                </div>

                <!-- EDIT REPORT FORM -->
                <form id="editForm" onsubmit="submitEditForm(event)" class="w3-container" style="font-family: 'Encode Sans Condensed';">
                    <h2 class="w3-padding" style="font-family: 'Encode Sans Condensed';">Edit Report Item</h2>
                    <p class="w3-padding" style="font-family: 'Encode Sans Condensed';">Kindly fill up this form to provide the description of the item</p>

                    <!-- Hidden input for item ID -->
                    <input type="hidden" id="editItemId" name="itemId">

                    <!-- PHOTO AND STATUS -->
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="itemImage">Upload Image:</label>
                            <input type="file" id="editItemImage" name="itemImage" accept="image/*" onchange="editImage(event)">
                            <img id="itemImageEdit" height="100" width="100" style="display: none;">
                        </div>

                        <div class="w3-half" style="margin-bottom: 16px;">
                            <!-- STATUS OF ITEM -->
                            <label style="display: block; font-weight: bold;">Item Condition:</label>
                            <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 16px;">
                                <label>
                                    <input class="w3-radio" type="radio" name="status" id="editStatusLost" value="lost">
                                    Lost
                                </label>
                                <label>
                                    <input class="w3-radio" type="radio" name="status" id="editStatusFound" value="found">
                                    Found
                                </label>
                            </div>

                            <!-- HOLDING STATUS -->
                            <label style="display: block; font-weight: bold;">Item Status:</label>

                            <!-- FOUND -->
                            <div id="editHstatusSurrendered" style="display: none;" class="holding-options">
                                <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                                    <label>
                                        <input class="w3-radio" type="radio" name="holding" value="Surrendered" checked>
                                        Surrendered
                                    </label>
                                </div>
                            </div>

                            <!-- LOST -->
                            <div id="editHstatusUnclaimed" style="display: none;" class="holding-options">
                                <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                                    <label>
                                        <input class="w3-radio" type="radio" name="holding" value="Not yet retrieved" id="notyetRetrieved" checked>
                                        Not yet retrieved
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ITEM NAME AND CATEGORY -->
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="editItemName"><b>Item Name:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="text" id="editItemName" name="itemName" required>
                        </div>

                        <div class="w3-half">
                            <label for="editItemCategory"><b>Item Category:</b></label>
                            <select class="w3-select" name="itemCategory" id="editItemCategory" onchange="toggleInput(this, 'editcustomCategoryInput')" required>
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
                            <input type="text" id="editcustomCategoryInput" name="customCategory" class="w3-input" placeholder="Enter Category" style="display: none;">
                        </div>
                    </div>

                    <!-- ITEM COLOR AND BRAND -->
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="editItemColor"><b>Item Color:</b></label>
                            <select class="w3-select" name="itemColor" id="editItemColor" required>
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
                            <input type="text" id="editcustomColorInput" name="customColor" class="w3-input" placeholder="Enter Color" style="display: none;">
                        </div>

                        <div class="w3-half">
                            <label for="editItemBrand"><b>Item Brand:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="text" id="editItemBrand" name="itemBrand" required>
                        </div>
                    </div>

                    <!-- ITEM DESCRIPTION -->
                    <div class="w3-row-padding">
                        <div class="w3-col" style="width: 100%;">
                            <label for="editDescription"><b>Item Description:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" id="editDescription" name="description" required>
                        </div>
                    </div>

                    <!-- LOCATION -->
                    <div class="w3-row-padding">
                        <!-- FLOOR NUMBER -->
                        <div class="w3-col s3">
                            <label for="editFloorNo"><b>Floor No.:</b></label>
                            <select class="w3-select" onchange="editlocSelect()" name="floorNo" id="editFloorNo" required>
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
                            <label for="editRoomNo"><b>Room No.:</b></label>
                            <select class="w3-select" name="roomNo" id="editRoomNo" onchange="editcheckOtherRoom()" required>
                                <option value="" disabled selected>Select Room</option>
                            </select>
                            <input type="text" id="editotherRoomInput" name="otherRoom" class="w3-input" placeholder="Enter Room Name" style="display:none;">
                        </div>

                        <!-- DATE ITEM LOST -->
                        <div class="w3-col s3">
                            <label for="editReportDate"><b>Date:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="date" id="editReportDate" name="reportDate" required>
                        </div>

                        <!-- TIME ITEM LOST -->
                        <div class="w3-col s3">
                            <label for="editReportTime"><b>Time:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="time" id="editReportTime" name="reportTime" required>
                        </div>
                    </div>

                    <!-- NON-NULOOF USER EMAIL ADD -->
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for=""><b>Email Address:</b></label>
                            <input class="w3-input w3-border w3-margin-bottom" type="text" id="editUserEmail" name="editUserEmail" required>
                        </div>

                        <!-- STORAGE ROOM LOCATED -->
                        <div class="w3-half" id="storageRoomDiv">
                            <label for="storageRoom"><b>Select Storage Room:</b></label>
                            <select id="editstorageRoom" name="editstorageRoom" class="w3-select">
                                <option value="" disabled selected>Select Room</option>
                                <option value="D.O. Office">D.O. Office</option>
                                <option value="Storage Room">Storage Room</option>
                            </select>
                        </div>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="w3-bar w3-section w3-padding">
                        <input type="submit" name="submit" class="w3-btn w3-ripple w3-pale-green w3-round-large w3-padding w3-right" value="Submit">

                        <button type="button"
                            onclick="document.getElementById('editReportModal').style.display='none'"
                            class="w3-btn w3-ripple w3-pale-red w3-round-large w3-padding w3-right"
                            style="margin-right: 10px;">
                            Cancel
                        </button>
                    </div>
                </form>

                <!-- LOADING SPINNER (SHOWS WHEN SUBMITTING) -->
                <div id="editloadingSpinner" class="w3-center" style="display: none; padding: 10px;">
                    <i class="w3-spin fa fa-spinner fa-2x"></i>
                    <p style="font-family: 'Encode Sans Condensed'">Processing...</p>
                </div>

                <!-- EDIT SUCCESS MODAL -->
                <div id="editsuccessModal" class="w3-modal">
                    <div class="w3-modal-content w3-card w3-animate-zoom">
                        <div class="w3-container">
                            <span onclick="document.getElementById('editsuccessModal').style.display='none'" class="w3-button w3-circle w3-xlarge w3-display-topright">&times;</span>
                            <h2 class="w3-center">Success!</h2>
                            <p class="w3-center">Your report has been submitted successfully.</p>
                            <button class="w3-button w3-green w3-round-large w3-block" onclick="closeEditSuccessModal()">OK</button>
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
                    <h2 style="margin: 0; font-family: Encode Sans Condensed;">Confirm Logout</h2>
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

        <!-- ARCHIVE MODAL -->
        <div id="archiveModal" class="w3-modal" style="display: none;">
            <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">
                <!-- Modal Header -->
                <header class="w3-container w3-center" style="padding: 16px; font-size: 18px;">
                    <h2 style="margin: 0; font-family: Encode Sans Condensed;">Confirm Archive</h2>
                </header>
                <!-- Modal Body -->
                <div class="w3-container w3-padding w3-center">
                    <p style="font-size: 16px; color: #444;">Are you sure you want to archive this report?</p>
                </div>
                <!-- Modal Footer -->
                <footer class="w3-container w3-padding w3-center" style="display: flex; gap: 10px; justify-content: center;">
                    <button class="w3-button w3-round w3-red w3-hover-dark-red" onclick="confirmArchive()" style="padding: 10px 20px;">Yes</button>
                    <button class="w3-button w3-round w3-gray w3-hover-dark-gray" onclick="closeArchiveModal()" style="padding: 10px 20px;">Cancel</button>
                </footer>
            </div>
        </div>

        <!-- RETRIEVE MODAL -->
        <div id="retrieveModal" class="w3-modal" style="display: none;">
            <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">
                <!-- Modal Header -->
                <header class="w3-container w3-center" style="padding: 16px; font-size: 18px;">
                    <h2 style="margin: 0; font-family: Encode Sans Condensed;">Confirm Retrieve</h2>
                </header>
                <!-- Modal Body -->
                <div class="w3-container w3-padding w3-center">
                    <p style="font-size: 16px; color: #444;">Are you sure you want to retrieve this report?</p>
                </div>
                <!-- Modal Footer -->
                <footer class="w3-container w3-padding w3-center" style="display: flex; gap: 10px; justify-content: center;">
                    <button class="w3-button w3-round w3-red w3-hover-dark-red" onclick="confirmRetrieve()" style="padding: 10px 20px;">Yes</button>
                    <button class="w3-button w3-round w3-gray w3-hover-dark-gray" onclick="closeRetrieveModal()" style="padding: 10px 20px;">Cancel</button>
                </footer>
            </div>
        </div>

        <!-- SUCCESS MODAL -->
        <div id="successModal" class="w3-modal" style="display: none;">
            <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">

                <header class="w3-container w3-center w3-green" style="padding: 16px; font-size: 18px;">
                    <h2 style="margin: 0; font-family: Encode Sans Condensed;">Success</h2>
                </header>

                <div class="w3-container w3-padding w3-center">
                    <p style="font-size: 16px; color: #444;">The operation was completed successfully!</p>
                </div>

                <footer class="w3-container w3-padding w3-center">
                    <button class="w3-button w3-round w3-green w3-hover-dark-green" onclick="closeSuccessModal()" style="padding: 10px 20px;">OK</button>
                </footer>
            </div>
        </div>

        <!-- ERROR MODAL -->
        <div id="errorModal" class="w3-modal" style="display: none;">
            <div class="w3-modal-content w3-card w3-animate-zoom" style="max-width: 400px; border-radius: 10px; overflow: hidden;">

                <header class="w3-container w3-center w3-red" style="padding: 16px; font-size: 18px;">
                    <h2 style="margin: 0; font-family: Encode Sans Condensed;">Error</h2>
                </header>

                <div class="w3-container w3-padding w3-center">
                    <p style="font-size: 16px; color: #444;">An error occurred while processing your request.</p>
                </div>

                <footer class="w3-container w3-padding w3-center">
                    <button class="w3-button w3-round w3-red w3-hover-dark-red" onclick="closeErrorModal()" style="padding: 10px 20px;">OK</button>
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

        

        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('section');
            let sectionHistory = [];

            // Function to display a section and track history
            const showSection = (sectionId) => {
                const target = document.getElementById(sectionId);
                if (!target) return;

                sections.forEach(section => section.style.display = 'none');
                target.style.display = 'block';
                sectionHistory.push(target);
            };

            // Expose function globally for button clicks
            window.showSection = showSection;
        });

        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'block';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmLogout() {
            window.location.href = "../users/logout.php"; // Redirect to logout
        }

        // GENERIC MODAL FUNCTIONS
        function openSuccessModal() {
            document.getElementById('successModal').style.display = 'block';

        }

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        function openErrorModal() {
            document.getElementById('errorModal').style.display = 'block';

        }

        function closeErrorModal() {
            document.getElementById('errorModal').style.display = 'none';
        }

        function showMatchSuccessModal() {
            document.getElementById('matchSuccessModal').style.display = 'block';
        }

        function closeMatchSuccessModal() {
            document.getElementById('matchSuccessModal').style.display = 'none';
        }

        // REFRESH TABLES
        function refreshRecentTable() {
            var tableBody = document.querySelector("#homerecentReportsTable tbody");
            if (!tableBody) return;

            // Show loading state
            tableBody.innerHTML = "<tr><td colspan='8' style='text-align: center; font-size: 18px;'><i class='fa fa-spinner fa-spin'></i> Refreshing...</td></tr>";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href, true); // Fetch the current page

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                    var updatedTableBody = doc.querySelector("#homerecentReportsTable tbody"); // Corrected ID

                    if (updatedTableBody) {
                        setTimeout(() => { // Delay to show loading animation
                            tableBody.innerHTML = updatedTableBody.innerHTML; // Replace only the table body
                        }, 500);
                    } else {
                        tableBody.innerHTML = "<tr><td colspan='8' style='text-align: center; color: red;'>Error refreshing table.</td></tr>";
                    }
                }
            };

            xhr.send();
        }

        function refreshFoundTable() {
            var tableBody = document.querySelector("#foundrecentReportsTable tbody");
            if (!tableBody) return;

            // Show loading state
            tableBody.innerHTML = "<tr><td colspan='9' style='text-align: center; font-size: 18px;'><i class='fa fa-spinner fa-spin'></i> Refreshing...</td></tr>";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href, true); // Fetch the current page

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                    var updatedTableBody = doc.querySelector("#foundrecentReportsTable tbody"); // Corrected ID

                    if (updatedTableBody) {
                        setTimeout(() => { // Delay to show loading animation
                            tableBody.innerHTML = updatedTableBody.innerHTML; // Replace only the table body
                        }, 500);
                    } else {
                        tableBody.innerHTML = "<tr><td colspan='9' style='text-align: center; color: red;'>Error refreshing table.</td></tr>";
                    }
                }
            };

            xhr.send();
        }

        function refreshLostTable() {
            var tableBody = document.querySelector("#lostrecentReportsTable tbody");
            if (!tableBody) return;

            // Show loading state
            tableBody.innerHTML = "<tr><td colspan='8' style='text-align: center; font-size: 18px;'><i class='fa fa-spinner fa-spin'></i> Refreshing...</td></tr>";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href, true); // Fetch the current page

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                    var updatedTableBody = doc.querySelector("#lostrecentReportsTable tbody"); // Corrected ID

                    if (updatedTableBody) {
                        setTimeout(() => { // Delay to show loading animation
                            tableBody.innerHTML = updatedTableBody.innerHTML; // Replace only the table body
                        }, 500);
                    } else {
                        tableBody.innerHTML = "<tr><td colspan='8' style='text-align: center; color: red;'>Error refreshing table.</td></tr>";
                    }
                }
            };

            xhr.send();
        }

        function refreshUnverifiedTable() {
            var tableBody = document.querySelector("#unverifiedrecentReportsTable tbody");
            if (!tableBody) return;

            // Show loading state
            tableBody.innerHTML = "<tr><td colspan='8' style='text-align: center; font-size: 18px;'><i class='fa fa-spinner fa-spin'></i> Refreshing...</td></tr>";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href, true); // Fetch the current page

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                    var updatedTableBody = doc.querySelector("#unverifiedrecentReportsTable tbody"); // Corrected ID

                    if (updatedTableBody) {
                        setTimeout(() => { // Delay to show loading animation
                            tableBody.innerHTML = updatedTableBody.innerHTML; // Replace only the table body
                        }, 500);
                    } else {
                        tableBody.innerHTML = "<tr><td colspan='8' style='text-align: center; color: red;'>Error refreshing table.</td></tr>";
                    }
                }
            };

            xhr.send();
        }

        function refreshPossibleTable() {
            var tableBody = document.querySelector("#possiblerecentReportsTable tbody");
            if (!tableBody) return;

            // Show loading state
            tableBody.innerHTML = "<tr><td colspan='7' style='text-align: center; font-size: 18px;'><i class='fa fa-spinner fa-spin'></i> Refreshing...</td></tr>";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href, true); // Fetch the current page

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                    var updatedTableBody = doc.querySelector("#possiblerecentReportsTable tbody"); // Corrected ID

                    if (updatedTableBody) {
                        setTimeout(() => { // Delay to show loading animation
                            tableBody.innerHTML = updatedTableBody.innerHTML; // Replace only the table body
                        }, 500);
                    } else {
                        tableBody.innerHTML = "<tr><td colspan='7' style='text-align: center; color: red;'>Error refreshing table.</td></tr>";
                    }
                }
            };

            xhr.send();
        }

        function refreshMatchedTable() {
            var tableBody = document.querySelector("#matchedrecentReportsTable tbody");
            if (!tableBody) return;

            // Show loading state
            tableBody.innerHTML = "<tr><td colspan='8' style='text-align: center; font-size: 18px;'><i class='fa fa-spinner fa-spin'></i> Refreshing...</td></tr>";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href, true); // Fetch the current page

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                    var updatedTableBody = doc.querySelector("#matchedrecentReportsTable tbody"); // Corrected ID

                    if (updatedTableBody) {
                        setTimeout(() => { // Delay to show loading animation
                            tableBody.innerHTML = updatedTableBody.innerHTML; // Replace only the table body
                        }, 500);
                    } else {
                        tableBody.innerHTML = "<tr><td colspan='8' style='text-align: center; color: red;'>Error refreshing table.</td></tr>";
                    }
                }
            };

            xhr.send();
        }

        function refreshUsersTable() {
            var tableBody = document.querySelector("#usersrecentReportsTable tbody");
            if (!tableBody) return;

            // Show loading state
            tableBody.innerHTML = "<tr><td colspan='6' style='text-align: center; font-size: 18px;'><i class='fa fa-spinner fa-spin'></i> Refreshing...</td></tr>";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href, true); // Fetch the current page

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                    var updatedTableBody = doc.querySelector("#usersrecentReportsTable tbody"); // Corrected ID

                    if (updatedTableBody) {
                        setTimeout(() => { // Delay to show loading animation
                            tableBody.innerHTML = updatedTableBody.innerHTML; // Replace only the table body
                        }, 500);
                    } else {
                        tableBody.innerHTML = "<tr><td colspan='6' style='text-align: center; color: red;'>Error refreshing table.</td></tr>";
                    }
                }
            };

            xhr.send();
        }

        function refreshArchiveTable() {
            var tableBody = document.querySelector("#archiverecentReportsTable tbody");
            if (!tableBody) return;

            // Show loading state
            tableBody.innerHTML = "<tr><td colspan='7' style='text-align: center; font-size: 18px;'><i class='fa fa-spinner fa-spin'></i> Refreshing...</td></tr>";

            var xhr = new XMLHttpRequest();
            xhr.open("GET", window.location.href, true); // Fetch the current page

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, "text/html");
                    var updatedTableBody = doc.querySelector("#archiverecentReportsTable tbody"); // Corrected ID

                    if (updatedTableBody) {
                        setTimeout(() => { // Delay to show loading animation
                            tableBody.innerHTML = updatedTableBody.innerHTML; // Replace only the table body
                        }, 500);
                    } else {
                        tableBody.innerHTML = "<tr><td colspan='7' style='text-align: center; color: red;'>Error refreshing table.</td></tr>";
                    }
                }
            };

            xhr.send();
        }

        function loadPage(page) {
            if (page < 1) return; // Prevent negative pages

            fetch(`?page=${page}&fetchTable=true`)
                .then(response => response.text())
                .then(data => {
                    document.querySelector("#homerecentReportsTable tbody").innerHTML = data;

                    // Get the actual number of reports loaded
                    let reportRows = document.querySelectorAll("#homerecentReportsTable tbody tr").length;

                    // Disable Next button only if the loaded reports are fewer than reportsPerPage
                    let reportsPerPage = 4; // Ensure this matches your PHP
                    document.getElementById("nextButton").disabled = (reportRows < reportsPerPage);

                    // Disable Previous button if on the first page
                    document.getElementById("prevButton").disabled = (page <= 1);

                    // Update button actions dynamically
                    document.getElementById("prevButton").setAttribute("onclick", `loadPage(${page - 1})`);
                    document.getElementById("nextButton").setAttribute("onclick", `loadPage(${page + 1})`);
                })
                .catch(error => console.error("Error fetching data:", error));
        }

        // Function to update Previous and Next button states
        function updatePaginationButtons(currentPage) {
            console.log("Updating buttons for page:", currentPage); // Debugging

            let totalPages = <?php echo $totalPages; ?>;
            document.getElementById("prevButton").disabled = (currentPage <= 1);
            document.getElementById("nextButton").disabled = (currentPage >= totalPages);

            // Update button onclick events dynamically
            document.getElementById("prevButton").setAttribute("onclick", `loadPage(${currentPage - 1})`);
            document.getElementById("nextButton").setAttribute("onclick", `loadPage(${currentPage + 1})`);
        }

        // Initialize buttons correctly when the page loads
        document.addEventListener("DOMContentLoaded", function () {
            updatePaginationButtons(<?php echo $page; ?>);
        });

    </script>

    <script src="../scripts/admin.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.3/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

</body>

</html>
