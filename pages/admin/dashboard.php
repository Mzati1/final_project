<?php

// Start session
session_start();

// Include the admin auth checker
require __DIR__ . "/../../includes/configs/adminAuthChecks.php";

require __DIR__ . "/../../includes/configs/database.php";
require __DIR__ . "/../../includes/functions/queryExecutor.php";

// SQL queries

// Dashboard queries
$getTotalVoterSql = "
    SELECT COUNT(DISTINCT user_id) AS total_voters
    FROM votes;
";

$getTotalCandidatesSql = "
    SELECT COUNT(*) AS total_candidates
    FROM candidates;
";

$getActiveElectionsSql = "
    SELECT COUNT(*) AS active_elections
    FROM elections
    WHERE election_status = 'open';
";

$getCompletedElectionsSql = "
    SELECT COUNT(*) AS completed_elections
    FROM elections
    WHERE election_status = 'closed';
";

$getRecentElectionsSql = "
    SELECT election_name, election_status, modified_at
    FROM elections
    ORDER BY modified_at DESC
    LIMIT 5;
";

// Users panel query
$getUsersDataSql = "
SELECT 
    students.first_name AS first_name,
    students.last_name AS last_name,
    students.email AS email,
    students.reg_number AS reg_number,
    students.is_registered AS status,
    students.created_at AS account_created_at,
    students.modified_at AS account_modified_at,
    users.user_id AS id
FROM 
    students
JOIN 
    users ON students.student_id = users.student_id
ORDER BY 
    students.created_at DESC;
";

// Elections panel queries
$getAllElectionsDataSql = "
SELECT 
    election_id AS election_id,
    election_name AS election_name,
    start_date,
    end_date,
    election_status AS status,
    created_at,
    modified_at AS updated_at
FROM elections
ORDER BY created_at DESC;
";

// Candidates panel query
$getCandidatesDataSql = "
SELECT 
    JSON_OBJECT(
        'election_id', e.election_id,
        'election_name', e.election_name,
        'start_date', e.start_date,
        'end_date', e.end_date,
        'election_status', e.election_status,
        'positions', (
            SELECT 
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'position_id', p.position_id,
                        'position_name', p.position_name,
                        'position_description', p.position_description,
                        'candidates', (
                            SELECT 
                                JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        'candidate_id', c.candidate_id,
                                        'first_name', c.first_name,
                                        'last_name', c.last_name,
                                        'image_url', c.image_url,
                                        'manifesto', c.manifesto
                                    )
                                )
                            FROM candidates c
                            WHERE c.position_id = p.position_id
                        )
                    )
                )
            FROM positions p
            WHERE p.election_id = e.election_id
        )
    ) AS election_details
FROM elections e;
";

$modalElectionDataSql = "
SELECT 
    e.election_id,
    e.election_name,
    e.start_date,
    e.end_date,
    e.election_status,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'position_id', p.position_id,
            'position_name', p.position_name,
            'position_description', p.position_description
        )
    ) AS positions
FROM elections e
LEFT JOIN positions p ON e.election_id = p.election_id
GROUP BY e.election_id;
";


//REPORTS SECTION

$getUserReportsMetricsSql = "
 SELECT 
    (SELECT COUNT(DISTINCT v.user_id) 
     FROM votes v) AS total_users_voted,

    -- Total users who have not voted
    (SELECT COUNT(DISTINCT u.user_id) 
     FROM users u 
     LEFT JOIN votes v ON u.user_id = v.user_id 
     WHERE v.user_id IS NULL) AS users_not_voted,

    -- Total registered users
    (SELECT COUNT(u.user_id) 
     FROM users u
     INNER JOIN students s ON u.student_id = s.student_id
     WHERE s.is_registered = TRUE) AS total_registered_users,

    -- Voter turnout percentage
    (SELECT 
        (COUNT(DISTINCT v.user_id) / 
         (SELECT COUNT(u.user_id) 
          FROM users u 
          INNER JOIN students s ON u.student_id = s.student_id 
          WHERE s.is_registered = TRUE)) * 100 
     FROM votes v) AS voter_turnout_percentage,

    -- Recently joined users (limit to 5 most recent)
    (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'user_id', u.user_id,
                'first_name', s.first_name,
                'last_name', s.last_name,
                'email', s.email,
                'created_at', u.created_at
            )
     )
     FROM users u
     INNER JOIN students s ON u.student_id = s.student_id
     ORDER BY u.created_at DESC
     LIMIT 5) AS recent_users;

";


// Step 2: Execute SQL queries and fetch results

// Dashboard queries
$getTotalVoterStatement = executeQuery($pdo, $getTotalVoterSql);
$totalVoters = $getTotalVoterStatement ? $getTotalVoterStatement->fetch(PDO::FETCH_ASSOC) : ['total_voters' => 0];

$getTotalCandidatesStatement = executeQuery($pdo, $getTotalCandidatesSql);
$totalCandidates = $getTotalCandidatesStatement ? $getTotalCandidatesStatement->fetch(PDO::FETCH_ASSOC) : ['total_candidates' => 0];

$getActiveElectionsSqlStatement = executeQuery($pdo, $getActiveElectionsSql);
$activeElections = $getActiveElectionsSqlStatement ? $getActiveElectionsSqlStatement->fetch(PDO::FETCH_ASSOC) : ['active_elections' => 0];

$getCompletedElectionsSqlStatement = executeQuery($pdo, $getCompletedElectionsSql);
$completedElections = $getCompletedElectionsSqlStatement ? $getCompletedElectionsSqlStatement->fetch(PDO::FETCH_ASSOC) : ['completed_elections' => 0];

$getRecentElectionsSqlStatement = executeQuery($pdo, $getRecentElectionsSql);
$recentElections = $getRecentElectionsSqlStatement ? $getRecentElectionsSqlStatement->fetchAll(PDO::FETCH_ASSOC) : [];

// Users panel queries
$getUsersDataSqlStatement = executeQuery($pdo, $getUsersDataSql);
$usersData = $getUsersDataSqlStatement ? $getUsersDataSqlStatement->fetchAll(PDO::FETCH_ASSOC) : [];

// Elections panel queries
$getAllElectionsDataSqlStatement = executeQuery($pdo, $getAllElectionsDataSql);
$allElectionsData = $getAllElectionsDataSqlStatement ? $getAllElectionsDataSqlStatement->fetchAll(PDO::FETCH_ASSOC) : [];

// Candidates panel queries
$getCandidatesDataSqlStatement = executeQuery($pdo, $getCandidatesDataSql);
$allCandidatesData = $getCandidatesDataSqlStatement ? $getCandidatesDataSqlStatement->fetchAll(PDO::FETCH_ASSOC) : [];

$getModalElectionDataStatement = executeQuery($pdo, $modalElectionDataSql);
$electionsData = $getModalElectionDataStatement ? $getModalElectionDataStatement->fetchAll(PDO::FETCH_ASSOC) : [];

//reports section
//user reports
$getUserReportMetricsStatement =  executeQuery($pdo, $getUserReportsMetricsSql);
$userReportMetricsData = $getUserReportMetricsStatement ? $getUserReportMetricsStatement->fetchAll(PDO::FETCH_ASSOC) : [];

// Prepare the result as an associative arraygetUserReportMetricsStatement
$dashboardMetrics = [
    // Dashboard panel
    'total_voters' => $totalVoters['total_voters'],
    'total_candidates' => $totalCandidates['total_candidates'],
    'active_elections' => $activeElections['active_elections'],
    'completed_elections' => $completedElections['completed_elections'],
    'recent_elections' => $recentElections,

    // Users panel
    'users' => $usersData,

    // Elections panel
    'all_elections' => $allElectionsData,

    // Candidates panel
    'all_candidates' => $allCandidatesData,
    'elections_with_positions' => $electionsData,

    //reports panel
    //user reports
    'userReport_metrics' => $userReportMetricsData
];

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/admin/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <!-- Admin Panel Sidebar -->
        <div class="sidebar">
            <h3>
                <?php

                if ($_SESSION['role'] === "ADMIN") {
                    echo "ADMIN";
                } else if ($_SESSION['role'] === "MEC") {
                    echo "MEC";
                } else {
                    echo "DoSA";
                }

                ?>

                Dashboard
            </h3>
            <ul>
                <!-- Sidebar items that act as tabs -->
                <li><a href="javascript:void(0);" class="tab-link" data-tab="dashboard">Dashboard</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="manage-users">Users</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="manage-candidates">Candidates</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="manage-elections">Elections</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="manage-auditLogs">Audit Logs</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="view-reports">Reports</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="settings">Results</a></li>
                <li><a href="../../includes/functions/logout.php">Logout</a></li>
            </ul>
        </div>

        <!-- Dashboard Content (Tab content will be loaded here) -->
        <div class="main-content">

            <!-- Admin Panel Topbar -->
            <div class="top-bar">
                <div class="user-info">
                    <p style="font-size:20px;"><b>Welcome back,
                            <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </b></p>
                    <div class="user-icon" onclick="toggleDropdown()"></div>
                </div>
            </div>

            <!-- Loader (Initially Hidden) -->
            <div id="loader" class="loader" style="display: none;"></div>

            <!-- Content for the Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <h2>Admin Dashboard</h2>

                <!-- Stats Section -->
                <div class="stats">
                    <div class="stat-box">
                        <h3>Total Voters</h3>
                        <div class="stat-value">
                            <?= $totalVoters['total_voters']; ?>
                        </div>
                    </div>
                    <div class="stat-box">
                        <h3>Total Candidates</h3>
                        <div class="stat-value">
                            <?= $totalCandidates['total_candidates']; ?>
                        </div>
                    </div>
                    <div class="stat-box">
                        <h3>Active Elections</h3>
                        <div class="stat-value">
                            <?= $activeElections['active_elections']; ?>
                        </div>
                    </div>
                    <div class="stat-box">
                        <h3>Completed Elections</h3>
                        <div class="stat-value">
                            <?= $completedElections['completed_elections']; ?>
                        </div>
                    </div>
                </div>

                <!-- Chart Section -->
                <h3>Recent Elections</h3>
                <canvas id="myChart" width="400" height="150"></canvas>

                <!-- Recent Activity Section -->
                <h3>Recent Activity</h3>
                <table class="recent-activity">
                    <thead>
                        <tr>
                            <th>Election</th>
                            <th>Status</th>
                            <th>Modified at</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentElections as $election): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($election['election_name']); ?>
                                </td>
                                <td>
                                    <span class="status <?= strtolower($election['election_status']); ?>"
                                        style="display: inline-block; padding: 5px 10px; border-radius: 15px; color: white; font-size: 15px; <?= strtolower($election['election_status']) == 'open' ? 'background-color: #28a745;' : 'background-color: #dc3545;'; ?>">
                                        <?= htmlspecialchars($election['election_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $modifiedAt = strtotime($election['modified_at']);
                                    $timeDifference = time() - $modifiedAt;
                                    $seconds = $timeDifference;
                                    $minutes = round($seconds / 60);
                                    $hours = round($seconds / 3600);
                                    $days = round($seconds / 86400);
                                    $weeks = round($seconds / 604800);
                                    $months = round($seconds / 2629440);
                                    $years = round($seconds / 31553280);

                                    if ($seconds <= 60) {
                                        echo "Just now";
                                    } else if ($minutes <= 60) {
                                        echo ($minutes == 1) ? "one minute ago" : "$minutes minutes ago";
                                    } else if ($hours <= 24) {
                                        echo ($hours == 1) ? "an hour ago" : "$hours hours ago";
                                    } else if ($days <= 7) {
                                        echo ($days == 1) ? "yesterday" : "$days days ago";
                                    } else if ($weeks <= 4.3) {
                                        echo ($weeks == 1) ? "a week ago" : "$weeks weeks ago";
                                    } else if ($months <= 12) {
                                        echo ($months == 1) ? "a month ago" : "$months months ago";
                                    } else {
                                        echo ($years == 1) ? "one year ago" : "$years years ago";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

            <!-- Content for the Manage Users Tab -->
            <div id="manage-users" class="tab-content">
                <h2>Manage Users</h2>
                <p>Below is a list of users with their information.</p>

                <div class="table-responsive">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Reg Number</th>
                                <th>Status</th>
                                <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersData as $user): ?>
                                <tr id="user-<?php echo $user['id']; ?>" data-user-id="<?php echo $user['id']; ?>">
                                    <td>
                                        <?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                    </td>
                                    <td>
                                        <?php echo $user['email']; ?>
                                    </td>
                                    <td>
                                        <?php echo $user['reg_number'] ?: 'N/A'; ?>
                                    </td>
                                    <td class="<?php echo $user['status'] == 'Active' ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['status']; ?>
                                    </td>
                                    <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>
                                        <td>
                                            <button class="btn-delete"
                                                onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- content for the manage elections tab -->
            <div id="manage-elections" class="tab-content">
                <h2>Manage Elections</h2>
                <p>Below is a list of elections with their details.</p>


                <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>

                    <!-- Button to Open the Modal -->
                    <button id="new-election-btn" class="btn btn-primary" onclick="openNewElectionModal()"
                        style="display: inline-block;padding: 12px 24px;font-size: 16px;font-weight: bold;color: white;background-color: #28a745; /* Green color */border: none;border-radius: 8px;cursor: pointer;text-align: center;transition: background-color 0.3s ease;margin-top: 30px;"
                        onmouseover="this.style.backgroundColor='#218838'"
                        onmouseout="this.style.backgroundColor='#28a745'">
                        New Election
                    </button>

                <?php endif; ?>


                <!-- Modal HTML -->
                <div id="new-election-modal"
                    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; padding-top: 50px; overflow-y: auto;">
                    <div
                        style="background-color: #fff; max-width: 600px; margin: auto; padding: 20px; border-radius: 8px; position: relative;">
                        <button id="close-modal-btn"
                            style="position: absolute; top: 10px; right: 10px; background: transparent; border: none; font-size: 20px; color: #333;">&times;</button>
                        <h2>Create New Election</h2>
                        <form id="election-form" style="display: flex; flex-direction: column; gap: 15px;">
                            <label for="election-name">Election Name:</label>
                            <input type="text" id="election-name" name="election_name" placeholder="Enter election name"
                                style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;" required>

                            <label for="start-date">Start Date:</label>
                            <input type="date" id="start-date" name="start_date"
                                style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;" required>

                            <label for="end-date">End Date:</label>
                            <input type="date" id="end-date" name="end_date"
                                style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;" required>

                            <label for="status">Status:</label>
                            <select id="status" name="status"
                                style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;" required>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>

                            <div id="positions-section" style="margin-top: 20px;">
                                <h3>Positions</h3>
                                <button type="button" id="add-position-btn"
                                    style="padding: 8px 16px; border: none; background-color: #007bff; color: white; border-radius: 5px; cursor: pointer;">Add
                                    Position</button>
                                <div id="positions-container" style="margin-top: 15px;">
                                    <!-- Dynamic Position Fields will be added here -->
                                </div>
                            </div>

                            <button type="submit" id="submit-election-btn"
                                style="padding: 10px 20px; border: none; background-color: #28a745; color: white; border-radius: 5px; cursor: pointer;">Submit</button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Election Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Modified</th>
                                <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allElectionsData as $election): ?>
                                <tr id="election-<?php echo $election['election_id']; ?>"
                                    data-election-id="<?php echo $election['election_id']; ?>">
                                    <td><span class="editable-field">
                                            <?php echo htmlspecialchars($election['election_name']); ?>
                                        </span></td>
                                    <td><span class="editable-field">
                                            <?php echo htmlspecialchars($election['start_date']); ?>
                                        </span></td>
                                    <td><span class="editable-field">
                                            <?php echo htmlspecialchars($election['end_date']); ?>
                                        </span></td>
                                    <td><span class="editable-field">
                                            <?php echo htmlspecialchars($election['status']); ?>
                                        </span></td>
                                    <td>
                                        <?php echo htmlspecialchars($election['created_at']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $modifiedAt = strtotime($election['updated_at']);
                                        $timeDifference = time() - $modifiedAt;

                                        // Calculate difference in time units
                                        $seconds = $timeDifference;
                                        $minutes = round($seconds / 60);
                                        $hours = round($seconds / 3600);
                                        $days = round($seconds / 86400);
                                        $weeks = round($seconds / 604800);
                                        $months = round($seconds / 2629440);
                                        $years = round($seconds / 31553280);

                                        // Format the time difference
                                        if ($seconds <= 60) {
                                            echo "Just now";
                                        } else if ($minutes <= 60) {
                                            echo ($minutes == 1) ? "one minute ago" : "$minutes minutes ago";
                                        } else if ($hours <= 24) {
                                            echo ($hours == 1) ? "an hour ago" : "$hours hours ago";
                                        } else if ($days <= 7) {
                                            echo ($days == 1) ? "yesterday" : "$days days ago";
                                        } else if ($weeks <= 4.3) {
                                            echo ($weeks == 1) ? "a week ago" : "$weeks weeks ago";
                                        } else if ($months <= 12) {
                                            echo ($months == 1) ? "a month ago" : "$months months ago";
                                        } else {
                                            echo ($years == 1) ? "one year ago" : "$years years ago";
                                        }
                                        ?>
                                    </td>

                                    <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>
                                        <td>
                                            <button class="btn-edit" onclick="toggleEdit(this)">Edit</button>
                                            <button class="btn-delete"
                                                onclick="deleteElection(<?php echo $election['election_id']; ?>)">Delete</button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-content" id="manage-candidates">
                <h2>Manage Candidates</h2>
                <p>Below is a list of candidates grouped by their elections.</p>

                <!-- Modal HTML -->

                <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>
                    <!-- Button to Open Add Candidate Modal -->
                    <button id="new-candidate-btn"
                        style="display: inline-block;padding: 12px 24px;font-size: 16px;font-weight: bold;color: white;background-color: #28a745; /* Green color */border: none;border-radius: 8px;cursor: pointer;text-align: center;transition: background-color 0.3s ease;margin-top: 30px;"
                        onmouseover="this.style.backgroundColor='#218838'" onmouseout="this.style.backgroundColor='#28a745'"
                        class="btn btn-primary" onclick="openNewCandidateModal()">Add New Candidate</button>
                <?php endif; ?>


                <!-- Candidates Table -->
                <?php
                // Loop through all elections and display each election with its candidates
                foreach ($allCandidatesData as $electionData) {
                    // Decode the JSON string for each election and its details
                    $electionDetails = json_decode($electionData['election_details'], true);

                    // Get election name
                    $electionName = $electionDetails['election_name'];  // Election name
                    $positions = isset($electionDetails['positions']) && is_array($electionDetails['positions']) ? $electionDetails['positions'] : [];

                ?>
                    <div class="election-group">
                        <h3>
                            <?php echo htmlspecialchars($electionName); ?>
                        </h3>

                        <!-- Check if positions exist for this election -->
                        <?php if (count($positions) > 0): ?>
                            <div class="table-responsive">
                                <table class="candidates-table">
                                    <thead>
                                        <tr>
                                            <th>Position</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Image</th>
                                            <th>Manifesto</th>

                                            <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>
                                                <th>Actions</th>
                                            <?php endif; ?>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Loop through each position in the current election
                                        foreach ($positions as $position) {
                                            $positionName = $position['position_name'];
                                            $candidates = isset($position['candidates']) ? $position['candidates'] : [];

                                            // Check if candidates exist for this position
                                            if (count($candidates) > 0) {
                                                // Loop through each candidate for the current position
                                                foreach ($candidates as $candidate) {
                                                    $candidateId = $candidate['candidate_id'];
                                                    $firstName = htmlspecialchars($candidate['first_name']);
                                                    $lastName = htmlspecialchars($candidate['last_name']);
                                                    $imageUrl = $candidate['image_url'] ? $candidate['image_url'] : 'https://via.placeholder.com/50';
                                                    $manifesto = htmlspecialchars($candidate['manifesto']); ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo htmlspecialchars($positionName); ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $firstName; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $lastName; ?>
                                                        </td>
                                                        <td><img src="<?php echo $imageUrl; ?>" alt="Candidate Image" width="50"></td>
                                                        <td>
                                                            <?php echo $manifesto; ?>
                                                        </td>

                                                        <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>
                                                            <td>
                                                                <button class="btn-edit"
                                                                    onclick="editCandidate(<?php echo $candidateId; ?>)">Edit</button>
                                                                <button class="btn-delete"
                                                                    onclick="deleteCandidate(<?php echo $candidateId; ?>)">Delete</button>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                        <?php
                                                }
                                            } else {
                                                // Display message if no candidates for this position
                                                echo '<tr><td colspan="6" style="text-align: center; color: red; font-weight: bold;">No candidates in this position.</td></tr>';
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <!-- Display message if no positions exist for this election -->
                            <p style="color: red; font-weight: bold; text-align: center;">No positions set for this election.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php
                }
                ?>
            </div>

            <div id="manage-auditLogs" class="tab-content">
                <h2>Manage Audit Logs</h2>
                <p style="margin-bottom: 10px;">Below is a list of audit logs grouped by type.</p>

                <div class="audit-tab-nav">
                    <button id="login-audit-tab" class="audit-tab active-tab"
                        onclick="showAuditTab('login-audit')">Login Audits</button>
                    <button id="vote-audit-tab" class="audit-tab" onclick="showAuditTab('vote-audit')">Voting
                        Audits</button>
                </div>

                <!-- Login Audit Content -->
                <div id="login-audit" class="audit-tab-content active">
                    <h3>Login Audit Logs</h3>
                    <p>Details of login attempts, including successful and failed attempts.</p>
                    <!-- Table structure for login audits -->
                    <div class="table-responsive">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Attempted Account</th>
                                    <th>Account Type</th>
                                    <th>Login Time</th>
                                    <th>IP Address</th>
                                    <th>Client</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows with login audit data would be added here live -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vote Audit Content -->
                <div id="vote-audit" class="audit-tab-content">
                    <h3>Vote Audit Logs</h3>
                    <p>Details of Votes made</p>
                    <!-- Table structure for vote audits -->
                    <div class="table-responsive">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Candidate Name</th>
                                    <th>Election</th>
                                    <th>Position</th>
                                    <th>Vote Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows with vote audit data would be added here live -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <div class="tab-content" id="view-reports">
                <h2 style="font-size: 1.75rem; font-weight: 600; color: #333; margin-bottom: 0.5em;">Audit Reports</h2>
                <p style="font-size: 1rem; color: #666; margin-bottom: 1.5em;">Below is a list of audit logs grouped by
                    type. Select
                    a tab to view specific report details.</p>

                <!-- Reports Tab Navigation -->
                <div class="report-tab-nav">
                    <button id="user-report-tab" class="report-tab active-report-tab"
                        onclick="showReportTab('user-report')">User
                        Reports</button>
                    <button id="login-report-tab" class="report-tab" onclick="showReportTab('login-report')">Login
                        Reports</button>
                    <button id="election-report-tab" class="report-tab"
                        onclick="showReportTab('election-report')">Election
                        Reports</button>
                    <button id="voting-activity-report-tab" class="report-tab"
                        onclick="showReportTab('voting-activity-report')">Voting Activity Reports</button>
                </div>

                <!-- Reports Tab Content Sections -->
                <div id="view-reports">
                    <div id="user-report" class="report-tab-content active-report-content">

                        <!-- Download PDF Button -->
                        <div class="download-container">
                            <button class="download-button">Download PDF</button>
                        </div>

                        <!-- Pie Chart Section -->
                        <h3>User Voting Activity</h3>
                        <div class="chart-container">
                            <canvas id="userRegistrationPieChart"></canvas>
                        </div>

                        <!-- Stats Boxes Section -->
                        <div class="stats-container">
                            <div class="stat-box">
                                <h4>Users who've Voted</h4>
                                <p id="total-unregistered"><?php echo htmlspecialchars($userReportMetricsData[0]['total_users_voted']) ?> </p>
                            </div>
                            <div class="stat-box">
                                <h4>Users not Voted</h4>
                                <p id="total-registered"><?php echo htmlspecialchars($userReportMetricsData[0]['users_not_voted']) ?> </p>
                            </div>
                            <div class="stat-box">
                                <h4>Registerd users</h4>
                                <p id="total-admins"><?php echo htmlspecialchars($userReportMetricsData[0]['total_registered_users']) ?> </p>
                            </div>
                            <div class="stat-box">
                                <h4>Voter Turnout Percentage</h4>
                                <p id="total-mec-staff"><?php echo htmlspecialchars(round($userReportMetricsData[0]['voter_turnout_percentage'], 0)) ?> %</p>
                            </div>
                        </div>

                        <!-- Recently Joined Users Table -->
                        <div class="recent-users-table-container">
                            <h3 id="recent-users-header">Recently Joined Users</h3>
                            <table id="recent-users-table">
                                <thead>
                                    <tr>
                                        <th class="recent-users-column-name">Name</th>
                                        <th class="recent-users-column-email">Email</th>
                                        <th class="recent-users-column-registration-date">Registration Date</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-users-tbody">
                                    <?php
                                    // Check if there are recent users
                                    if (!empty($userReportMetricsData['recent_users'])) {
                                        // Loop through each user and display their data
                                        foreach ($userReportMetricsData['recent_users'] as $user) {
                                            echo '<tr class="recent-users-table-row">';
                                            // Display name
                                            echo '<td class="recent-users-name">' . htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']) . '</td>';
                                            // Display email
                                            echo '<td class="recent-users-email">' . htmlspecialchars($user['email']) . '</td>';
                                            // Display registration date
                                            echo '<td class="recent-users-registration-date">' . date('Y-m-d', strtotime($user['created_at'])) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr class="recent-users-empty"><td colspan="3">No recent users found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <div id="login-report" class="report-tab-content">
                        <!-- Content for Login Reports (empty for now) -->
                    </div>
                    <div id="election-report" class="report-tab-content">
                        <!-- Content for Election Reports (empty for now) -->
                    </div>
                    <div id="voting-activity-report" class="report-tab-content">
                        <!-- Content for Voting Activity Reports (empty for now) -->
                    </div>
                </div>
            </div>

        </div>
    </div>



    <!--tab switching logic ( both sub tabs and main tabs guys)-->
    <script src="../../assets//js/admin/tabSwitchingLogic.js"></script>

    <!---live audit logs-->
    <script src="../../assets/js/admin/liveAuditLogs.js"></script>

    <!--main dashboard chart-->
    <script src="../../assets/js/admin/dashboardMain.js"></script>

    <!--ELECTIONS MODAL-->
    <script>
        //create election modal
        // Open the modal
        function openNewElectionModal() {
            document.getElementById('new-election-modal').style.display = 'block';
        }

        // Close the modal
        function closeModal() {
            document.getElementById('new-election-modal').style.display = 'none';
        }

        // Close modal event listener
        document.getElementById('close-modal-btn').addEventListener('click', closeModal);

        // Handle add position button click
        document.getElementById('add-position-btn').addEventListener('click', function() {
            const positionsContainer = document.getElementById('positions-container');

            // Create new position input fields
            const positionGroup = document.createElement('div');
            positionGroup.classList.add('position-group');
            positionGroup.style.display = 'flex';
            positionGroup.style.alignItems = 'center';
            positionGroup.style.gap = '10px';
            positionGroup.style.marginBottom = '10px';

            // Position Name input field
            const positionInput = document.createElement('input');
            positionInput.type = 'text';
            positionInput.placeholder = 'Position Name';
            positionInput.name = 'position_name[]'; // Make it an array to store multiple positions
            positionInput.style.width = 'calc(100% - 100px)'; // Adjust width to leave space for the remove button
            positionInput.style.padding = '10px';
            positionInput.style.border = '1px solid #ccc';
            positionInput.style.borderRadius = '5px';

            // Position Description input field
            const positionDescInput = document.createElement('textarea');
            positionDescInput.placeholder = 'Position Description';
            positionDescInput.name = 'position_description[]'; // Make it an array to store multiple descriptions
            positionDescInput.style.width = 'calc(100% - 100px)'; // Adjust width to leave space for the remove button
            positionDescInput.style.padding = '10px';
            positionDescInput.style.border = '1px solid #ccc';
            positionDescInput.style.borderRadius = '5px';
            positionDescInput.style.height = '60px';

            const removeButton = document.createElement('button');
            removeButton.textContent = 'Remove';
            removeButton.style.backgroundColor = '#dc3545';
            removeButton.style.color = 'white';
            removeButton.style.padding = '5px 10px';
            removeButton.style.border = 'none';
            removeButton.style.cursor = 'pointer';
            removeButton.addEventListener('click', function() {
                positionsContainer.removeChild(positionGroup);
            });

            positionGroup.appendChild(positionInput);
            positionGroup.appendChild(positionDescInput);
            positionGroup.appendChild(removeButton);
            positionsContainer.appendChild(positionGroup);
        });

        // Handle form submission via AJAX
        document.getElementById('election-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting normally

            const formData = new FormData(this);
            const positions = [];

            // Collect the positions data
            const positionInputs = document.querySelectorAll('[name="position_name[]"]');
            const positionDescInputs = document.querySelectorAll('[name="position_description[]"]');
            positionInputs.forEach(function(input, index) {
                if (input.value && positionDescInputs[index].value) {
                    positions.push({
                        name: input.value,
                        description: positionDescInputs[index].value
                    }); // Push position names and descriptions to the array
                }
            });

            // Add positions data to the form data
            formData.append('positions', JSON.stringify(positions));

            // Send AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../../includes/functions/admin/createElection.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('Election created successfully!');
                        closeModal(); // Close the modal after successful creation
                    } else {
                        alert('Failed to create election.');
                    }
                }
            };
            xhr.send(formData);
        });
    </script>

</body>

</html>