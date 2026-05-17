<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

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
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { display: flex; flex-direction: column; height: 100vh; background-color: #f4f7f6; color: #333; }
        
        .top-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white;
            padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10;
        }
        .system-title { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; display: flex; align-items: center; gap: 12px; }
        .system-logo {
            width: 36px; height: 36px; background-color: #10b981; color: white;
            border-radius: 8px; display: flex; justify-content: center; align-items: center;
        }
        
        .user-profile-section { display: flex; align-items: center; gap: 20px; }
        .profile-group { display: flex; align-items: center; gap: 12px; }
        .top-bar-photo {
            width: 40px; height: 40px; background-color: #334155; 
            border-radius: 50%; border: 2px solid #10b981;
            display: flex; justify-content: center; align-items: center; 
            overflow: hidden; font-size: 16px; font-weight: bold; color: white;
        }
        .welcome-text { font-size: 15px; font-weight: bold; }
        .role-badge { background-color: #10b981; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; color: white;}

        .main-layout { display: flex; flex: 1; overflow: hidden; }
        .sidebar { width: 250px; background-color: white; border-right: 1px solid #e2e8f0; padding-top: 20px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-title { padding: 0 20px 10px 20px; font-size: 12px; text-transform: uppercase; color: #718096; font-weight: bold; letter-spacing: 1px; }
        
        .sidebar a { padding: 15px 20px; color: #4a5568; text-decoration: none; font-weight: bold; font-size: 14px; border-left: 4px solid transparent; transition: all 0.3s; }
        .sidebar a:hover { background-color: #f8fafc; color: #0f172a; }
        .sidebar a.active { background-color: #f1f5f9; color: #0f172a; border-left: 4px solid #0f172a; }
        .logout-btn { margin-top: auto; border-top: 1px solid #e2e8f0; color: #ef4444 !important; }

        .content-area { flex: 1; padding: 40px; overflow-y: auto; }

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

    <div class="top-bar">
        <div class="system-title">
            <div class="system-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
            </div>
            FK Student Club & Event Management - Admin Panel
        </div>

        <div class="user-profile-section">
            <div class="profile-group">
                <div class="top-bar-photo">
                    <?php if (!empty($photo_path)): ?>
                        <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="welcome-text">Admin: <?php echo htmlspecialchars($admin_name); ?></div>
            </div>
            <div class="role-badge">Administrator</div>
        </div>
    </div>

    <div class="main-layout">
        
        <div class="sidebar">
            <div class="sidebar-title">Admin Controls</div>
            <a href="administrator_dashboard.php">Dashboard Overview</a>
			<a href="admin_profile.php" class="active">My Profile</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_clubs.php">Manage Clubs</a>
            <a href="manage_events.php">Manage Events</a>
            <a href="system_reports.php">Reports & Analytics</a> 
            <a href="logout.php" class="logout-btn">LogOut</a>
        </div>

        <div class="content-area">
            
            <div class="card">
                <h2 class="card-title">Administrator Profile</h2>

                <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
                    <div class="success-msg">Profile updated successfully!</div>
                <?php endif; ?>

                <div class="profile-header">
                    <div class="profile-photo">
                        <?php if (!empty($photo_path)): ?>
                            <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover;">
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
                
                <form action="update_admin_profile.php" method="POST" enctype="multipart/form-data">
                    
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