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
        // 1. FIXED: Changed 'MEM%' to 'M%' to match your actual ID format
        $max_query = mysqli_query($link, "
            SELECT memberID 
            FROM membership 
            WHERE memberID LIKE 'M%' 
            ORDER BY memberID DESC 
            LIMIT 1
        ");

        // Default baseline integer if the table has no records yet
        $next_number = 1; 

        if ($max_query && mysqli_num_rows($max_query) > 0) {
            $max_row = mysqli_fetch_assoc($max_query);
        
            // Extract just the numbers out of the string (e.g., "M001" becomes 1)
            $last_number = (int) preg_replace('/[^0-9]/', '', $max_row['memberID']);
        
            // Increment the raw number value by 1
            $next_number = $last_number + 1;
        }

        // 2. FIXED: Re-combine using str_pad to force a clean 3-digit format (e.g., M002)
        $memberID = "M" . str_pad($next_number, 3, "0", STR_PAD_LEFT);

        // 3. Insert as normal member with 1-year end date
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