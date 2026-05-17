<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

$admin_name = $username; 
$department = "";
$position = "";
$photo_path = "";

try {
    $sql_profile = "SELECT admin_name, admin_department, admin_position, admin_photo FROM administrator WHERE userID = '$userID'";
    $result_profile = mysqli_query($link, $sql_profile);
    
    if ($result_profile && mysqli_num_rows($result_profile) > 0) {
        $row = mysqli_fetch_assoc($result_profile);
        
        $admin_name = !empty($row['admin_name']) ? $row['admin_name'] : $username;
        $department = !empty($row['admin_department']) ? $row['admin_department'] : "";
        $position = !empty($row['admin_position']) ? $row['admin_position'] : "";
        $photo_path = !empty($row['admin_photo']) ? $row['admin_photo'] : "";
    }
} catch (Exception $e) {}

/* Put your sql code here */

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - FK Management System</title>
    <style>
        /* Put your content's css here */
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
        <div class="content-area">
            /* Put your content here */
        </div>
    </div>
</body>
</html>