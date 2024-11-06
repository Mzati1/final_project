<?php

session_start();
require __DIR__ . "/../includes/configs/database.php";

$error = "";
$account_type = "unknown";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['identifier']);
    $password = trim($_POST['password']);

    // Check if the identifier has either 2 hyphens, 2 slashes, or none ( all reg numbers faill under this or atleast i think )
    $hyphen_count = substr_count($identifier, '-');
    $slash_count = substr_count($identifier, '/');

    if (!(($hyphen_count == 2) || ($slash_count == 2) || ($hyphen_count == 0 && $slash_count == 0))) {
        $error = "Invalid Reg number";
    }

    // Get IP and client information for the auditing table
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $client = $_SERVER['HTTP_USER_AGENT'];

    $isStudent = false;
    $isAdmin = false;
    $userData = null;

    // Check if the email is valid
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL) && !str_ends_with($identifier, '@must.ac.mw')) {
        $error = "Email must be from @must.ac.mw.";
    }

    if (empty($error)) {
        // check if it's a student (either by reg number or email)
        $stmt = $pdo->prepare("SELECT * FROM students WHERE reg_number = :identifier OR email = :identifier LIMIT 1");
        $stmt->execute(['identifier' => $identifier]);

        if ($stmt->rowCount() > 0) {
            // if found this is a student
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            $account_type = 'student';
            $isStudent = true;

            // Check if the student has a user account and the password matches
            $stmt = $pdo->prepare("SELECT * FROM users WHERE student_id = :student_id LIMIT 1");
            $stmt->execute(['student_id' => $userData['student_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                // Password is correct store sessions and login
                $_SESSION['first_name'] = $userData['first_name'];
                $_SESSION['last_name'] = $userData['last_name'];
                $_SESSION['email'] = $userData['email'];
                $_SESSION['is_registered'] = $userData['is_registered'];
                $_SESSION['is_logged_in'] = true;
                $_SESSION['reg_number'] = $userData['reg_number'];
                $_SESSION['is_student'] = true;

                // Log the login attempt in the audit table
                $stmt = $pdo->prepare("
                    INSERT INTO login_audit (attempted_account, account_type, login_time, ip_address, client, login_status, student_id, admin_id)
                    VALUES (:attempted_account, :account_type, NOW(), :ip_address, :client, :login_status, :student_id, :admin_id)
                ");
                $stmt->execute([
                    'attempted_account' => $identifier,
                    'account_type' => $account_type,
                    'ip_address' => $ip_address,
                    'client' => $client,
                    'login_status' => 'successful',
                    'student_id' => $userData['student_id'],
                    'admin_id' => null
                ]);

                //redirect
                header("Location: ../pages/client/dashboard.php");
                exit;
            } else {
                $error = "Invalid password for this student.";
            }
        } else {
            // If no student found check for an admin with the email
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :identifier LIMIT 1");
            $stmt->execute(['identifier' => $identifier]);

            if ($stmt->rowCount() > 0) {
                // if found this is an admin
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                $account_type = 'admin';
                $isAdmin = true;

                // Check if the password matches for admin
                if (password_verify($password, $userData['password'])) {

                    // if correct log the admin in
                    $_SESSION['first_name'] = $userData['first_name'];
                    $_SESSION['last_name'] = $userData['last_name'];
                    $_SESSION['email'] = $userData['email'];
                    $_SESSION['is_logged_in'] = true;
                    $_SESSION['is_admin'] = true;
                    $_SESSION['role'] = $userData['role'];

                    // Log the login attempt in the audit table
                    $stmt = $pdo->prepare("
                        INSERT INTO login_audit (attempted_account, account_type, login_time, ip_address, client, login_status, student_id, admin_id)
                        VALUES (:attempted_account, :account_type, NOW(), :ip_address, :client, :login_status, :student_id, :admin_id)
                    ");
                    $stmt->execute([
                        'attempted_account' => $identifier,
                        'account_type' => $account_type,
                        'ip_address' => $ip_address,
                        'client' => $client,
                        'login_status' => 'successful',
                        'student_id' => null,
                        'admin_id' => $userData['admin_id']
                    ]);

                    header("Location: ../pages/admin/dashboard.php");
                    exit;
                } else {
                    $error = "Invalid password for admin.";
                }
            } else {
                $error = "Account not found.";
            }
        }

        // If no match for student or admin, show error and log either way
        if ($error) {
            $stmt = $pdo->prepare("
                INSERT INTO login_audit (attempted_account, account_type, login_time, ip_address, client, login_status, student_id, admin_id)
                VALUES (:attempted_account, :account_type, NOW(), :ip_address, :client, :login_status, :student_id, :admin_id)
            ");
            $stmt->execute([
                'attempted_account' => $identifier,
                'account_type' => $account_type,
                'ip_address' => $ip_address,
                'client' => $client,
                'login_status' => 'failed',
                'student_id' => null,
                'admin_id' => null
            ]);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Stylesheet -->
    <link rel="stylesheet" href="../assets/css/login.css">
    <!-- FontAwesome for Icons -->
    <script src="https://kit.fontawesome.com/e99d0fa6e7.js" crossorigin="anonymous"></script>
    <title>Login</title>
</head>

<body>
    <div class="container">
        <!-- Left section with background image -->
        <div class="left-section">
            <div class="overlay">
                <h1>Welcome Back!</h1>
                <p>Please Log in </p>
            </div>
        </div>

        <!-- Right section with login form -->
        <div class="right-section">
            <form method="post" class="login-form" id="login-form">
                <!-- Display error message if any -->
                <?php if (!empty($error)): ?>
                    <p class="error-message" id="error-message"><?php echo $error; ?></p>
                <?php endif; ?>

                <h2>Login to Your Account</h2>

                <!-- Inputs -->
                <div class="input-container">
                    <input type="text" id="login-identifier" name="identifier" placeholder="e.g. css-xxx-x" required autocomplete="off">
                    <label for="login-identifier">Email/Reg Number:</label>
                    <div class="input-bg"></div>
                </div>

                <div class="input-container">
                    <input type="password" id="login-password" name="password" placeholder="" required autocomplete="off">
                    <label for="login-password">Password:</label>
                    <div class="input-bg"></div>
                </div>

                <button type="submit" id="login-button">Login</button>

                <!-- Signup link if no account -->
                <p class="signup-link">Don’t have an account? <a href="register.php">Sign up here</a></p>
            </form>
        </div>
    </div>

    <script>
        // Button click animation
        const loginButton = document.getElementById('login-button');
        loginButton.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.95)';
            this.style.backgroundColor = '#cc8400';
        });

        loginButton.addEventListener('mouseup', function() {
            this.style.transform = 'scale(1)';
            this.style.backgroundColor = '#e6a500';
        });
    </script>
</body>

</html>