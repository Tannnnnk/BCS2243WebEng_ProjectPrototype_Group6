<?php
require_once 'student_login_materials.php';

// 1. Fetch Events Assigned to this specific user
$managed_events = [];
$total_registrations = 0;
$total_managed = 0;

$sql_events = "
    SELECT 
        e.eventID, e.event_title, e.event_date, e.event_time, e.event_venue, e.event_max_participants,
        (SELECT COUNT(*) FROM eventregistration er WHERE er.eventID = e.eventID) as total_reg,
        (SELECT COUNT(*) FROM attendance a WHERE a.eventID = e.eventID AND (a.attendance_status = 'Present' OR a.attendance_status = 'Late')) as total_attended
    FROM events e
    JOIN committee com ON e.eventID = com.eventID
    JOIN membership m ON com.memberID = m.memberID
    WHERE m.userID = '$userID'
    ORDER BY e.event_date ASC
";

$res_events = mysqli_query($link, $sql_events);
if ($res_events) {
    while ($row = mysqli_fetch_assoc($res_events)) {
        $managed_events[] = $row;
        $total_registrations += $row['total_reg'];
        $total_managed++;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Dashboard - FK Management System</title>
    <style>
        /* PAGE SPECIFIC CSS */
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; margin-top: 0; }
        .header-section h2 { font-size: 24px; color: #1a202c; }
        
        .camera-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, #f97316 0%, #ea580c 200%); color: white; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: bold; letter-spacing: 0.5px; cursor: pointer; border: none; box-shadow: 0 4px 10px rgba(234, 88, 12, 0.25); transition: all 0.3s ease; }
        .camera-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(234, 88, 12, 0.4); }
        .camera-btn:active { transform: translateY(1px); box-shadow: 0 2px 5px rgba(234, 88, 12, 0.2); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-bottom: 4px solid #ea580c; }
        .stat-card h3 { color: #718096; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .stat-card .value { font-size: 36px; font-weight: bold; color: #1e293b; }
        
        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; margin-bottom: 40px; }
        .table-title { padding: 20px; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; font-size: 18px; font-weight: bold; color: #2d3748; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; color: #4a5568; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; text-align: left; padding: 16px 20px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 20px; border-bottom: 1px solid #e2e8f0; color: #1a202c; font-size: 14px; vertical-align: middle; }
        tr:hover { background-color: #f1f5f9; }

        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.3s; display: inline-flex; align-items: center; text-decoration: none; }
        .btn-primary { background-color: #f97316; color: white; }
        .btn-primary:hover { background-color: #ea580c; }
        
        .progress-bar-bg { width: 100%; background-color: #ffedd5; border-radius: 10px; height: 8px; margin-top: 5px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background-color: #f97316; border-radius: 10px; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    <div class="content-area">
        <div class="header-section">
            <div>
                <h2>Committee Workspace</h2>
                <p style="color: #718096; margin-top: 3px;">Monitor registrations and manage attendance for your assigned events.</p>
            </div>
            <button id="start-camera" class="camera-btn">Start Camera</button>
            <video id="video-feed" autoplay playsinline style="width: 100%; max-width: 500px; border: 2px solid black; display: none;"></video>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>My Managed Events</h3>
                <div class="value"><?php echo $total_managed; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Pax Registered</h3>
                <div class="value"><?php echo $total_registrations; ?></div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-title">Events Under Your Management</div>
            <table>
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Date & Time</th>
                        <th style="text-align: center;">Venue</th>
                        <th>Registrations</th>
                        <th style="width: 200px;">Attendance Status</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($managed_events) > 0): ?>
                        <?php foreach ($managed_events as $evt): ?>
                            <?php 
                                $reg_rate = 0;
                                if ($evt['event_max_participants'] > 0) {
                                    $reg_rate = round(($evt['total_reg'] / $evt['event_max_participants']) * 100);
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($evt['event_title']); ?></strong></td>
                                <td style="color: #4a5568;">
                                    <?php echo htmlspecialchars($evt['event_date']); ?><br>
                                    <span style="font-size: 12px; color: #a0aec0;"><?php echo htmlspecialchars($evt['event_time']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($evt['event_venue']); ?></td>
                                <td>
                                    <div style="font-weight: bold; text-align: center;"><?php echo htmlspecialchars($evt['total_reg']); ?> / <?php echo htmlspecialchars($evt['event_max_participants']); ?></div>
                                    <div class="progress-bar-bg" style="height: 5px;">
                                        <div class="progress-bar-fill" style="width: <?php echo $reg_rate; ?>%;"></div>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span style="font-size: 13px; font-weight: bold; color: #ea580c;">
                                        <?php echo htmlspecialchars($evt['total_attended']); ?> Attended
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="../attendance_form.php?eventID=<?php echo htmlspecialchars($evt['eventID']); ?>" target="_blank" class="btn btn-primary" style="background-color: #ea580c;">
                                        Take Attendance
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #718096;">You have not been assigned to manage any upcoming events.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div> 

    <script>
        document.getElementById('start-camera').addEventListener('click', async function() {
            let video = document.getElementById('video-feed');
    
            try {
                let stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'environment' } 
                });
                video.style.display = 'block';
                video.srcObject = stream;
        
            } catch (err) {
                alert("Oops! We couldn't access the camera. Did you block permissions?");
                console.error(err);
            }
        });
    </script>
</body>
</html>