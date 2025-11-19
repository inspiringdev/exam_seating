<?php
require_once 'config.php';
requireLogin();

$stats = [];
$stats['total_students'] = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$stats['total_rooms'] = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
$stats['total_exams'] = $conn->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'];
$stats['scheduled_exams'] = $conn->query("SELECT COUNT(*) as count FROM exams WHERE status='scheduled'")->fetch_assoc()['count'];

$recent_exams = $conn->query("SELECT * FROM exams ORDER BY exam_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_students']; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            
            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                        <path d="M9 3v18"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_rooms']; ?></h3>
                    <p>Exam Rooms</p>
                </div>
            </div>
            
            <div class="stat-card stat-warning">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_exams']; ?></h3>
                    <p>Total Exams</p>
                </div>
            </div>
            
            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['scheduled_exams']; ?></h3>
                    <p>Scheduled</p>
                </div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Recent Exams</h2>
                    <a href="exams.php" class="btn btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Exam Name</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($exam = $recent_exams->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['course_code']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($exam['start_time'])); ?></td>
                                    <td><span class="badge badge-<?php echo $exam['status']; ?>"><?php echo ucfirst($exam['status']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div class="action-grid">
                        <a href="students.php" class="action-card">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <line x1="19" y1="8" x2="19" y2="14"></line>
                                <line x1="22" y1="11" x2="16" y2="11"></line>
                            </svg>
                            <span>Add Student</span>
                        </a>
                        
                        <a href="exams.php" class="action-card">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="12" y1="18" x2="12" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                            <span>Create Exam</span>
                        </a>
                        
                        <a href="seating.php" class="action-card">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <path d="M9 3v18"></path>
                            </svg>
                            <span>Arrange Seating</span>
                        </a>
                        
                        <a href="rooms.php" class="action-card">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <span>Manage Rooms</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>