<?php
session_start();

// Redirect to login if not an Administrator
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

// Fetch Admin's Real Name and Photo for the Top Bar
$admin_name = $username; 
$photo_path = "";
try {
    $sql_admin = "SELECT admin_name, admin_photo FROM administrator WHERE userID = '$userID'";
    $res_admin = mysqli_query($link, $sql_admin);
    if ($res_admin && $row = mysqli_fetch_assoc($res_admin)) {
        $admin_name = !empty($row['admin_name']) ? $row['admin_name'] : $username;
        $photo_path = !empty($row['admin_photo']) ? $row['admin_photo'] : "";
    }
} catch (Exception $e) {}

// ==========================================
// HANDLE FORM SUBMISSIONS (CREATE, DELETE)
// ==========================================
$msg = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ACTION: ADD STUDENT
    if (isset($_POST['action']) && $_POST['action'] == 'add_student') {
        $user_id = mysqli_real_escape_string($link, $_POST['user_id']);
        $user_username = mysqli_real_escape_string($link, $_POST['user_username']);
        $student_id = mysqli_real_escape_string($link, $_POST['student_id']);
        $student_name = mysqli_real_escape_string($link, $_POST['student_name']);
        $student_email = mysqli_real_escape_string($link, $_POST['student_email']);
        $user_password = mysqli_real_escape_string($link, $_POST['user_password']);
        
        $check = mysqli_query($link, "SELECT * FROM users WHERE userID = '$user_id'");
        if (mysqli_num_rows($check) > 0) {
            $msg = "Error: User ID already exists!";
            $msg_type = "error";
        } else {
            $sql_u = "INSERT INTO users (userID, user_username, user_password, user_role) VALUES ('$user_id', '$user_username', '$user_password', 'Student')";
            if (mysqli_query($link, $sql_u)) {
                mysqli_query($link, "INSERT INTO students (userID, stu_ID, stu_name, stu_email) VALUES ('$user_id', '$student_id', '$student_name', '$student_email')");
                $msg = "Success: Student registered successfully!";
                $msg_type = "success";
            } else {
                $msg = "Error: Could not create student account.";
                $msg_type = "error";
            }
        }
    }
    
    // ACTION: ADD ADMINISTRATOR
    if (isset($_POST['action']) && $_POST['action'] == 'add_admin') {
        $user_id = mysqli_real_escape_string($link, $_POST['user_id']);
        $user_username = mysqli_real_escape_string($link, $_POST['user_username']);
        $admin_fullname = mysqli_real_escape_string($link, $_POST['admin_fullname']);
        $user_password = mysqli_real_escape_string($link, $_POST['user_password']);
        
        $check = mysqli_query($link, "SELECT * FROM users WHERE userID = '$user_id'");
        if (mysqli_num_rows($check) > 0) {
            $msg = "Error: User ID already exists!";
            $msg_type = "error";
        } else {
            $sql_u = "INSERT INTO users (userID, user_username, user_password, user_role) VALUES ('$user_id', '$user_username', '$user_password', 'Administrator')";
            if (mysqli_query($link, $sql_u)) {
                mysqli_query($link, "INSERT INTO administrator (userID, admin_name) VALUES ('$user_id', '$admin_fullname')");
                $msg = "Success: Administrator registered successfully!";
                $msg_type = "success";
            } else {
                $msg = "Error: Could not create administrator account.";
                $msg_type = "error";
            }
        }
    }
    
    // ACTION: DELETE USER
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $del_id = mysqli_real_escape_string($link, $_POST['del_id']);
        $del_role = mysqli_real_escape_string($link, $_POST['del_role']);
        
        if ($del_id == $userID) {
            $msg = "Error: You cannot delete your own logged-in account!";
            $msg_type = "error";
        } else {
            if ($del_role == 'Student') {
                mysqli_query($link, "DELETE FROM students WHERE userID = '$del_id'");
            } else if ($del_role == 'Administrator') {
                mysqli_query($link, "DELETE FROM administrator WHERE userID = '$del_id'");
            }
            
            if (mysqli_query($link, "DELETE FROM users WHERE userID = '$del_id'")) {
                $msg = "Success: Account securely removed from database.";
                $msg_type = "success";
            } else {
                $msg = "Error: Could not complete deletion cascade anomalies.";
                $msg_type = "error";
            }
        }
    }
}

$student_users = [];
$admin_users = [];

// Fetch Students
$res_students = mysqli_query($link, "SELECT u.userID, u.user_username, s.stu_ID, s.stu_name, s.stu_email FROM users u JOIN students s ON u.userID = s.userID WHERE u.user_role = 'Student' ORDER BY u.userID ASC");
if ($res_students) { while ($row = mysqli_fetch_assoc($res_students)) { $student_users[] = $row; } }

// Fetch Admins
$res_admins = mysqli_query($link, "SELECT u.userID, u.user_username, a.admin_name FROM users u JOIN administrator a ON u.userID = a.userID WHERE u.user_role = 'Administrator' ORDER BY u.userID ASC");
if ($res_admins) { while ($row = mysqli_fetch_assoc($res_admins)) { $admin_users[] = $row; } }

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - FK Management System</title>
    <style>
        /* CONTENT AREA */
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; margin-top: 20px; }
        .header-section:first-of-type { margin-top: 0; }
        .header-section h2 { font-size: 24px; color: #1a202c; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background-color: #10b981; color: white; }
        .btn-primary:hover { background-color: #059669; }
        .btn-admin { background-color: #0f172a; color: white; }
        .btn-admin:hover { background-color: #1e293b; }
        .btn-danger { background-color: #ef4444; color: white; padding: 6px 12px; font-size: 12px; }
        .btn-danger:hover { background-color: #dc2626; }

        /* ALERT MESSAGES */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert.success { background-color: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

        /* FORMS LAYOUT */
        .form-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; display: none; }
        .form-card.student-border { border-top: 4px solid #10b981; }
        .form-card.admin-border { border-top: 4px solid #0f172a; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; color: #4a5568; font-weight: bold; margin-bottom: 8px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; background-color: #fff; }
        .form-group input:disabled { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #10b981; }

        /* DATA TABLE */
        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; color: #4a5568; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; text-align: left; padding: 16px 20px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 20px; border-bottom: 1px solid #e2e8f0; color: #1a202c; font-size: 14px; vertical-align: middle; }
        tr:hover { background-color: #f1f5f9; }
        
        .role-tag { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .role-tag.student { background-color: #dcfce7; color: #065f46; }
        .role-tag.admin { background-color: #e0e7ff; color: #3730a3; }
        
        .section-divider { border: 0; height: 1px; background: #cbd5e1; margin: 30px 0; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
        <div class="content-area">
            <?php if (!empty($msg)): ?>
                <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="header-section">
                <div>
                    <h2>Manage Students Accounts</h2>
                    <p style="color: #718096; margin-top: 3px;">Register new students, review records, and control application system availability.</p>
                </div>
                <button class="btn btn-primary" onclick="toggleForm('addStudentForm')">+ Add New Student</button>
            </div>

            <div class="form-card student-border" id="addStudentForm">
                <h3 style="margin-bottom: 20px; color: #065f46;">Register Student Profile</h3>
                <form action="manage_users.php" method="POST">
                    <input type="hidden" name="action" value="add_student">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>User ID</label>
                            <input type="text" name="user_id" required placeholder="e.g. U103">
                        </div>
                        <div class="form-group">
                            <label>User Username</label>
                            <input type="text" name="user_username" required placeholder="Account System Username">
                        </div>
                        <div class="form-group">
                            <label>Student ID (Matric No.)</label>
                            <input type="text" name="student_id" required placeholder="e.g. CB24001">
                        </div>
                        <div class="form-group">
                            <label>Student Full Name</label>
                            <input type="text" name="student_name" required placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label>Student Email Address</label>
                            <input type="email" name="student_email" required placeholder="Contact email">
                        </div>
                        <div class="form-group">
                            <label>User Role</label>
                            <input type="text" value="Student" disabled>
                            <input type="hidden" name="new_role" value="Student">
                        </div>
                        <div class="form-group">
                            <label>Temporary Password</label>
                            <input type="password" name="user_password" required placeholder="">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Create Student</button>
                        <button type="button" class="btn" style="background: #e2e8f0; color: #4a5568;" onclick="toggleForm('addStudentForm')">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($student_users) > 0): ?>
                            <?php foreach ($student_users as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['userID']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['user_username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['stu_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($student['stu_name']); ?></td>
                                    <td style="color: #718096;"><?php echo htmlspecialchars($student['stu_email']); ?></td>
                                    <td><span class="role-tag student">Student</span></td>
                                    <td style="text-align: right;">
                                        <form action="manage_users.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student profile? Permanent erasure cascade will occur.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="del_role" value="Student">
                                            <input type="hidden" name="del_id" value="<?php echo htmlspecialchars($student['userID']); ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; padding: 30px; color: #718096;">No registered student records mapped.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <hr class="section-divider">

            <div class="header-section">
                <div>
                    <h2>Manage System Administrators</h2>
                    <p style="color: #718096; margin-top: 3px;">Register institutional management staff, track system structural engineers, and update privilege hierarchies.</p>
                </div>
                <button class="btn btn-admin" onclick="toggleForm('addAdminForm')">+ Add New Admin</button>
            </div>

            <div class="form-card admin-border" id="addAdminForm">
                <h3 style="margin-bottom: 20px; color: #1e293b;">Register Administrator Profile</h3>
                <form action="manage_users.php" method="POST">
                    <input type="hidden" name="action" value="add_admin">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>User ID</label>
                            <input type="text" name="user_id" required placeholder="e.g. U202">
                        </div>
                        <div class="form-group">
                            <label>User Username</label>
                            <input type="text" name="user_username" required placeholder="Account System Username">
                        </div>
                        <div class="form-group">
                            <label>Admin Full Name</label>
                            <input type="text" name="admin_fullname" required placeholder="Full Name">
                        </div>
                        <div class="form-group">
                            <label>User Role</label>
                            <input type="text" value="Administrator" disabled>
                        </div>
                        <div class="form-group">
                            <label>Temporary Password</label>
                            <input type="password" name="user_password" required placeholder="">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-admin">Create Administrator</button>
                        <button type="button" class="btn" style="background: #e2e8f0; color: #4a5568;" onclick="toggleForm('addAdminForm')">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Admin ID</th>
                            <th>Username</th>
                            <th>Admin Full Name</th>
                            <th>Role</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($admin_users) > 0): ?>
                            <?php foreach ($admin_users as $admin): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($admin['userID']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($admin['user_username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['admin_name']); ?></td>
                                    <td><span class="role-tag admin">Administrator</span></td>
                                    <td style="text-align: right;">
                                        <form action="manage_users.php" method="POST" style="display:inline;" onsubmit="return confirm('CRITICAL ACCOUNT DELETION PROMPT: Are you completely certain you want to delete this administrator entity profile? Access permissions will be terminated.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="del_role" value="Administrator">
                                            <input type="hidden" name="del_id" value="<?php echo htmlspecialchars($admin['userID']); ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px; color: #718096;">No distinct non-self administrators parsed.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleForm(formId) {
            var targetForm = document.getElementById(formId);
            
            // Hide the other form to keep UI neat
            var alternateFormId = (formId === 'addStudentForm') ? 'addAdminForm' : 'addStudentForm';
            var alternateForm = document.getElementById(alternateFormId);
            if (alternateForm) alternateForm.style.display = "none";
            
            // Toggle clicked form
            if (targetForm.style.display === "block") {
                targetForm.style.display = "none";
            } else {
                targetForm.style.display = "block";
            }
        }
    </script>
</body>
</html>