<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Exam Seating System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-circle student-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h1>Student Portal</h1>
                <p>Enter your roll number to view seat details</p>
            </div>
            
            <div id="loginError" class="alert alert-error" style="display: none;"></div>
            
            <form id="studentLoginForm" class="login-form">
                <div class="form-group">
                    <label for="roll_number">Roll Number</label>
                    <input type="text" id="roll_number" name="roll_number" placeholder="Enter your roll number" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    View My Seat
                </button>
                
                <div class="login-footer">
                    <a href="index.php" class="back-link">← Back to Home</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="student_script.js"></script>
</body>
</html>