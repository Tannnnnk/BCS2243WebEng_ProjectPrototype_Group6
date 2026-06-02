<?php
// 1. Start session and connect to DB
session_start();
require_once '../db_connection.php';

// 2. Prevent "Undefined variable" warnings
$userID = $_SESSION['userID'];

// Fetch user data for the background display
$stu_query = mysqli_query($link, "SELECT stu_name FROM students WHERE userID = '$userID'");
$stu_row = mysqli_fetch_assoc($stu_query);
$stu_name = $stu_row['stu_name'] ?? $_SESSION['user_username'];

// Define active_role for student_background.php to fix the warning
$active_role = "Student"; 

// 3. Handle Cancellation Logic
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $eventID = mysqli_real_escape_string($link, $_GET['id']);
    
    // Delete only from the existing eventregistration table
    mysqli_query($link, "DELETE FROM eventregistration WHERE userID='$userID' AND eventID='$eventID'");
    
    // Redirect to self to refresh the page after deletion
    header("Location: student_my_participation.php");
    exit();
}

// 4. Data display query
$sql = "
    SELECT e.eventID, e.event_title, e.event_date, e.event_venue, 'Confirmed' as status 
    FROM eventregistration er
    JOIN events e ON er.eventID = e.eventID
    WHERE er.userID = '$userID'
";
$result = mysqli_query($link, $sql);

// 5. Include the background (Sidebar)
include 'student_background.php'; 
?>

<div class="content-area">
    <h2>My Registration</h2>
    <table border="1" style="width: 100%; text-align: left; border-collapse: collapse;">
        <tr>
            <th style="padding: 10px;">Event Title</th>
            <th style="padding: 10px;">Date</th>
            <th style="padding: 10px;">Venue</th>
            <th style="padding: 10px;">Status</th>
            <th style="padding: 10px;">Action</th>
        </tr>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td style="padding: 10px;"><?php echo htmlspecialchars($row['event_title']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($row['event_date']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($row['event_venue']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($row['status']); ?></td>
                <td style="padding: 10px;">
                    <a href="student_view_event_details.php?id=<?php echo $row['eventID']; ?>">View Details</a> | 
                    <a href="student_my_participation.php?action=cancel&id=<?php echo $row['eventID']; ?>" 
                       onclick="return confirm('Are you sure you want to cancel this registration?')">Cancel</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="padding: 20px; text-align: center;">No registered events found.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>