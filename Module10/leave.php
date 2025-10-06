<?php
require '../db.php';
require '../shared/config.php';

// Handle actions
 $action = $_GET['action'] ?? '';
 $id = $_GET['id'] ?? null;

// Process leave application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $employeeId = $_POST['employee_id'];
    $leaveTypeId = $_POST['leave_type_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $reason = $_POST['reason'];
    
    try {
        // Calculate total days
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $totalDays = $interval->days + 1; // Include both start and end dates
        
        // Check leave balance
        $currentYear = date('Y');
        $stmt = $pdo->prepare("
            SELECT remaining_days FROM hr_leave_balances 
            WHERE employee_id = ? AND leave_type_id = ? AND year = ?
        ");
        $stmt->execute([$employeeId, $leaveTypeId, $currentYear]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$balance || $balance['remaining_days'] < $totalDays) {
            throw new Exception("Insufficient leave balance. Available: " . ($balance ? $balance['remaining_days'] : 0) . " days, Requested: " . $totalDays . " days");
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Insert leave application
            $stmt = $pdo->prepare("
                INSERT INTO hr_leave_applications 
                (employee_id, leave_type_id, start_date, end_date, total_days, reason, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$employeeId, $leaveTypeId, $startDate, $endDate, $totalDays, $reason]);
            
            // Update leave balance (reserve the days)
            $stmt = $pdo->prepare("
                UPDATE hr_leave_balances 
                SET remaining_days = remaining_days - ? 
                WHERE employee_id = ? AND leave_type_id = ? AND year = ?
            ");
            $stmt->execute([$totalDays, $employeeId, $leaveTypeId, $currentYear]);
            
            // Commit transaction
            $pdo->commit();
            
            header("Location: leave.php?status=applied");
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = "Error applying for leave: " . $e->getMessage();
    }
}

// Approve leave application
if ($action === 'approve' && $id) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Get leave application details
            $stmt = $pdo->prepare("SELECT * FROM hr_leave_applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$application) {
                throw new Exception("Leave application not found");
            }
            
            // Update application status
            $stmt = $pdo->prepare("
                UPDATE hr_leave_applications 
                SET status = 'approved', approved_by = ?, approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([1, $id]); // Using 1 as default approved_by
            
            // Update used days in leave balance
            $stmt = $pdo->prepare("
                UPDATE hr_leave_balances 
                SET used_days = used_days + ? 
                WHERE employee_id = ? AND leave_type_id = ? AND year = ?
            ");
            $stmt->execute([
                $application['total_days'], 
                $application['employee_id'], 
                $application['leave_type_id'], 
                date('Y', strtotime($application['start_date']))
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            header("Location: leave.php?status=approved");
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = "Error approving leave: " . $e->getMessage();
    }
}

// Reject leave application
if ($action === 'reject' && $id) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Get leave application details
            $stmt = $pdo->prepare("SELECT * FROM hr_leave_applications WHERE id = ?");
            $stmt->execute([$id]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$application) {
                throw new Exception("Leave application not found");
            }
            
            // Update application status
            $stmt = $pdo->prepare("
                UPDATE hr_leave_applications 
                SET status = 'rejected', approved_by = ?, approved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([1, $id]); // Using 1 as default approved_by
            
            // Return days to leave balance
            $stmt = $pdo->prepare("
                UPDATE hr_leave_balances 
                SET remaining_days = remaining_days + ? 
                WHERE employee_id = ? AND leave_type_id = ? AND year = ?
            ");
            $stmt->execute([
                $application['total_days'], 
                $application['employee_id'], 
                $application['leave_type_id'], 
                date('Y', strtotime($application['start_date']))
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            header("Location: leave.php?status=rejected");
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = "Error rejecting leave: " . $e->getMessage();
    }
}

// Get all employees
 $employees = $pdo->query("
    SELECT id, employee_id, first_name, last_name 
    FROM hr_employees 
    WHERE status = 'active'
    ORDER BY last_name, first_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get leave types
 $leaveTypes = $pdo->query("SELECT * FROM hr_leave_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get leave applications
 $leaveApplications = $pdo->query("
    SELECT la.*, e.employee_id, e.first_name, e.last_name, lt.name as leave_type
    FROM hr_leave_applications la
    JOIN hr_employees e ON la.employee_id = e.id
    JOIN hr_leave_types lt ON la.leave_type_id = lt.id
    ORDER BY la.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module10/styles.css">
    <base href="<?php echo BASE_URL; ?>">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        .main-content {
            margin-left: 18rem;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            margin: 0;
            color: #2c3e50;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #2c3e50;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            width: 50%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .no-data {
            text-align: center;
            color: #7f8c8d;
            padding: 2rem 0;
            margin: 0;
        }
        
        .form-row {
            display: flex;
            gap: 2rem; /* Increased gap for more space between fields */
            margin-bottom: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-approved {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .form-actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 60px; /* Reduced height */
        }
        
        /* Added specific styling for date fields */
        .date-fields .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .employee-field {
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Leave Management</h1>
            <div>
                <button class="btn btn-primary" onclick="document.getElementById('applyLeaveForm').style.display='block'">Apply for Leave</button>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo $_GET['status'] === 'applied' || $_GET['status'] === 'approved' ? 'success' : 'danger'; ?>">
                <?php 
                if ($_GET['status'] === 'applied') echo "Leave application submitted successfully!";
                elseif ($_GET['status'] === 'approved') echo "Leave application approved!";
                elseif ($_GET['status'] === 'rejected') echo "Leave application rejected!";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Apply Leave Form -->
        <div id="applyLeaveForm" style="display: none;" class="card">
            <div class="card-header">
                <h2>Apply for Leave</h2>
            </div>
            <div class="card-content">
                <form method="post">
                    <div class="form-group employee-field">
                        <label>Employee</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?> (<?php echo htmlspecialchars($emp['employee_id']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row date-fields">
                        <div class="form-group">
                            <label>Leave Type</label>
                            <select name="leave_type_id" class="form-control" required>
                                <option value="">Select Leave Type</option>
                                <?php foreach ($leaveTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?> (<?php echo $type['days_allowed']; ?> days)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" rows="2" required placeholder="Reason..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="apply_leave" class="btn btn-primary">Submit Application</button>
                        <button type="button" class="btn" onclick="document.getElementById('applyLeaveForm').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leave Applications -->
        <div class="card">
            <div class="card-header">
                <h2>Leave Applications</h2>
            </div>
            <div class="card-content">
                <?php if (count($leaveApplications) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Status</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveApplications as $leave): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                                    <td><?php echo $leave['total_days']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $leave['status'] === 'approved' ? 'approved' : ($leave['status'] === 'rejected' ? 'rejected' : 'pending'); ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($leave['status'] === 'pending'): ?>
                                            <a href="<?php echo BASE_URL; ?>Module10/leave.php?action=approve&id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-primary">Approve</a>
                                            <a href="<?php echo BASE_URL; ?>Module10/leave.php?action=reject&id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No leave applications found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>