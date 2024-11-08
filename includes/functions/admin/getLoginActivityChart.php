<?php
// Includes
require __DIR__ . "/../../configs/database.php";

header('Content-Type: application/json');

function getDeviceFromUserAgent($userAgent)
{

    //case sensititivy issues so i just set to lower case
    $userAgent = strtolower($userAgent);

    if (strpos($userAgent, 'macintosh') !== false) {
        return 'Mac';
    } elseif (strpos($userAgent, 'windows nt') !== false) {
        return 'Windows';
    } elseif (strpos($userAgent, 'android') !== false) {
        return 'Android';
    } elseif (strpos($userAgent, 'iphone') !== false) {
        return 'iPhone';
    } elseif (strpos($userAgent, 'ipad') !== false) {
        return 'iPad';
    } elseif (strpos($userAgent, 'linux') !== false) {
        return 'Linux';
    } elseif (strpos($userAgent, 'x11') !== false) {
        return 'Unix';
    }
    return 'Unknown';  // If no known device is detected
}

// Initialize the chart data array
$chartData = [
    'labels' => [],
    'data' => []
];

try {
    $loginsQuery = "
        SELECT client AS device_name, COUNT(*) AS login_count
        FROM login_audit
        WHERE login_status = 'successful'
        GROUP BY client
        ORDER BY login_count DESC";
    $stmt = $pdo->prepare($loginsQuery);
    $stmt->execute();

    // Fetch all results
    $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through the login data and populate the chart data arrays
    foreach ($logins as $login) {
        // Extract the device name from the client (user agent) string
        $deviceName = getDeviceFromUserAgent($login['device_name']);

        // Check if the device already exists in the chart data
        $key = array_search($deviceName, $chartData['labels']);

        if ($key !== false) {
            $chartData['data'][$key] += (int)$login['login_count'];
        } else {
            // If the device name doesn't exist yet, add a new entry
            $chartData['labels'][] = $deviceName;
            $chartData['data'][] = (int)$login['login_count'];
        }
    }
} catch (Exception $e) {
    // Handle exceptions or errors
    $chartData = [
        'error' => 'An error occurred while fetching the data.'
    ];
}

echo json_encode($chartData);
