CREATE DATABASE IF NOT EXISTS exam_seating_db;
USE exam_seating_db;

CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    roll_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(50) UNIQUE NOT NULL,
    building VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    rows INT NOT NULL,
    columns INT NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    department VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    total_students INT DEFAULT 0,
    status ENUM('scheduled', 'ongoing', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS seating_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    plan_date DATE NOT NULL,
    created_by INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);


CREATE TABLE seating_arrangements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    row_position INT NOT NULL,
    column_position INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_seat (exam_id, room_id, seat_number)
);

CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS semesters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    exam_date DATE,
    semester_number INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT
);

ALTER TABLE students 
ADD COLUMN semester_id INT AFTER department,
ADD FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE RESTRICT;


ALTER TABLE exams 
ADD COLUMN semester_id INT AFTER department,
ADD FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE RESTRICT;

ALTER TABLE seating_arrangements 
ADD COLUMN plan_id INT AFTER id,
ADD FOREIGN KEY (plan_id) REFERENCES seating_plans(id) ON DELETE CASCADE,
ADD UNIQUE KEY unique_plan_seat (plan_id, room_id, seat_number);

CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seating_id INT NOT NULL,
    present BOOLEAN DEFAULT FALSE,
    marked_at TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (seating_id) REFERENCES seating_arrangements(id) ON DELETE CASCADE
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'supervisor') DEFAULT 'supervisor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO departments (name, code) VALUES 
('Computer Science', 'CS'),
('Electrical Engineering', 'EE'),
('Mechanical Engineering', 'ME'),
('Software Engineering', 'SE'),
('Data Science', 'DS');

-- Insert initial semesters
INSERT INTO semesters (department_id, title, code, semester_number, academic_year, exam_date) VALUES
(1, 'Computer Science Semester 4', 'CS-4', 4, '2023-2025', '2024-12-15'),
(1, 'Computer Science Semester 6', 'CS-6', 6, '2022-2025', '2024-12-20'),
(2, 'Electrical Engineering Semester 4', 'EE-4', 4, '2023-2025', '2024-12-18'),
(2, 'Electrical Engineering Semester 6', 'EE-6', 6, '2022-2025', '2024-12-22'),
(3, 'Mechanical Engineering Semester 2', 'ME-2', 2, '2024-2025', '2024-12-16'),
(3, 'Mechanical Engineering Semester 4', 'ME-4', 4, '2023-2025', '2024-12-19'),
(4, 'Software Engineering Semester 4', 'SE-4', 4, '2023-2025', '2024-12-17'),
(5, 'Data Science Semester 4', 'DS-4', 4, '2023-2025', '2024-12-21');

INSERT INTO users (username, password, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin'),
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Exam Supervisor', 'supervisor');

INSERT INTO rooms (room_number, building, capacity, rows, columns) VALUES
('101', 'Main Block', 40, 8, 5),
('102', 'Main Block', 40, 8, 5),
('201', 'Science Block', 50, 10, 5),
('202', 'Science Block', 50, 10, 5),
('301', 'Admin Block', 30, 6, 5);

-- Insert 200 Pakistani students with realistic names
INSERT INTO students (roll_number, name, email, phone, department, semester_id, semester) VALUES
-- Computer Science - Semester 4 (40 students)
('CS-2023-001', 'Ahmed Ali Khan', 'ahmed.ali@university.edu', '0300-1234567', 'Computer Science', 1, 4),
('CS-2023-002', 'Fatima Zahra', 'fatima.zahra@university.edu', '0300-2345678', 'Computer Science', 1, 4),
('CS-2023-003', 'Muhammad Hassan Raza', 'hassan.raza@university.edu', '0300-3456789', 'Computer Science', 1, 4),
('CS-2023-004', 'Ayesha Malik', 'ayesha.malik@university.edu', '0300-4567890', 'Computer Science', 1, 4),
('CS-2023-005', 'Bilal Ahmed', 'bilal.ahmed@university.edu', '0300-5678901', 'Computer Science', 1, 4),
('CS-2023-006', 'Sana Javed', 'sana.javed@university.edu', '0300-6789012', 'Computer Science', 1, 4),
('CS-2023-007', 'Usman Sheikh', 'usman.sheikh@university.edu', '0300-7890123', 'Computer Science', 1, 4),
('CS-2023-008', 'Zainab Tariq', 'zainab.tariq@university.edu', '0300-8901234', 'Computer Science', 1, 4),
('CS-2023-009', 'Hamza Iqbal', 'hamza.iqbal@university.edu', '0300-9012345', 'Computer Science', 1, 4),
('CS-2023-010', 'Mariam Khan', 'mariam.khan@university.edu', '0300-0123456', 'Computer Science', 1, 4),
('CS-2023-011', 'Omar Farooq', 'omar.farooq@university.edu', '0310-1234567', 'Computer Science', 1, 4),
('CS-2023-012', 'Hina Aslam', 'hina.aslam@university.edu', '0310-2345678', 'Computer Science', 1, 4),
('CS-2023-013', 'Abdullah Shah', 'abdullah.shah@university.edu', '0310-3456789', 'Computer Science', 1, 4),
('CS-2023-014', 'Sadia Rahman', 'sadia.rahman@university.edu', '0310-4567890', 'Computer Science', 1, 4),
('CS-2023-015', 'Tariq Mahmood', 'tariq.mahmood@university.edu', '0310-5678901', 'Computer Science', 1, 4),
('CS-2023-016', 'Nadia Akhtar', 'nadia.akhtar@university.edu', '0310-6789012', 'Computer Science', 1, 4),
('CS-2023-017', 'Kamran Ali', 'kamran.ali@university.edu', '0310-7890123', 'Computer Science', 1, 4),
('CS-2023-018', 'Rabia Bashir', 'rabia.bashir@university.edu', '0310-8901234', 'Computer Science', 1, 4),
('CS-2023-019', 'Saad Ahmed', 'saad.ahmed@university.edu', '0310-9012345', 'Computer Science', 1, 4),
('CS-2023-020', 'Fariha Noor', 'fariha.noor@university.edu', '0310-0123456', 'Computer Science', 1, 4),
('CS-2023-021', 'Waqas Hussain', 'waqas.hussain@university.edu', '0320-1234567', 'Computer Science', 1, 4),
('CS-2023-022', 'Saba Ilyas', 'saba.ilyas@university.edu', '0320-2345678', 'Computer Science', 1, 4),
('CS-2023-023', 'Asim Raza', 'asim.raza@university.edu', '0320-3456789', 'Computer Science', 1, 4),
('CS-2023-024', 'Mehak Ali', 'mehak.ali@university.edu', '0320-4567890', 'Computer Science', 1, 4),
('CS-2023-025', 'Nasir Khan', 'nasir.khan@university.edu', '0320-5678901', 'Computer Science', 1, 4),
('CS-2023-026', 'Amina Sheikh', 'amina.sheikh@university.edu', '0320-6789012', 'Computer Science', 1, 4),
('CS-2023-027', 'Faisal Iqbal', 'faisal.iqbal@university.edu', '0320-7890123', 'Computer Science', 1, 4),
('CS-2023-028', 'Sumaira Jamil', 'sumaira.jamil@university.edu', '0320-8901234', 'Computer Science', 1, 4),
('CS-2023-029', 'Haris Malik', 'haris.malik@university.edu', '0320-9012345', 'Computer Science', 1, 4),
('CS-2023-030', 'Zara Ahmed', 'zara.ahmed@university.edu', '0320-0123456', 'Computer Science', 1, 4),
('CS-2023-031', 'Danish Ali', 'danish.ali@university.edu', '0330-1234567', 'Computer Science', 1, 4),
('CS-2023-032', 'Saima Raza', 'saima.raza@university.edu', '0330-2345678', 'Computer Science', 1, 4),
('CS-2023-033', 'Imran Yousaf', 'imran.yousaf@university.edu', '0330-3456789', 'Computer Science', 1, 4),
('CS-2023-034', 'Noreen Akhtar', 'noreen.akhtar@university.edu', '0330-4567890', 'Computer Science', 1, 4),
('CS-2023-035', 'Shahid Mehmood', 'shahid.mehmood@university.edu', '0330-5678901', 'Computer Science', 1, 4),
('CS-2023-036', 'Tahira Bibi', 'tahira.bibi@university.edu', '0330-6789012', 'Computer Science', 1, 4),
('CS-2023-037', 'Arslan Khan', 'arslan.khan@university.edu', '0330-7890123', 'Computer Science', 1, 4),
('CS-2023-038', 'Sara Javed', 'sara.javed@university.edu', '0330-8901234', 'Computer Science', 1, 4),
('CS-2023-039', 'Noman Ali', 'noman.ali@university.edu', '0330-9012345', 'Computer Science', 1, 4),
('CS-2023-040', 'Kashif Raza', 'kashif.raza@university.edu', '0330-0123456', 'Computer Science', 1, 4),

-- Computer Science - Semester 6 (30 students)
('CS-2022-041', 'Ali Raza Khan', 'ali.raza@university.edu', '0340-1234567', 'Computer Science', 2, 6),
('CS-2022-042', 'Sadia Noor', 'sadia.noor@university.edu', '0340-2345678', 'Computer Science', 2, 6),
('CS-2022-043', 'Waseem Akram', 'waseem.akram@university.edu', '0340-3456789', 'Computer Science', 2, 6),
('CS-2022-044', 'Nazia Tahir', 'nazia.tahir@university.edu', '0340-4567890', 'Computer Science', 2, 6),
('CS-2022-045', 'Farhan Ahmed', 'farhan.ahmed@university.edu', '0340-5678901', 'Computer Science', 2, 6),
('CS-2022-046', 'Ayesha Siddiqua', 'ayesha.siddiqua@university.edu', '0340-6789012', 'Computer Science', 2, 6),
('CS-2022-047', 'Kamran Bashir', 'kamran.bashir@university.edu', '0340-7890123', 'Computer Science', 2, 6),
('CS-2022-048', 'Saba Khan', 'saba.khan@university.edu', '0340-8901234', 'Computer Science', 2, 6),
('CS-2022-049', 'Babar Ali', 'babar.ali@university.edu', '0340-9012345', 'Computer Science', 2, 6),
('CS-2022-050', 'Hina Malik', 'hina.malik@university.edu', '0340-0123456', 'Computer Science', 2, 6),
('CS-2022-051', 'Javed Iqbal', 'javed.iqbal@university.edu', '0341-1234567', 'Computer Science', 2, 6),
('CS-2022-052', 'Saima Yousaf', 'saima.yousaf@university.edu', '0341-2345678', 'Computer Science', 2, 6),
('CS-2022-053', 'Nadeem Ahmed', 'nadeem.ahmed@university.edu', '0341-3456789', 'Computer Science', 2, 6),
('CS-2022-054', 'Fouzia Tariq', 'fouzia.tariq@university.edu', '0341-4567890', 'Computer Science', 2, 6),
('CS-2022-055', 'Shahzad Khan', 'shahzad.khan@university.edu', '0341-5678901', 'Computer Science', 2, 6),
('CS-2022-056', 'Nadia Jamil', 'nadia.jamil@university.edu', '0341-6789012', 'Computer Science', 2, 6),
('CS-2022-057', 'Rizwan Ali', 'rizwan.ali@university.edu', '0341-7890123', 'Computer Science', 2, 6),
('CS-2022-058', 'Amina Bibi', 'amina.bibi@university.edu', '0341-8901234', 'Computer Science', 2, 6),
('CS-2022-059', 'Tahir Mahmood', 'tahir.mahmood@university.edu', '0341-9012345', 'Computer Science', 2, 6),
('CS-2022-060', 'Sara Khan', 'sara.khan@university.edu', '0341-0123456', 'Computer Science', 2, 6),
('CS-2022-061', 'Usman Ghani', 'usman.ghani@university.edu', '0342-1234567', 'Computer Science', 2, 6),
('CS-2022-062', 'Rabia Noor', 'rabia.noor@university.edu', '0342-2345678', 'Computer Science', 2, 6),
('CS-2022-063', 'Asad Ullah', 'asad.ullah@university.edu', '0342-3456789', 'Computer Science', 2, 6),
('CS-2022-064', 'Hira Shah', 'hira.shah@university.edu', '0342-4567890', 'Computer Science', 2, 6),
('CS-2022-065', 'Zubair Ahmed', 'zubair.ahmed@university.edu', '0342-5678901', 'Computer Science', 2, 6),
('CS-2022-066', 'Nida Ali', 'nida.ali@university.edu', '0342-6789012', 'Computer Science', 2, 6),
('CS-2022-067', 'Fahad Raza', 'fahad.raza@university.edu', '0342-7890123', 'Computer Science', 2, 6),
('CS-2022-068', 'Sanaullah Khan', 'sanaullah.khan@university.edu', '0342-8901234', 'Computer Science', 2, 6),
('CS-2022-069', 'Mariam Javed', 'mariam.javed@university.edu', '0342-9012345', 'Computer Science', 2, 6),
('CS-2022-070', 'Bilal Yousaf', 'bilal.yousaf@university.edu', '0342-0123456', 'Computer Science', 2, 6),

-- Electrical Engineering - Semester 4 (35 students)
('EE-2023-071', 'Sara Ahmed Khan', 'sara.ahmed@university.edu', '0301-1234567', 'Electrical Engineering', 3, 4),
('EE-2023-072', 'Bilal Mahmood', 'bilal.mahmood@university.edu', '0301-2345678', 'Electrical Engineering', 3, 4),
('EE-2023-073', 'Noor Fatima', 'noor.fatima@university.edu', '0301-3456789', 'Electrical Engineering', 3, 4),
('EE-2023-074', 'Abdul Rehman', 'abdul.rehman@university.edu', '0301-4567890', 'Electrical Engineering', 3, 4),
('EE-2023-075', 'Sadia Batool', 'sadia.batool@university.edu', '0301-5678901', 'Electrical Engineering', 3, 4),
('EE-2023-076', 'Imran Ali', 'imran.ali@university.edu', '0301-6789012', 'Electrical Engineering', 3, 4),
('EE-2023-077', 'Ayesha Hassan', 'ayesha.hassan@university.edu', '0301-7890123', 'Electrical Engineering', 3, 4),
('EE-2023-078', 'Kamran Shah', 'kamran.shah@university.edu', '0301-8901234', 'Electrical Engineering', 3, 4),
('EE-2023-079', 'Zara Sheikh', 'zara.sheikh@university.edu', '0301-9012345', 'Electrical Engineering', 3, 4),
('EE-2023-080', 'Saif Ullah', 'saif.ullah@university.edu', '0301-0123456', 'Electrical Engineering', 3, 4),
('EE-2023-081', 'Hina Raza', 'hina.raza@university.edu', '0302-1234567', 'Electrical Engineering', 3, 4),
('EE-2023-082', 'Noman Khan', 'noman.khan@university.edu', '0302-2345678', 'Electrical Engineering', 3, 4),
('EE-2023-083', 'Saba Javed', 'saba.javed@university.edu', '0302-3456789', 'Electrical Engineering', 3, 4),
('EE-2023-084', 'Arham Ali', 'arham.ali@university.edu', '0302-4567890', 'Electrical Engineering', 3, 4),
('EE-2023-085', 'Fariha Malik', 'fariha.malik@university.edu', '0302-5678901', 'Electrical Engineering', 3, 4),
('EE-2023-086', 'Waqar Ahmed', 'waqar.ahmed@university.edu', '0302-6789012', 'Electrical Engineering', 3, 4),
('EE-2023-087', 'Nadia Yousaf', 'nadia.yousaf@university.edu', '0302-7890123', 'Electrical Engineering', 3, 4),
('EE-2023-088', 'Shahid Iqbal', 'shahid.iqbal@university.edu', '0302-8901234', 'Electrical Engineering', 3, 4),
('EE-2023-089', 'Rabia Tariq', 'rabia.tariq@university.edu', '0302-9012345', 'Electrical Engineering', 3, 4),
('EE-2023-090', 'Asim Khan', 'asim.khan@university.edu', '0302-0123456', 'Electrical Engineering', 3, 4),
('EE-2023-091', 'Mehwish Ali', 'mehwish.ali@university.edu', '0303-1234567', 'Electrical Engineering', 3, 4),
('EE-2023-092', 'Faisal Raza', 'faisal.raza@university.edu', '0303-2345678', 'Electrical Engineering', 3, 4),
('EE-2023-093', 'Sana Bibi', 'sana.bibi@university.edu', '0303-3456789', 'Electrical Engineering', 3, 4),
('EE-2023-094', 'Nasir Mahmood', 'nasir.mahmood@university.edu', '0303-4567890', 'Electrical Engineering', 3, 4),
('EE-2023-095', 'Amina Khan', 'amina.khan@university.edu', '0303-5678901', 'Electrical Engineering', 3, 4),
('EE-2023-096', 'Haris Jamil', 'haris.jamil@university.edu', '0303-6789012', 'Electrical Engineering', 3, 4),
('EE-2023-097', 'Zainab Noor', 'zainab.noor@university.edu', '0303-7890123', 'Electrical Engineering', 3, 4),
('EE-2023-098', 'Danish Sheikh', 'danish.sheikh@university.edu', '0303-8901234', 'Electrical Engineering', 3, 4),
('EE-2023-099', 'Saima Ahmed', 'saima.ahmed@university.edu', '0303-9012345', 'Electrical Engineering', 3, 4),
('EE-2023-100', 'Kamal Khan', 'kamal.khan@university.edu', '0303-0123456', 'Electrical Engineering', 3, 4),
('EE-2023-101', 'Noreen Malik', 'noreen.malik@university.edu', '0304-1234567', 'Electrical Engineering', 3, 4),
('EE-2023-102', 'Tahir Ahmed', 'tahir.ahmed@university.edu', '0304-2345678', 'Electrical Engineering', 3, 4),
('EE-2023-103', 'Sara Yousaf', 'sara.yousaf@university.edu', '0304-3456789', 'Electrical Engineering', 3, 4),
('EE-2023-104', 'Babar Khan', 'babar.khan@university.edu', '0304-4567890', 'Electrical Engineering', 3, 4),
('EE-2023-105', 'Hira Raza', 'hira.raza@university.edu', '0304-5678901', 'Electrical Engineering', 3, 4),

-- Electrical Engineering - Semester 6 (30 students)
('EE-2022-106', 'Ali Ahmed', 'ali.ahmed@university.edu', '0305-1234567', 'Electrical Engineering', 4, 6),
('EE-2022-107', 'Fatima Khan', 'fatima.khan@university.edu', '0305-2345678', 'Electrical Engineering', 4, 6),
('EE-2022-108', 'Hassan Raza', 'hassan.raza@university.edu', '0305-3456789', 'Electrical Engineering', 4, 6),
('EE-2022-109', 'Ayesha Malik', 'ayesha.malik@university.edu', '0305-4567890', 'Electrical Engineering', 4, 6),
('EE-2022-110', 'Bilal Ahmed', 'bilal.ahmed@university.edu', '0305-5678901', 'Electrical Engineering', 4, 6),
('EE-2022-111', 'Sana Javed', 'sana.javed@university.edu', '0305-6789012', 'Electrical Engineering', 4, 6),
('EE-2022-112', 'Usman Sheikh', 'usman.sheikh@university.edu', '0305-7890123', 'Electrical Engineering', 4, 6),
('EE-2022-113', 'Zainab Tariq', 'zainab.tariq@university.edu', '0305-8901234', 'Electrical Engineering', 4, 6),
('EE-2022-114', 'Hamza Iqbal', 'hamza.iqbal@university.edu', '0305-9012345', 'Electrical Engineering', 4, 6),
('EE-2022-115', 'Mariam Khan', 'mariam.khan@university.edu', '0305-0123456', 'Electrical Engineering', 4, 6),
('EE-2022-116', 'Omar Farooq', 'omar.farooq@university.edu', '0306-1234567', 'Electrical Engineering', 4, 6),
('EE-2022-117', 'Hina Aslam', 'hina.aslam@university.edu', '0306-2345678', 'Electrical Engineering', 4, 6),
('EE-2022-118', 'Abdullah Shah', 'abdullah.shah@university.edu', '0306-3456789', 'Electrical Engineering', 4, 6),
('EE-2022-119', 'Sadia Rahman', 'sadia.rahman@university.edu', '0306-4567890', 'Electrical Engineering', 4, 6),
('EE-2022-120', 'Tariq Mahmood', 'tariq.mahmood@university.edu', '0306-5678901', 'Electrical Engineering', 4, 6),
('EE-2022-121', 'Nadia Akhtar', 'nadia.akhtar@university.edu', '0306-6789012', 'Electrical Engineering', 4, 6),
('EE-2022-122', 'Kamran Ali', 'kamran.ali@university.edu', '0306-7890123', 'Electrical Engineering', 4, 6),
('EE-2022-123', 'Rabia Bashir', 'rabia.bashir@university.edu', '0306-8901234', 'Electrical Engineering', 4, 6),
('EE-2022-124', 'Saad Ahmed', 'saad.ahmed@university.edu', '0306-9012345', 'Electrical Engineering', 4, 6),
('EE-2022-125', 'Fariha Noor', 'fariha.noor@university.edu', '0306-0123456', 'Electrical Engineering', 4, 6),
('EE-2022-126', 'Waqas Hussain', 'waqas.hussain@university.edu', '0307-1234567', 'Electrical Engineering', 4, 6),
('EE-2022-127', 'Saba Ilyas', 'saba.ilyas@university.edu', '0307-2345678', 'Electrical Engineering', 4, 6),
('EE-2022-128', 'Asim Raza', 'asim.raza@university.edu', '0307-3456789', 'Electrical Engineering', 4, 6),
('EE-2022-129', 'Mehak Ali', 'mehak.ali@university.edu', '0307-4567890', 'Electrical Engineering', 4, 6),
('EE-2022-130', 'Nasir Khan', 'nasir.khan@university.edu', '0307-5678901', 'Electrical Engineering', 4, 6),
('EE-2022-131', 'Amina Sheikh', 'amina.sheikh@university.edu', '0307-6789012', 'Electrical Engineering', 4, 6),
('EE-2022-132', 'Faisal Iqbal', 'faisal.iqbal@university.edu', '0307-7890123', 'Electrical Engineering', 4, 6),
('EE-2022-133', 'Sumaira Jamil', 'sumaira.jamil@university.edu', '0307-8901234', 'Electrical Engineering', 4, 6),
('EE-2022-134', 'Haris Malik', 'haris.malik@university.edu', '0307-9012345', 'Electrical Engineering', 4, 6),
('EE-2022-135', 'Zara Ahmed', 'zara.ahmed@university.edu', '0307-0123456', 'Electrical Engineering', 4, 6),

-- Mechanical Engineering - Semester 2 (35 students)
('ME-2024-136', 'Danish Ali', 'danish.ali@university.edu', '0308-1234567', 'Mechanical Engineering', 5, 2),
('ME-2024-137', 'Saima Raza', 'saima.raza@university.edu', '0308-2345678', 'Mechanical Engineering', 5, 2),
('ME-2024-138', 'Imran Yousaf', 'imran.yousaf@university.edu', '0308-3456789', 'Mechanical Engineering', 5, 2),
('ME-2024-139', 'Noreen Akhtar', 'noreen.akhtar@university.edu', '0308-4567890', 'Mechanical Engineering', 5, 2),
('ME-2024-140', 'Shahid Mehmood', 'shahid.mehmood@university.edu', '0308-5678901', 'Mechanical Engineering', 5, 2),
('ME-2024-141', 'Tahira Bibi', 'tahira.bibi@university.edu', '0308-6789012', 'Mechanical Engineering', 5, 2),
('ME-2024-142', 'Arslan Khan', 'arslan.khan@university.edu', '0308-7890123', 'Mechanical Engineering', 5, 2),
('ME-2024-143', 'Sara Javed', 'sara.javed@university.edu', '0308-8901234', 'Mechanical Engineering', 5, 2),
('ME-2024-144', 'Noman Ali', 'noman.ali@university.edu', '0308-9012345', 'Mechanical Engineering', 5, 2),
('ME-2024-145', 'Kashif Raza', 'kashif.raza@university.edu', '0308-0123456', 'Mechanical Engineering', 5, 2),
('ME-2024-146', 'Ali Raza Khan', 'ali.raza@university.edu', '0309-1234567', 'Mechanical Engineering', 5, 2),
('ME-2024-147', 'Sadia Noor', 'sadia.noor@university.edu', '0309-2345678', 'Mechanical Engineering', 5, 2),
('ME-2024-148', 'Waseem Akram', 'waseem.akram@university.edu', '0309-3456789', 'Mechanical Engineering', 5, 2),
('ME-2024-149', 'Nazia Tahir', 'nazia.tahir@university.edu', '0309-4567890', 'Mechanical Engineering', 5, 2),
('ME-2024-150', 'Farhan Ahmed', 'farhan.ahmed@university.edu', '0309-5678901', 'Mechanical Engineering', 5, 2),
('ME-2024-151', 'Ayesha Siddiqua', 'ayesha.siddiqua@university.edu', '0309-6789012', 'Mechanical Engineering', 5, 2),
('ME-2024-152', 'Kamran Bashir', 'kamran.bashir@university.edu', '0309-7890123', 'Mechanical Engineering', 5, 2),
('ME-2024-153', 'Saba Khan', 'saba.khan@university.edu', '0309-8901234', 'Mechanical Engineering', 5, 2),
('ME-2024-154', 'Babar Ali', 'babar.ali@university.edu', '0309-9012345', 'Mechanical Engineering', 5, 2),
('ME-2024-155', 'Hina Malik', 'hina.malik@university.edu', '0309-0123456', 'Mechanical Engineering', 5, 2),
('ME-2024-156', 'Javed Iqbal', 'javed.iqbal@university.edu', '0311-1234567', 'Mechanical Engineering', 5, 2),
('ME-2024-157', 'Saima Yousaf', 'saima.yousaf@university.edu', '0311-2345678', 'Mechanical Engineering', 5, 2),
('ME-2024-158', 'Nadeem Ahmed', 'nadeem.ahmed@university.edu', '0311-3456789', 'Mechanical Engineering', 5, 2),
('ME-2024-159', 'Fouzia Tariq', 'fouzia.tariq@university.edu', '0311-4567890', 'Mechanical Engineering', 5, 2),
('ME-2024-160', 'Shahzad Khan', 'shahzad.khan@university.edu', '0311-5678901', 'Mechanical Engineering', 5, 2),
('ME-2024-161', 'Nadia Jamil', 'nadia.jamil@university.edu', '0311-6789012', 'Mechanical Engineering', 5, 2),
('ME-2024-162', 'Rizwan Ali', 'rizwan.ali@university.edu', '0311-7890123', 'Mechanical Engineering', 5, 2),
('ME-2024-163', 'Amina Bibi', 'amina.bibi@university.edu', '0311-8901234', 'Mechanical Engineering', 5, 2),
('ME-2024-164', 'Tahir Mahmood', 'tahir.mahmood@university.edu', '0311-9012345', 'Mechanical Engineering', 5, 2),
('ME-2024-165', 'Sara Khan', 'sara.khan@university.edu', '0311-0123456', 'Mechanical Engineering', 5, 2),
('ME-2024-166', 'Usman Ghani', 'usman.ghani@university.edu', '0312-1234567', 'Mechanical Engineering', 5, 2),
('ME-2024-167', 'Rabia Noor', 'rabia.noor@university.edu', '0312-2345678', 'Mechanical Engineering', 5, 2),
('ME-2024-168', 'Asad Ullah', 'asad.ullah@university.edu', '0312-3456789', 'Mechanical Engineering', 5, 2),
('ME-2024-169', 'Hira Shah', 'hira.shah@university.edu', '0312-4567890', 'Mechanical Engineering', 5, 2),
('ME-2024-170', 'Zubair Ahmed', 'zubair.ahmed@university.edu', '0312-5678901', 'Mechanical Engineering', 5, 2),

-- Software Engineering - Semester 4 (30 students)
('SE-2023-171', 'Nida Ali', 'nida.ali@university.edu', '0313-1234567', 'Software Engineering', 7, 4),
('SE-2023-172', 'Fahad Raza', 'fahad.raza@university.edu', '0313-2345678', 'Software Engineering', 7, 4),
('SE-2023-173', 'Sanaullah Khan', 'sanaullah.khan@university.edu', '0313-3456789', 'Software Engineering', 7, 4),
('SE-2023-174', 'Mariam Javed', 'mariam.javed@university.edu', '0313-4567890', 'Software Engineering', 7, 4),
('SE-2023-175', 'Bilal Yousaf', 'bilal.yousaf@university.edu', '0313-5678901', 'Software Engineering', 7, 4),
('SE-2023-176', 'Sara Ahmed Khan', 'sara.ahmed@university.edu', '0313-6789012', 'Software Engineering', 7, 4),
('SE-2023-177', 'Bilal Mahmood', 'bilal.mahmood@university.edu', '0313-7890123', 'Software Engineering', 7, 4),
('SE-2023-178', 'Noor Fatima', 'noor.fatima@university.edu', '0313-8901234', 'Software Engineering', 7, 4),
('SE-2023-179', 'Abdul Rehman', 'abdul.rehman@university.edu', '0313-9012345', 'Software Engineering', 7, 4),
('SE-2023-180', 'Sadia Batool', 'sadia.batool@university.edu', '0313-0123456', 'Software Engineering', 7, 4),
('SE-2023-181', 'Imran Ali', 'imran.ali@university.edu', '0314-1234567', 'Software Engineering', 7, 4),
('SE-2023-182', 'Ayesha Hassan', 'ayesha.hassan@university.edu', '0314-2345678', 'Software Engineering', 7, 4),
('SE-2023-183', 'Kamran Shah', 'kamran.shah@university.edu', '0314-3456789', 'Software Engineering', 7, 4),
('SE-2023-184', 'Zara Sheikh', 'zara.sheikh@university.edu', '0314-4567890', 'Software Engineering', 7, 4),
('SE-2023-185', 'Saif Ullah', 'saif.ullah@university.edu', '0314-5678901', 'Software Engineering', 7, 4),
('SE-2023-186', 'Hina Raza', 'hina.raza@university.edu', '0314-6789012', 'Software Engineering', 7, 4),
('SE-2023-187', 'Noman Khan', 'noman.khan@university.edu', '0314-7890123', 'Software Engineering', 7, 4),
('SE-2023-188', 'Saba Javed', 'saba.javed@university.edu', '0314-8901234', 'Software Engineering', 7, 4),
('SE-2023-189', 'Arham Ali', 'arham.ali@university.edu', '0314-9012345', 'Software Engineering', 7, 4),
('SE-2023-190', 'Fariha Malik', 'fariha.malik@university.edu', '0314-0123456', 'Software Engineering', 7, 4),
('SE-2023-191', 'Waqar Ahmed', 'waqar.ahmed@university.edu', '0315-1234567', 'Software Engineering', 7, 4),
('SE-2023-192', 'Nadia Yousaf', 'nadia.yousaf@university.edu', '0315-2345678', 'Software Engineering', 7, 4),
('SE-2023-193', 'Shahid Iqbal', 'shahid.iqbal@university.edu', '0315-3456789', 'Software Engineering', 7, 4),
('SE-2023-194', 'Rabia Tariq', 'rabia.tariq@university.edu', '0315-4567890', 'Software Engineering', 7, 4),
('SE-2023-195', 'Asim Khan', 'asim.khan@university.edu', '0315-5678901', 'Software Engineering', 7, 4),
('SE-2023-196', 'Mehwish Ali', 'mehwish.ali@university.edu', '0315-6789012', 'Software Engineering', 7, 4),
('SE-2023-197', 'Faisal Raza', 'faisal.raza@university.edu', '0315-7890123', 'Software Engineering', 7, 4),
('SE-2023-198', 'Sana Bibi', 'sana.bibi@university.edu', '0315-8901234', 'Software Engineering', 7, 4),
('SE-2023-199', 'Nasir Mahmood', 'nasir.mahmood@university.edu', '0315-9012345', 'Software Engineering', 7, 4),
('SE-2023-200', 'Amina Khan', 'amina.khan@university.edu', '0315-0123456', 'Software Engineering', 7, 4);