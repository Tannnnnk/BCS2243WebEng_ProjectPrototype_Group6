<?php
require_once 'admin_login_materials.php';

// ── Validate clubID ────────────────────────────────────────────
if (!isset($_GET['clubID'])) {
    header("Location: club_directory.php");
    exit();
}
$clubID = mysqli_real_escape_string($link, $_GET['clubID']);
 
// ── 1. Club details ────────────────────────────────────────────
$club = null;
$res = mysqli_query($link, "
    SELECT c.*, COALESCE(a.admin_name, s.stu_name, 'No Advisor') AS club_advisor_name
    FROM club c
    LEFT JOIN administrator a ON c.userID = a.userID
    LEFT JOIN students s      ON c.userID = s.userID
    WHERE c.clubID = '$clubID'
");
if ($res && $r = mysqli_fetch_assoc($res)) $club = $r;
if (!$club) { header("Location: club_directory.php"); exit(); }
 
// ── 2. Committee members ───────────────────────────────────────
$committee = [];
$res = mysqli_query($link, "
    SELECT mr.m_role_desc AS comm_position, s.stu_name, s.stu_ID
    FROM membership m
    JOIN membershiprole mr ON m.roleID = mr.roleID
    JOIN students s        ON m.userID = s.userID
    WHERE m.clubID = '$clubID'
    AND mr.m_role_desc IN ('President','Vice President','Secretary','Treasurer')
    ORDER BY FIELD(mr.m_role_desc,'President','Vice President','Secretary','Treasurer')
");
if ($res) while ($r = mysqli_fetch_assoc($res)) $committee[] = $r;
 
// ── 3. Member count ────────────────────────────────────────────
$member_count = 0;
$res = mysqli_query($link, "SELECT COUNT(*) AS cnt FROM membership WHERE clubID='$clubID'");
if ($res && $r = mysqli_fetch_assoc($res)) $member_count = $r['cnt'];
 
// ── 4. Events via committee table (ERD compliant) ──────────────
$upcoming_events = [];
$past_events = [];
$res = mysqli_query($link, "
    SELECT DISTINCT e.*
    FROM events e
    JOIN committee cm ON e.eventID = cm.eventID
    JOIN membership m ON cm.memberID = m.memberID
    WHERE m.clubID = '$clubID'
    ORDER BY e.event_date DESC
");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        if ($r['event_date'] >= date('Y-m-d')) {
            $upcoming_events[] = $r;
        } else {
            $past_events[] = $r;
        }
    }
}

 
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($club['club_name']); ?> - FK Management System</title>
    <style>

        .back-link { color: #10b981; text-decoration: none; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 20px; }

        .club-header { background: white; border-radius: 12px; padding: 35px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; border-top: 5px solid #10b981; display: flex; justify-content: space-between; align-items: flex-start; }
        .club-header-left h1 { font-size: 28px; color: #1a202c; margin-bottom: 8px; }
        .club-header-left p { color: #718096; font-size: 15px; line-height: 1.6; max-width: 600px; }
        .club-header-meta { margin-top: 15px; display: flex; gap: 25px; }
        .meta-item { font-size: 14px; color: #4a5568; }
        .meta-item strong { color: #1a202c; }
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .section-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .section-title { font-size: 16px; font-weight: bold; color: #1a202c; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f4f8; }

        .committee-list { list-style: none; }
        .committee-list li { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f4f8; font-size: 14px; }
        .committee-list li:last-child { border-bottom: none; }
        .position-tag { background-color: #e0e7ff; color: #3730a3; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }

        .event-list { list-style: none; }
        .event-list li { padding: 12px 0; border-bottom: 1px solid #f0f4f8; }
        .event-list li:last-child { border-bottom: none; }
        .event-title { font-size: 14px; font-weight: bold; color: #1a202c; }
        .event-meta { font-size: 12px; color: #718096; margin-top: 3px; }

        .empty-msg { color: #a0aec0; font-size: 14px; font-style: italic; padding: 10px 0; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    <div class="content-area">

        <a href="club_directory.php" class="back-link">← Back to Club Directory</a>

        <!-- CLUB HEADER -->
       <div class="club-header">
    <div style="display: flex; align-items: flex-start; gap: 20px;">
        
        <?php if (!empty($club['club_photo'])): ?>
            <img src="<?php echo htmlspecialchars($club['club_photo']); ?>" 
                 style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
        <?php else: ?>
            <div style="width: 100px; height: 100px; background: #e2e8f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 12px; flex-shrink: 0;">No Logo</div>
        <?php endif; ?>

        <div class="club-header-left">
            <h1 style="margin-top: 0;"><?php echo htmlspecialchars($club['club_name']); ?></h1>
            <p><?php echo htmlspecialchars($club['club_desc']); ?></p>
            <div class="club-header-meta">
                <div class="meta-item">Advisor: <strong><?php echo htmlspecialchars($club['club_advisor_name']); ?></strong></div>
                <div class="meta-item">Members: <strong><?php echo $member_count; ?></strong></div>
                <div class="meta-item">Committee: <strong><?php echo count($committee); ?></strong></div>
            </div>
        </div>
    </div>

    <span class="status-badge <?php echo $club['club_operational_status']=='Active'?'status-active':'status-inactive'; ?>">
        <?php echo htmlspecialchars($club['club_operational_status']); ?>
    </span>
</div>
        <!-- COMMITTEE + EVENTS -->
        <div class="two-col">
            <div class="section-card">
                <div class="section-title">Committee Members</div>
                <?php if (count($committee) > 0): ?>
                    <ul class="committee-list">
                        <?php foreach ($committee as $cm): ?>
                            <li>
                                <span><?php echo htmlspecialchars($cm['stu_name']); ?> <span style="color:#718096;font-size:12px;">(<?php echo htmlspecialchars($cm['stu_ID']); ?>)</span></span>
                                <span class="position-tag"><?php echo htmlspecialchars($cm['comm_position']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-msg">No committee members assigned yet.</p>
                <?php endif; ?>
            </div>

           <div class="section-card">
                <div class="section-title">Upcoming Events</div>
                <?php if ($upcoming_events): ?>
                    <ul class="event-list">
                        <?php foreach ($upcoming_events as $ev): ?>
                            <li>
                                <div class="event-title"><?= htmlspecialchars($ev['event_title']) ?></div>
                                <div class="event-meta">
                                    <?= date('d M Y', strtotime($ev['event_date'])) ?> |
                                    <?= htmlspecialchars($ev['event_venue']) ?> |
                                    Max: <?= $ev['event_max_participants'] ?> pax
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="empty-msg">No upcoming events.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-card" style="margin-top:25px;">
            <div class="section-title">Past Events</div>
            <?php if ($past_events): ?>
                <ul class="event-list">
                    <?php foreach ($past_events as $ev): ?>
                        <li>
                            <div class="event-title" style="color:#718096;">
                                <?= htmlspecialchars($ev['event_title']) ?>
                                <span style="font-size:11px;background:#f1f5f9;color:#94a3b8;padding:2px 8px;border-radius:10px;margin-left:8px;">Past</span>
                            </div>
                            <div class="event-meta">
                                <?= date('d M Y', strtotime($ev['event_date'])) ?> |
                                <?= htmlspecialchars($ev['event_venue']) ?> |
                                Max: <?= $ev['event_max_participants'] ?> pax
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="empty-msg">No past events.</p>
            <?php endif; ?>
        </div>

    </div>
</div>
</div>

</body>
</html>