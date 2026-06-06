<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'student_login_materials.php';

if (!isset($_GET['id'])) {
    header("Location: browse_event.php");
    exit();
}

$eventID = mysqli_real_escape_string($link, $_GET['id']);

// --- FETCH EVENT DATA ---
$event_result = mysqli_query($link, "SELECT * FROM events WHERE eventID = '$eventID'");
$event = mysqli_fetch_assoc($event_result);

$count_query = mysqli_query($link, "SELECT COUNT(*) as total FROM eventregistration WHERE eventID = '$eventID'");
$total_registered = mysqli_fetch_assoc($count_query)['total'];
$remaining_slots = (int)$event['event_max_participants'] - (int)$total_registered;

$is_registered = mysqli_num_rows(mysqli_query($link, "SELECT * FROM eventregistration WHERE userID = '$userID' AND eventID = '$eventID'")) > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Details</title>
    <style>
        .details-wrapper { max-width: 700px; margin: 20px auto; padding: 30px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; }
        .btn-action { padding: 10px 20px; color: white; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: bold; font-size: 14px; }
        .msg-box { padding: 15px; margin-top: 20px; border-radius: 8px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    
    <div class="content-area">
        <div class="details-wrapper">
            <h2><?php echo htmlspecialchars($event['event_title']); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($event['event_desc'])); ?></p>
            <p><strong>Date:</strong> <?php echo $event['event_date']; ?> | <strong>Time:</strong> <?php echo $event['event_time']; ?></p>
            <p><strong>Remaining Slots:</strong> <?php echo max(0, $remaining_slots); ?></p>

            <div class="btn-container">
                <?php if ($is_registered): ?>
                    <div class="msg-box" style="background:#d1fae5; color:#065f46;">✅ Already Registered</div>
                <?php elseif ($remaining_slots > 0): ?>
                    <a href="register_confirm.php?eventID=<?php echo urlencode($eventID); ?>" 
                       class="btn-action" style="background:#10b981;">Register Now</a>
                <?php else: ?>
                    <div class="msg-box" style="background:#fee2e2; color:#991b1b;">🚫 Event Full - No slots available</div>
                <?php endif; ?>
                
                <br><br>
                <a href="browse_event.php" class="btn-action" style="background:#475569;">Back</a>
            </div>
        </div>
    </div>
</body>
</html>