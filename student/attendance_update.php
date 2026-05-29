<?php
require_once 'student_login_materials.php';

if (isset($_GET['eventID'])) {
    $eventID = $_GET['eventID'];
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$current_datetime = date('Y-m-d H:i:s');
$current_time_now = date('H:i:s'); 

$check_events_query = "
    SELECT event_date, event_time 
    FROM events 
    WHERE DATE_ADD(CONCAT(event_date, ' ', event_time), INTERVAL 2 HOUR) < '$current_datetime' 
    AND eventID = '$eventID'
";
$events_res = mysqli_query($link, $check_events_query);

if ($events_res) {
    while ($ev = mysqli_fetch_assoc($events_res)) {
        $eventDate = $ev['event_date'];
        
        $absent_students_query = "
            SELECT expected_attendees.userID 
            FROM (
                SELECT userID FROM eventregistration 
                WHERE eventID = '$eventID'
        
                UNION
        
                SELECT m.userID 
                FROM committee c
                JOIN membership m ON c.memberID = m.memberID
                WHERE c.eventID = '$eventID'
            ) AS expected_attendees

            WHERE NOT EXISTS (
                SELECT 1 FROM attendance a 
                WHERE a.userID = expected_attendees.userID 
                AND a.eventID = '$eventID'
            )";
        $absent_res = mysqli_query($link, $absent_students_query);

        if ($absent_res) {
            while ($absent_student = mysqli_fetch_assoc($absent_res)) {
                $absent_user_id = $absent_student['userID'];

                $id_query = "SELECT attendanceID FROM attendance ORDER BY CAST(SUBSTRING(attendanceID, 2) AS UNSIGNED) DESC LIMIT 1";
                $id_res = mysqli_query($link, $id_query);
                
                if ($id_res && mysqli_num_rows($id_res) > 0) {
                    $last_row = mysqli_fetch_assoc($id_res);
                    $next_attendance_id = "A" . ((int)substr($last_row['attendanceID'], 1) + 1);
                } else {
                    $next_attendance_id = "A101";
                }

                $insert_absent = "INSERT INTO attendance (attendanceID, attendance_date, attendance_time, attendance_status, eventID, userID) 
                                  VALUES ('$next_attendance_id', '$eventDate', '$current_time_now', 'Absent', '$eventID', '$absent_user_id')";
                
                if (!mysqli_query($link, $insert_absent)) {
                    echo "<div style='color:red; font-weight:bold;'>System Error recording absent user $absent_user_id: " . mysqli_error($link) . "</div>";
                } else  {
                    $p_id_res = mysqli_query($link, "SELECT pointID FROM points ORDER BY CAST(SUBSTRING(pointID, 2) AS UNSIGNED) DESC LIMIT 1");
                    if ($p_id_res && mysqli_num_rows($p_id_res) > 0) {
                        $pid_row = mysqli_fetch_assoc($p_id_res);
                        $next_point_id = "P" . ((int)substr($pid_row['pointID'], 1) + 1);
                    } else {
                        $next_point_id = "P101";
                    }
                    mysqli_query($link, "INSERT INTO points (pointID, stu_enforce, point_value, attendanceID, userID) 
                                         VALUES ('$next_point_id', 'Absent without notice', -10, '$next_attendance_id', '$absent_user_id')");
                }
            }
        }
    }
} else {
    echo "<div style='color:red;'>Event check query failed: " . mysqli_error($link) . "</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $attendanceID = mysqli_real_escape_string($link, $_POST['attendanceID']);
    $old_status = mysqli_real_escape_string($link, $_POST['old_status']);
    $new_status = mysqli_real_escape_string($link, $_POST['new_status']);

    if ($old_status !== $new_status) {
        $participant_role = mysqli_real_escape_string($link, $_POST['participant_role'] ?? 'Attendee');
        if ($participant_role === 'Volunteer') {
            $point_rules = [
                'Present Volunteer'    => ['points' => 15,  'desc' => 'Volunteer present on time'],
                'Late Volunteer'       => ['points' => 10,  'desc' => 'Volunteer late arrival'],
                'Reasonable' => ['points' => 0,   'desc' => 'Volunteer absent with reason'],
                'Absent'     => ['points' => -10, 'desc' => 'Volunteer absent without notice']
            ];
        } else {
            $point_rules = [
                'Present'    => ['points' => 10,  'desc' => 'Present on time'],
                'Late'       => ['points' => 5,   'desc' => 'Late arrival'],
                'Reasonable' => ['points' => 0,   'desc' => 'Absent with reason'],
                'Absent'     => ['points' => -10, 'desc' => 'Absent without notice']
            ];
        }

        $new_points = $point_rules[$new_status]['points'];
        $new_desc = $point_rules[$new_status]['desc'];

        $status_to_save = $new_status;
        if ($participant_role === 'Volunteer' && ($new_status === 'Present' || $new_status === 'Late')) {
            $status_to_save = $new_status . ' Volunteer';
        }

        mysqli_begin_transaction($link);
        try {
            if (!mysqli_query($link, "UPDATE attendance SET attendance_status = '$status_to_save' WHERE attendanceID = '$attendanceID'")) {
                throw new Exception("Attendance Table Error: " . mysqli_error($link));
            }

            if (!mysqli_query($link, "UPDATE points SET point_value = '$new_points', stu_enforce = '$new_desc' WHERE attendanceID = '$attendanceID'")) {
                throw new Exception("Points Table Error: " . mysqli_error($link));
            }

            mysqli_commit($link);
            
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Success!',
                            html: 'Status updated from <b>$old_status</b> to <b>$new_status</b>.',
                            icon: 'success',
                            confirmButtonColor: '#10b981'
                        });
                    });
                  </script>";

        } catch (Exception $e) {
            mysqli_rollback($link);
            
            $error_message = addslashes($e->getMessage()); 
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Update Failed',
                            text: '$error_message',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    });
                  </script>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
    $attendanceID = mysqli_real_escape_string($link, $_POST['attendanceID']);

    mysqli_begin_transaction($link);
    try {
        if (!mysqli_query($link, "DELETE FROM points WHERE attendanceID = '$attendanceID'")) {
            throw new Exception("Could not delete from points table: " . mysqli_error($link));
        }

        if (!mysqli_query($link, "DELETE FROM attendance WHERE attendanceID = '$attendanceID'")) {
            throw new Exception("Could not delete from attendance table: " . mysqli_error($link));
        }

        mysqli_commit($link);
        
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'The attendance record has been completely removed.',
                        icon: 'success',
                        confirmButtonColor: '#ef4444' 
                    });
                });
              </script>";

    } catch (Exception $e) {
        mysqli_rollback($link);
        $error_message = addslashes($e->getMessage());
        
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Deletion Failed',
                        text: '$error_message',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
              </script>";
    }
}

$target_event_id = mysqli_real_escape_string($link, $_GET['eventID']); 
$participants_query = "
    SELECT 
        a.attendanceID, 
        a.attendance_status, 
        s.userID, 
        s.stu_ID, 
        s.stu_name,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM committee c 
                JOIN membership m ON c.memberID = m.memberID 
                WHERE m.userID = s.userID AND c.eventID = a.eventID
            ) THEN 'Volunteer'
            ELSE 'Attendee'
        END AS role
    FROM attendance a
    JOIN students s ON a.userID = s.userID
    WHERE a.eventID = '$target_event_id'
    ORDER BY s.stu_name ASC
";
$participants_result = mysqli_query($link, $participants_query);

$participants_html = ""; 

if ($participants_result && mysqli_num_rows($participants_result) > 0) {
    while ($row = mysqli_fetch_assoc($participants_result)) {
        $att_id = $row['attendanceID'];
        $user_id = $row['userID'];
        $current_status = htmlspecialchars($row['attendance_status']);
        $stu_ID = htmlspecialchars($row['stu_ID']);
        $stu_name = htmlspecialchars($row['stu_name']);
        
        $participant_role = htmlspecialchars($row['role'] ?? 'Attendee'); 
        
        $status_color = ($current_status == 'Absent') ? '#dc2626' : '#059669';
        $form_id = "form_" . $att_id; 

        $participants_html .= "<tr>";
        $participants_html .= "<td><strong>" . $stu_ID . "</strong></td>";
        $role_badge = ($participant_role === 'Volunteer') ? " <small style='color:#3b82f6;'></small>" : "";
        $participants_html .= "<td>" . $stu_name . $role_badge . "</td>";
        $participants_html .= "<td style='color: $status_color; font-weight: bold;'>$current_status</td>";
        
        $participants_html .= "<td>
                <select name='new_status' form='$form_id' class='status-dropdown'>
                    <option value='Present' ".($current_status == 'Present' ? 'selected' : '').">Present</option>
                    <option value='Late' ".($current_status == 'Late' ? 'selected' : '').">Late</option>
                    <option value='Reasonable' ".($current_status == 'Reasonable' ? 'selected' : '').">Reasonable</option>
                    <option value='Absent' ".($current_status == 'Absent' ? 'selected' : '').">Absent</option>
                </select>
              </td>";
        
        $participants_html .= "<td>
                <form id='$form_id' method='POST' action='' style='display: flex; gap: 10px; align-items: center; margin: 0;'>
                    <input type='hidden' name='attendanceID' value='$att_id'>
                    <input type='hidden' name='userID' value='$user_id'>
                    <input type='hidden' name='old_status' value='$current_status'>
                    <!-- Added hidden input to pass the role to the PHP logic -->
                    <input type='hidden' name='participant_role' value='$participant_role'>
                    
                    <button type='submit' name='update_status' class='save-btn'>Save</button>
                    <button type='submit' name='delete_record' class='delete-btn' onclick=\"return confirm('Are you sure you want to completely delete this attendance record? This will also remove the points.');\">Delete</button>
                </form>
              </td>";
        $participants_html .= "</tr>";
    }
} else {
    $participants_html = "<tr><td colspan='4' style='text-align: center; padding: 20px; color: #6b7280;'>No attendance records found for this event.</td></tr>";
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Update Attendance - FK Management System</title>
    <style>
        .back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; color: #f97316; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.2s ease; user-select: none; width: fit-content; }
        .back-btn:hover { background-color: #e5e7eb; color: #111827; }

        .correction-card { background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px; }
        .correction-header { background-color: #f9fafb; padding: 20px 25px; border-bottom: 1px solid #e5e7eb; }
        .correction-table { width: 100%; border-collapse: collapse; text-align: left; }
        .correction-table th { background-color: #ffffff; color: #4b5563; font-size: 14px; padding: 15px 25px; border-bottom: 2px solid #e5e7eb; }
        .correction-table td { padding: 15px 25px; font-size: 15px; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .correction-table tr:hover { background-color: #f0fdf4; }

        .status-dropdown { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; background-color: #fff; cursor: pointer; outline: none; }
        .status-dropdown:focus { border-color: #10b981; }
        .save-btn { padding: 8px 16px; background-color: #10b981; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: background-color 0.2s; }
        .save-btn:hover { background-color: #f97316; }
    
        .delete-btn { padding: 8px 16px; background-color: #ef4444; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: background-color 0.2s; }
        .delete-btn:hover { background-color: #dc2626; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
        <div class="content-area">
            <div class="back-btn" onclick="window.history.back();">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Go to previous page
            </div><br><br>

            <div class="correction-card">
                <div class="correction-header">
                    <h2 style="margin: 0; font-size: 20px; color: #1f2937;">Attendance Corrections</h2>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">Modify participant status. Points will auto-recalculate.</p>
                </div>

                <div class="table-responsive">
                    <table class="correction-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Current Status</th>
                                <th>Change Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo $participants_html; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>