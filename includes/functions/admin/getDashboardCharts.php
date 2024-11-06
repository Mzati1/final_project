<?php
//includes
require __DIR__ . "/../../configs/database.php";
require __DIR__ . "/../../functions/queryExecutor.php";

header('Content-Type: application/json');


// Sample data for different charts
$mainDashboardChart = [
    ['election_name' => 'Election 1', 'votes' => 120],
    ['election_name' => 'Election 2', 'votes' => 80],
    ['election_name' => 'Election 3', 'votes' => 50],
    ['election_name' => 'Election 4', 'votes' => 90]
];


// Get the requested chart type from the URL
$chartType = isset($_GET['chart']) ? $_GET['chart'] : 'mainDashboardChart';

// Determine which data to use based on the chart type
switch ($chartType) {
    case 'mainDashboardChart':
        $chartData = [
            'labels' => array_column($mainDashboardChart, 'election_name'),
            'data' => array_column($mainDashboardChart, 'votes')
        ];
        break;

        // Default case for unknown chart types
    default:
        $chartData = ['labels' => [], 'data' => []];
        break;
}

echo json_encode($chartData);
