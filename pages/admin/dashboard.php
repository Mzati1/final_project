<?php

// Start session
session_start();

// Include the admin auth checker
require __DIR__ . "/../../includes/configs/adminAuthChecks.php";

require __DIR__ . "/../../includes/configs/database.php";
require __DIR__ . "/../../includes/functions/queryExecutor.php";

// SQL queries
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

// Execute queries for metrics

$getTotalVoterStatement = executeQuery($pdo, $getTotalVoterSql);
$totalVoters = $getTotalVoterStatement ? $getTotalVoterStatement->fetch(PDO::FETCH_ASSOC) : ['total_voters' => 0];

$getTotalCandidatesStatement = executeQuery($pdo, $getTotalCandidatesSql);
$totalCandidates = $getTotalCandidatesStatement ? $getTotalCandidatesStatement->fetch(PDO::FETCH_ASSOC) : ['total_candidates' => 0];

$getActiveElectionsSqlStatement = executeQuery($pdo, $getActiveElectionsSql);
$activeElections = $getActiveElectionsSqlStatement ? $getActiveElectionsSqlStatement->fetch(PDO::FETCH_ASSOC) : ['active_elections' => 0];

$getCompletedElectionsSqlStatement = executeQuery($pdo, $getCompletedElectionsSql);
$completedElections = $getCompletedElectionsSqlStatement ? $getCompletedElectionsSqlStatement->fetch(PDO::FETCH_ASSOC) : ['completed_elections' => 0];

$getUsersDataSqlStatement = executeQuery($pdo, $getUsersDataSql);
$usersData = $getUsersDataSqlStatement ? $getUsersDataSqlStatement->fetchAll(PDO::FETCH_ASSOC) : [];

$getRecentElectionsSqlStatement = executeQuery($pdo, $getRecentElectionsSql);
$recentElections = $getRecentElectionsSqlStatement ? $getRecentElectionsSqlStatement->fetchAll(PDO::FETCH_ASSOC) : [];

// Prepare the result as an associative array
$dashboardMetrics = [
    'total_voters' => $totalVoters['total_voters'],
    'total_candidates' => $totalCandidates['total_candidates'],
    'active_elections' => $activeElections['active_elections'],
    'completed_elections' => $completedElections['completed_elections'],
    'recent_elections' => $recentElections,
    'users' => $usersData
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

    <style>
        /* Loader Styling */
        .loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Admin Panel Sidebar -->
        <div class="sidebar">
            <h3> MEC Dashboard</h3>
            <ul>
                <!-- Sidebar items that act as tabs -->
                <li><a href="javascript:void(0);" class="tab-link" data-tab="dashboard">Dashboard</a></li>
                <li><a href="javascript:void(0);" class="tab-link" data-tab="manage-users">Users</a></li>
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
                    <p style="font-size:20px;"><b>
                            Welcome back,
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
                                        style="display: inline-block; padding: 5px 10px; border-radius: 15px; color: white;font-size: 15px;
                                     <?= strtolower($election['election_status']) == 'open' ? 'background-color: #28a745;' : 'background-color: #dc3545;'; ?>s ">
                                        <?= htmlspecialchars($election['election_status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($election['modified_at']); ?>
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
                                <tr data-user-id="<?php echo $user['id']; ?>">

                                    <td>
                                        <span class="user-name"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                                    </td>
                                    <td>
                                        <span class="user-email"><?php echo $user['email']; ?></span>
                                    </td>
                                    <td>
                                        <span class="user-reg-num"><?php echo $user['reg_number'] ?: 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $user['status'] == 'Active' ? 'active' : 'inactive'; ?>"><?php echo $user['status']; ?></span>
                                    </td>
                                    <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'MEC'): ?>
                                        <td>
                                            <button class="btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Content for the Manage Elections Tab -->
            <div id="manage-elections" class="tab-content">
                <h2>Manage Elections</h2>
                <p>Content for managing elections goes here...</p>
            </div>

            <!-- Content for the View Audit Logs Tab -->
            <div id="view-logs" class="tab-content">
                <h2>View Audit Logs</h2>
                <p>Content for viewing audit logs goes here...</p>
            </div>

            <!-- Content for the Results Tab -->
            <div id="results" class="tab-content">
                <h2>Election Results</h2>
                <p>Content for viewing results goes here...</p>
            </div>

            <!-- Content for the Settings Tab -->
            <div id="settings" class="tab-content">
                <h2>Settings</h2>
                <p>Content for settings goes here...</p>
            </div>
        </div>
    </div>

    <script>
        // Helper function to toggle visibility of elements
        function toggleVisibility(elements, show = true) {
            elements.forEach((element) => {
                element.style.display = show ? "inline-block" : "none";
            });
        }

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

    <script src="../../assets/js/admin/dashboardMain.js"></script>

</body>

</html>