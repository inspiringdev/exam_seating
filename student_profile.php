<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header student-header">
        <div class="header-container">
            <div class="header-brand">
                <div class="logo student-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <span class="brand-name">Student Portal</span>
            </div>
            
            <div class="header-actions">
                <a href="student_dashboard.php?roll=<?php echo isset($_GET['roll']) ? urlencode($_GET['roll']) : ''; ?>" class="btn btn-sm btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h1>My Profile</h1>
        </div>
        
        <div id="loadingSpinner" class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading profile...</p>
        </div>
        
        <div id="profileContent" style="display: none;">
            <div class="profile-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Personal Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="profile-avatar">
                            <div class="avatar-large student-avatar-large" id="avatarCircle">
                            </div>
                            <div class="avatar-info">
                                <h3 id="studentName"></h3>
                                <span class="role-badge role-student">Student</span>
                            </div>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Roll Number</label>
                                <p id="rollNumber"></p>
                            </div>
                            <div class="info-item">
                                <label>Department</label>
                                <p id="department"></p>
                            </div>
                            <div class="info-item">
                                <label>Semester</label>
                                <p id="semester"></p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p id="email"></p>
                            </div>
                            <div class="info-item">
                                <label>Phone</label>
                                <p id="phone"></p>
                            </div>
                            <div class="info-item">
                                <label>Member Since</label>
                                <p id="memberSince"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const rollNumber = urlParams.get('roll');
        
        if (!rollNumber) {
            window.location.href = 'student_login.php';
        } else {
            loadProfile(rollNumber);
        }
        
        async function loadProfile(roll) {
            try {
                const response = await fetch(`student_api.php?action=get_student_details&roll_number=${encodeURIComponent(roll)}`);
                const data = await response.json();
                
                if (data.success && data.student) {
                    const student = data.student;
                    document.getElementById('studentName').textContent = student.name;
                    document.getElementById('rollNumber').textContent = student.roll_number;
                    document.getElementById('department').textContent = student.department;
                    document.getElementById('semester').textContent = 'Semester ' + student.semester;
                    document.getElementById('email').textContent = student.email;
                    document.getElementById('phone').textContent = student.phone || 'Not provided';
                    document.getElementById('memberSince').textContent = new Date(student.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'});
                    document.getElementById('avatarCircle').textContent = student.name.charAt(0).toUpperCase();
                    
                    document.getElementById('loadingSpinner').style.display = 'none';
                    document.getElementById('profileContent').style.display = 'block';
                } else {
                    window.location.href = 'student_login.php';
                }
            } catch (error) {
                console.error('Error:', error);
                window.location.href = 'student_login.php';
            }
        }
    </script>
</body>
</html>