<?php
// Includes
require __DIR__ . "/../../configs/database.php";

header('Content-Type: application/json');

// Initialize the chart data format
$chartData = [
    'labels' => [],
    'data' => []
];

try {
    // Query to get the total number of students
    $totalStudentsQuery = "
        SELECT COUNT(*) AS total_students
        FROM users";
    $stmtTotalStudents = $pdo->prepare($totalStudentsQuery);
    $stmtTotalStudents->execute();
    $totalStudentsCount = $stmtTotalStudents->fetch(PDO::FETCH_ASSOC);
    $totalStudents = (int)$totalStudentsCount['total_students'];

    // Add the total students label and data
    $chartData['labels'][] = 'Total users';
    $chartData['data'][] = $totalStudents;

    // Query to get the number of students who are not users
    $studentsNotUsersQuery = "
        SELECT COUNT(*) AS students_who_are_not_users
        FROM students s
        LEFT JOIN users u ON s.student_id = u.student_id
        WHERE u.user_id IS NULL";
    $stmtNotUsers = $pdo->prepare($studentsNotUsersQuery);
    $stmtNotUsers->execute();
    $studentsNotUsersCount = $stmtNotUsers->fetch(PDO::FETCH_ASSOC);
    $studentsNotUsers = (int)$studentsNotUsersCount['students_who_are_not_users'];

    // Add the students who are not users label and data
    $chartData['labels'][] = 'Students Who Are Not Users';
    $chartData['data'][] = $studentsNotUsers;
} catch (Exception $e) {
    // In case of error, set error information
    $chartData = [
        'error' => 'An error occurred while fetching the data.',
        'exception_message' => $e->getMessage()
    ];
}

// Output the response as JSON
echo json_encode($chartData);
