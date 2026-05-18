<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] === 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clubID'])) {

    $clubID = mysqli_real_escape_string($link, $_POST['clubID']);

    // Check if already joined
    $check = mysqli_query($link, "
        SELECT memberID 
        FROM membership
        WHERE userID='$userID'
        AND clubID='$clubID'
    ");

    if (mysqli_num_rows($check) == 0) {

        // Generate unique member ID
        do {
            $memberID = "MEM" . rand(1000, 9999);

            $exists = mysqli_query($link, "
                SELECT memberID 
                FROM membership
                WHERE memberID='$memberID'
            ");

        } while (mysqli_num_rows($exists) > 0);

        // Insert as normal member (Club Members = R08)
      // Insert as normal member with 1-year end date
        $insert = mysqli_query($link, "
            INSERT INTO membership (memberID, userID, clubID, roleID, start_date, end_date)
            VALUES ('$memberID', '$userID', '$clubID', 'R08', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))
        ");
    }
}

mysqli_close($link);

header("Location: club_directory.php");
exit();
?>