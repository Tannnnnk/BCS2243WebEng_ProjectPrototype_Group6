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
        .board-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        .filter-container { display: flex; gap: 15px; margin-bottom: 20px; }
        .filter-box { flex: 1; display: flex; flex-direction: column; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8fafc; padding: 12px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; cursor: pointer; border: none; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    <div class="content-area">
        <div class="central-board">
            <div class="board-title">📝 Manage Events Workspace</div>

            <div class="filter-container">
                <div class="filter-box">
                    <label style="font-size:12px; font-weight:bold; margin-bottom:5px;">Search Events</label>
                    <input type="text" id="searchInput" placeholder="Search title or venue..." style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="filter-box">
                    <label style="font-size:12px; font-weight:bold; margin-bottom:5px;">Status Filter</label>
                    <select id="statusFilter" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">All Statuses</option>
                        <option value="Upcoming">Upcoming</option>
                        <option value="Past">Past</option>
                    </select>
                </div>
                <div class="filter-box" style="justify-content: flex-end;">
                    <button class="btn" style="background:#64748b; color:white;" onclick="applyFilters()">Apply Filters</button>
                </div>
                <div class="filter-box" style="justify-content: flex-end;">
                    <button class="btn" style="background:#94a3b8; color:white;" onclick="resetFilters()">Reset</button>
                </div>
            </div>

            <table id="eventsTable">
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
                <a href="#" class="btn" style="background:#0ea5e9; color:white;" onclick="return checkSelection('view')">👁️ View Details</a>
                <a href="#" class="btn" style="background:#f59e0b; color:white;" onclick="return checkSelection('edit')">✏️ Edit</a>
                <a href="#" class="btn" style="background:#ef4444; color:white;" onclick="return checkSelection('delete')">🗑️ Delete</a>
                <a href="#" class="btn" style="background:#10b981; color:white;" onclick="return checkSelection('list')">📋 Participants List</a>
            </div>
        </div>
    </div>

    <script>
        let selectedID = null;
        const clubID = "<?php echo urlencode($clubID); ?>";
        function selectEvent(id) { selectedID = id; }

        function applyFilters() {
            let search = document.getElementById("searchInput").value.toLowerCase();
            let status = document.getElementById("statusFilter").value.toLowerCase();
            let table = document.getElementById("eventsTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let title = tr[i].getElementsByTagName("td")[1].textContent.toLowerCase();
                let venue = tr[i].getElementsByTagName("td")[3].textContent.toLowerCase();
                let rowStatus = tr[i].getElementsByTagName("td")[5].textContent.toLowerCase();
                
                let matchesSearch = (title.indexOf(search) > -1 || venue.indexOf(search) > -1);
                let matchesStatus = (status === "" || rowStatus === status);
                
                tr[i].style.display = (matchesSearch && matchesStatus) ? "" : "none";
            }
        }

        function resetFilters() {
            document.getElementById("searchInput").value = "";
            document.getElementById("statusFilter").value = "";
            let tr = document.getElementById("eventsTable").getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) tr[i].style.display = "";
        }

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