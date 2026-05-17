<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];

// Fetch Admin Profile Info for the background include
$admin_name = $username;
$photo_path = "";
$res_admin = mysqli_query($link, "SELECT admin_name, admin_photo FROM administrator WHERE userID = '$userID'");
if ($res_admin && $row = mysqli_fetch_assoc($res_admin)) {
    $admin_name = !empty($row['admin_name']) ? $row['admin_name'] : $username;
    $photo_path = !empty($row['admin_photo']) ? $row['admin_photo'] : "";
}

// Fetch all active clubs
$res_clubs = mysqli_query($link, "SELECT clubID, club_name, club_photo FROM club WHERE club_operational_status='Active' ORDER BY club_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Committees - FK System</title>
    <style>
        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f8fafc; padding: 16px 20px; text-align: left; color: #4a5568; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { padding: 14px 20px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .btn-primary { background-color: #10b981; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 13px; }
        .position-badge { background-color: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .club-logo-img { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    
    <div class="content-area">
        <div class="header-section" style="margin-bottom: 30px;">
            <h2>Club Committee Management</h2>
            <p style="color:#718096;">Select an active club to manage its executive members and roles.</p>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Club Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($club = mysqli_fetch_assoc($res_clubs)): ?>
                    <tr>
                        <td>
                            <?php if (!empty($club['club_photo'])): ?>

    
   <img src="<?php echo htmlspecialchars($club['club_photo']); ?>" 
     class="club-logo-img"
     onerror='this.style.display="none"'>
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#e2e8f0; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:10px;">No Logo</div>
                            <?php endif; ?>
                        </td>
                        <td><strong style="font-size: 15px;"><?php echo htmlspecialchars($club['club_name']); ?></strong></td>
                        <td><span class="position-badge">Active</span></td>
                        <td>
                            <a href="comdetails.php?clubID=<?php echo $club['clubID']; ?>" class="btn-primary">Manage Members</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>