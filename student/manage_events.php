<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'student_login_materials.php';

// --- CLUB ID PRESERVATION ---
$clubID = isset($_GET['clubID']) ? mysqli_real_escape_string($link, $_GET['clubID']) : '';
if (empty($clubID)) {
    $c_query = mysqli_query($link, "SELECT clubID FROM membership WHERE userID = '$userID' LIMIT 1");
    if ($row = mysqli_fetch_assoc($c_query)) $clubID = $row['clubID'];
}

// --- DELETE HANDLER ---
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($link, $_GET['delete_id']);
    mysqli_query($link, "DELETE FROM events WHERE eventID = '$del_id'");
    header("Location: manage_events.php?status=deleted&clubID=" . urlencode($clubID));
    exit();
}

// --- QUERY ---
$sql = "SELECT DISTINCT e.* FROM events e
        JOIN committee cm ON e.eventID = cm.eventID
        JOIN membership m ON cm.memberID = m.memberID
        WHERE m.clubID = '$clubID'
        ORDER BY e.event_date ASC, e.event_time ASC"; 
$result_events = mysqli_query($link, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Events Workspace</title>
    <style>
        .content-area { padding: 20px; }
        .central-board { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8fafc; padding: 12px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    <div class="content-area">
        <div class="central-board">
            <div class="board-title">📝 Manage Events Workspace</div>
            <table>
                <thead>
                    <tr><th>EventID</th><th>Title</th><th>Date & Time</th><th>Venue</th><th>Capacity</th><th>Status</th><th>Select</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result_events)): ?>
                        <tr>
                            <td>#<?php echo $row['eventID']; ?></td>
                            <td><?php echo htmlspecialchars($row['event_title']); ?></td>
                            <td><?php echo $row['event_date'] . ' ' . $row['event_time']; ?></td>
                            <td><?php echo htmlspecialchars($row['event_venue']); ?></td>
                            <td><?php echo $row['event_max_participants']; ?></td>
                            <td><?php echo (strtotime($row['event_date']) < time()) ? "Past" : "Upcoming"; ?></td>
                            <td><input type="radio" name="select" value="<?php echo $row['eventID']; ?>" onclick="selectEvent('<?php echo $row['eventID']; ?>')"></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div style="margin-top:20px; display:flex; gap:10px;">
                <a href="#" id="view_btn" class="btn" style="background:#0ea5e9; color:white;" onclick="return checkSelection('view')">👁️ View Details</a>
                <a href="#" id="edit_btn" class="btn" style="background:#f59e0b; color:white;" onclick="return checkSelection('edit')">✏️ Edit</a>
                <a href="#" id="del_btn" class="btn" style="background:#ef4444; color:white;" onclick="return checkSelection('delete')">🗑️ Delete</a>
                <a href="#" id="list_btn" class="btn" style="background:#10b981; color:white;" onclick="return checkSelection('list')">📋 Participants List</a>
            </div>
        </div>
    </div>

    <script>
        let selectedID = null;
        const clubID = "<?php echo urlencode($clubID); ?>";
        function selectEvent(id) { selectedID = id; }

        function checkSelection(action) {
            if (!selectedID) { alert('Please select an event from the list first.'); return false; }
            if (action === 'delete') {
                if(confirm('Are you sure you want to delete this event?')) {
                    window.location.href = "manage_events.php?delete_id=" + selectedID + "&clubID=" + clubID;
                }
                return false;
            }
            if (action === 'edit') { window.location.href = "edit_event.php?id=" + selectedID + "&clubID=" + clubID; return false; }
            if (action === 'view') { window.location.href = "view_event_details.php?id=" + selectedID + "&clubID=" + clubID; return false; }
            if (action === 'list') { window.location.href = "manage_attendance.php?eventID=" + selectedID + "&clubID=" + clubID; return false; }
            return false;
        }
    </script>
</body>
</html>