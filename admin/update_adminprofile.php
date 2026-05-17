<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../db_connection.php';

    $userID = $_SESSION['userID'];

    $admin_name = mysqli_real_escape_string($link, $_POST['admin_name']);
    $department = mysqli_real_escape_string($link, $_POST['department']);
    $position = mysqli_real_escape_string($link, $_POST['position']);

    $photo_path_for_db = ""; 

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
        $allowed_types = array("jpg", "jpeg", "png", "gif");
        
        if (in_array($file_extension, $allowed_types)) {
            $new_filename = $userID . "_admin." . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                $photo_path_for_db = $target_file; 
            }
        }
    }

    $check_sql = "SELECT * FROM administrator WHERE userID = '$userID'";
    $check_res = mysqli_query($link, $check_sql);

    if (mysqli_num_rows($check_res) > 0) {
        if ($photo_path_for_db != "") {
            $sql = "UPDATE administrator SET admin_name = '$admin_name', admin_department = '$department', admin_position = '$position', admin_photo = '$photo_path_for_db' WHERE userID = '$userID'";
        } else {
            $sql = "UPDATE administrator SET admin_name = '$admin_name', admin_department = '$department', admin_position = '$position' WHERE userID = '$userID'";
        }
    } else {
        $sql = "INSERT INTO administrator (userID, admin_name, admin_department, admin_position, admin_photo) 
                VALUES ('$userID', '$admin_name', '$department', '$position', '$photo_path_for_db')";
    }

    if (mysqli_query($link, $sql)) {
        header("Location: admin_profile.php?update=success");
    } else {
        die("DATABASE ERROR: " . mysqli_error($link));
    }
    mysqli_close($link);
} else {
    header("Location: admin_profile.php");
}
exit();
?>