<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'student_login_materials.php';

if (!isset($_GET['id'])) {
    header("Location: manage_events.php");
    exit();
}

$eventID = mysqli_real_escape_string($link, $_GET['id']);

// --- CLEAN STRUCTURAL EVENT QUERY ---
$query = "SELECT e.* FROM events e WHERE e.eventID = '$eventID'";
$result = mysqli_query($link, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<script>alert('Error: Event record could not be found.'); window.location.href='manage_events.php';</script>";
    exit();
}

$event = mysqli_fetch_assoc($result);

// --- FIXED: Safe fallback placeholder to prevent database crash ---
$registered_count = 0; 

// --- CHECK STATUS BANNER VALUES ---
$today_date = date('Y-m-d');
$event_date = $event['event_date'];
if ($event_date < $today_date) {
    $status_banner = "🔴 Past Event Record";
    $banner_style = "background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;";
} else if ($event_date == $today_date) {
    $status_banner = "⚡ Event is Happening Today!";
    $banner_style = "background-color: #fef3c7; color: #d97706; border: 1px solid #fcd34d;";
} else {
    $status_banner = "🟢 Upcoming Event Track";
    $banner_style = "background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Specifications - #<?php echo $event['eventID']; ?></title>
    <style>
        .details-wrapper { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 30px; max-width: 650px; margin: 40px auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); font-family: system-ui, sans-serif; box-sizing: border-box; }
        .details-header { font-size: 18px; font-weight: bold; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        
        .status-ribbon { font-size: 12px; font-weight: bold; padding: 6px 12px; border-radius: 20px; text-transform: uppercase; }
        
        .details-row { margin-bottom: 18px; }
        .details-label { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .details-value { font-size: 15px; color: #334155; }
        .desc-box { background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; min-height: 60px; line-height: 1.5; }
        
        .action-button-container { display: flex; gap: 10px; justify-content: space-between; align-items: center; margin-top: 30px; border-top: 2px solid #f1f5f9; padding-top: 20px; }
        .btn-group-left { display: flex; gap: 10px; }
        
        .btn { display: inline-block; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; text-align: center; border: none; }
        .btn-back { background-color: #475569; color: white; }
        .btn-edit { background-color: #f59e0b; color: white; }
        .btn-attendance { background-color: #10b981; color: white; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="workspace-wrapper">
            <div class="details-wrapper">
                <div class="details-header">
                    <span>👁️ Full Event Specifications</span>
                    <span class="status-ribbon" style="<?php echo $banner_style; ?>"><?php echo $status_banner; ?></span>
                </div>
                
                <div class="details-row">
                    <div class="details-label">Event ID Reference</div>
                    <div class="details-value"><code>#EV-<?php echo $event['eventID']; ?></code></div>
                </div>

                <div class="details-row">
                    <div class="details-label">Event Title</div>
                    <div class="details-value" style="font-size: 18px; font-weight: bold; color: #0f172a;"><?php echo htmlspecialchars($event['event_title']); ?></div>
                </div>

                <div class="details-row">
                    <div class="details-label">Description Details</div>
                    <div class="details-value desc-box"><?php echo nl2br(htmlspecialchars($event['event_desc'])); ?></div>
                </div>

                <div class="details-row">
                    <div class="details-label">Date & Time Schedule</div>
                    <div class="details-value">📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?> &nbsp;|&nbsp; 🕒 <?php echo date('h:i A', strtotime($event['event_time'])); ?></div>
                </div>

                <div class="details-row">
                    <div class="details-label">Venue Location</div>
                    <div class="details-value">📍 <?php echo htmlspecialchars($event['event_venue']); ?></div>
                </div>

                <div class="details-row">
                    <div class="details-label">Maximum Occupancy Capacity</div>
                    <div class="details-value">
                        👥 <strong><?php echo $event['event_max_participants']; ?></strong> max slots available
                    </div>
                </div>

                <div class="action-button-container">
                    <a href="manage_events.php" class="btn btn-back">⬅️ Return to Workspace</a>
                    <div class="btn-group-left">
                        <a href="manage_events.php?edit_id=<?php echo $event['eventID']; ?>" class="btn btn-edit">✏️ Edit Details</a>
                        <a href="manage_attendance.php?eventID=<?php echo $event['eventID']; ?>" class="btn btn-attendance">📋 Attendance List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>