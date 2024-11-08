<?php
// Includes
require __DIR__ . "/../../configs/database.php";

header('Content-Type: application/json');

$chartData = [
    'labels' => [],
    'data' => []
];

try {
    // Query to get the total votes for each election
    $votesQuery = "
       SELECT e.election_name, COUNT(v.vote_id) AS total_votes
FROM votes v
JOIN elections e ON v.election_id = e.election_id
GROUP BY e.election_id
ORDER BY total_votes DESC;
";

    $stmt = $pdo->prepare($votesQuery);
    $stmt->execute();

    // Fetch all results
    $electionVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through the results and populate the chart data arrays
    foreach ($electionVotes as $election) {
        // Add the election name and vote count to the chart data
        $chartData['labels'][] = $election['election_name'];  // Election name
        $chartData['data'][] = (int)$election['total_votes'];  // Vote count
    }
} catch (Exception $e) {
    // Handle exceptions or errors
    $chartData = [
        'error' => 'An error occurred while fetching the data.'
    ];
}

// Return the chart data as JSON
echo json_encode($chartData);
