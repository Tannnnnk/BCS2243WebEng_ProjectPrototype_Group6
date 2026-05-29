<?php
require_once 'student_login_materials.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ONLY run the database insertion IF the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $event_id = mysqli_real_escape_string($link, $_POST['event_id']);
    $club_id = mysqli_real_escape_string($link, $_POST['club_id']);
    
    // Grab the array of selected users from the checkboxes
    $selected_users = $_POST['user_ids'] ?? [];

    if (empty($selected_users)) {
        $_SESSION['msg'] = "Please select at least one new member.";
        $_SESSION['msg_type'] = "error";
    } else {
        $all_success = true;

        // Loop through every person you checked
        foreach ($selected_users as $uid) {
            $user_id = mysqli_real_escape_string($link, $uid);
            
            // 1. Generate new committeeID for this specific person
            $id_query = "SELECT committeeID FROM committee ORDER BY CAST(SUBSTRING(committeeID, 3) AS UNSIGNED) DESC LIMIT 1";
            $id_result = mysqli_query($link, $id_query);
                    
            if ($id_result && mysqli_num_rows($id_result) > 0) {
                $row = mysqli_fetch_assoc($id_result);
                $committeeID = "CM" . ((int)substr($row['committeeID'], 2) + 1); 
            } else {
                $committeeID = "CM101"; 
            }

            // 2. Look up this specific member
            $member_query = "SELECT memberID FROM membership WHERE clubID = '$club_id' AND userID = '$user_id'";  
            $member_result = mysqli_query($link, $member_query);
                        
            if ($member_result && mysqli_num_rows($member_result) > 0) {
                $row = mysqli_fetch_assoc($member_result);
                $memberID = $row['memberID'];
                
                // --- DUPLICATE CHECK ---
                $check_duplicate = "SELECT committeeID FROM committee WHERE eventID = '$event_id' AND memberID = '$memberID'";
                $duplicate_result = mysqli_query($link, $check_duplicate);
                
                if ($duplicate_result && mysqli_num_rows($duplicate_result) > 0) {
                    $all_success = false; 
                    continue; 
                }
                
                // 3. INSERT the record 
                $insert_query = "INSERT INTO committee (committeeID, eventID, memberID) VALUES ('$committeeID', '$event_id', '$memberID')";
                if (!mysqli_query($link, $insert_query)) {
                    $all_success = false;
                }
            } else {
                $all_success = false;
            }
        }

        // Set the message based on if the loop worked
        if ($all_success) {
            $_SESSION['msg'] = "Committees assigned successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Assignments complete, but some failed or were already assigned.";
            $_SESSION['msg_type'] = "error";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Committee - FK Management System</title>
    <style>
        .page-header { margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
        .page-header h3 { margin: 0; color: #1e293b; font-size: 24px; }
        .page-subtitle { color: #475569; font-size: 15px; margin-top: 5px; }

        .member-table { width: 100%; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
        .member-table th, .member-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .member-table th { background-color: #f8fafc; color: #334155; font-weight: 600; font-size: 14px; }
        .member-table td { color: #1e293b; font-size: 14px; }
        .member-table tr:hover { background-color: #f1f5f9; }

        .form-footer { display: flex; justify-content: left; gap: 12px; margin-top: 20px; }

        .btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background-color 0.2s; text-decoration: none; }
        .btn-cancel { background-color: #f1f5f9; color: #475569; }
        .btn-cancel:hover { background-color: #e2e8f0; }
        .btn-submit { background-color: #fb923c; color: white; }
        .btn-submit:hover { background-color: #fb923c; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
    
    <div class="content-area">
        <div class="page-header">
            <h3>Assign Committee Members</h3>
            <p class="page-subtitle">Assigning to Event: <strong><?php echo htmlspecialchars($_GET['eventID'] ?? 'Unknown Event'); ?></strong></p>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="club_id" value="<?php echo htmlspecialchars($_GET['clubID'] ?? ''); ?>">
            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($_GET['eventID'] ?? ''); ?>">
            
            <table class="member-table">
                <thead>
                    <tr>
                        <th style="width: 10%; text-align: center;">Select</th>
                        <th style="width: 30%;">Student ID</th>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $current_club_id = mysqli_real_escape_string($link, $_GET['clubID'] ?? '');
                    $current_event_id = mysqli_real_escape_string($link, $_GET['eventID'] ?? '');
                    
                    // --- STEP 1: Find out who is ALREADY assigned to this event ---
                    $assigned_users = [];
                    $assigned_query = "SELECT m.userID FROM committee c 
                                       JOIN membership m ON c.memberID = m.memberID 
                                       WHERE c.eventID = '$current_event_id'";
                    $assigned_result = mysqli_query($link, $assigned_query);
                    if ($assigned_result) {
                        while ($row = mysqli_fetch_assoc($assigned_result)) {
                            $assigned_users[] = $row['userID'];
                        }
                    }

                    // --- STEP 2: Fetch all club members to display in the table ---
                    $dropdown_query = "SELECT s.userID, s.stu_name, s.stu_ID 
                                       FROM membership m 
                                       JOIN students s ON m.userID = s.userID 
                                       WHERE m.clubID = '$current_club_id'";
                                       
                    $dropdown_result = mysqli_query($link, $dropdown_query);
                    
                    if ($dropdown_result && mysqli_num_rows($dropdown_result) > 0) {
                        while ($student = mysqli_fetch_assoc($dropdown_result)) {
                            $val = htmlspecialchars($student['userID']);
                            $name = htmlspecialchars($student['stu_name']);
                            $matric = htmlspecialchars($student['stu_ID']);
                            
                            // Check if this student's ID is in our list of already assigned users
                            $is_already_assigned = in_array($val, $assigned_users);
                            
                            echo '<tr>';
                            echo '<td style="text-align: center;">';
                            
                            // If assigned, tick the box and disable it. If not, normal checkbox.
                            if ($is_already_assigned) {
                                echo '<input type="checkbox" checked disabled>';
                            } else {
                                echo '<input type="checkbox" name="user_ids[]" value="'.$val.'">';
                            }
                            
                            echo '</td>';
                            echo '<td>' . $matric . '</td>';
                            echo '<td>' . $name . '</td>';
                            
                            // Show a small status text so it's clear why it's ticked
                            echo '<td>';
                            if ($is_already_assigned) {
                                echo '<span style="color: #10b981; font-weight: bold;">Already Assigned</span>';
                            } else {
                                echo '<span style="color: #94a3b8;">Available</span>';
                            }
                            echo '</td>';
                            
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align: center; color: #64748b;">No registered members found in this club.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <div class="form-footer">
                <button type="submit" class="btn btn-submit">Save Assignments</button>
                <a href="event_directory.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>