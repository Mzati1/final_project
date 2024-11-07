<?php
// Include the database connection file
require __DIR__ . "/../../configs/database.php";

// Function to sanitize input data
function sanitizeInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if the request is a POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get the election details from the form
    $electionName = sanitizeInput($_POST['election_name']);
    $startDate = sanitizeInput($_POST['start_date']);
    $endDate = sanitizeInput($_POST['end_date']);
    $status = sanitizeInput($_POST['status']);

    // Decode the positions from JSON
    $positions = json_decode($_POST['positions'], true);

    if (empty($electionName) || empty($startDate) || empty($endDate) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'All election fields are required.']);
        exit;
    }

    // Prepare SQL to insert into the elections table
    try {
        $pdo->beginTransaction();

        // Insert election data into the elections table
        $stmt = $pdo->prepare("INSERT INTO elections (election_name, start_date, end_date, election_status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$electionName, $startDate, $endDate, $status]);
        $electionId = $pdo->lastInsertId(); // Get the last inserted election ID

        // Insert positions data into the positions table
        if (!empty($positions)) {
            $stmt = $pdo->prepare("INSERT INTO positions (election_id, position_name, position_description) VALUES (?, ?, ?)");

            foreach ($positions as $position) {
                if (!empty($position['name']) && !empty($position['description'])) {
                    $stmt->execute([$electionId, $position['name'], $position['description']]);
                }
            }
        }

        $pdo->commit(); // Commit transaction
        echo json_encode(['success' => true, 'message' => 'Election created successfully!']);
    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback transaction in case of error
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
