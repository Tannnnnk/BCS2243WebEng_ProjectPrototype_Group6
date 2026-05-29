<?php
require_once 'admin_login_materials.php';

if (!isset($_GET['clubID'])) { header("Location: manage_committees.php"); exit(); }
$target_clubID = mysqli_real_escape_string($link, $_GET['clubID']);

$msg = "";
$msg_type = "error"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- 1. PROMOTE / UPDATE COMMITTEE ROLE ---
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $uID = mysqli_real_escape_string($link, $_POST['userID']);
        $rID = mysqli_real_escape_string($link, $_POST['roleID']);
        
        $role_query = mysqli_query($link, "SELECT m_role_desc FROM membershiprole WHERE roleID = '$rID'");
        $role_data = mysqli_fetch_assoc($role_query);
        $role_name = $role_data['m_role_desc'];

        $executive_roles = ['President', 'Vice President', 'Secretary', 'Treasurer'];
        
        if (in_array($role_name, $executive_roles)) {
            $check_role = mysqli_query($link, "
                SELECT m.memberID FROM membership m 
                JOIN membershiprole mr ON m.roleID = mr.roleID 
                WHERE m.clubID = '$target_clubID' 
                AND mr.m_role_desc = '$role_name'
                AND m.userID != '$uID'
            ");
            
            if (mysqli_num_rows($check_role) > 0) {
                $msg = "Error: This club already has an active $role_name. Change their position first.";
            }
        }

        if (empty($msg)) {
            $update = mysqli_query($link, "UPDATE membership SET roleID = '$rID' WHERE userID = '$uID' AND clubID = '$target_clubID'");
            if ($update) {
                $msg = "Role updated successfully!";
                $msg_type = "success";
            } else {
                $msg = "Database Error: Could not assign role.";
            }
        }
    }

    // --- 2. DEMOTE COMMITTEE MEMBER BACK TO ORDINARY MEMBER ---
    if (isset($_POST['action']) && $_POST['action'] == 'remove') {
        $mID = mysqli_real_escape_string($link, $_POST['memberID']);
        
        $default_role_query = mysqli_query($link, "SELECT roleID FROM membershiprole WHERE m_role_desc = 'Club Members' LIMIT 1");
        $default_role = mysqli_fetch_assoc($default_role_query);
        $club_member_roleID = $default_role['roleID'] ?? '';

        if (!empty($club_member_roleID)) {
            mysqli_query($link, "UPDATE membership SET roleID = '$club_member_roleID' WHERE memberID = '$mID'");
            $msg = "Demoted to standard club member status.";
            $msg_type = "success";
        } else {
            $msg = "Error: Could not locate baseline role ID.";
        }
    }

    // --- 3. NEW: COMPLETE DELETION FROM CLUB (KICK OUT) ---
    if (isset($_POST['action']) && $_POST['action'] == 'delete_member') {
        $mID = mysqli_real_escape_string($link, $_POST['memberID']);
        
        // This physically deletes the link table row completely
        $delete = mysqli_query($link, "DELETE FROM membership WHERE memberID = '$mID'");
        if ($delete) {
            $msg = "Student successfully removed from the club roster entirely.";
            $msg_type = "success";
        } else {
            $msg = "Database Error: Complete removal failed.";
        }
    }
}

// Fetch Club Info
$club_res = mysqli_query($link, "SELECT club_name, club_photo FROM club WHERE clubID = '$target_clubID'");
$club_info = mysqli_fetch_assoc($club_res);

// Dropdown Roles (Committee roles only)
$roles = mysqli_query($link, "
    SELECT roleID, m_role_desc FROM membershiprole
    WHERE m_role_desc IN ('President', 'Vice President', 'Secretary', 'Treasurer', 'Stor Manager', 'Logistics')
    ORDER BY FIELD(m_role_desc, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Stor Manager', 'Logistics')
");

// Dropdown Students (Anyone in the club who can be adjusted)
$students = mysqli_query($link, "
    SELECT u.userID, s.stu_name, mr.m_role_desc
    FROM membership m
    JOIN users u ON m.userID = u.userID
    JOIN students s ON u.userID = s.userID
    JOIN membershiprole mr ON m.roleID = mr.roleID
    WHERE m.clubID = '$target_clubID'
    ORDER BY s.stu_name ASC
");

// Committee List Query
$committee_members = mysqli_query($link, "
    SELECT m.memberID, s.stu_name, s.stu_ID, mr.m_role_desc
    FROM membership m
    JOIN students s ON m.userID = s.userID
    JOIN membershiprole mr ON m.roleID = mr.roleID
    WHERE m.clubID = '$target_clubID'
    AND mr.m_role_desc IN ('President', 'Vice President', 'Secretary', 'Treasurer', 'Stor Manager', 'Logistics')
    ORDER BY FIELD(mr.m_role_desc, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Stor Manager', 'Logistics')
");

// General Club Members Query (Those with no leadership roles)
$general_members = mysqli_query($link, "
    SELECT m.memberID, s.stu_name, s.stu_ID, mr.m_role_desc
    FROM membership m
    JOIN students s ON m.userID = s.userID
    JOIN membershiprole mr ON m.roleID = mr.roleID
    WHERE m.clubID = '$target_clubID'
    AND mr.m_role_desc NOT IN ('President', 'Vice President', 'Secretary', 'Treasurer', 'Stor Manager', 'Logistics')
    ORDER BY s.stu_name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Members - <?php echo htmlspecialchars($club_info['club_name']); ?></title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 5px solid #10b981; }
        .form-card { background: white; padding: 20px; border-radius: 12px; border-top: 4px solid #10b981; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .btn-primary { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        select { padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 35px; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        th { text-align: left; background: #f8fafc; padding: 15px; color: #64748b; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .role-badge { background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .member-badge { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .section-heading { font-size: 18px; color: #1e293b; margin-top: 20px; margin-bottom: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>

    <div class="content-area">
        <a href="manage_committees.php" style="text-decoration:none; color:#10b981; font-weight:bold;">← Back to Clubs</a>

        <div class="header-section" style="display:flex; align-items:center; gap:20px; margin: 20px 0;">
            <?php $img_path = "../uploads/" . ltrim($club_info['club_photo'], '/'); ?>
            <img src="<?php echo $img_path; ?>" style="width:60px; height:60px; border-radius:10px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/60'">
            <h2>Roster Management: <?php echo htmlspecialchars($club_info['club_name']); ?></h2>
        </div>

        <?php if($msg): ?>
            <div class="alert <?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3 style="margin-bottom:15px; font-size: 16px;">Promote / Update Club Member Position</h3>
            <form method="POST" style="display:flex; gap:10px;">
                <input type="hidden" name="action" value="add">
                
                <select name="userID" required style="flex:2;">
                    <option value="">-- Select Registered Club Member --</option>
                    <?php mysqli_data_seek($students, 0); // reset pointer ?>
                    <?php while($s = mysqli_fetch_assoc($students)): ?>
                        <option value="<?php echo $s['userID']; ?>">
                            <?php echo htmlspecialchars($s['stu_name']) . " (" . htmlspecialchars($s['m_role_desc']) . ")"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <select name="roleID" required style="flex:1;">
                    <option value="">-- Committee Role --</option>
                    <?php while($r = mysqli_fetch_assoc($roles)): ?>
                        <option value="<?php echo $r['roleID']; ?>"><?php echo htmlspecialchars($r['m_role_desc']); ?></option>
                    <?php endwhile; ?>
                </select>
                
                <button type="submit" class="btn-primary">Apply Role Upgrade</button>
            </form>
        </div>

        <div class="section-heading">Executive Committee</div>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($committee_members) > 0): ?>
                    <?php while($m = mysqli_fetch_assoc($committee_members)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($m['stu_name']); ?></strong><br><small style="color:#64748b;"><?php echo $m['stu_ID']; ?></small></td>
                        <td><span class="role-badge"><?php echo htmlspecialchars($m['m_role_desc']); ?></span></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this member from their committee role? (They will remain a regular club member)');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="memberID" value="<?php echo $m['memberID']; ?>">
                                <button type="submit" style="color:#f59e0b; border:none; background:none; cursor:pointer; font-weight:bold;">Demote to Member</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#a0aec0; padding:20px;">No executive committee members assigned yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="section-heading">General Club Members</div>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($general_members) > 0): ?>
                    <?php while($gm = mysqli_fetch_assoc($general_members)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($gm['stu_name']); ?></strong><br><small style="color:#64748b;"><?php echo $gm['stu_ID']; ?></small></td>
                        <td><span class="member-badge"><?php echo htmlspecialchars($gm['m_role_desc']); ?></span></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('CRITICAL: Completely remove this student from the club roster? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_member">
                                <input type="hidden" name="memberID" value="<?php echo $gm['memberID']; ?>">
                                <button type="submit" style="color:#ef4444; border:none; background:none; cursor:pointer; font-weight:bold;">Remove from Club</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#a0aec0; padding:20px;">No baseline members currently registered in this club.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</body>
</html>