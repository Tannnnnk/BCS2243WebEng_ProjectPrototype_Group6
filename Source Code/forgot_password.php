<?php
session_start();

// Initialize message variables
$error_message = "";
$success_message = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Connect to the database
    $link = mysqli_connect("127.0.0.1", "root", "", "webeproject", 3307);
    
    if (!$link) {
        die('Could not connect: ' . mysqli_connect_error());
    }

    // Retrieve and sanitize inputs
    $userID = mysqli_real_escape_string($link, $_POST['userID']);
    $username = mysqli_real_escape_string($link, $_POST['username']);
    $role = isset($_POST['role']) ? mysqli_real_escape_string($link, $_POST['role']) : "";
    $new_password = mysqli_real_escape_string($link, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($link, $_POST['confirm_password']);

    // Validation
    if (empty($role)) {
        $error_message = "Please select a role.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "The new passwords do not match. Please try again.";
    } else {
        // Query to verify if the user exists with the exact ID, Username, and Role
        $sql_check = "SELECT * FROM USERS WHERE userID = '$userID' AND user_username = '$username' AND user_role = '$role'";
        $result = mysqli_query($link, $sql_check);

        if (mysqli_num_rows($result) == 1) {
            // User verified successfully. Update the password.
            $sql_update = "UPDATE USERS SET user_password = '$new_password' WHERE userID = '$userID'";
            
            if (mysqli_query($link, $sql_update)) {
                $success_message = "Password reset successfully! You can now login.";
            } else {
                $error_message = "Something went wrong while updating your password.";
            }
        } else {
            // Verification failed
            $error_message = "Verification failed. Invalid User ID, Username, or Role.";
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - FK Student Club & Event Management</title>
    <style>
        /* Pure, simple CSS matching the login page */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            height: 100%;
            font-family: Arial, Helvetica, sans-serif;
        }
        .login-container {
            height: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: ''; position: absolute; width: 400px; height: 400px;
            background: rgba(255, 255, 255, 0.1); border-radius: 50%;
            top: -100px; left: -100px;
        }
        .login-container::after {
            content: ''; position: absolute; width: 300px; height: 300px;
            background: rgba(255, 255, 255, 0.08); border-radius: 50%;
            bottom: -50px; right: -50px;
        }
        .login-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
        }
        .club-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .club-header h2 {
            font-size: 22px; font-weight: bold;
            color: #1a202c; line-height: 1.4;
        }
        .club-header p {
            color: #718096;
            font-size: 14px;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block; font-size: 13px; font-weight: bold;
            color: #2d3748; margin-bottom: 6px;
        }
        input, select {
            width: 100%; padding: 10px 14px;
            border: 2px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-family: Arial, sans-serif;
            transition: all 0.3s ease;
        }
        input:focus, select:focus {
            outline: none; border-color: #10b981;
        }
        select {
            cursor: pointer;
            background-color: white;
        }
        .login-btn {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white; border: none; border-radius: 10px;
            font-size: 16px; font-weight: bold; cursor: pointer;
            margin-top: 10px;
        }
        .login-btn:hover {
            background: #059669;
        }
        .forgot-link {
            text-align: center; margin-top: 15px;
        }
        .forgot-link a {
            font-size: 14px; color: #10b981;
            text-decoration: none; font-weight: bold;
        }
        .forgot-link a:hover {
            color: #059669;
        }
        .error-message {
            background: #fff5f5; color: #742a2a;
            padding: 12px; border-radius: 8px; font-size: 13px;
            margin-bottom: 20px; border-left: 4px solid #742a2a;
            font-weight: bold;
        }
        .success-message {
            background: #e6ffed; color: #22863a;
            padding: 12px; border-radius: 8px; font-size: 13px;
            margin-bottom: 20px; border-left: 4px solid #22863a;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-box">
            
            <div class="club-header">
                <h2>Reset Password</h2>
                <p>Verify your identity to reset your password</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                
                <div class="form-group">
                    <label for="userID">User ID</label>
                    <input type="text" id="userID" name="userID" placeholder="Enter your User ID (e.g. U123)" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your exact registered name" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select your role</option>
                        <option value="Student">Student</option>
                        <option value="Administrator">Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                </div>

                <button type="submit" class="login-btn">
                    Reset Password
                </button>
            </form>

            <div class="forgot-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

</body>
</html>