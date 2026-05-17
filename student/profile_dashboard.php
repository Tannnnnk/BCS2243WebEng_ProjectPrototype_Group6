<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';

$userID = $_SESSION['userID'];
$username = $_SESSION['user_username'];
$role = $_SESSION['user_role'];

$stu_name = $username; 
$stu_ID = $userID;     
$email = "";
$phone = "";
$address = "";
$photo_path = ""; 

$sql_profile = "SELECT stu_ID, stu_name, stu_email, stu_contact_no, stu_address, stu_profile_photo FROM students WHERE userID = '$userID'";
$result_profile = mysqli_query($link, $sql_profile);

if ($result_profile && mysqli_num_rows($result_profile) > 0) {
    $row = mysqli_fetch_assoc($result_profile);
    
    $stu_name = !empty($row['stu_name']) ? $row['stu_name'] : $username;
    $stu_ID = !empty($row['stu_ID']) ? $row['stu_ID'] : $userID;
    $email = !empty($row['stu_email']) ? $row['stu_email'] : "";
    $phone = !empty($row['stu_contact_no']) ? $row['stu_contact_no'] : "";
    $address = !empty($row['stu_address']) ? $row['stu_address'] : "";
    $photo_path = !empty($row['stu_profile_photo']) ? $row['stu_profile_photo'] : "";
}

$clubs = [];
$sql_clubs = "
    SELECT c.club_name, mr.m_role_desc 
    FROM membership m
    JOIN club c ON m.clubID = c.clubID
    JOIN membershiprole mr ON m.roleID = mr.roleID
    WHERE m.userID = '$userID'
";
$result_clubs = mysqli_query($link, $sql_clubs);
if ($result_clubs) {
    while ($row = mysqli_fetch_assoc($result_clubs)) {
        $clubs[] = $row;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FK Management System</title>
    <style>
        /* PROFILE CARD STYLING */
        .card { background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 800px; margin: 0 auto; }
        .card-title { font-size: 24px; color: #1a202c; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #f0f4f8; }

        .profile-header { display: flex; align-items: center; margin-bottom: 30px; }
        .profile-photo {
            width: 100px; height: 100px; background-color: #e2e8f0; border-radius: 50%;
            display: flex; justify-content: center; align-items: center; color: #718096;
            font-weight: bold; margin-right: 25px; border: 3px solid #10b981;
            overflow: hidden; 
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
        .btn-primary { background-color: #10b981; color: white; }
        .btn-primary:hover { background-color: #059669; }

        .club-list { list-style: none; margin-top: 15px; }
        .club-list li { background-color: #f8fafc; padding: 15px 20px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #10b981; color: #2d3748; font-size: 14px; font-weight: bold; display: flex; justify-content: space-between; }
        .club-list li span { color: #718096; font-weight: normal; }
        .empty-state { color: #a0aec0; font-size: 14px; font-style: italic; padding: 10px 0; }
        
        .success-msg { background-color: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10b981; }
    </style>
</head>
<body>
    <?php include 'student_background.php'; ?>
        <div class="content-area">
            <div class="card">
                <h2 class="card-title">My Profile</h2>

                <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
                    <div class="success-msg">Profile updated successfully!</div>
                <?php endif; ?>

                <div class="profile-header">
                    <div class="profile-photo">
                        <?php if (!empty($photo_path)): ?>
                            <img src="../<?php echo htmlspecialchars($photo_path); ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 36px;"><?php echo strtoupper(substr($stu_name, 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-identity">
                        <h3><?php echo htmlspecialchars($stu_name); ?></h3>
                        <p>Student ID: <?php echo htmlspecialchars($stu_ID); ?></p>
                    </div>
                </div>

                <div class="section-subtitle">Contact Information</div>
                
                <form action="update_studentprofile.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="form-row">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email address" required>
                    </div>

                    <div class="form-row">
                        <label for="phone">Contact Number</label>
                        <input type="text" id="phone" name="phoneNum" value="<?php echo htmlspecialchars($phone); ?>" placeholder="e.g. 012-3456789">
                    </div>

                    <div class="form-row">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" placeholder="Enter your full address">
                    </div>

                    <div class="form-row">
                        <label for="profile_photo">Change Photo</label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg, image/png, image/jpg">
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>

                <div class="section-subtitle" style="margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 25px;">My Club Memberships</div>
                
                <?php if (count($clubs) > 0): ?>
                    <ul class="club-list">
                        <?php foreach ($clubs as $club): ?>
                            <li>
                                <?php echo htmlspecialchars($club['club_name']); ?> 
                                <span>(<?php echo htmlspecialchars($club['m_role_desc']); ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">You are not a member of any clubs yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>