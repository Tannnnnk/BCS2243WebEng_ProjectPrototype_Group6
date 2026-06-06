<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'student_login_materials.php';

$eid = isset($_GET['id']) ? mysqli_real_escape_string($link, $_GET['id']) : '';
$clubID = isset($_GET['clubID']) ? mysqli_real_escape_string($link, $_GET['clubID']) : '';

// 1. Process Update
if (isset($_POST['update_event'])) {
    $id = mysqli_real_escape_string($link, $_POST['eventID']);
    $title = mysqli_real_escape_string($link, $_POST['event_title']);
    $date = mysqli_real_escape_string($link, $_POST['event_date']);
    $time = mysqli_real_escape_string($link, $_POST['event_time']);
    $venue = mysqli_real_escape_string($link, $_POST['event_venue']);
    $max = intval($_POST['event_max_participants']);
    
    $sql_upd = "UPDATE events SET event_title='$title', event_date='$date', event_time='$time', event_venue='$venue', event_max_participants=$max WHERE eventID='$id'";
    mysqli_query($link, $sql_upd);
    
    header("Location: manage_events.php?status=updated&clubID=" . urlencode($clubID));
    exit();
}

// 2. Fetch existing data
$res = mysqli_query($link, "SELECT * FROM events WHERE eventID = '$eid'");
$row = mysqli_fetch_assoc($res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event - #<?php echo $row['eventID']; ?></title>
    <style>
        .details-wrapper { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 30px; max-width: 650px; margin: 40px auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); font-family: system-ui, sans-serif; }
        .details-header { font-size: 18px; font-weight: bold; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; }
        .form-group { margin-bottom: 18px; }
        .form-label { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 6px; display: block; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        .btn { display: inline-block; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; border: none; cursor: pointer; }
        .btn-save { background-color: #059669; color: white; }
        .btn-cancel { background-color: #64748b; color: white; margin-left: 10px; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>

    <div class="content-area">
        <div class="workspace-wrapper">
            <div class="details-wrapper">
                <div class="details-header">✏️ Edit Event: <?php echo htmlspecialchars($row['event_title']); ?></div>
                
                <form method="POST">
                    <input type="hidden" name="eventID" value="<?php echo $row['eventID']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Event Title</label>
                        <input type="text" name="event_title" class="form-control" value="<?php echo htmlspecialchars($row['event_title']); ?>" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" name="event_date" class="form-control" value="<?php echo $row['event_date']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Time</label>
                            <input type="time" name="event_time" class="form-control" value="<?php echo $row['event_time']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Venue</label>
                        <input type="text" name="event_venue" class="form-control" value="<?php echo htmlspecialchars($row['event_venue']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Maximum Capacity</label>
                        <input type="number" name="event_max_participants" class="form-control" value="<?php echo $row['event_max_participants']; ?>" required>
                    </div>

                    <div style="margin-top: 30px; border-top: 2px solid #f1f5f9; padding-top: 20px;">
                        <button type="submit" name="update_event" class="btn btn-save">💾 Save Changes</button>
                        <a href="manage_events.php?clubID=<?php echo urlencode($clubID); ?>" class="btn btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>