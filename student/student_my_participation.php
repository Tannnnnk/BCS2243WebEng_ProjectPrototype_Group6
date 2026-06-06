<?php
require_once 'student_login_materials.php';

// 1. Session check to prevent access without login
if (!isset($_SESSION['userID'])) {
    header("Location: student_login.php");
    exit();
}

$userID = $_SESSION['userID'];

// 2. Fetch user data for sidebar
$stu_query = mysqli_query($link, "SELECT stu_name FROM students WHERE userID = '$userID'");
$stu_row = mysqli_fetch_assoc($stu_query);
$stu_name = $stu_row['stu_name'] ?? ($_SESSION['user_username'] ?? 'Student');
$active_role = "Student";

// 3. Handle Cancellation Logic
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $eventID = mysqli_real_escape_string($link, $_GET['id']);
    mysqli_query($link, "DELETE FROM eventregistration WHERE userID='$userID' AND eventID='$eventID'");
    
    // Redirect to clear URL parameters
    header("Location: student_my_participation.php");
    exit();
}

// 4. Data query: Join registration with events
$sql = "SELECT e.eventID, e.event_title, e.event_date, e.event_venue, er.registration_status 
        FROM eventregistration er
        JOIN events e ON er.eventID = e.eventID
        WHERE er.userID = '$userID'
        ORDER BY e.event_date ASC";
$result = mysqli_query($link, $sql);

// 5. Load UI
include 'student_background.php';
?>

<div class="content-area">
    <div class="workspace-wrapper">
        <div class="central-board">
            <div class="board-title">
                <h2>📋 My Registered Events</h2>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['event_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['event_venue']); ?></td>
                                <td><?php echo htmlspecialchars($row['registration_status']); ?></td>
                                <td>
                                    <a href="student_view_event_details.php?id=<?php echo urlencode($row['eventID']); ?>" class="btn-action">View Details</a>
                                    <a href="student_my_participation.php?action=cancel&id=<?php echo urlencode($row['eventID']); ?>" 
                                       class="btn-cancel" 
                                       onclick="return confirm('Are you sure you want to cancel this registration?')">Cancel</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">No registered events found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .workspace-wrapper { max-width: 900px; margin: 20px auto; }
    .data-table { width: 100%; border-collapse: collapse; background: white; margin-top: 15px; border-radius: 8px; overflow: hidden; }
    .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .data-table th { background-color: #f8fafc; }
    .btn-action { color: #2563eb; text-decoration: none; font-weight: bold; }
    .btn-cancel { color: #dc2626; text-decoration: none; font-weight: bold; margin-left: 10px; }
    .btn-cancel:hover, .btn-action:hover { text-decoration: underline; }
</style>