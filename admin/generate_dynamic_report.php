<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once '../db_connection.php';
require('../fpdf.php'); 

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

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'System Analytics & Evaluation Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, 'Generated on: ' . date('d M Y, h:i A'), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SectionTitle($title, $r, $g, $b) {
        $this->Ln(8);
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($r, $g, $b);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, '  ' . $title, 0, 1, 'L', true);
        $this->Ln(2);
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- START OF NEW CODE ---

// 1. Assumed Dates (Change these to your actual session/POST variables later)
// 1. Get Dates from the URL (Fallback to today if missing)
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Defaults to 1st of the month if empty
$end_date   = $_GET['end_date'] ?? date('Y-m-d');    // Defaults to today if empty

// Make sure they are safe for the database
$start_date = mysqli_real_escape_string($link, $start_date);
$end_date   = mysqli_real_escape_string($link, $end_date);

// Print Dates at the top
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(95, 10, 'Start date: ' . date('d M Y', strtotime($start_date)), 0, 0, 'L');
$pdf->Cell(95, 10, 'End date: ' . date('d M Y', strtotime($end_date)), 0, 1, 'R');
$pdf->Ln(5);

// 2. Fetch Actual Data from Database
$participants_data = [];
$attendance_data   = [];
$student_points    = [];
$semester_points   = [];
$active_clubs      = [];

// Table 1: Number of participants per event
$q1 = mysqli_query($link, "
    SELECT e.event_title, 
           SUM(CASE WHEN a.attendanceID IS NOT NULL 
                     AND NOT EXISTS (
                         SELECT 1 FROM committee c 
                         JOIN membership m ON c.memberID = m.memberID 
                         WHERE m.userID = a.userID AND c.eventID = e.eventID
                     ) 
                THEN 1 ELSE 0 END) as total
    FROM events e
    LEFT JOIN attendance a ON e.eventID = a.eventID
    WHERE e.event_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY e.eventID
");
if ($q1) while ($row = mysqli_fetch_assoc($q1)) $participants_data[] = [$row['event_title'], $row['total']];

// Table 2: Attendance rate for each event
$q2 = mysqli_query($link, "
    SELECT e.event_title, 
           COUNT(er.userID) as total_registered,
           SUM(CASE WHEN a.attendance_status IN ('Present', 'Late') THEN 1 ELSE 0 END) as attended
    FROM events e
    LEFT JOIN eventregistration er ON e.eventID = er.eventID
    LEFT JOIN attendance a ON er.userID = a.userID AND er.eventID = a.eventID
    WHERE e.event_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY e.eventID
");
if ($q2) {
    while ($row = mysqli_fetch_assoc($q2)) {
        $rate = ($row['total_registered'] > 0) ? round(($row['attended'] / $row['total_registered']) * 100, 2) . '%' : '0%';
        $attendance_data[] = [$row['event_title'], $rate];
    }
}

// Table 3: Points accumulated per event
$q3 = mysqli_query($link, "
    SELECT e.event_title, IFNULL(SUM(p.point_value), 0) as total_points
    FROM events e
    LEFT JOIN attendance a ON e.eventID = a.eventID
    LEFT JOIN points p ON a.attendanceID = p.attendanceID
    WHERE e.event_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY e.eventID
");
if ($q3) while ($row = mysqli_fetch_assoc($q3)) $student_points[] = [$row['event_title'], $row['total_points']];

// Table 4: Points accumulated per semester
// NOTE: Assuming your events table has a 'semester' column. If not, change 'e.semester' to match your DB.
$q4 = mysqli_query($link, "
    SELECT IFNULL(SUM(p.point_value), 0) as total_points
    FROM events e
    LEFT JOIN attendance a ON e.eventID = a.eventID
    LEFT JOIN points p ON a.attendanceID = p.attendanceID
    WHERE e.event_date BETWEEN '$start_date' AND '$end_date'
");
if ($q4) while ($row = mysqli_fetch_assoc($q4)) $semester_points[] = [$row['semester'] ?? "SEM 2 2025/2026", $row['total_points']];

// Table 5: Most active clubs (Top 10)
// NOTE: Assuming your events table has a 'clubID' column linking it to the club table.
// Table 5: Most active clubs (Top 10)
$q5 = mysqli_query($link, "
    SELECT c.club_name, COUNT(DISTINCT e.eventID) as total_events
    FROM club c
    LEFT JOIN membership m ON c.clubID = m.clubID
    LEFT JOIN committee cm ON m.memberID = cm.memberID
    LEFT JOIN events e ON cm.eventID = e.eventID
    WHERE e.event_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY c.clubID
    ORDER BY total_events DESC
    LIMIT 10
");
if ($q5) while ($row = mysqli_fetch_assoc($q5)) $active_clubs[] = [$row['club_name'], $row['total_events']];
if ($q5) while ($row = mysqli_fetch_assoc($q5)) $active_clubs[] = [$row['club_name'], $row['total_events']];

// Theme color for section headers (Matches your green #10b981)
$r = 16; $g = 185; $b = 129;

// Table 1: Number of participants per event
$pdf->SectionTitle('Number of participants per event', $r, $g, $b);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(140, 8, 'Event name', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Number', 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);
foreach($participants_data as $row) {
    $pdf->Cell(140, 8, $row[0], 1, 0, 'L');
    $pdf->Cell(50, 8, $row[1], 1, 1, 'C');
}

// Table 2: Attendance rate for each event
$pdf->SectionTitle('Attendance rate for each event', $r, $g, $b);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(140, 8, 'Event name', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Rate', 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);
foreach($attendance_data as $row) {
    $pdf->Cell(140, 8, $row[0], 1, 0, 'L');
    $pdf->Cell(50, 8, $row[1], 1, 1, 'C');
}

// Table 3: Points accumulated by each student per event
$pdf->SectionTitle('Points accumulated by each student per event', $r, $g, $b);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(140, 8, 'Event name', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Points', 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);
foreach($student_points as $row) {
    $pdf->Cell(140, 8, $row[0], 1, 0, 'L');
    $pdf->Cell(50, 8, $row[1], 1, 1, 'C');
}

// Table 4: Points accumulated per overall semester
$pdf->SectionTitle('Points accumulated by each student per overall semester', $r, $g, $b);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(140, 8, 'Semester', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Points', 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);
foreach($semester_points as $row) {
    $pdf->Cell(140, 8, $row[0], 1, 0, 'L');
    $pdf->Cell(50, 8, $row[1], 1, 1, 'C');
}

// Table 5: Most active clubs (Top 10)
$pdf->SectionTitle('Most active clubs (Top 10)', $r, $g, $b);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(140, 8, 'Club name', 1, 0, 'L', true);
$pdf->Cell(50, 8, 'Number of events', 1, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);
foreach($active_clubs as $row) {
    $pdf->Cell(140, 8, $row[0], 1, 0, 'L');
    $pdf->Cell(50, 8, $row[1], 1, 1, 'C');
}

// Output the PDF to the browser
$pdf->Output('I', 'Evaluation_Report.pdf');

// --- END OF NEW CODE ---

mysqli_close($link);
?>