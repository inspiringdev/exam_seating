# EXAM SEATING MANAGEMENT SYSTEM (Simple Guide)

This is a small website that helps students and admins manage exam seating.  
Students can see their seats, and admins can add students, rooms, and exams.

## Website Video
<video src="demo.mp4" controls width="600"></video>

## What the Website Does

### Landing Page
- You can pick **Student Portal** or **Admin Portal**.
- Nice and simple design.

### Student Portal
- Students just type their **roll number** (no password).
- They can see:
  - Exam dates  
  - Seat number  
  - Room number  
  - A seating map showing exactly where they sit  
- Works on mobile too.

### Admin Portal
- Admins can:
  - Add, edit, or delete students  
  - Add exam schedules  
  - Add rooms  
  - See reports and charts  
  - Auto-assign seats to students  

### Seating System
- The system fills seats automatically.
- Shows rooms with empty and filled seats.
- You can print the seating plan.

## What You Need
- XAMPP with Apache + MySQL  
- PHP 7.4 or newer  
- A modern browser (Chrome is best)

## How to Install
1. Install XAMPP and start **Apache** + **MySQL**  
2. Open **phpMyAdmin**  
3. Make a database named **exam_seating_db**  
4. Import the file **exam_seating.sql**  
5. Copy all project files into:  
   `C:\xampp\htdocs\exam_seating\`
6. Open in browser:  
   `http://localhost/exam_seating/`

## Login Info

### Students
- Roll numbers: CS001, CS002, EE001, ME001, etc.
- No password needed.

### Admin
- Username: **admin**  
- Password: **password**

### Supervisor
- Username: **supervisor**  
- Password: **password**

## How to Use

### Student
1. Open Student Portal  
2. Enter roll number  
3. See exam + seat details  
4. Open seating map if needed  

### Admin
- Add students  
- Add rooms  
- Add exams  
- Auto-assign seats  
- View reports  

## Files Included
- `index.php` – Landing page  
- `students.php` – Manage students  
- `exams.php` – Manage exams  
- `rooms.php` – Manage rooms  
- `seating.php` – Auto seat assignment  
- `view_seating.php` – View seating layouts  
- `style.css` – Website design  
- `exam_seating.sql` – Database  

## Troubleshooting
- Page blank → Check `config.php`  
- Database not working → Import SQL file again  
- CSS not loading → Refresh or clear cache  
- Student login not working → Check roll number in DB  

## Future Ideas
- Mobile app  
- QR codes  
- Notifications  
- Better reports  
- Auto exam timetable  
