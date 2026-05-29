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

$stu_name = $username; 
$stu_ID = $userID;     
$email = "";
$phone = "";
$address = "";
$photo_path = ""; 
$active_role = $_SESSION['active_role'] ?? 'Student';
$has_committee_access = $_SESSION['has_committee_access'] ?? false;

$sql_profile = "SELECT stu_ID, stu_name, stu_email, stu_contact_no, stu_address, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);

if ($result_profile && mysqli_num_rows($result_profile) > 0) {
    $row = mysqli_fetch_assoc($result_profile);
    
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
    $stu_ID = !empty($row['stu_ID']) ? $row['stu_ID'] : $userID;
    $email = !empty($row['stu_email']) ? $row['stu_email'] : "";
    $phone = !empty($row['stu_contact_no']) ? $row['stu_contact_no'] : "";
    $address = !empty($row['stu_address']) ? $row['stu_address'] : "";
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
}
?>