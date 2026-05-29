<?php
require_once 'admin_login_materials.php';

// 1. Initialize variables so they don't throw warnings on a fresh page load
$msg = "";
$msg_type = "";

// Upload helper function (Includes rename to ClubID)
function handleUpload($new_clubID) {
    if (!isset($_FILES['club_photo']) || $_FILES['club_photo']['error'] !== 0) return "";
    $dir = "../uploads/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $ext = strtolower(pathinfo($_FILES['club_photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return "";
    
    $safe_clubID = preg_replace('/[^a-zA-Z0-9_-]/', '', $new_clubID);
    $file = $new_clubID . "." . $ext;
    move_uploaded_file($_FILES['club_photo']['tmp_name'], $dir . $file);
    return "../uploads/" . $file;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_club') {
        $clubID  = mysqli_real_escape_string($link, $_POST['clubID'] ?? '');
        $name    = mysqli_real_escape_string($link, trim($_POST['club_name']));
        $desc    = mysqli_real_escape_string($link, trim($_POST['club_desc']));
        $status  = mysqli_real_escape_string($link, $_POST['club_operational_status']);
        $adv_id  = mysqli_real_escape_string($link, $_POST['advisor_userID']);
        
        // Call upload function
        $logo    = mysqli_real_escape_string($link, handleUpload($clubID));

        $dupName = mysqli_num_rows(mysqli_query($link, "SELECT clubID FROM club WHERE club_name='$name'"));
        $dupID   = $clubID ? mysqli_num_rows(mysqli_query($link, "SELECT clubID FROM club WHERE clubID='$clubID'")) : 0;

        if ($dupName) {
            $msg = "Error: Club name already exists!"; 
            $msg_type = "error";
        } elseif ($dupID) {
            $msg = "Error: Club ID already exists!"; 
            $msg_type = "error";
        } else {
            $idField = $clubID ? "clubID," : "";
            $idVal   = $clubID ? "'$clubID'," : "";
            $ok = mysqli_query($link,
                "INSERT INTO club ($idField club_name, club_desc, userID, club_operational_status, club_photo)
                 VALUES ($idVal '$name','$desc','$adv_id','$status','$logo')");
                 
            if ($ok) {
                // Redirect back to manage clubs with a success message
                header("Location: manage_clubs.php?msg=success");
                exit();
            } else {
                // Now this error will actually show up on your screen!
                $msg = "Database Error: " . mysqli_error($link);
                $msg_type = "error";
            }
        }
    }
}

$new_clubID = 'C001'; // Default if database is empty
$res_id = mysqli_query($link, "SELECT clubID FROM club");
$max_num = 0;
$prefix = 'C';

if ($res_id && mysqli_num_rows($res_id) > 0) {
    while ($row = mysqli_fetch_assoc($res_id)) {
        // Extract numbers and letters from existing IDs
        $num = (int) preg_replace('/[^0-9]/', '', $row['clubID']);
        $pfx = preg_replace('/[^a-zA-Z]/', '', $row['clubID']);
        
        if ($num > $max_num) {
            $max_num = $num;
            if (!empty($pfx)) $prefix = $pfx; // Keep the same letter prefix they are using
        }
    }
    // Increment the highest number found and pad with zeros (e.g., C005)
    if ($max_num > 0) {
        $new_clubID = $prefix . str_pad($max_num + 1, 3, '0', STR_PAD_LEFT);
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Club - FK Management System</title>
    <style>
        .alert{padding:15px;border-radius:8px;margin-bottom:20px;font-weight:bold;}
        .alert.error{background:#fee2e2;color:#991b1b;border-left:4px solid #ef4444;}

        .btn{padding:6px 12px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;border:none;
             text-decoration:none;display:inline-flex;align-items:center;justify-content:center;
             min-width:90px;text-align:center;transition:opacity .2s;}
        .btn:hover{opacity:.85;}
        .btn-primary{background:#10b981;color:white;padding:10px 20px;font-size:14px;}
        .btn-cancel{background:#e2e8f0;color:#4a5568;}

        .form-card{background:white;border-radius:12px;padding:30px;
                   box-shadow:0 4px 15px rgba(0,0,0,.05);margin-bottom:30px;
                   border-top:4px solid #10b981;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
        .form-group label{display:block;font-size:13px;color:#4a5568;font-weight:bold;margin-bottom:8px;}
        .form-group input,.form-group select,.form-group textarea{
            width:100%;padding:10px;border:1px solid #cbd5e0;border-radius:6px;font-size:14px;box-sizing:border-box;}
        .form-group input[readonly]{background:#f1f5f9;color:#718096;cursor:not-allowed;}
        .form-group textarea{resize:vertical;height:80px;}
        .btn-row{display:flex;gap:10px;margin-top:5px;}
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    <div class="content-area">
        
        <?php if (!empty($msg)): ?>
            <div class="alert <?php echo $msg_type; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="form-card" id="addClubForm">
            <h3 style="margin-bottom:20px;color:#065f46;">Register New Club</h3>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_club">

                <div class="form-group" style="margin-bottom:15px;">
                    <label>Club ID</label>
                    <input type="text" name="clubID" value="<?php echo htmlspecialchars($new_clubID); ?>" readonly>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Club Name</label>
                        <input type="text" name="club_name" required>
                    </div>
                    <div class="form-group">
                        <label>Club Logo</label>
                        <input type="file" name="club_photo" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label>Advisor User ID</label>
                        <input type="text" name="advisor_userID" id="advisor_userID"
                               required onblur="fetchAdvisorName(this.value)">
                    </div>
                    <div class="form-group">
                        <label>Advisor Name</label>
                        <input type="text" id="advisor_name" readonly placeholder="Auto-fills on ID entry">
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
                    <a href="manage_clubs.php" class="btn btn-cancel" style="padding:10px 20px;font-size:14px;">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    function fetchAdvisorName(userID) {
        if (!userID) {
            document.getElementById('advisor_name').value = '';
            return;
        }
        fetch('get_advisor.php?userID=' + encodeURIComponent(userID))
            .then(response => response.text())
            .then(data => { 
                document.getElementById('advisor_name').value = data; 
            })
            .catch(error => console.error('Error fetching advisor:', error));
    }
    </script>
</body>
</html>