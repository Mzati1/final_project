<?php

require __DIR__ . "/../../configs/database.php";

// Your SQL query
$sql = "
SELECT 

    (SELECT COUNT(DISTINCT v.user_id) 
     FROM votes v) AS total_users_voted,

    -- Total users who have not voted
    (SELECT COUNT(DISTINCT u.user_id) 
     FROM users u 
     LEFT JOIN votes v ON u.user_id = v.user_id 
     WHERE v.user_id IS NULL) AS users_not_voted,

    -- Total registered users
    (SELECT COUNT(u.user_id) 
     FROM users u
     INNER JOIN students s ON u.student_id = s.student_id
     WHERE s.is_registered = TRUE) AS total_registered_users,

    -- Voter turnout percentage
    (SELECT 
        (COUNT(DISTINCT v.user_id) / 
         (SELECT COUNT(u.user_id) 
          FROM users u 
          INNER JOIN students s ON u.student_id = s.student_id 
          WHERE s.is_registered = TRUE)) * 100 
     FROM votes v) AS voter_turnout_percentage,

    -- Recently joined users (limit to 5 most recent)
    (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                'user_id', u.user_id,
                'first_name', s.first_name,
                'last_name', s.last_name,
                'email', s.email,
                'created_at', u.created_at
            )
     )
     FROM users u
     INNER JOIN students s ON u.student_id = s.student_id
     ORDER BY u.created_at DESC
     LIMIT 5) AS recent_users;

";

// Prepare and execute the query
try {
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Output the result as JSON
    echo json_encode($result);
} catch (PDOException $e) {
    // Error handling
    echo json_encode(['error' => 'Failed to fetch data: ' . $e->getMessage()]);
}
