# EXAM SEATING MANAGEMENT SYSTEM

A comprehensive dual-portal web application for managing examination seating arrangements with separate interfaces for students and administrators. Features an engaging modern design with smooth animations and full client-side interactivity.

## FEATURES

### Landing Page
- Dual access selection interface
- Choose between Student Portal and Admin Portal
- Modern card-based design with smooth animations
- Clear feature descriptions for each portal type

### Student Portal (Client-Side JavaScript Implementation)
- Roll number-based authentication without passwords
- View personal exam schedule with all details
- Check assigned seat numbers and room locations
- Interactive visual seating plan showing exact seat position
- Real-time seat highlighting on seating map
- Print-friendly seat allocation details
- View exam date, time, and venue information
- Mobile-responsive design for on-the-go access
- No login credentials required - just roll number
- Smooth animations and modern interface

### Admin Portal (Server-Side PHP Implementation)
- Add new students with complete details including roll number, name, email, phone, department, and semester
- Search and filter students by name, roll number, email, or department
- View all students in a responsive table layout
- Edit and delete student records
- Track students by department and semester

### Examination Management
- Schedule new examinations with exam name, course code, date, time, department, and semester
- View all scheduled, ongoing, and completed exams in card format
- Track number of students assigned to each exam
- Delete examination records
- Color-coded status badges for easy identification

### Seating Arrangement
- Automatic seat allocation based on room capacity and layout
- Intelligent distribution of students across multiple rooms
- Visual seat grid showing occupied and empty seats
- Seat labeling with row letters and column numbers
- View seating plans by room with complete student details
- Print-friendly seating plan layouts

### Room Management
- Add examination rooms with room number, building, capacity, and seating layout
- Configure room dimensions with rows and columns
- Enable or disable rooms for examination use
- Track room utilization across multiple exams
- View room capacity and layout information

### Reports and Analytics
- Student distribution by department with visual bar charts
- Room utilization statistics showing usage across exams
- Upcoming examinations report with assignment status
- Print-friendly report generation

### User Authentication
- Secure login system with role-based access
- Admin and supervisor roles with different permissions
- Session management with automatic timeout
- User profile display in header

## SYSTEM REQUIREMENTS

- XAMPP with Apache and MySQL
- PHP version 7.4 or higher
- Modern web browser supporting CSS Grid and Flexbox
- Minimum 1024x768 screen resolution recommended

## INSTALLATION STEPS

1. Install XAMPP on your computer and start Apache and MySQL services

2. Open phpMyAdmin by visiting http://localhost/phpmyadmin in your browser

3. Create a new database named "exam_seating_db"

4. Click on the "exam_seating_db" database and go to the SQL tab

5. Copy the entire content from "exam_seating.sql" file and paste it in the SQL query box

6. Click "Go" to execute the SQL commands and create all tables with sample data

7. Copy all the PHP files, CSS file, and JavaScript files to your XAMPP htdocs folder
   Default location: C:\xampp\htdocs\exam_seating\

8. Open your web browser and visit http://localhost/exam_seating/

9. You will see the landing page with two options: Student Portal and Admin Portal

10. For Student Access:
    - Click "Student Portal"
    - Enter any roll number from the sample data (e.g., CS001, CS002, EE001)
    - View your exam schedule and seat allocations

11. For Admin Access:
    - Click "Admin Portal"
    - Login with credentials: admin / password
    - Access full management features

## FILE STRUCTURE

index.php - Landing page with dual portal access
student_login.php - Student roll number entry page
student_dashboard.php - Student portal dashboard
student_api.php - RESTful API for student data
student_script.js - Client-side JavaScript for student portal
exam_seating.sql - Database schema and sample data
config.php - Database connection and authentication functions
login.php - Admin/supervisor login page
logout.php - Logout script
header.php - Common header navigation component
dashboard.php - Admin dashboard with statistics
students.php - Student management interface
exams.php - Examination scheduling and management
seating.php - Seating arrangement automation
view_seating.php - Visual seating plan display for admins
rooms.php - Room management interface
reports.php - Reports and analytics page
style.css - Complete stylesheet with responsive design for both portals
script.js - JavaScript functions for admin portal interactivity

## DEFAULT LOGIN CREDENTIALS

Student Access:
Roll Numbers: CS001, CS002, CS003, EE001, EE002, ME001, ME002, CS004, CS005, EE003
Note: Students don't need passwords - just enter roll number

Admin Account:
Username: admin
Password: password

Supervisor Account:
Username: supervisor
Password: password

Note: Change admin passwords immediately after first login for security

## HOW TO USE

### For Students

1. Visit the application homepage
2. Click "Student Portal" on the landing page
3. Enter your roll number (no password needed)
4. View your complete exam schedule
5. Check your assigned seat numbers for each exam
6. Click "View Seating Plan" to see visual room layout
7. Your seat will be highlighted in the seating map
8. Print seat allocation details if needed

### For Administrators

### Adding Students
1. Navigate to Students page from the header menu
2. Click "Add Student" button
3. Fill in all required fields including roll number, name, email, phone, department, and semester
4. Click "Add Student" to save

### Scheduling Exams
1. Go to Exams page
2. Click "Schedule Exam" button
3. Enter exam details including name, course code, date, time, department, and semester
4. Click "Schedule Exam" to save

### Automatic Seating Arrangement
1. Navigate to Seating page
2. Select an exam from the available list
3. Click "Auto Assign Seats" button
4. Confirm the action when prompted
5. System will automatically distribute students across available rooms
6. View the seating arrangement by clicking "View Arrangement"

### Viewing Seating Plans
1. From the Seating page or Exams page, click "View Plan"
2. Seating plan shows all rooms with visual seat grids
3. Each seat displays seat number, student roll number, and name
4. Empty seats are shown with dashed borders
5. Use the Print button to generate printable seating plans

### Managing Rooms
1. Go to Rooms page
2. Click "Add Room" to add new examination venues
3. Specify room number, building, capacity, and layout dimensions
4. Enable or disable rooms using the toggle button
5. Delete rooms that are no longer needed

### Generating Reports
1. Access Reports page from the header menu (admin only)
2. View student distribution charts by department
3. Check room utilization statistics
4. Review upcoming examinations list
5. Use Print button to generate physical reports

## DESIGN FEATURES

Dual portal system with distinct visual identities
Landing page with clear portal selection
Student portal with green gradient theme
Admin portal with purple gradient theme
Modern gradient backgrounds with smooth color transitions
Smooth animations and transitions throughout both interfaces
Card-based layouts for better visual organization
Responsive design that works on desktop, tablet, and mobile devices
Interactive hover effects on buttons and cards
Color-coded status badges for quick identification
Modal dialogs for data entry forms and seating plans
Sticky header navigation for easy access
Real-time seat highlighting for students
Interactive seating map with visual feedback
Print-optimized layouts for seating plans and reports
Client-side rendering for instant student portal updates
AJAX-based API calls for seamless data loading

## SECURITY FEATURES

Dual authentication system (admin with passwords, students with roll numbers)
Password hashing using PHP password_verify function for admin accounts
SQL injection prevention through prepared statements
XSS protection with htmlspecialchars function
Session-based authentication with timeout for admins
Role-based access control for admin features
CSRF protection through session validation
RESTful API with proper error handling
Client-side input validation and sanitization
Secure data transmission between client and server

## RESPONSIVE DESIGN

Desktop view with multi-column layouts
Tablet view with adjusted grid columns
Mobile view with single column layouts and hamburger menu
Touch-friendly buttons and interactive elements
Optimized font sizes for different screen sizes

## BROWSER COMPATIBILITY

Google Chrome (recommended)
Mozilla Firefox
Microsoft Edge
Safari
Opera

## TROUBLESHOOTING

If landing page doesn't load: Ensure index.php is in the root exam_seating folder

If student login fails: Verify roll numbers exist in database and student_api.php has correct permissions

If seat map doesn't display: Check JavaScript console for errors and ensure student_script.js is loading properly

If login fails: Check database connection in config.php file and ensure MySQL service is running in XAMPP

If pages are blank: Enable error reporting in PHP by setting display_errors = On in php.ini file

If seating arrangement fails: Ensure there are enough available rooms with sufficient capacity

If styles don't load: Clear browser cache and verify style.css file is in the same folder

If database errors occur: Re-run the SQL file to recreate tables and check database credentials

If AJAX calls fail: Check browser console for errors and verify student_api.php file path is correct

## CUSTOMIZATION

You can customize colors by editing the CSS variables in style.css under the :root selector
Add more sample data by inserting additional records in the SQL file
Modify room layouts by adjusting rows and columns in room management
Change date and time formats in PHP files using date() function parameters
Add additional fields to forms by modifying the database schema and form HTML

## BACKUP RECOMMENDATIONS

Regular database backups through phpMyAdmin export feature
Keep copies of all PHP and CSS files in a separate location
Export seating arrangements before starting new examination session
Store student data CSV exports for external records

## SUPPORT

For issues with XAMPP installation, visit the Apache Friends website
For PHP syntax questions, refer to official PHP documentation at php.net
For MySQL database help, check MySQL documentation at dev.mysql.com
For web design questions, visit MDN Web Docs at developer.mozilla.org

## FUTURE ENHANCEMENTS

Student mobile app for easier access
Push notifications for exam schedule updates
QR code-based seat verification
Email notifications for exam schedules to students and admins
Barcode generation for seat tickets
Student attendance tracking integration via mobile scanning
Bulk student import from CSV or Excel files
Advanced analytics with graphical charts and trends
Automated exam conflict detection
Automated exam timetable generation
Real-time seat availability dashboard
SMS notifications for exam reminders
Digital hall tickets generation
Integration with university management systems
Parent portal for exam schedule viewing

## VERSION INFORMATION

Version 2.0 - Dual Portal System
Release Date: 2024
PHP Version: 7.4+
MySQL Version: 5.7+
JavaScript: ES6+ (Modern browsers)
API Architecture: RESTful JSON API
Responsive Design: Yes
Mobile Friendly: Yes
Student Portal: Pure JavaScript (Client-Side)
Admin Portal: PHP + JavaScript (Full Stack)
Authentication: Dual system (Admin with sessions, Student with roll number lookup)

This system provides a complete dual-portal solution for managing examination seating arrangements with separate intuitive interfaces for students and administrators, featuring powerful automation and real-time client-side interactivity.