<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID   = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$msg      = "";
$msg_type = "";

// ── Admin profile ──────────────────────────────────────────────
$admin_name = $username;
$photo_path = "";
$r = mysqli_query($link, "SELECT admin_name, admin_photo FROM administrator WHERE userID='$userID'");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $admin_name = $row['admin_name'] ?: $username;
    $photo_path = $row['admin_photo'] ?: "";
}

// ── Logo upload helper ─────────────────────────────────────────
function handleUpload() {
    if (!isset($_FILES['club_photo']) || $_FILES['club_photo']['error'] !== 0) return "";
    $dir = "../uploads/";          // physical: go up one level
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $ext = strtolower(pathinfo($_FILES['club_photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return "";
    $file = time() . "_" . basename($_FILES['club_photo']['name']);
    move_uploaded_file($_FILES['club_photo']['tmp_name'], $dir . $file);
    return "../uploads/" . $file;  // save THIS path to DB
}

// ── Form handling ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_club') {
        $clubID  = mysqli_real_escape_string($link, $_POST['clubID'] ?? '');
        $name    = mysqli_real_escape_string($link, trim($_POST['club_name']));
        $desc    = mysqli_real_escape_string($link, trim($_POST['club_desc']));
        $status  = mysqli_real_escape_string($link, $_POST['club_operational_status']);
        $adv_id  = mysqli_real_escape_string($link, $_POST['advisor_userID']);
        $logo    = mysqli_real_escape_string($link, handleUpload());

        $dupName = mysqli_num_rows(mysqli_query($link, "SELECT clubID FROM club WHERE club_name='$name'"));
        $dupID   = $clubID ? mysqli_num_rows(mysqli_query($link, "SELECT clubID FROM club WHERE clubID='$clubID'")) : 0;

        if ($dupName) {
            $msg = "Error: Club name already exists!"; $msg_type = "error";
        } elseif ($dupID) {
            $msg = "Error: Club ID already exists!"; $msg_type = "error";
        } else {
            $idField = $clubID ? "clubID," : "";
            $idVal   = $clubID ? "'$clubID'," : "";
            $ok = mysqli_query($link,
                "INSERT INTO club ($idField club_name, club_desc, userID, club_operational_status, club_photo)
                 VALUES ($idVal '$name','$desc','$adv_id','$status','$logo')");
            $msg = $ok ? "Club created successfully!" : "Error: " . mysqli_error($link);
            $msg_type = $ok ? "success" : "error";
        }
    }

    if ($action === 'edit_club') {
        $clubID  = mysqli_real_escape_string($link, $_POST['clubID']);
        $name    = mysqli_real_escape_string($link, trim($_POST['club_name']));
        $desc    = mysqli_real_escape_string($link, trim($_POST['club_desc']));
        $status  = mysqli_real_escape_string($link, $_POST['club_operational_status']);
        $adv_id  = mysqli_real_escape_string($link, $_POST['advisor_userID']);
        $newLogo = handleUpload();
        $logoSQL = $newLogo ? ", club_photo='" . mysqli_real_escape_string($link, $newLogo) . "'" : "";

        $ok = mysqli_query($link,
            "UPDATE club SET club_name='$name', club_desc='$desc',
             userID='$adv_id', club_operational_status='$status' $logoSQL
             WHERE clubID='$clubID'");
        $msg = $ok ? "Club updated successfully!" : "Error: " . mysqli_error($link);
        $msg_type = $ok ? "success" : "error";
    }

    if ($action === 'delete_club') {
        $clubID = mysqli_real_escape_string($link, $_POST['clubID']);
        $hasMem = mysqli_num_rows(mysqli_query($link, "SELECT memberID FROM membership WHERE clubID='$clubID' LIMIT 1"));
        if ($hasMem) {
            $msg = "Error: Cannot delete club with active members."; $msg_type = "error";
        } else {
            mysqli_query($link, "DELETE FROM club WHERE clubID='$clubID'");
            $msg = "Club deleted!"; $msg_type = "success";
        }
    }

    if ($action === 'toggle_status') {
        $clubID     = mysqli_real_escape_string($link, $_POST['clubID']);
        $new_status = ($_POST['current_status'] === 'Active') ? 'Inactive' : 'Active';
        mysqli_query($link, "UPDATE club SET club_operational_status='$new_status' WHERE clubID='$clubID'");
        $msg = "Club status updated to $new_status!"; $msg_type = "success";
    }
}

// ── Fetch clubs ────────────────────────────────────────────────
$clubs = [];
$res = mysqli_query($link, "
    SELECT c.*, a.admin_name,
           COUNT(DISTINCT m.memberID) AS member_count,
           GROUP_CONCAT(DISTINCT s.stu_name SEPARATOR ', ') AS committee_names
    FROM club c
    LEFT JOIN administrator a ON c.userID = a.userID
    LEFT JOIN membership m    ON c.clubID = m.clubID
    LEFT JOIN students s      ON m.userID = s.userID
    GROUP BY c.clubID
");
if ($res) while ($row = mysqli_fetch_assoc($res)) $clubs[] = $row;

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clubs - FK Management System</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif;}
        body{display:flex;flex-direction:column;height:100vh;background:#f4f7f6;color:#333;}

        .top-bar{background:linear-gradient(135deg,#0f172a,#1e293b);color:white;padding:15px 30px;
                 display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,.1);z-index:10;}
        .system-title{font-size:22px;font-weight:bold;display:flex;align-items:center;gap:12px;}
        .user-profile-section{display:flex;align-items:center;gap:20px;}
        .profile-group{display:flex;align-items:center;gap:12px;}
        .top-bar-photo{width:40px;height:40px;background:#334155;border-radius:50%;border:2px solid #10b981;
                       display:flex;justify-content:center;align-items:center;overflow:hidden;}
        .role-badge{background:#10b981;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:bold;}

        .main-layout{display:flex;flex:1;overflow:hidden;}
        .sidebar{width:250px;background:white;border-right:1px solid #e2e8f0;padding-top:20px;
                 display:flex;flex-direction:column;flex-shrink:0;}
        .sidebar-title{padding:0 20px 10px;font-size:12px;text-transform:uppercase;color:#718096;font-weight:bold;letter-spacing:1px;}
        .sidebar a{padding:15px 20px;color:#4a5568;text-decoration:none;font-weight:bold;font-size:14px;
                   border-left:4px solid transparent;transition:.3s;display:block;}
        .sidebar a:hover{background:#f8fafc;color:#0f172a;}
        .sidebar a.active{background:#f1f5f9;color:#0f172a;border-left-color:#0f172a;}
        .logout-btn{margin-top:auto;border-top:1px solid #e2e8f0;color:#ef4444!important;}
        .content-area{flex:1;padding:40px;overflow-y:auto;}

        .alert{padding:15px;border-radius:8px;margin-bottom:20px;font-weight:bold;}
        .alert.success{background:#d1fae5;color:#065f46;border-left:4px solid #10b981;}
        .alert.error{background:#fee2e2;color:#991b1b;border-left:4px solid #ef4444;}

        .header-section{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;}

        /* unified button styles — no conflicts */
        .btn{padding:6px 12px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;border:none;
             text-decoration:none;display:inline-flex;align-items:center;justify-content:center;
             min-width:90px;text-align:center;transition:opacity .2s;}
        .btn:hover{opacity:.85;}
        .btn-primary{background:#10b981;color:white;padding:10px 20px;font-size:14px;}
        .btn-edit{background:#3b82f6;color:white;}
        .btn-activate{background:#10b981;color:white;}
        .btn-deactivate{background:#64748b;color:white;}
        .btn-delete{background:#ef4444;color:white;}
        .btn-cancel{background:#e2e8f0;color:#4a5568;}

        .form-card{background:white;border-radius:12px;padding:30px;
                   box-shadow:0 4px 15px rgba(0,0,0,.05);margin-bottom:30px;
                   display:none;border-top:4px solid #10b981;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
        .form-group label{display:block;font-size:13px;color:#4a5568;font-weight:bold;margin-bottom:8px;}
        .form-group input,.form-group select,.form-group textarea{
            width:100%;padding:10px;border:1px solid #cbd5e0;border-radius:6px;font-size:14px;}
        .form-group input[readonly]{background:#f1f5f9;color:#718096;cursor:not-allowed;}
        .form-group textarea{resize:vertical;height:80px;}
        .btn-row{display:flex;gap:10px;margin-top:5px;}

        .table-card{background:white;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,.03);overflow:hidden;}
        table{width:100%;border-collapse:collapse;}
        th{background:#f8fafc;padding:16px 20px;text-align:left;font-size:13px;
           text-transform:uppercase;letter-spacing:1px;border-bottom:2px solid #e2e8f0;}
        td{padding:12px 20px;border-bottom:1px solid #e2e8f0;font-size:14px;vertical-align:middle;}
        tr:hover{background:#f8fafc;}
        .status-badge{padding:4px 12px;border-radius:20px;font-size:12px;font-weight:bold;}
        .status-active{background:#d1fae5;color:#065f46;}
        .status-inactive{background:#fee2e2;color:#991b1b;}
        .action-cell{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
        .inline-form{display:inline;margin:0;}

        .modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;
                       background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center;}
        .modal-overlay.active{display:flex;}
        .modal{background:white;border-radius:12px;padding:30px;width:600px;
               max-height:90vh;overflow-y:auto;border-top:4px solid #3b82f6;}
        .modal h3{margin-bottom:20px;}
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    <div class="content-area">

        <?php if ($msg): ?>
            <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="header-section">
            <h2>Manage Clubs</h2>
            <button class="btn btn-primary" onclick="toggleForm('addClubForm')">+ Add New Club</button>
        </div>

        <!-- ══ ADD FORM ══ -->
        <div class="form-card" id="addClubForm">
            <h3 style="margin-bottom:20px;color:#065f46;">Register New Club</h3>
            <form action="manage_clubs.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_club">

                <div class="form-group" style="margin-bottom:15px;">
                    <label>Club ID</label>
                    <input type="text" name="clubID" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Club Name</label>
                        <input type="text" name="club_name" required>
                    </div>
                    <div class="form-group">
                        <label>Club Logo</label>
                        <input type="file" name="club_photo" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Advisor User ID</label>
                        <input type="text" name="advisor_userID" id="advisor_userID"
                               required onblur="fetchAdvisorName(this.value)">
                    </div>
                    <div class="form-group">
                        <label>Advisor Name</label>
                        <input type="text" id="advisor_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="club_operational_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Description</label>
                        <textarea name="club_desc" required></textarea>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn btn-primary">Create Club</button>
                    <button type="button" class="btn btn-cancel" style="padding:10px 20px;font-size:14px;"
                            onclick="toggleForm('addClubForm')">Cancel</button>
                </div>
            </form>
        </div>

        <!-- ══ TABLE ══ -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Logo</th><th>Name</th><th>Advisor</th>
                        <th>Committee Members</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clubs as $club): ?>
                <tr>
                    <td><?= $club['clubID'] ?></td>
                    <td>
                        <?php
                        // club_photo is stored as "uploads/filename"
                        // this file is inside admin/ so we need "../uploads/filename"
$logo_src = !empty($club['club_photo']) ? htmlspecialchars($club['club_photo']) : '../uploads/default.png';
                        ?>
                        <img src="<?= $logo_src ?>"
                             style="width:40px;height:40px;object-fit:cover;border-radius:4px;"
                             onerror="this.src='../uploads/default.png'">
                    </td>
                    <td><?= htmlspecialchars($club['club_name']) ?></td>
                    <td><?= htmlspecialchars($club['admin_name'] ?? '—') ?></td>
                    <td><small><?= htmlspecialchars($club['committee_names'] ?? 'None') ?></small></td>
                    <td>
                        <span class="status-badge <?= $club['club_operational_status']==='Active' ? 'status-active':'status-inactive' ?>">
                            <?= $club['club_operational_status'] ?>
                        </span>
                    </td>
                    <td class="action-cell">

                        <!-- EDIT -->
                        <button type="button" class="btn btn-edit" onclick="openEditModal(
                            '<?= $club['clubID'] ?>',
                            '<?= addslashes($club['club_name']) ?>',
                            '<?= addslashes($club['club_desc']) ?>',
                            '<?= $club['club_operational_status'] ?>',
                            '<?= addslashes($club['committee_names'] ?? 'None') ?>',
                            '<?= addslashes($club['userID'] ?? '') ?>'
                        )">Edit</button>

                        <!-- ACTIVATE / DEACTIVATE -->
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="clubID" value="<?= $club['clubID'] ?>">
                            <input type="hidden" name="current_status" value="<?= $club['club_operational_status'] ?>">
                            <button type="submit" class="btn <?= $club['club_operational_status']==='Active' ? 'btn-deactivate':'btn-activate' ?>">
                                <?= $club['club_operational_status']==='Active' ? 'Deactivate':'Activate' ?>
                            </button>
                        </form>

                        <!-- DELETE -->
                        <form method="POST" class="inline-form"
                              onsubmit="return confirm('Delete this club permanently?')">
                            <input type="hidden" name="action" value="delete_club">
                            <input type="hidden" name="clubID" value="<?= $club['clubID'] ?>">
                            <button type="submit" class="btn btn-delete">Delete</button>
                        </form>

                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- ══ EDIT MODAL ══ -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3>Edit Club</h3>
        <form action="manage_clubs.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_club">
            <input type="hidden" name="clubID" id="edit_clubID">

            <div class="form-grid">
                <div class="form-group">
                    <label>Club Name</label>
                    <input type="text" name="club_name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Change Logo</label>
                    <input type="file" name="club_photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Advisor User ID</label>
                    <input type="text" name="advisor_userID" id="edit_advisor_userID"
                           required onblur="fetchAdvisorNameEdit(this.value)">
                </div>
                <div class="form-group">
                    <label>Advisor Name</label>
                    <input type="text" id="edit_advisor_name" readonly>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="club_operational_status" id="edit_status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Description</label>
                    <textarea name="club_desc" id="edit_desc" required></textarea>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Committee Members</label>
                    <div style="display:flex;justify-content:space-between;align-items:center;
                                gap:10px;padding:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
                        <div id="committeeDisplay" style="flex:1;font-size:14px;"></div>
                        <a id="assignBtn" href="#" class="btn btn-activate"
                           style="min-width:auto;white-space:nowrap;">Edit Committee</a>
                    </div>
                </div>
            </div>

            <div class="btn-row" style="margin-top:10px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-cancel"
                        style="padding:10px 20px;font-size:14px;"
                        onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleForm(id) {
    var f = document.getElementById(id);
    f.style.display = f.style.display === 'block' ? 'none' : 'block';
}

function openEditModal(id, name, desc, status, committee, advID) {
    document.getElementById('edit_clubID').value          = id;
    document.getElementById('edit_name').value            = name;
    document.getElementById('edit_desc').value            = desc;
    document.getElementById('edit_status').value          = status;
    document.getElementById('edit_advisor_userID').value  = advID;
    document.getElementById('committeeDisplay').innerHTML = committee;
    document.getElementById('assignBtn').href = 'manage_committees.php?clubID=' + id;
    document.getElementById('editModal').classList.add('active');

    // auto-fill advisor name on open
    if (advID) fetchAdvisorNameEdit(advID);
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

function fetchAdvisorName(userID) {
    if (!userID) return;
    fetch('get_advisor.php?userID=' + userID)
        .then(r => r.text())
        .then(data => { document.getElementById('advisor_name').value = data; });
}

function fetchAdvisorNameEdit(userID) {
    if (!userID) return;
    fetch('get_advisor.php?userID=' + userID)
        .then(r => r.text())
        .then(data => { document.getElementById('edit_advisor_name').value = data; });
}
</script>
</body>
</html>