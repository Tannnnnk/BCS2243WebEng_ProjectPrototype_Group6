<?php
require_once 'db_connection.php';

$popup_type = ""; 
$popup_message = "";

date_default_timezone_set('Asia/Kuala_Lumpur');
$current_date_today = date('Y-m-d');
$current_time_now = date('H:i:s');

$check_events_query = "SELECT eventID, event_date, event_time FROM events 
                       WHERE event_date < '$current_date_today' 
                       OR (event_date = '$current_date_today' AND ADDTIME(event_time, '02:00:00') <= '$current_time_now')";
$events_res = mysqli_query($link, $check_events_query);

if ($events_res) {
    while ($ev = mysqli_fetch_assoc($events_res)) {
        $past_event_id = $ev['eventID'];
        $past_event_date = $ev['event_date'];
        $past_event_time = $ev['event_time'];

        $absent_students_query = "SELECT er.userID FROM eventregistration er 
                                  WHERE er.eventID = '$past_event_id' 
                                  AND NOT EXISTS (
                                      SELECT 1 FROM attendance a 
                                      WHERE a.userID = er.userID AND a.eventID = er.eventID
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
                                  VALUES ('$next_attendance_id', '$past_event_date', '$past_event_time', 'Absent', '$past_event_id', '$absent_user_id')";
                mysqli_query($link, $insert_absent);
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submitted_data = $_POST['student_data'] ?? ''; 
    
    if ($submitted_data) {
        list($userID, $eventID, $stu_ID, $role, $event_start_time) = explode('|', $submitted_data);
        
        $attendance_time = date('H:i:s'); 
        $attendance_date = date('Y-m-d'); 

        $duplicate_check = "SELECT attendance_status FROM attendance WHERE userID = '$userID' AND eventID = '$eventID'";
        $duplicate_result = mysqli_query($link, $duplicate_check);

        if (mysqli_num_rows($duplicate_result) > 0) {
            $existing_record = mysqli_fetch_assoc($duplicate_result);
            $popup_type = "error";
            $popup_message = "<b>$stu_ID</b> has already submitted attendance!<br>Current Status: <b>".$existing_record['attendance_status']."</b>.";
        } else {
            if (strtolower($role) == 'volunteer') {
                $attendance_status = "Volunteer";
            } else {
                if (strtotime($attendance_time) > strtotime($event_start_time)) {
                    $attendance_status = "Late";
                } else {
                    $attendance_status = "Present";
                }
            }

            $id_query = "SELECT attendanceID FROM attendance ORDER BY CAST(SUBSTRING(attendanceID, 2) AS UNSIGNED) DESC LIMIT 1";
            $id_result = mysqli_query($link, $id_query);
            
            if ($id_result && mysqli_num_rows($id_result) > 0) {
                $row = mysqli_fetch_assoc($id_result);
                $attendanceID = "A" . ((int)substr($row['attendanceID'], 1) + 1); 
            } else {
                $attendanceID = "A101"; 
            }

            $insert_query = "INSERT INTO attendance (attendanceID, attendance_date, attendance_time, attendance_status, eventID, userID) 
                             VALUES ('$attendanceID', '$attendance_date', '$attendance_time', '$attendance_status', '$eventID', '$userID')";
            
            if (mysqli_query($link, $insert_query)) {
                $popup_type = "success";
                $popup_message = "Success! <b>$stu_ID</b> recorded as <b>$attendance_status</b>.";
            } else {
                $popup_type = "error";
                $popup_message = "Database Error: " . mysqli_error($link);
            }
        }
    } else {
        $popup_type = "error";
        $popup_message = "Please select a student.";
    }
}

$student_name = 
"SELECT 
    s.userID, s.stu_ID, s.stu_name, e.eventID, e.event_title, e.event_date, e.event_time, 
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM committee c 
            JOIN membership m ON c.memberID = m.memberID 
            WHERE m.userID = s.userID AND c.eventID = e.eventID
        ) THEN 'Volunteer'
        ELSE 'Attendee'
    END AS role
FROM students s
JOIN events e ON 1=1
WHERE 
    (
        EXISTS (SELECT 1 FROM eventregistration er WHERE er.userID = s.userID AND er.eventID = e.eventID)
        OR 
        EXISTS (SELECT 1 FROM committee c JOIN membership m ON c.memberID = m.memberID WHERE m.userID = s.userID AND c.eventID = e.eventID)
    )
ORDER BY s.stu_name ASC";

$result = mysqli_query($link, $student_name);

$students_data = ['Volunteer' => [], 'Attendee' => []];
$event_title_html = "";
$event_date_html = "";

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $userID = htmlspecialchars($row['userID']);
        $eventID = htmlspecialchars($row['eventID']);
        $stu_ID = htmlspecialchars($row['stu_ID']);
        $name = htmlspecialchars($row['stu_name']);
        
        $event_title_html = htmlspecialchars($row['event_title']);
        $event_date_html = htmlspecialchars($row['event_date']);
        $event_time_db = htmlspecialchars($row['event_time']); 
        $role = htmlspecialchars($row['role']);

        $display_text = "$stu_ID - $name"; 
        $value_to_submit = "$userID|$eventID|$stu_ID|$role|$event_time_db";

        $students_data[$role][] = [
            'value' => $value_to_submit,
            'text' => $display_text
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Form</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { display: flex;; height: 100vh; background-color: #f4f7f6; color: #333; justify-content: center; padding: 20px;}
        .page-container { width: 100%; max-width: 800px; border: 2px solid #059669; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(5, 150, 105, 0.1); }
        .form-title { margin-bottom: 40px; padding: 15px 30px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border-radius: 4px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; row-gap: 30px; align-items: center; }
        .form-label { text-align: center; font-size: 16px; color: #333; font-weight: 500; }
        .form-input { text-align: center; }
        select, input[type="text"], input[type="date"], input[type="time"] { width: 100%; max-width: 300px; box-sizing: border-box; height: 42px; padding: 10px; font-size: 14px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
        select:disabled { background-color: #f3f4f6; cursor: not-allowed; }
        .submit-container { grid-column: 1 / -1; display: flex;justify-content: center; width: 100%; margin-top: 10px; }
        .submit-btn { padding: 12px 35px; font-size: 16px; font-weight: bold; color: #ffffff; background-color: #10b981; border: none; border-radius: 6px; cursor: pointer; margin-left: 0px; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease;}

        .modal-box { background-color: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 400px; width: 80%; transform: translateY(-50px); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }

        .modal-overlay.show { display: flex; opacity: 1; }
        .modal-overlay.show .modal-box { transform: translateY(0); } 

        .modal-title { font-size: 22px; font-weight: bold; margin-bottom: 10px; }
        .modal-title.success { color: #10b981; }
        .modal-title.error { color: #dc2626; }
        
        .modal-text { font-size: 16px; color: #4b5563; margin-bottom: 25px; line-height: 1.5; }
        
        .modal-btn { padding: 10px 30px; font-size: 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white;}
        .modal-btn.success { background-color: #10b981; }
        .modal-btn.error { background-color: #dc2626; }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; row-gap: 15px; }
            .form-label { text-align: left; font-weight: bold; }
            select, input[ type="text"], input[type="date"], input[type="time"] { max-width: 100%; }
            .radio-group { flex-wrap: wrap; }
            .submit-btn { margin-left: 0;width: 100%; }
        }
    </style>
</head>
<body>
    <div class="modal-overlay" id="resultModal">
        <div class="modal-box">
            <div id="modalTitle" class="modal-title"></div>
            <div id="modalText" class="modal-text"></div>
            <button id="modalBtn" class="modal-btn" onclick="closeModal()">OK</button>
        </div>
    </div>

    <div class="page-container">
        <form method="POST" action="">
            <div class="form-title">Attendance Form</div>

            <div class="form-grid">
                <div class="form-label">Select Role</div>
                <div class="form-input">
                    <select name="selected_role" id="role_select" onchange="updateStudents()" required>
                        <option value="">-- Choose Role --</option>
                        <option value="Volunteer">Volunteer</option>
                        <option value="Attendee">Attendee</option>
                    </select>
                </div>

                <div class="form-label">Student ID and name</div>
                <div class="form-input">
                    <select name="student_data" id="student_select" required disabled>
                        <option value="">-- Select Role First --</option>
                    </select>
                </div>

                <div class="form-label">Event name</div>
                <div class="form-input">
                    <input type="text" value="<?php echo $event_title_html; ?>" readonly>
                </div>

                <div class="form-label">Attendance date</div>
                <div class="form-input">
                    <input type="text" value="<?php echo $event_date_html; ?>" readonly>
                </div>

                <div class="form-label">Attendance time</div>
                <div class="form-input">
                    <input type="time" value="<?php echo date('H:i'); ?>" readonly required>
                </div>

                <div class="submit-container">
                    <button type="submit" class="submit-btn">Submit</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const studentData = <?php echo json_encode($students_data); ?>;

        function updateStudents() {
            const roleSelect = document.getElementById('role_select');
            const studentSelect = document.getElementById('student_select');
            const selectedRole = roleSelect.value;

            studentSelect.innerHTML = '<option value="">-- Select Student --</option>';

            if (selectedRole !== "") {
                studentSelect.disabled = false;
                if (studentData[selectedRole]) {
                    studentData[selectedRole].forEach(function(student) {
                        const option = document.createElement('option');
                        option.value = student.value;       
                        option.textContent = student.text;  
                        studentSelect.appendChild(option);
                    });
                }
            } else {
                studentSelect.disabled = true;
                studentSelect.innerHTML = '<option value="">-- Select Role First --</option>';
            }
        }

        function showModal(type, text) {
            const modal = document.getElementById('resultModal');
            const title = document.getElementById('modalTitle');
            const textElement = document.getElementById('modalText');
            const btn = document.getElementById('modalBtn');

            if (type === 'success') {
                title.textContent = "Awesome!";
                title.className = "modal-title success";
                btn.className = "modal-btn success";
            } else {
                title.textContent = "Oops!";
                title.className = "modal-title error";
                btn.className = "modal-btn error";
            }

            textElement.innerHTML = text; 

            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('resultModal').classList.remove('show');
        }

        window.onload = function() {
            const phpPopupType = "<?php echo $popup_type; ?>";
            const phpPopupMessage = "<?php echo addslashes($popup_message); ?>";

            if (phpPopupType !== "") {
                showModal(phpPopupType, phpPopupMessage);
            }
        };
    </script>
</body>
</html>