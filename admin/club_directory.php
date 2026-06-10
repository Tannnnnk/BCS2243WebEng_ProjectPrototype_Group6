<?php
require_once 'admin_login_materials.php';

// Search/Filter Logic
// Search/Filter Logic
$search = isset($_GET['search']) ? mysqli_real_escape_string($link, trim($_GET['search'])) : "";
$filter = isset($_GET['status']) ? mysqli_real_escape_string($link, $_GET['status']) : "Active";
$sort = $_GET['sort'] ?? 'name_asc'; // Default sort

$status_clause = ($role == 'Administrator' && $filter != 'All') ? "AND c.club_operational_status = '$filter'" : ($role != 'Administrator' ? "AND c.club_operational_status = 'Active'" : "");
// Updated search_clause to include clubID
$search_clause = $search ? "AND (c.clubID = '$search' OR c.club_name LIKE '%$search%' OR adm.admin_name LIKE '%$search%' OR stu.stu_name LIKE '%$search%')" : "";

$sort_sql = match($sort) {
    'pop_desc' => "ORDER BY member_count DESC",
    'pop_asc'  => "ORDER BY member_count ASC",
    default    => "ORDER BY c.club_name ASC"
};

// SQL Query
$sql = "
    SELECT c.*, 
           COALESCE(adm.admin_name, stu.stu_name, 'No Advisor') AS advisor_display,
           COUNT(DISTINCT m.memberID) as member_count,
           (SELECT COUNT(*) 
            FROM membership m2 
            JOIN membershiprole mr ON m2.roleID = mr.roleID 
            WHERE m2.clubID = c.clubID 
            AND mr.m_role_desc IN ('President', 'Vice President', 'Secretary', 'Treasurer')
           ) as committee_count
    FROM club c
    LEFT JOIN administrator adm ON c.userID = adm.userID
    LEFT JOIN students stu ON c.userID = stu.userID
    LEFT JOIN membership m ON c.clubID = m.clubID
    WHERE 1=1 $status_clause $search_clause
    GROUP BY c.clubID
    $sort_sql
";
$res_clubs = mysqli_query($link, $sql);
$clubs = [];
while ($r = mysqli_fetch_assoc($res_clubs)) { $clubs[] = $r; }

// Check joined clubs for students
$joined_clubs = [];
if ($role != 'Administrator') {
    $res_joined = mysqli_query($link, "SELECT clubID FROM membership WHERE userID='$userID'");
    while ($r = mysqli_fetch_assoc($res_joined)) { $joined_clubs[] = $r['clubID']; }
}

$is_admin = ($role == 'Administrator');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Club Directory - FK System</title>
    <style>

    /* Add this specifically to control the image size */
    .club-logo-img {
        width: 50px;       /* Forces the width */
        height: 50px;      /* Forces the height */
        object-fit: cover; /* Keeps it from looking squashed/stretched */
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    
    /* ... your existing styles ... */

        /* CSS FIX: Prevents the background image from covering the content */
        .main-layout { display: flex; flex: 1; position: relative; z-index: 1; }
        .content-area { 
            flex: 1; padding: 40px; overflow-y: auto; 
            background: rgba(244, 247, 246, 0.95); /* Slight opacity to see background without it being distracting */
        }
        .club-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 4px solid #10b981; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; font-size: 13px; }
        .btn-view { background: #e0e7ff; color: #3730a3; }
        .btn-join { background: #10b981; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    
    <div class="content-area">
        <div class="page-header">
            <h2>Club Directory</h2>
            <p>Search results for: <strong><?php echo $search ?: "All Clubs"; ?></strong></p>
        </div><br>

      <form method="GET" style="margin-bottom: 30px; display: flex; gap: 10px; align-items: center; background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
    
    <input type="text" name="search" placeholder="Search ID, Name, or Advisor..." 
           value="<?php echo htmlspecialchars($search); ?>" 
           style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; flex: 1;">

    <?php if ($is_admin): ?>
    <select name="status" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
        <option value="Active" <?= $filter == 'Active' ? 'selected' : '' ?>>Active</option>
        <option value="Inactive" <?= $filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        <option value="All" <?= $filter == 'All' ? 'selected' : '' ?>>All</option>
    </select>
    <?php endif; ?>

    <select name="sort" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
        <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
        <option value="pop_desc" <?= $sort == 'pop_desc' ? 'selected' : '' ?>>Most Popular</option>
        <option value="pop_asc" <?= $sort == 'pop_asc' ? 'selected' : '' ?>>Least Popular</option>
    </select>
    
    <button type="submit" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer;">Apply</button>
    <a href="?" style="padding: 10px 15px; background: #f3f4f6; color: #4b5563; text-decoration: none; border-radius: 8px;">Reset</a>
</form>
<div class="clubs-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px;">
    <?php foreach ($clubs as $club): ?>
        <div class="club-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <div style="display:flex; align-items:center; gap:12px;">
<img src="<?php echo htmlspecialchars($club['club_photo']); ?>"
     class="club-logo-img"
     onerror='this.style.display="none"'>
                    <h3 style="margin:0;"><?php echo htmlspecialchars($club['club_name']); ?></h3>
                </div>
                <span class="status-badge status-active"><?php echo $club['club_operational_status']; ?></span>
            </div>

            <p style="color:#666; font-size:14px; height:45px; overflow:hidden;"><?php echo htmlspecialchars($club['club_desc']); ?></p>

            <div style="font-size:13px; margin:15px 0;">
                Advisor: <strong><?php echo htmlspecialchars($club['advisor_display']); ?></strong>
            </div>

            <div style="display:flex; gap:20px; font-size:13px; color:#444; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:15px;">
                <span>Members: <strong><?php echo $club['member_count']; ?></strong></span>
                <span>Committee: <strong><?php echo $club['committee_count']; ?></strong></span>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <a href="club_details.php?clubID=<?php echo $club['clubID']; ?>" class="btn btn-view">View Details</a>
                <?php if (!$is_admin): ?>
                    <?php if (in_array($club['clubID'], $joined_clubs)): ?>
                        <span style="color:#059669; font-weight:bold; font-size:13px;">✓ Joined</span>
                    <?php else: ?>
                        <form method="POST" action="join_club.php">
                            <input type="hidden" name="clubID" value="<?php echo $club['clubID']; ?>">
                            <button type="submit" class="btn btn-join">Join Club</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
    </div>
    </div> </body>
</html>