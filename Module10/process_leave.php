<?php
require '../db.php';
require '../shared/config.php';

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

// Get leave balances for current user (if employee)
 $leaveBalances = [];
if (isset($_SESSION['employee_id'])) {
    $stmt = $pdo->prepare("
        SELECT lb.*, lt.name as leave_type
        FROM hr_leave_balances lb
        JOIN hr_leave_types lt ON lb.leave_type_id = lt.id
        WHERE lb.employee_id = ? AND lb.year = ?
    ");
    $stmt->execute([$_SESSION['employee_id'], date('Y')]);
    $leaveBalances = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module10/styles.css">
    <base href="<?php echo BASE_URL; ?>">
    <style>
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: #2c3e50;
        }
        .card-content {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 500;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
            padding: 20px 0;
            margin: 0;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
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
        .balance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .balance-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .balance-type {
            font-weight: 500;
            margin-bottom: 5px;
        }
        .balance-days {
            font-size: 18px;
        }
        .balance-days .remaining {
            font-weight: bold;
            color: #2c3e50;
        }
        .balance-days .total {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="main-content" style="margin-left: 18rem; padding: 20px;">
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

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!-- Leave Balances (for employees) -->
        <?php if (!empty($leaveBalances)): ?>
        <div class="card">
            <div class="card-header">
                <h2>Your Leave Balances - <?php echo date('Y'); ?></h2>
            </div>
            <div class="card-content">
                <div class="balance-grid">
                    <?php foreach ($leaveBalances as $balance): ?>
                        <div class="balance-item">
                            <div class="balance-type"><?php echo htmlspecialchars($balance['leave_type']); ?></div>
                            <div class="balance-days">
                                <span class="remaining"><?php echo $balance['remaining_days']; ?></span>
                                <span class="total">/ <?php echo $balance['total_days']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Apply Leave Form -->
        <div id="applyLeaveForm" style="display: none;" class="card">
            <div class="card-header">
                <h2>Apply for Leave</h2>
            </div>
            <div class="card-content">
                <form method="post" action="<?php echo BASE_URL; ?>Module10/process_leave.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Employee</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?> (<?php echo htmlspecialchars($emp['employee_id']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Leave Type</label>
                            <select name="leave_type_id" class="form-control" required>
                                <option value="">Select Leave Type</option>
                                <?php foreach ($leaveTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?> (<?php echo $type['days_allowed']; ?> days)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
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
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
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
                                <th>Action</th>
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
                                            <a href="<?php echo BASE_URL; ?>Module10/process_leave.php?action=approve&id=<?php echo $leave['id']; ?>" class="btn btn-sm">Approve</a>
                                            <a href="<?php echo BASE_URL; ?>Module10/process_leave.php?action=reject&id=<?php echo $leave['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
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