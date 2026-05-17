<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connection.php';

if (!isset($_GET['clubID'])) { header("Location: manage_committees.php"); exit(); }
$target_clubID = mysqli_real_escape_string($link, $_GET['clubID']);

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];

// Fetch Admin Profile for background
$res_admin = mysqli_query($link, "SELECT admin_name, admin_photo FROM administrator WHERE userID = '$userID'");
$admin_row = mysqli_fetch_assoc($res_admin);
$admin_name = !empty($admin_row['admin_name']) ? $admin_row['admin_name'] : $username;
$photo_path = !empty($admin_row['admin_photo']) ? $admin_row['admin_photo'] : "";

// Handle Logic for Add/Remove
$msg = "";
$msg_type = "error"; // Default to error style

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $uID = mysqli_real_escape_string($link, $_POST['userID']);
        $rID = mysqli_real_escape_string($link, $_POST['roleID']);
        
        // 1. Get the description of the role being assigned
        $role_query = mysqli_query($link, "SELECT m_role_desc FROM membershiprole WHERE roleID = '$rID'");
        $role_data = mysqli_fetch_assoc($role_query);
        $role_name = $role_data['m_role_desc'];

        // 2. Check if the role is an executive position (President, VP, etc.)
        $executive_roles = ['President', 'Vice President', 'Secretary', 'Treasurer'];
        
        if (in_array($role_name, $executive_roles)) {
            // Check if this club already has someone in this role
            $check_role = mysqli_query($link, "SELECT m.memberID FROM membership m 
                                              JOIN membershiprole mr ON m.roleID = mr.roleID 
                                              WHERE m.clubID = '$target_clubID' AND mr.m_role_desc = '$role_name'");
            
            if (mysqli_num_rows($check_role) > 0) {
                $msg = "Error: This club already has a $role_name. You must remove the current one first.";
            }
        }

        // 3. Check if student is already in the club at all
        $check_membership = mysqli_query($link, "SELECT memberID FROM membership WHERE userID='$uID' AND clubID='$target_clubID'");
        
        if (empty($msg) && mysqli_num_rows($check_membership) > 0) {
            $msg = "Error: This student is already a member of this club.";
        }

        // 4. If no errors, proceed with insert
        if (empty($msg)) {
            $memID = "MEM" . rand(1000, 9999);
            $insert = mysqli_query($link, "INSERT INTO membership (memberID, userID, clubID, roleID, start_date) 
                                          VALUES ('$memID', '$uID', '$target_clubID', '$rID', CURDATE())");
            if ($insert) {
                $msg = "Member assigned successfully!";
                $msg_type = "success";
            } else {
                $msg = "Database Error: Could not assign member.";
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'remove') {
        $mID = mysqli_real_escape_string($link, $_POST['memberID']);
        mysqli_query($link, "DELETE FROM membership WHERE memberID = '$mID'");
        $msg = "Member removed.";
        $msg_type = "success";
    }
}

// Fetch Club Info
$club_res = mysqli_query($link, "SELECT club_name, club_photo FROM club WHERE clubID = '$target_clubID'");
$club_info = mysqli_fetch_assoc($club_res);

// Fetch Data for UI
$roles = mysqli_query($link, "SELECT roleID, m_role_desc FROM membershiprole WHERE m_role_desc IN ('President', 'Vice President', 'Secretary', 'Treasurer', 'Member') ORDER BY FIELD(m_role_desc, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Member')");
$students = mysqli_query($link, "SELECT u.userID, s.stu_name FROM users u JOIN students s ON u.userID = s.userID WHERE u.user_role IN ('Student','Committee') ORDER BY s.stu_name ASC");
$members = mysqli_query($link, "SELECT m.memberID, s.stu_name, s.stu_ID, mr.m_role_desc FROM membership m JOIN students s ON m.userID = s.userID JOIN membershiprole mr ON m.roleID = mr.roleID WHERE m.clubID = '$target_clubID'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Committee - <?php echo htmlspecialchars($club_info['club_name']); ?></title>
    <style>
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 5px solid #10b981; }
        .form-card { background: white; padding: 20px; border-radius: 12px; border-top: 4px solid #10b981; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn-primary { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        select { padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 12px; overflow: hidden; }
        th { text-align: left; background: #f8fafc; padding: 15px; color: #64748b; font-size: 13px; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; }
        .role-badge { background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>

    <div class="content-area">
        <a href="manage_committees.php" style="text-decoration:none; color:#10b981; font-weight:bold;">← Back to Clubs</a>

        <div class="header-section" style="display:flex; align-items:center; gap:20px; margin: 20px 0;">
            <?php 
                $img_path = "../uploads/" . ltrim($club_info['club_photo'], '/'); 
            ?>
            <img src="<?php echo $img_path; ?>" style="width:60px; height:60px; border-radius:10px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/60'">
            <h2>Manage: <?php echo htmlspecialchars($club_info['club_name']); ?></h2>
        </div>

        <?php if($msg): ?>
            <div class="alert <?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3 style="margin-bottom:15px;">Assign New Member</h3>
            <form method="POST" style="display:flex; gap:10px;">
                <input type="hidden" name="action" value="add">
                <select name="userID" required style="flex:2;">
                    <option value="">-- Select Student --</option>
                    <?php while($s = mysqli_fetch_assoc($students)) echo "<option value='{$s['userID']}'>".htmlspecialchars($s['stu_name'])."</option>"; ?>
                </select>
                <select name="roleID" required style="flex:1;">
                    <option value="">-- Role --</option>
                    <?php while($r = mysqli_fetch_assoc($roles)) echo "<option value='{$r['roleID']}'>".htmlspecialchars($r['m_role_desc'])."</option>"; ?>
                </select>
                <button type="submit" class="btn-primary">Assign</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($m = mysqli_fetch_assoc($members)): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($m['stu_name']); ?></strong><br><small><?php echo $m['stu_ID']; ?></small></td>
                    <td><span class="role-badge"><?php echo $m['m_role_desc']; ?></span></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Remove this member?');">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="memberID" value="<?php echo $m['memberID']; ?>">
                            <button type="submit" style="color:#ef4444; border:none; background:none; cursor:pointer; font-weight:bold;">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>