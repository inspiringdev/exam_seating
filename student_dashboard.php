<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exam Details - Student Portal</title>
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
                <span id="studentName" class="student-name-display"></span>
                <a href="" id="profileLink" class="btn btn-sm btn-secondary" style="background: linear-gradient(135deg, #ebd37cff, #fabf11ff); margin-right: 8px; text-font-weight: bold;">
                    My Profile
                </a>
                
                <button onclick="logout()" class="btn btn-sm btn-secondary" style="background: linear-gradient(135deg, #ebd37cff, #fabf11ff); margin-right: 8px; padding:11px 14px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </button>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1>My Exam Schedule</h1>
                <p id="studentInfo"></p>
            </div>
        </div>
        
        <div id="loadingSpinner" class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading your exam details...</p>
        </div>
        
        <div id="noExamsMessage" class="empty-state" style="display: none; color: white;">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" >
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <h2>No Exams Scheduled</h2>
            <p>You don't have any scheduled exams at the moment.</p>
        </div>
        
        <div id="examsContainer" class="exams-grid" style="display: none;"></div>
        
        <div id="seatMapModal" class="modal">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2 id="modalTitle">Seating Plan</h2>
                    <button onclick="closeSeatMap()" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="seatMapContainer"></div>
                </div>
                <div class="modal-footer">
                    <button onclick="closeSeatMap()" class="btn btn-secondary">Close</button>
                    <button onclick="window.print()" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="student_script.js"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const rollNumber = urlParams.get('roll');
        
        if (!rollNumber) {
            window.location.href = 'student_login.php';
        } else {
            loadStudentData(rollNumber);
        }
    </script>
</body>
</html>
