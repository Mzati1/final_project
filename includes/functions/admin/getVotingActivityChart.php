<?php
// Include database connection
require __DIR__ . "/../../configs/database.php";  // Adjust the path as needed

header('Content-Type: application/json');

// Initialize the chart data array
$chartData = [
    'labels' => ['12 AM', '6 AM', '12 PM', '6 PM'],  // Static time labels
    'data' => [0, 0, 0, 0]  // Default data (4 intervals, one for each 6-hour block)
];

// Function to categorize times into 6-hour intervals
function getTimeCategory($hour)
{
    if ($hour >= 0 && $hour < 6) {
        return 0;  // 12 AM - 6 AM
    } elseif ($hour >= 6 && $hour < 12) {
        return 1;  // 6 AM - 12 PM
    } elseif ($hour >= 12 && $hour < 18) {
        return 2;  // 12 PM - 6 PM
    } else {
        return 3;  // 6 PM - 12 AM
    }
}

// Get today's date in YYYY-MM-DD format
$today = date('Y-m-d');

// Database query to get the number of votes cast per 6-hour interval for today
try {
    // Query to get the total number of votes cast per 6-hour interval for today
    $votesQuery = "
        SELECT HOUR(vote_time) AS vote_hour, COUNT(*) AS total_votes
        FROM votes
        WHERE DATE(vote_time) = :today
        GROUP BY vote_hour
        ORDER BY vote_hour";

    // Execute the query using PDO
    $stmt = $pdo->prepare($votesQuery);
    $stmt->execute([':today' => $today]);
    $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through the result and categorize into 6-hour intervals
    foreach ($votes as $vote) {
        $voteHour = $vote['vote_hour'];
        $categoryIndex = getTimeCategory($voteHour);  // Get the 6-hour time block index
        $chartData['data'][$categoryIndex] += (int)$vote['total_votes'];  // Add votes to the corresponding interval
    }
} catch (Exception $e) {
    // If there's an error, return an error message
    $chartData = [
        'error' => 'An error occurred while fetching the data.'
    ];
}

// Return the chart data as JSON
echo json_encode($chartData);
