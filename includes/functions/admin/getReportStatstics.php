<?php
require __DIR__ . "/../../configs/database.php";

// Fetch data based on the report type
if (isset($_GET['report_type'])) {
    $reportType = $_GET['report_type'];

    switch ($reportType) {
        case 'user_voting_activity':
            echo json_encode(getUserVotingActivity($pdo));
            break;

        case 'login_activity':
            echo json_encode(getLoginActivity($pdo));
            break;

        case 'election_report':
            echo json_encode(getElectionStatus($pdo));
            break;

        case 'voting_activity':
            echo json_encode(getVotingActivity($pdo));
            break;

        default:
            echo json_encode(['error' => 'Invalid report type']);
            break;
    }
}

// Function to get user voting activity stats
function getUserVotingActivity($pdo)
{
    // Get the total number of registered and unregistered students
    $stmt = $pdo->query("SELECT COUNT(*) AS total_users FROM students WHERE is_registered = 1");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    $stmt = $pdo->query("SELECT COUNT(*) AS total_voters FROM students WHERE is_registered = 1 AND student_id IN (SELECT user_id FROM votes)");
    $totalVoters = $stmt->fetch(PDO::FETCH_ASSOC)['total_voters'];

    $totalUnregistered = $totalUsers - $totalVoters;

    // Get the registered users count (admins, MEC staff, etc.)
    $stmt = $pdo->query("SELECT COUNT(*) AS total_admins FROM admins");
    $totalAdmins = $stmt->fetch(PDO::FETCH_ASSOC)['total_admins'];

    // Get voter turnout percentage
    $voterTurnoutPercentage = $totalVoters > 0 ? round(($totalVoters / $totalUsers) * 100, 2) : 0;

    // Get the recently joined users
    $stmt = $pdo->query("SELECT first_name, last_name, email, created_at FROM students ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'users_voted' => $totalVoters,
        'users_not_voted' => $totalUnregistered,
        'registered_users' => $totalAdmins,
        'voter_turnout_percentage' => $voterTurnoutPercentage,
        'recent_users' => $recentUsers
    ];
}

// Function to get login activity stats
function getLoginActivity($pdo)
{
    // Logins today
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS logins_today FROM login_audit WHERE login_time LIKE :today AND login_status = 'successful'");
    $stmt->execute([':today' => "$today%"]);
    $loginsToday = $stmt->fetch(PDO::FETCH_ASSOC)['logins_today'];

    // Failed logins today
    $stmt = $pdo->prepare("SELECT COUNT(*) AS failed_logins_today FROM login_audit WHERE login_time LIKE :today AND login_status = 'failed'");
    $stmt->execute([':today' => "$today%"]);
    $failedLoginsToday = $stmt->fetch(PDO::FETCH_ASSOC)['failed_logins_today'];

    // Popular devices (most common client)
    $stmt = $pdo->query("SELECT client, COUNT(*) AS count FROM login_audit GROUP BY client ORDER BY count DESC LIMIT 1");
    $popularDevice = $stmt->fetch(PDO::FETCH_ASSOC)['client'] ?? 'N/A';

    // Last login IP
    $stmt = $pdo->prepare("SELECT ip_address FROM login_audit ORDER BY login_time DESC LIMIT 1");
    $stmt->execute();
    $lastLoginIP = $stmt->fetch(PDO::FETCH_ASSOC)['ip_address'] ?? 'N/A';

    // Get recent login attempts
    $stmt = $pdo->query("SELECT attempted_account, login_time, login_status, ip_address, client FROM login_audit ORDER BY login_time DESC LIMIT 5");
    $recentLogins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'logins_today' => $loginsToday,
        'failed_logins_today' => $failedLoginsToday,
        'popular_devices' => $popularDevice,
        'last_login_ip' => $lastLoginIP,
        'recent_logins' => $recentLogins
    ];
}

// Function to get election status stats
function getElectionStatus($pdo)
{
    // Total elections
    $stmt = $pdo->query("SELECT COUNT(*) AS total_elections FROM elections");
    $totalElections = $stmt->fetch(PDO::FETCH_ASSOC)['total_elections'];

    // Open elections
    $stmt = $pdo->query("SELECT COUNT(*) AS open_elections FROM elections WHERE election_status = 'open'");
    $openElections = $stmt->fetch(PDO::FETCH_ASSOC)['open_elections'];

    // Closed elections
    $stmt = $pdo->query("SELECT COUNT(*) AS closed_elections FROM elections WHERE election_status = 'closed'");
    $closedElections = $stmt->fetch(PDO::FETCH_ASSOC)['closed_elections'];

    // Get upcoming elections (future elections)
    $stmt = $pdo->query("SELECT COUNT(*) AS upcoming_elections FROM elections WHERE start_date > NOW()");
    $upcomingElections = $stmt->fetch(PDO::FETCH_ASSOC)['upcoming_elections'];

    // Get recent elections
    $stmt = $pdo->query("SELECT election_name, start_date, end_date, election_status FROM elections ORDER BY created_at DESC LIMIT 5");
    $recentElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'total_elections' => $totalElections,
        'open_elections' => $openElections,
        'closed_elections' => $closedElections,
        'upcoming_elections' => $upcomingElections,
        'recent_elections' => $recentElections
    ];
}

// Function to get voting activity stats
function getVotingActivity($pdo)
{
    // Total votes cast
    $stmt = $pdo->query("SELECT COUNT(*) AS total_votes FROM votes");
    $totalVotes = $stmt->fetch(PDO::FETCH_ASSOC)['total_votes'];

    // Votes cast today
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS votes_today FROM votes WHERE vote_time LIKE :today");
    $stmt->execute([':today' => "$today%"]);
    $votesToday = $stmt->fetch(PDO::FETCH_ASSOC)['votes_today'];

    // Most voted-for election (based on votes) - Returning election name
    $stmt = $pdo->query("
        SELECT e.election_name, COUNT(v.vote_id) AS vote_count
        FROM votes v
        JOIN elections e ON v.election_id = e.election_id
        GROUP BY e.election_id
        ORDER BY vote_count DESC LIMIT 1
    ");
    $mostVotedForElection = $stmt->fetch(PDO::FETCH_ASSOC);
    $mostVotedForElectionName = $mostVotedForElection['election_name'] ?? 'N/A';

    // Most voted-for position (based on votes) - Returning candidate name
    $stmt = $pdo->query("
        SELECT p.position_name, c.first_name AS candidate_first, c.last_name AS candidate_last, COUNT(v.vote_id) AS vote_count
        FROM votes v
        JOIN positions p ON v.position_id = p.position_id
        JOIN candidates c ON v.candidate_id = c.candidate_id
        GROUP BY p.position_id, c.candidate_id
        ORDER BY vote_count DESC LIMIT 1
    ");
    $mostVotedForPosition = $stmt->fetch(PDO::FETCH_ASSOC);
    $mostVotedForPositionName = $mostVotedForPosition['position_name'] ?? 'N/A';
    $mostVotedForCandidate = $mostVotedForPosition['candidate_first'] . ' ' . $mostVotedForPosition['candidate_last'] ?? 'N/A';

    // Get recent voting activity
    $stmt = $pdo->query("
        SELECT 
            students.first_name, 
            students.last_name, 
            elections.election_name, 
            positions.position_name, 
            candidates.first_name AS candidate_first, 
            candidates.last_name AS candidate_last, 
            votes.vote_time
        FROM votes
        JOIN students ON students.student_id = votes.user_id
        JOIN elections ON elections.election_id = votes.election_id
        JOIN positions ON positions.position_id = votes.position_id
        JOIN candidates ON candidates.candidate_id = votes.candidate_id
        ORDER BY votes.vote_time DESC LIMIT 5
    ");
    $recentVotingActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'total_votes' => $totalVotes,
        'votes_today' => $votesToday,
        'most_voted_election' => $mostVotedForElectionName,  // Changed to election name
        'most_voted_position' => $mostVotedForPositionName,  // Changed to position name
        'most_voted_candidate' => $mostVotedForCandidate,    // Added most voted candidate
        'recent_voting_activity' => $recentVotingActivity
    ];
}
