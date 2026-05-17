<?php
session_start();

// 1. Security Check: Only allow logged-in Students or Committee members
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] === 'Administrator') {
    header("Location: ../login.php");
    exit();
}

require_once '../db_connection.php'; // Looks one folder up for DB connection

$userID   = $_SESSION['userID'];
$role     = $_SESSION['user_role'];
$username = $_SESSION['user_username'];

// Get display name and photo based on actual DB columns
$display_name = $username;
$stu_name = $username;
$photo_path   = "";

$res = mysqli_query($link, "SELECT stu_name, stu_profile_photo FROM students WHERE userID='$userID'");
if ($res && $r = mysqli_fetch_assoc($res)) {
    $display_name = $r['stu_name'] ?: $username;
    $photo_path   = $r['stu_profile_photo'] ?: "";
}

// Search Logic (Students only see 'Active' clubs)
$search = isset($_GET['search']) ? mysqli_real_escape_string($link, trim($_GET['search'])) : "";
$search_clause = $search ? "AND (c.club_name LIKE '%$search%' OR adm.admin_name LIKE '%$search%' OR stu.stu_name LIKE '%$search%')" : "";

// FIXED SQL: Cleaned up subqueries to prevent duplicate count multiplication errors
$sql = "
    SELECT c.*, 
           COALESCE(adm.admin_name, stu.stu_name, 'No Advisor') AS advisor_display,
           (SELECT COUNT(DISTINCT m2.userID) FROM membership m2 WHERE m2.clubID = c.clubID) as member_count,
           (SELECT COUNT(*) 
            FROM membership m3 
            JOIN membershiprole mr ON m3.roleID = mr.roleID 
            WHERE m3.clubID = c.clubID 
            AND mr.m_role_desc IN ('President', 'Vice President', 'Secretary', 'Treasurer')
           ) as committee_count
    FROM club c
    LEFT JOIN administrator adm ON c.userID = adm.userID
    LEFT JOIN students stu ON c.userID = stu.userID
    WHERE c.club_operational_status = 'Active' $search_clause
    GROUP BY c.clubID
    ORDER BY c.club_name ASC
";

$res_clubs = mysqli_query($link, $sql);
$clubs = [];
while ($r = mysqli_fetch_assoc($res_clubs)) { $clubs[] = $r; }

// Check clubs joined by this specific student
$joined_clubs = [];
$res_joined = mysqli_query($link, "SELECT clubID FROM membership WHERE userID='$userID'");
while ($r = mysqli_fetch_assoc($res_joined)) { $joined_clubs[] = $r['clubID']; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Club Directory - Student View</title>
    <style>
        /* Main Layout structural fixes */
        .main-layout { display: flex; flex: 1; position: relative; z-index: 1; }
        .content-area { 
            flex: 1; padding: 40px; overflow-y: auto; 
            background: rgba(244, 247, 246, 0.95);
        }
        .club-grid-container { display: flex; flex-direction: column; width: 100%; }
        .club-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 4px solid #10b981; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; font-size: 13px; }
        .btn-view { background: #e0e7ff; color: #3730a3; }
        .btn-join { background: #10b981; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <?php include 'student_background.php'; ?>
    
    <div class="content-area">
        <div class="club-grid-container">
            <div class="page-header">
                <h2>Club Directory</h2>
                <p>Search results for: <strong><?php echo $search ?: "All Clubs"; ?></strong></p>
            </div>

            <form method="GET" class="filter-bar" style="margin-bottom: 30px; display: flex; gap: 10px; margin-top: 15px;">
                <input type="text" name="search" placeholder="Search club or advisor..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; width: 300px;">
                <button type="submit" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer;">Search</button>
            </form>

            <div class="clubs-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px;">
                <?php foreach ($clubs as $club): ?>
                    <div class="club-card">
                        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h3 style="margin:0;"><?php echo htmlspecialchars($club['club_name']); ?></h3>
                            <span class="status-badge status-active"><?php echo $club['club_operational_status']; ?></span>
                        </div>
                        
                        <p style="color: #666; font-size: 14px; height: 45px; overflow: hidden;"><?php echo htmlspecialchars($club['club_desc']); ?></p>
                        
                        <div style="font-size: 13px; margin: 15px 0;">
                            Advisor: <strong><?php echo htmlspecialchars($club['advisor_display']); ?></strong>
                        </div>

                        <div style="display: flex; gap: 20px; font-size: 13px; color: #444; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                            <span>Members: <strong><?php echo $club['member_count']; ?></strong></span>
                            <span>Committee: <strong><?php echo $club['committee_count']; ?></strong></span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <a href="club_details.php?clubID=<?php echo $club['clubID']; ?>" class="btn btn-view">View Details</a>
                            
                            <?php if (in_array($club['clubID'], $joined_clubs)): ?>
                                <span style="color: #059669; font-weight: bold; font-size: 13px;">✓ Joined</span>
                            <?php else: ?>
                                <form method="POST" action="join_club.php">
                                    <input type="hidden" name="clubID" value="<?php echo $club['clubID']; ?>">
                                    <button type="submit" class="btn btn-join">Join Club</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>