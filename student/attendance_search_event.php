<?php
require_once 'student_login_materials.php';

$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
date_default_timezone_set('Asia/Kuala_Lumpur');

// 1. Get club id from url parameter, fallback to database if missing
$clubID = isset($_GET['clubID']) ? mysqli_real_escape_string($link, $_GET['clubID']) : '';

if (empty($clubID)) {
    $fallback = mysqli_query($link, "
        SELECT m.clubID
        FROM club c
        JOIN membership m ON c.clubID = m.clubID
        WHERE m.userID = '$userID'
        LIMIT 1
    ");

    if ($fallback && $f_row = mysqli_fetch_assoc($fallback)) {
        $clubID = $f_row['clubID'];
    }
}

// 2. UPDATED: Join events table to track down the clubID
// We use DISTINCT so that an event isn't duplicated if it has multiple committee members
$events_query = "
    SELECT DISTINCT e.* FROM events e
    INNER JOIN committee cm ON e.eventID = cm.eventID
    INNER JOIN membership m ON cm.memberID = m.memberID
    WHERE 1=1
";

// 3. ADDED: Filter strictly by the current clubID if it exists
if (!empty($clubID)) {
    $events_query .= " AND m.clubID = '$clubID'";
}

// 4. Keep your search keyword functionality intact
if (!empty($search_keyword)) {
    $events_query .= " AND e.event_title LIKE '%$search_keyword%'";
}

$current_datetime = date('Y-m-d H:i:s');
if ($filter_status === 'upcoming') {
    $events_query .= " AND CONCAT(e.event_date, ' ', e.event_time) >= '$current_datetime'";
} elseif ($filter_status === 'past') {
    $events_query .= " AND CONCAT(e.event_date, ' ', e.event_time) < '$current_datetime'";
}

$events_query .= " ORDER BY e.event_date DESC";
$events_result = mysqli_query($link, $events_query);

$events_html = "";

if ($events_result && mysqli_num_rows($events_result) > 0) {
    while ($event = mysqli_fetch_assoc($events_result)) {
        $e_id = htmlspecialchars($event['eventID']);
        $e_title = htmlspecialchars($event['event_title']);
        $e_date = date("d M Y", strtotime($event['event_date']));
        $e_time = date("h:i A", strtotime($event['event_time']));

        $events_html .= "<tr>";
        $events_html .= "<td>$e_title</td>";
        $events_html .= "<td>$e_date <br><span style='color:#6b7280; font-size:12px;'>$e_time</span></td>";
        
        $events_html .= "<td>
                            <a href='attendance_update.php?eventID=$e_id' class='btn-action'>Edit Attendance</a>
                         </td>";
        $events_html .= "</tr>";
    }
} else {
    $events_html = "<tr><td colspan='4' class='empty-state'>No events found matching your search.</td></tr>";
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance - FK Management System</title>
    <style>
    .event-dashboard { background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); overflow: hidden; }
    .dashboard-header { background-color: #f9fafb; padding: 20px 25px; border-bottom: 1px solid #e5e7eb; }
    .dashboard-header h2 { margin: 0; font-size: 20px; color: #1f2937; }
    .dashboard-header p { margin: 5px 0 0 0; font-size: 14px; color: #6b7280; }
    
    .filter-container { padding: 20px 25px; background-color: #ffffff; border-bottom: 1px solid #e5e7eb; }
    .filter-form { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }

    .input-group input, .input-group select { padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; outline: none; min-width: 200px; }
    .input-group input:focus, .input-group select:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }

    .btn-filter { padding: 10px 20px; background-color: #fb923c; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .btn-filter:hover { background-color: #f97316; }
    .btn-clear { padding: 10px 20px; background-color: #f3f4f6; color: #4b5563; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold; transition: 0.2s; }
    .btn-clear:hover { background-color: #e5e7eb; }

    .event-table { width: 100%; border-collapse: collapse; text-align: left; }
    .event-table th { background-color: #f9fafb; color: #4b5563; font-size: 14px; padding: 15px 25px; border-bottom: 2px solid #e5e7eb; }
    .event-table td { padding: 15px 25px; font-size: 15px; border-bottom: 1px solid #f3f4f6; }
    .event-table tr:hover { background-color: #f0fdf4; }

    .btn-action { display: inline-block; padding: 6px 12px; background: linear-gradient(135deg, #ffaa00 0%, #ff5500 100%);; color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; }
    .btn-action:hover { background-color: #2563eb; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
        <div class="content-area">
            <div class="event-dashboard">
                <div class="dashboard-header">
                    <h2>Event Management</h2>
                        <p>Find an event to edit details or correct attendance.</p>
                </div>

                <div class="filter-container">
                    <form method="GET" action="" class="filter-form">
                        <div class="input-group">
                            <input type="text" name="search" placeholder="Search event name..." 
                            value="<?php echo htmlspecialchars($search_keyword); ?>">
                        </div>

                        <div class="input-group">
                            <select name="status">
                                <option value="all" <?php if($filter_status == 'all') echo 'selected'; ?>>All Events</option>
                                <option value="upcoming" <?php if($filter_status == 'upcoming') echo 'selected'; ?>>Upcoming Events</option>
                                <option value="past" <?php if($filter_status == 'past') echo 'selected'; ?>>Past Events</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-filter">Filter</button>
                        <a href="?" class="btn-clear">Clear</a>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="event-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date & Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $events_html ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
