<?php
session_start();

// Initialize the error message variable
$error_message = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Connect to the database
    $link = mysqli_connect("127.0.0.1", "root", "", "webeproject", 3307);
    
    if (!$link) {
        die('Could not connect: ' . mysqli_connect_error());
    }

    // Retrieve and sanitize inputs
    $userID = mysqli_real_escape_string($link, $_POST['userID']);
    $password = mysqli_real_escape_string($link, $_POST['password']);
    $role = isset($_POST['role']) ? mysqli_real_escape_string($link, $_POST['role']) : "";

    // Validation
    if (empty($role)) {
        $error_message = "Please select a role.";
    } else {
        // Query the database
        $sql = "SELECT * FROM USERS WHERE userID = '$userID' AND user_password = '$password' AND user_role = '$role'";
        $result = mysqli_query($link, $sql);

        if (mysqli_num_rows($result) == 1) {
            // Login successful
            $row = mysqli_fetch_assoc($result);
            $_SESSION['logged_in'] = true;
            $_SESSION['userID'] = $row['userID'];
            $_SESSION['user_username'] = $row['user_username']; 
            $_SESSION['user_role'] = $row['user_role'];

            // Route to correct dashboard
            if ($role == 'Administrator') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit();
        } else {
            // Login failed
            $error_message = "Invalid User ID, Password, or Role.";
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
    <title>FK Student Club & Event Management</title>
    <style>
        /* Pure, simple CSS - No external links or fonts required! */
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
            padding: 50px 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
        }
        .club-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .club-header h2 {
            font-size: 24px; font-weight: bold;
            color: #1a202c; line-height: 1.4;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block; font-size: 14px; font-weight: bold;
            color: #2d3748; margin-bottom: 8px;
        }
        input, select {
            width: 100%; padding: 12px 14px;
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
            text-align: center; margin-top: 16px;
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
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-box">
            
            <div class="club-header">
                <h2>FK Student Club & Event Management System</h2>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="userID">User ID</label>
                    <input type="text" id="userID" name="userID" placeholder="Enter your User ID (e.g. U123)" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select your role</option>
                        <option value="Student">Student</option>
                        <option value="Administrator">Administrator</option>
                    </select>
                </div>
                <button type="submit" class="login-btn">
                    Login
                </button>
            </form>

            <div class="forgot-link">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
        </div>
    </div>

</body>
</html>