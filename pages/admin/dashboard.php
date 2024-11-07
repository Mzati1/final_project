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
    SELECT COUNT(DISTINCT student_id) AS total_voters
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

// Prepare the result as an associative array
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
    'elections_with_positions' => $electionsData
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
            <h3>MEC Dashboard</h3>
            <ul>
                <!-- Sidebar items that act as tabs -->
                <li><a href="javascript:void(0);" class="tab-link" data-tab="dashboard">Dashboard</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="manage-users">Users</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="manage-candidates">Candidates</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="manage-elections">Elections</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="view-logs">Audit Logs</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="results">Reports</a></li>
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
                    <div class="dropdown" id="profileDropdown">
                        <ul>
                            <li><a href="#">Profile</a></li>
                        </ul>
                    </div>
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

                <!-- Button to Open the Modal -->
                <button id="new-election-btn"
                    class="btn btn-primary"
                    onclick="openNewElectionModal()"
                    style="display: inline-block;padding: 12px 24px;font-size: 16px;font-weight: bold;color: white;background-color: #28a745; /* Green color */border: none;border-radius: 8px;cursor: pointer;text-align: center;transition: background-color 0.3s ease;margin-top: 30px;" onmouseover="this.style.backgroundColor='#218838'" onmouseout="this.style.backgroundColor='#28a745'">
                    New Election
                </button>

                <!-- Modal HTML -->
                <div id="new-election-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; padding-top: 50px; overflow-y: auto;">
                    <div style="background-color: #fff; max-width: 600px; margin: auto; padding: 20px; border-radius: 8px; position: relative;">
                        <button id="close-modal-btn" style="position: absolute; top: 10px; right: 10px; background: transparent; border: none; font-size: 20px; color: #333;">&times;</button>
                        <h2>Create New Election</h2>
                        <form id="election-form" style="display: flex; flex-direction: column; gap: 15px;">
                            <label for="election-name">Election Name:</label>
                            <input type="text" id="election-name" name="election_name" placeholder="Enter election name" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;" required>

                            <label for="start-date">Start Date:</label>
                            <input type="date" id="start-date" name="start_date" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;" required>

                            <label for="end-date">End Date:</label>
                            <input type="date" id="end-date" name="end_date" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;" required>

                            <label for="status">Status:</label>
                            <select id="status" name="status" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;" required>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>

                            <div id="positions-section" style="margin-top: 20px;">
                                <h3>Positions</h3>
                                <button type="button" id="add-position-btn" style="padding: 8px 16px; border: none; background-color: #007bff; color: white; border-radius: 5px; cursor: pointer;">Add Position</button>
                                <div id="positions-container" style="margin-top: 15px;">
                                    <!-- Dynamic Position Fields will be added here -->
                                </div>
                            </div>

                            <button type="submit" id="submit-election-btn" style="padding: 10px 20px; border: none; background-color: #28a745; color: white; border-radius: 5px; cursor: pointer;">Submit</button>
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
                                <tr id="election-<?php echo $election['election_id']; ?>" data-election-id="<?php echo $election['election_id']; ?>">
                                    <td><span class="editable-field"><?php echo htmlspecialchars($election['election_name']); ?></span></td>
                                    <td><span class="editable-field"><?php echo htmlspecialchars($election['start_date']); ?></span></td>
                                    <td><span class="editable-field"><?php echo htmlspecialchars($election['end_date']); ?></span></td>
                                    <td><span class="editable-field"><?php echo htmlspecialchars($election['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($election['created_at']); ?></td>
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
                                            <button class="btn-delete" onclick="deleteElection(<?php echo $election['election_id']; ?>)">Delete</button>
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
                <div id="new-candidate-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; padding-top: 50px;">
                    <div style="background-color: #fff; max-width: 600px; margin: auto; padding: 20px; border-radius: 8px; position: relative;">
                        <button id="close-candidate-modal-btn" style="position: absolute; top: 10px; right: 10px; background: transparent; border: none; font-size: 20px; color: #333;">&times;</button>
                        <h2>Add New Candidate</h2>
                        <form id="candidate-form" style="display: flex; flex-direction: column; gap: 20px;">
                            <!-- 2x2 Grid Layout for Name and Election Fields -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <!-- First Name -->
                                <div>
                                    <label for="first-name">First Name:</label>
                                    <input type="text" id="first-name" name="first_name" placeholder="Enter first name" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
                                </div>

                                <!-- Last Name -->
                                <div>
                                    <label for="last-name">Last Name:</label>
                                    <input type="text" id="last-name" name="last_name" placeholder="Enter last name" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
                                </div>

                                <!-- Election Dropdown -->
                                <div>
                                    <label for="election">Election:</label>
                                    <select id="election" name="election" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
                                        <option value="">Select Election</option>
                                        <?php foreach ($electionsData as $election): ?>
                                            <option value="<?php echo $election['election_id']; ?>"><?php echo htmlspecialchars($election['election_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Position Dropdown -->
                                <div>
                                    <label for="position">Position:</label>
                                    <select id="position" name="position" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
                                        <option value="">Select Position</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Image Upload (Below the 2x2 Grid) -->
                            <div>
                                <label for="candidate-image">Upload Image:</label>
                                <input type="file" id="candidate-image" name="candidate_image" accept="image/*" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; width: 100%;">
                            </div>

                            <!-- Manifesto Text Area -->
                            <div>
                                <label for="manifesto">Manifesto:</label>
                                <textarea id="manifesto" name="manifesto" placeholder="Enter manifesto" rows="4" style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; width: 100%;"></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div>
                                <button type="submit" id="submit-candidate-btn" style="padding: 10px 20px; border: none; background-color: #28a745; color: white; border-radius: 5px; cursor: pointer; width: 100%;">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Button to Open Add Candidate Modal -->
                <button id="new-candidate-btn" style="display: inline-block;padding: 12px 24px;font-size: 16px;font-weight: bold;color: white;background-color: #28a745; /* Green color */border: none;border-radius: 8px;cursor: pointer;text-align: center;transition: background-color 0.3s ease;margin-top: 30px;" onmouseover="this.style.backgroundColor='#218838'" onmouseout="this.style.backgroundColor='#28a745'"
                    class="btn btn-primary" onclick="openNewCandidateModal()">Add New Candidate</button>

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
                        <h3><?php echo htmlspecialchars($electionName); ?></h3>

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
                                            <th>Actions</th>
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
                                                    $manifesto = htmlspecialchars($candidate['manifesto']);
                                        ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($positionName); ?></td>
                                                        <td><?php echo $firstName; ?></td>
                                                        <td><?php echo $lastName; ?></td>
                                                        <td><img src="<?php echo $imageUrl; ?>" alt="Candidate Image" width="50"></td>
                                                        <td><?php echo $manifesto; ?></td>
                                                        <td>
                                                            <button class="btn-edit" onclick="editCandidate(<?php echo $candidateId; ?>)">Edit</button>
                                                            <button class="btn-delete" onclick="deleteCandidate(<?php echo $candidateId; ?>)">Delete</button>
                                                        </td>
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
                            <p style="color: red; font-weight: bold; text-align: center;">No positions set for this election.</p>
                        <?php endif; ?>
                    </div>
                <?php
                }
                ?>
            </div>

        </div>
    </div>

    <script>
        // Tab Switching Logic
        const tabLinks = document.querySelectorAll(".tab-link");
        const tabContents = document.querySelectorAll(".tab-content");

        tabLinks.forEach((link) => {
            link.addEventListener("click", (e) => {
                const targetTab = e.target.getAttribute("data-tab");

                // Hide all tab contents and remove active class from all links
                tabContents.forEach((content) => content.classList.remove("active"));
                tabLinks.forEach((tabLink) => tabLink.classList.remove("active"));

                // Show the clicked tab content
                document.getElementById(targetTab).classList.add("active");

                // Add active class to the clicked link
                e.target.classList.add("active");
            });
        });
    </script>

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

    <!--CANDIDATES MODAL -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Open Modal Function
            function openNewCandidateModal() {
                document.getElementById('new-candidate-modal').style.display = 'block';
            }

            // Close Modal Function
            function closeCandidateModal() {
                document.getElementById('new-candidate-modal').style.display = 'none';
            }

            // Populate Positions based on selected Election
            document.getElementById('election').addEventListener('change', function() {
                const electionId = this.value;
                const positionSelect = document.getElementById('position');

                // Clear current position options
                positionSelect.innerHTML = '<option value="">Select Position</option>';

                if (electionId) {
                    // Fetch available positions for the selected election
                    // Assuming $electionsData is available as a JavaScript object in the page.
                    const elections = <?php echo json_encode($electionsData); ?>;
                    const selectedElection = elections.find(e => e.election_id == electionId);

                    if (selectedElection && selectedElection.positions) {
                        selectedElection.positions.forEach(function(position) {
                            const option = document.createElement('option');
                            option.value = position.position_id;
                            option.textContent = position.position_name;
                            positionSelect.appendChild(option);
                        });
                    }
                }
            });
            // Event Listeners
            document.getElementById('new-candidate-btn').addEventListener('click', openNewCandidateModal);
            document.getElementById('close-candidate-modal-btn').addEventListener('click', closeCandidateModal);
        });
    </script>



    <script src="../../assets/js/admin/dashboardMain.js"></script>
</body>

</html>