<?php
require_once 'admin_login_materials.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $start_date = $_GET['start_date'] ?? '';
    $end_date   = $_GET['end_date'] ?? '';
    $action     = $_GET['action'];

    if ($start_date && $end_date) {
        if ($action === 'generate_pdf') {
            header("Location: generate_dynamic_report.php?start_date=$start_date&end_date=$end_date");
            exit();
        } 
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - FK Management System</title>
    <style>
        .analytics-card { background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 4px solid #10b981; }

        /* Main Buttons */
        .action-section { display: flex; gap: 30px; }
        .action-btn { flex: 1; background: #ffffff; border: 2px solid #e2e8f0; border-radius: 12px; padding: 40px 20px; font-size: 16px; font-weight: bold; color: #2d3748; cursor: pointer; text-align: center; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02); line-height: 1.5; }
        .action-btn:hover { border-color: #10b981; background: #f0fdf4; color: #065f46; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(16, 185, 129, 0.1); }
        .action-btn:active { transform: translateY(0); }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal-box { background: white; padding: 30px; border-radius: 12px; width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); position: relative; }
        .modal-title { margin-top: 0; margin-bottom: 20px; font-size: 18px; color: #1e293b; text-align: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; }
        .close-btn:hover { color: #ef4444; }

        /* Modal Form Inputs */
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        .form-group label { font-size: 14px; color: #4a5568; font-weight: bold; margin-bottom: 8px; }
        .form-group input[type="date"] { padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 15px; color: #2d3748; outline: none; transition: border-color 0.2s; width: 100%; box-sizing: border-box; }
        .form-group input[type="date"]:focus { border-color: #10b981; }
        
        .submit-btn { width: 100%; background: #10b981; color: white; border: none; padding: 12px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .submit-btn:hover { background: #059669; }
    </style>
</head>
<body>
    <?php include 'administrator_background.php'; ?>
    
    <div class="content-area">
        <div class="analytics-card">
            
            <div class="action-section">
                <button type="button" onclick="openDateModal('generate_pdf', 'Generate Report Details')" class="action-btn">
                    Generate report about<br>participation and points
                </button>
            
                <a href="participation_and_attendance_dashboard.php" class="action-btn" style="text-decoration:none">
                    View participation and<br>attendance dashboard
                </a>
            </div>

        </div>
    </div>

    <div id="dateModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeDateModal()">&times;</span>
            <h3 id="modalHeader" class="modal-title">Select Date Range</h3>
            
            <form action="" method="GET" id="reportForm">
                <input type="hidden" name="action" id="formAction" value="">
                
                <div class="form-group">
                    <label for="start_date">Start date</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo date('Y-m-01'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date">End date</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <button type="submit" class="submit-btn">Confirm & Continue</button>
            </form>
        </div>
    </div>

    <script>
        // Modal logic
        const modal = document.getElementById('dateModal');
        const actionInput = document.getElementById('formAction');
        const modalHeader = document.getElementById('modalHeader');

        function openDateModal(actionValue, title) {
            // Set the hidden input to either 'generate_pdf' or 'view_dashboard'
            actionInput.value = actionValue;
            // Update the title so the user knows what they are confirming
            modalHeader.innerText = title;
            // Show the modal
            modal.style.display = 'flex';
        }

        function closeDateModal() {
            modal.style.display = 'none';
        }

        // Close modal if user clicks the background overlay outside the box
        window.onclick = function(event) {
            if (event.target == modal) {
                closeDateModal();
            }
        }

        // Date validation logic
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if(endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        });
    </script>
</body>
</html>