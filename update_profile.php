<?php
session_start();


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    
require_once 'db_connection.php';

    $userID = $_SESSION['userID'];

   
    $email = mysqli_real_escape_string($link, $_POST['email']);
    $phone = mysqli_real_escape_string($link, $_POST['phoneNum']);
    $address = mysqli_real_escape_string($link, $_POST['address']);

    
    $photo_path_for_db = ""; 

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        
        $target_dir = "uploads/";
        
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
        $allowed_types = array("jpg", "jpeg", "png", "gif");
        
        if (in_array($file_extension, $allowed_types)) {
            $new_filename = $userID . "_profile." . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                $photo_path_for_db = $target_file; 
            }
        }
    }


    if ($photo_path_for_db != "") {
        
        $sql = "UPDATE students SET stu_email = '$email', stu_contact_no = '$phone', stu_address = '$address', stu_profile_photo = '$photo_path_for_db' WHERE userID = '$userID'";
    } else {
        
        $sql = "UPDATE students SET stu_email = '$email', stu_contact_no = '$phone', stu_address = '$address' WHERE userID = '$userID'";
    }


    if (mysqli_query($link, $sql)) {
        
        header("Location: student_dashboard.php?update=success");
    } else {
    
        die("DATABASE ERROR: " . mysqli_error($link) . "<br>THE SQL QUERY WAS: " . $sql);
    }

    mysqli_close($link);
} else {
    
    header("Location: student_dashboard.php");
}
exit();
?>