<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

$admin_name = $username; 
$department = "";
$position = "";
$photo_path = "";

try {
    $sql_profile = "SELECT admin_name, admin_department, admin_position, admin_photo FROM administrator WHERE userID = '$userID'";
    $result_profile = mysqli_query($link, $sql_profile);
    
    if ($result_profile && mysqli_num_rows($result_profile) > 0) {
        $row = mysqli_fetch_assoc($result_profile);
        
        $admin_name = !empty($row['admin_name']) ? $row['admin_name'] : $username;
        $department = !empty($row['admin_department']) ? $row['admin_department'] : "";
        $position = !empty($row['admin_position']) ? $row['admin_position'] : "";
        $photo_path = !empty($row['admin_photo']) ? $row['admin_photo'] : "";
    }
} catch (Exception $e) {}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - FK Management System</title>
    <style>
        .card { background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); max-width: 800px; margin: 0 auto; border-left: 5px solid #0f172a; }
        .card-title { font-size: 24px; color: #1a202c; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #f0f4f8; }

        .profile-header { display: flex; align-items: center; margin-bottom: 30px; }
        .profile-photo {
            width: 100px; height: 100px; background-color: #f1f5f9; border-radius: 50%;
            display: flex; justify-content: center; align-items: center; color: #0f172a;
            font-weight: bold; margin-right: 25px; border: 3px solid #10b981;
            overflow: hidden; font-size: 40px;
        }
        .profile-identity h3 { font-size: 20px; margin-bottom: 5px; color: #2d3748; }
        .profile-identity p { font-size: 14px; color: #718096; font-weight: bold; }

        .section-subtitle { font-size: 16px; font-weight: bold; color: #1a202c; margin-bottom: 15px; margin-top: 30px; }
        .form-row { display: grid; grid-template-columns: 150px 1fr; align-items: center; margin-bottom: 15px; }
        .form-row label { font-size: 14px; color: #4a5568; font-weight: bold; }
        .form-row input, .form-row input[type="file"] { width: 100%; padding: 10px 15px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; color: #2d3748; outline: none; transition: border-color 0.3s; }
        .form-row input:focus { border-color: #10b981; }

        .button-group { display: flex; gap: 15px; margin-top: 25px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: background-color 0.3s; }
        .btn-primary { background-color: #0f172a; color: white; }
        .btn-primary:hover { background-color: #1e293b; }

        .success-msg { background-color: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10b981; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
        <div class="content-area">
            <div class="card">
                <h2 class="card-title">Administrator Profile</h2>
                <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
                    <div class="success-msg">Profile updated successfully!</div>
                <?php endif; ?>

                <div class="profile-header">
                    <div class="profile-photo">
                        <?php if (!empty($photo_path)): ?>
                            <img src="../<?php echo htmlspecialchars($photo_path); ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-identity">
                        <h3><?php echo htmlspecialchars($admin_name); ?></h3>
                        <p>Admin ID: <?php echo htmlspecialchars($userID); ?></p>
                    </div>
                </div>

                <div class="section-subtitle">Official Details</div>
                
                <form action="update_adminprofile.php" method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <label for="admin_name">Full Name</label>
                        <input type="text" id="admin_name" name="admin_name" value="<?php echo htmlspecialchars($admin_name); ?>" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-row">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($department); ?>" placeholder="e.g. Faculty of Computing">
                    </div>

                    <div class="form-row">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($position); ?>" placeholder="e.g. System Administrator">
                    </div>

                    <div class="form-row">
                        <label for="profile_photo">Change Photo</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg, image/png, image/jpg">
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>