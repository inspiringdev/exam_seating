<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $roll = sanitize($_POST['roll_number']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $dept = sanitize($_POST['department']);
    $sem = intval($_POST['semester']);
    
    $stmt = $conn->prepare("INSERT INTO students (roll_number, name, email, phone, department, semester) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $roll, $name, $email, $phone, $dept, $sem);
    
    if ($stmt->execute()) {
        $success = 'Student added successfully';
    } else {
        $error = 'Failed to add student';
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM students WHERE id = $id");
    $success = 'Student deleted successfully';
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$dept_filter = isset($_GET['department']) ? sanitize($_GET['department']) : '';

$query = "SELECT * FROM students WHERE 1=1";
if ($search) {
    $query .= " AND (name LIKE '%$search%' OR roll_number LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($dept_filter) {
    $query .= " AND department = '$dept_filter'";
}
$query .= " ORDER BY roll_number ASC";

$students = $conn->query($query);
$departments = $conn->query("SELECT DISTINCT department FROM students ORDER BY department");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Students Management</h1>
                <p>Manage student registrations</p>
            </div>
            <button onclick="openModal('addStudentModal')" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Student
            </button>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="filters">
                    <form method="GET" class="filter-form">
                        <div class="search-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select name="department" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $dept['department']; ?>" <?php echo $dept_filter == $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['department']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Roll Number</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Semester</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                <td><?php echo htmlspecialchars($student['department']); ?></td>
                                <td><?php echo $student['semester']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn btn-sm btn-icon" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <a href="?delete=<?php echo $student['id']; ?>" onclick="return confirm('Delete this student?')" class="btn btn-sm btn-icon btn-danger" title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Student</h2>
                <button onclick="closeModal('addStudentModal')" class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Roll Number</label>
                            <input type="text" name="roll_number" required>
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" required>
                        </div>
                        <div class="form-group">
                            <label>Semester</label>
                            <input type="number" name="semester" min="1" max="8" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('addStudentModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>