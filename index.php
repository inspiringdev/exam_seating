<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Seating System - Home</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-page">
    <div class="landing-container">
        <div class="landing-header">
            <div class="logo-large">
                <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <h1>Exam Seating System</h1>
            <p>Choose your access type to continue</p>
        </div>
        
        <div class="access-cards">
            <a href="student_login.php" class="access-card student-card">
                <div class="access-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h2>Student Portal</h2>
                <p>View your exam schedule, seat allocation, and room details</p>
                <div class="access-features">
                    <span>✓ Check Seat Number</span>
                    <span>✓ View Exam Schedule</span>
                    <span>✓ Room Location</span>
                </div>
                <div class="access-button">
                    Enter as Student
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </div>
            </a>
            
            <a href="login.php" class="access-card admin-card">
                <div class="access-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <h2>Admin Portal</h2>
                <p>Manage students, exams, seating arrangements, and generate reports</p>
                <div class="access-features">
                    <span>✓ Manage Students</span>
                    <span>✓ Schedule Exams</span>
                    <span>✓ Arrange Seating</span>
                </div>
                <div class="access-button">
                    Enter as Admin
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </div>
            </a>
        </div>
        
        <div class="landing-footer">
            <p>Secure • Efficient • User-Friendly</p>
        </div>
    </div>
</body>
</html>