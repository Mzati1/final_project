<?php

//start session
session_start();

//includes
require __DIR__ . "/../includes/configs/database.php";
require __DIR__ . "/../includes/functions/queryExecutor.php";

$error = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //clean inputs
    $email = strtolower(trim($_POST['email'])); // is this okay for emails?
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@must.ac.mw')) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {

        // Check if the email or reg number already exists in the students table
        $query = 'SELECT student_id FROM students WHERE email = :email';
        $params = ['email' => $email];
        $stmt = executeQuery($pdo, $query, $params);

        $student = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

        if ($student) {

            // If student exists check if they already have a user in users table
            $checkUserQuery = 'SELECT user_id FROM users WHERE student_id = :student_id';
            $checkParams = ['student_id' => $student['student_id']];
            $userStmt = executeQuery($pdo, $checkUserQuery, $checkParams);
            $user = $userStmt ? $userStmt->fetch(PDO::FETCH_ASSOC) : null;

            if ($user) {
                // If user exists throw error
                $error = 'A user already exists with this registration number or email.';
            } else {
                // If no user, create a new user

                // secure password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insert into the users table
                $insertUserSql = 'INSERT INTO users (student_id, password) VALUES (:student_id, :password)';
                $insertParams = [
                    'student_id' => $student['student_id'],
                    'password' => $hashed_password
                ];

                $userStmt = executeQuery($pdo, $insertUserSql, $insertParams);

                if ($userStmt) {
                    // on  Success, redirect to login page
                    header('Location: login.php');
                    exit();
                } else {
                    $error = 'An error occurred while creating the user record.';
                }
            }
        } else {
            $error = 'No student found with this registration number or email, Please check with the school';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/register.css">
</head>

<body>
    <div class="container">
        <div class="left-section">
            <div class="overlay">
                <h1>[words here]</h1>
                <p>[description]</p>
            </div>
        </div>
        <div class="right-section">
            <form method="post" class="register-form" id="register-form">
                <!-- Display error message if any -->
                <?php if (!empty($error)): ?>
                    <p class="error-message" id="error-message">
                        <?php echo $error; ?>
                    </p>
                <?php endif; ?>
                <h2>Create Your Account</h2>
                <div class="input-grid">
                    <div class="input-container">
                        <input type="email" id="email" name="email" placeholder="" required autocomplete="off">
                        <label for="email">Email</label>
                        <div class="input-bg"></div>
                    </div>

                    <div class="input-container">
                        <input type="password" id="password" name="password" placeholder="" required autocomplete="off">
                        <label for="password">Password</label>
                        <div class="input-bg"></div>
                    </div>
                    <div class="input-container">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="" required
                            autocomplete="off">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-bg"></div>
                    </div>
                </div>
                <button type="submit" id="register-button">Register</button>
                <p class="signup-link">Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>
</body>

</html>