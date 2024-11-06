<?php
// Check if the user is authenticated and has the right permissions
session_start();

// Ensure user has the correct role
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'MEC') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user_id is passed
if (isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];

    // Prepare and execute the SQL delete query
    $deleteSql = "DELETE FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($deleteSql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

    // Execute the query and check success
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
