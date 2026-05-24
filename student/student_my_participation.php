<?php
// 1. Start session and connect to DB
session_start();
require_once '../db_connection.php';

// 2. Prevent "Undefined variable" warnings by defining these BEFORE including student_background.php
$userID = $_SESSION['userID'];

// Fetch user data for the background display
$stu_query = mysqli_query($link, "SELECT stu_name FROM students WHERE userID = '$userID'");
$stu_row = mysqli_fetch_assoc($stu_query);
$stu_name = $stu_row['stu_name'] ?? $_SESSION['user_username'];
$role = "Student"; // You can fetch this from the database if needed

// 3. Handle Cancellation Logic BEFORE outputting any HTML
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $eventID = mysqli_real_escape_string($link, $_GET['id']);
    $type = $_GET['type']; // 'Confirmed' or 'Waiting List'

    if ($type == 'Confirmed') {
        mysqli_query($link, "DELETE FROM eventregistration WHERE userID='$userID' AND eventID='$eventID'");
    } else {
        mysqli_query($link, "DELETE FROM waiting_list WHERE userID='$userID' AND eventID='$eventID'");
    }
    
    // Redirect to self to refresh the page after deletion
    header("Location: student_my_participation.php");
    exit();
}

// 4. Now perform the data display query
$sql = "
    SELECT e.eventID, e.event_title, e.event_date, e.event_venue, 'Confirmed' as status 
    FROM eventregistration er
    JOIN events e ON er.eventID = e.eventID
    WHERE er.userID = '$userID'
    UNION
    SELECT e.eventID, e.event_title, e.event_date, e.event_venue, 'Waiting List' as status 
    FROM waiting_list wl
    JOIN events e ON wl.eventID = e.eventID
    WHERE wl.userID = '$userID'
";
$result = mysqli_query($link, $sql);

// 5. Now include the background (Sidebar)
include 'student_background.php'; 
?>

<div class="content-area">
    <h2>My Registration</h2>
    <table border="1" style="width: 100%; text-align: left;">
        <tr>
            <th>Event Title</th>
            <th>Date</th>
            <th>Venue</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['event_title']); ?></td>
            <td><?php echo htmlspecialchars($row['event_date']); ?></td>
            <td><?php echo htmlspecialchars($row['event_venue']); ?></td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>
            <td>
                <a href="student_view_event_details.php?id=<?php echo $row['eventID']; ?>">View Details</a> | 
                <a href="student_my_participation.php?action=cancel&id=<?php echo $row['eventID']; ?>&type=<?php echo $row['status']; ?>" 
                   onclick="return confirm('Are you sure you want to cancel this registration?')">Cancel</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>