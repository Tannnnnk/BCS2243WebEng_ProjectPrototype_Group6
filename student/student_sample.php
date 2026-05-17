<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

// Fetch basic student info for top bar
$stu_name = $username; 
$photo_path = ""; 

$sql_profile = "SELECT stu_name, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);
if ($result_profile && mysqli_num_rows($result_profile) > 0) {
    $row = mysqli_fetch_assoc($result_profile);
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
}

/*Put your sql code here */

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Details - FK Management System</title>
    <style>
        /* put your content's css here*/
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
        <div class="content-area">
            <div>/* Put your content here */</div>
        </div>
    </div>
</body>
</html>