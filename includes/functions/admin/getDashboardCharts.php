<?php
// Includes
require __DIR__ . "/../../configs/database.php";  // Database connection setup

header('Content-Type: application/json');

// Initialize the chart data array
$chartData = [
    'labels' => [],
    'data' => []
];

// Database query to get the most recent 5 elections and aggregate votes
try {
    // Query to get the most recent 5 elections
    $electionsQuery = "
        SELECT election_id, election_name 
        FROM elections 
        ORDER BY created_at DESC 
        LIMIT 5";

    // Execute the query to get the elections using PDO
    $stmt = $pdo->prepare($electionsQuery);
    $stmt->execute();
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through the elections and aggregate the votes for each
    foreach ($elections as $election) {
        // Query to get the total votes for the current election
        $votesQuery = "
            SELECT COUNT(*) AS total_votes 
            FROM votes v
            WHERE v.election_id = :election_id";

        // Execute the query to get the vote count for the election using PDO
        $stmtVotes = $pdo->prepare($votesQuery);
        $stmtVotes->bindParam(':election_id', $election['election_id'], PDO::PARAM_INT);
        $stmtVotes->execute();
        $voteCount = $stmtVotes->fetch(PDO::FETCH_ASSOC);

        // Add the election name and vote count to the chart data
        $chartData['labels'][] = $election['election_name'];
        $chartData['data'][] = (int)$voteCount['total_votes'];
    }
} catch (Exception $e) {
    // Handle exceptions or errors
    $chartData = [
        'error' => 'An error occurred while fetching the data.'
    ];
}

echo json_encode($chartData);
