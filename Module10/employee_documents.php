<?php
require '../db.php';
require '../shared/config.php';

// Security: Validate all inputs
 $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
 $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
 $employeeId = filter_input(INPUT_GET, 'employee_id', FILTER_VALIDATE_INT);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    handleDocumentUpload($pdo);
} elseif ($action === 'delete' && $id) {
    handleDocumentDeletion($pdo, $id, $employeeId);
}

// Handle document viewing
if ($action === 'view' && $id) {
    handleDocumentView($pdo, $id);
}

// Fetch data
 $employee = $employeeId ? fetchEmployee($pdo, $employeeId) : null;
 $documents = $employeeId ? fetchEmployeeDocuments($pdo, $employeeId) : [];
 $employees = fetchActiveEmployees($pdo);

// Helper functions
function handleDocumentUpload($pdo) {
    $employeeId = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
    $documentType = filter_input(INPUT_POST, 'document_type', FILTER_SANITIZE_STRING);
    $documentName = filter_input(INPUT_POST, 'document_name', FILTER_SANITIZE_STRING);
    $expiryDate = filter_input(INPUT_POST, 'expiry_date');
    
    // Validate required fields
    if (!$employeeId || !$documentType || !$documentName) {
        $error = "All required fields must be filled.";
        return;
    }
    
    // Handle file upload
    $targetDir = "../uploads/employee_documents/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // File validation
    $file = $_FILES["document_file"];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $allowTypes = ['jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload error: " . $file['error'];
        return;
    }
    
    if ($file['size'] > $maxFileSize) {
        $error = "File is too large. Maximum size is 5MB.";
        return;
    }
    
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowTypes)) {
        $error = "Only JPG, JPEG, PNG, PDF, DOC & DOCX files are allowed.";
        return;
    }
    
    // Generate secure filename
    $fileName = uniqid('doc_', true) . '.' . $fileType;
    $targetFilePath = $targetDir . $fileName;
    
    // Create web-accessible path for database storage
    $webPath = "uploads/employee_documents/" . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO hr_employee_documents 
                (employee_id, document_type, document_name, file_path, expiry_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$employeeId, $documentType, $documentName, $webPath, $expiryDate]);
            
            header("Location: employee_documents.php?employee_id=$employeeId&status=uploaded");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Sorry, there was an error uploading your file.";
    }
}

function handleDocumentDeletion($pdo, $id, $employeeId) {
    try {
        // Get file path before deleting
        $stmt = $pdo->prepare("SELECT file_path FROM hr_employee_documents WHERE id = ?");
        $stmt->execute([$id]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($document) {
            // Convert web path to server path for file deletion
            $serverPath = "../" . $document['file_path'];
            
            // Delete file
            if (file_exists($serverPath)) {
                unlink($serverPath);
            }
            
            // Delete record
            $stmt = $pdo->prepare("DELETE FROM hr_employee_documents WHERE id = ?");
            $stmt->execute([$id]);
            
            header("Location: employee_documents.php?employee_id=$employeeId&status=deleted");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Error deleting document: " . $e->getMessage();
    }
}

function handleDocumentView($pdo, $id) {
    try {
        // Get file path
        $stmt = $pdo->prepare("SELECT file_path, document_name FROM hr_employee_documents WHERE id = ?");
        $stmt->execute([$id]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($document) {
            $fileExtension = strtolower(pathinfo($document['file_path'], PATHINFO_EXTENSION));
            $filePath = "../" . $document['file_path'];
            
            // Check if file exists
            if (!file_exists($filePath)) {
                http_response_code(404);
                echo "File not found";
                exit;
            }
            
            // Set appropriate headers based on file type
            switch ($fileExtension) {
                case 'jpg':
                case 'jpeg':
                    header('Content-Type: image/jpeg');
                    break;
                case 'png':
                    header('Content-Type: image/png');
                    break;
                case 'pdf':
                    header('Content-Type: application/pdf');
                    break;
                case 'doc':
                    header('Content-Type: application/msword');
                    break;
                case 'docx':
                    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                    break;
                default:
                    header('Content-Type: application/octet-stream');
                    break;
            }
            
            // Set content disposition
            header('Content-Disposition: inline; filename="' . $document['document_name'] . '.' . $fileExtension . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            header('Expires: 0');
            
            // Output file
            readfile($filePath);
            exit;
        } else {
            http_response_code(404);
            echo "Document not found";
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Error retrieving document: " . $e->getMessage();
        exit;
    }
}

function fetchEmployee($pdo, $employeeId) {
    $stmt = $pdo->prepare("
        SELECT e.*, p.title as position_name, d.name as department_name
        FROM hr_employees e
        LEFT JOIN hr_positions p ON e.position_id = p.id
        LEFT JOIN hr_departments d ON e.department_id = d.id
        WHERE e.id = ?
    ");
    $stmt->execute([$employeeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function fetchEmployeeDocuments($pdo, $employeeId) {
    $stmt = $pdo->prepare("
        SELECT * FROM hr_employee_documents 
        WHERE employee_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$employeeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchActiveEmployees($pdo) {
    return $pdo->query("
        SELECT id, employee_id, first_name, last_name 
        FROM hr_employees 
        WHERE status = 'active'
        ORDER BY last_name, first_name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getStatusBadge($expiryDate) {
    if (!$expiryDate) return '';
    
    $today = new DateTime();
    $expiryDateObj = new DateTime($expiryDate);
    $interval = $today->diff($expiryDateObj);
    
    if ($interval->invert) {
        return '<span class="status-badge status-expired"><i class="fas fa-exclamation-circle"></i> Expired</span>';
    } elseif ($interval->days <= 30) {
        return '<span class="status-badge status-expiring"><i class="fas fa-exclamation-triangle"></i> Expiring Soon</span>';
    } else {
        return '<span class="status-badge status-valid"><i class="fas fa-check-circle"></i> Valid</span>';
    }
}

function formatDate($dateString) {
    return $dateString ? date('M d, Y', strtotime($dateString)) : '';
}

function getDocumentIcon($documentType) {
    $icons = [
        'ID' => 'fa-id-card',
        'Passport' => 'fa-passport',
        'Driver License' => 'fa-id-card',
        'Certificate' => 'fa-certificate',
        'Diploma' => 'fa-graduation-cap',
        'Contract' => 'fa-file-contract',
        'Other' => 'fa-file'
    ];
    return $icons[$documentType] ?? 'fa-file';
}

function getFileIcon($fileType) {
    $fileType = strtolower($fileType);
    if (in_array($fileType, ['jpg', 'jpeg', 'png'])) {
        return 'fa-file-image';
    } elseif ($fileType === 'pdf') {
        return 'fa-file-pdf';
    } elseif (in_array($fileType, ['doc', 'docx'])) {
        return 'fa-file-word';
    } else {
        return 'fa-file';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Documents</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Module10/styles.css">
    <base href="<?php echo BASE_URL; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --border-radius: 8px;
            --box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .container {
            margin-left: 18rem;
            padding: 20px;
            max-width: 1200px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .header h1 {
            color: var(--secondary-color);
            font-size: 28px;
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 25px;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--light-color);
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 20px;
            color: var(--secondary-color);
            font-weight: 500;
        }
        
        .card-content {
            padding: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 30%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: var(--transition);
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: var(--light-color);
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            padding: 30px 0;
            margin: 0;
            font-style: italic;
        }
        
        .document-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            transition: var(--transition);
        }
        
        .document-item:hover {
            box-shadow: var(--box-shadow);
            transform: translateY(-2px);
        }
        
        .document-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--border-radius);
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .document-details {
            flex: 1;
        }
        
        .document-name {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .document-type {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 8px;
        }
        
        .document-actions {
            display: flex;
            gap: 10px;
        }
        
        .document-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-valid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-expiring {
            background: #fff3cd;
            color: #856404;
        }
        
        .employee-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .employee-info p {
            margin: 0;
            padding: 10px;
            background: var(--light-color);
            border-radius: var(--border-radius);
        }
        
        .employee-info strong {
            color: var(--secondary-color);
        }
        
        .search-container {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
        
        .search-input {
            padding-left: 45px;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 0;
            border-radius: var(--border-radius);
            width: 400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--secondary-color);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: var(--danger-color);
        }
        
        /* Document viewer modal */
        .document-viewer {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s;
        }
        
        .document-viewer-content {
            position: relative;
            margin: 2% auto;
            padding: 0;
            background: white;
            width: 90%;
            height: 90%;
            border-radius: var(--border-radius);
            overflow: hidden;
            animation: slideIn 0.3s;
        }
        
        .document-viewer-header {
            padding: 15px 20px;
            background: var(--secondary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .document-viewer-body {
            height: calc(100% - 60px);
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .document-viewer-body img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .document-viewer-body iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }
        
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .document-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .document-icon {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .document-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .document-viewer-content {
                width: 95%;
                height: 95%;
            }
        }
    </style>
</head>
<body>
    <?php include '../shared/sidebar.php'; ?>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-folder-open"></i> Employee Documents</h1>
            <div class="header-actions">
                <?php if ($employeeId): ?>
                    <a href="<?php echo BASE_URL; ?>Module10/employee_documents.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Employee Selection
                    </a>
                <?php endif; ?>
                <button class="btn btn-primary" onclick="document.getElementById('uploadDocumentForm').style.display='block'">
                    <i class="fas fa-upload"></i> Upload Document
                </button>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo in_array($_GET['status'], ['uploaded', 'deleted']) ? 'success' : 'danger'; ?>">
                <i class="fas fa-<?php echo in_array($_GET['status'], ['uploaded', 'deleted']) ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php 
                if ($_GET['status'] === 'uploaded') echo "Document uploaded successfully!";
                elseif ($_GET['status'] === 'deleted') echo "Document deleted successfully!";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Employee Selection -->
        <?php if (!$employeeId): ?>
        <div class="card">
            <div class="card-content">
                <form method="get">
                    <div class="form-group">
                        <label>Select Employee</label>
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <select name="employee_id" class="form-control search-input" onchange="this.form.submit()">
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?> 
                                        (<?php echo htmlspecialchars($emp['employee_id']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upload Document Form -->
        <div id="uploadDocumentForm" style="display: none;" class="card">
            <div class="card-header">
                <h2><i class="fas fa-cloud-upload-alt"></i> Upload Document</h2>
            </div>
            <div class="card-content">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="upload_document" value="1">
                    
                    <div class="form-group">
                        <label>Employee</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo ($employeeId == $emp['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?> 
                                    (<?php echo htmlspecialchars($emp['employee_id']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Document Type</label>
                            <select name="document_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="ID">ID</option>
                                <option value="Passport">Passport</option>
                                <option value="Driver License">Driver License</option>
                                <option value="Certificate">Certificate</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Contract">Contract</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Document Name</label>
                            <input type="text" name="document_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Document File</label>
                            <input type="file" name="document_file" class="form-control" required>
                            <small>Allowed file types: JPG, PNG, PDF, DOC, DOCX (Max 5MB)</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Document
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('uploadDocumentForm').style.display='none'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employee Info -->
        <?php if ($employee): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user"></i> Employee Information</h2>
            </div>
            <div class="card-content">
                <div class="employee-info">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></p>
                    <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department_name']); ?></p>
                    <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['position_name']); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Employee Documents -->
        <?php if ($employeeId): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-file-alt"></i> Employee Documents</h2>
            </div>
            <div class="card-content">
                <?php if (count($documents) > 0): ?>
                    <div class="documents-list">
                        <?php foreach ($documents as $document): ?>
                            <?php 
                            $fileExtension = pathinfo($document['file_path'], PATHINFO_EXTENSION);
                            $fileIcon = getFileIcon($fileExtension);
                            ?>
                            <div class="document-item">
                                <div class="document-icon">
                                    <i class="fas <?php echo $fileIcon; ?>"></i>
                                </div>
                                <div class="document-details">
                                    <div class="document-name"><?php echo htmlspecialchars($document['document_name']); ?></div>
                                    <div class="document-type"><?php echo htmlspecialchars($document['document_type']); ?></div>
                                    <div class="document-meta">
                                        <span><i class="fas fa-calendar"></i> Uploaded: <?php echo formatDate($document['uploaded_at']); ?></span>
                                        <?php if ($document['expiry_date']): ?>
                                            <span><i class="fas fa-calendar-check"></i> Expiry: <?php echo formatDate($document['expiry_date']); ?></span>
                                            <?php echo getStatusBadge($document['expiry_date']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm view-document" 
                                       data-id="<?php echo $document['id']; ?>" 
                                       data-name="<?php echo htmlspecialchars($document['document_name']); ?>"
                                       data-type="<?php echo strtolower($fileExtension); ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <a href="#" class="btn btn-sm btn-danger delete-document" 
                                       data-id="<?php echo $document['id']; ?>" 
                                       data-employee-id="<?php echo $employeeId; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data"><i class="fas fa-inbox"></i> No documents found for this employee.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this document? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button id="cancelDelete" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a id="confirmDelete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </div>
    </div>

    <!-- Document Viewer Modal -->
    <div id="documentViewer" class="document-viewer">
        <div class="document-viewer-content">
            <div class="document-viewer-header">
                <h3 id="documentTitle">Document Viewer</h3>
                <span class="close">&times;</span>
            </div>
            <div class="document-viewer-body" id="documentViewerBody">
                <!-- Document content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Delete confirmation modal
        const modal = document.getElementById("deleteModal");
        const deleteButtons = document.querySelectorAll(".delete-document");
        const confirmDeleteBtn = document.getElementById("confirmDelete");
        const cancelBtn = document.getElementById("cancelDelete");
        const closeBtn = document.getElementsByClassName("close")[0];

        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const employeeId = this.getAttribute('data-employee-id');
                confirmDeleteBtn.setAttribute('href', `<?php echo BASE_URL; ?>Module10/employee_documents.php?action=delete&id=${id}&employee_id=${employeeId}`);
                modal.style.display = "block";
            });
        });

        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        cancelBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Document viewer modal
        const documentViewer = document.getElementById("documentViewer");
        const documentViewerBody = document.getElementById("documentViewerBody");
        const documentTitle = document.getElementById("documentTitle");
        const viewButtons = document.querySelectorAll(".view-document");
        const viewerCloseBtn = document.querySelectorAll(".close")[1];

        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const type = this.getAttribute('data-type');
                
                documentTitle.textContent = name;
                documentViewerBody.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading document...</div>';
                documentViewer.style.display = "block";
                
                // Load document based on type
                if (['jpg', 'jpeg', 'png'].includes(type)) {
                    // For images, load directly
                    const img = document.createElement('img');
                    img.src = `<?php echo BASE_URL; ?>Module10/employee_documents.php?action=view&id=${id}`;
                    img.onload = function() {
                        documentViewerBody.innerHTML = '';
                        documentViewerBody.appendChild(img);
                    };
                    img.onerror = function() {
                        documentViewerBody.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Error loading document</div>';
                    };
                } else if (type === 'pdf') {
                    // For PDFs, use iframe
                    const iframe = document.createElement('iframe');
                    iframe.src = `<?php echo BASE_URL; ?>Module10/employee_documents.php?action=view&id=${id}`;
                    iframe.onload = function() {
                        documentViewerBody.innerHTML = '';
                        documentViewerBody.appendChild(iframe);
                    };
                    iframe.onerror = function() {
                        documentViewerBody.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Error loading document</div>';
                    };
                } else {
                    // For other document types, provide download link
                    documentViewerBody.innerHTML = `
                        <div class="download-prompt">
                            <i class="fas fa-file-alt" style="font-size: 48px; color: #3498db; margin-bottom: 20px;"></i>
                            <h3>This document type cannot be previewed</h3>
                            <p>Please download the document to view it.</p>
                            <a href="<?php echo BASE_URL; ?>Module10/employee_documents.php?action=view&id=${id}" class="btn btn-primary" download>
                                <i class="fas fa-download"></i> Download Document
                            </a>
                        </div>
                    `;
                }
            });
        });

        viewerCloseBtn.onclick = function() {
            documentViewer.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == documentViewer) {
                documentViewer.style.display = "none";
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 3000);
    }
});
    </script>
</body>
</html>