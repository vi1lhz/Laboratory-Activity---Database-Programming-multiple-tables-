-- Database Schema for Training Enrollment System
-- IPT - Integrative Programming and Technology

CREATE DATABASE IF NOT EXISTS training_db;
USE training_db;

-- Table: students
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: courses
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Table: classes
CREATE TABLE IF NOT EXISTS classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    class_code VARCHAR(20) NOT NULL,
    schedule VARCHAR(100),
    instructor VARCHAR(100),
    slots INT NOT NULL DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- Table: enrollments
CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE
);

-- Initial Sample Seed Data
INSERT INTO courses (course_code, course_name, description) VALUES
('WD101', 'Web Development Fundamentals', 'Comprehensive introduction to semantic HTML5, modern CSS3 styling, and JavaScript essentials.'),
('PHP201', 'Advanced PHP & PDO Architecture', 'Deep dive into Object-Oriented PHP, Singleton database wrappers, Repository pattern, and prepared statements.'),
('DB301', 'Relational Database Design & SQL', 'Study of normalization, relational integrity, multi-table JOINs, indexing, and ACID transaction mechanics.'),
('PY101', 'Python Data Processing', 'Practical scripting with Python for data analytics, file processing, and system automation.')
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

INSERT INTO classes (course_id, class_code, schedule, instructor, slots) VALUES
(1, 'WD101-SEC1', 'Mon/Wed 09:00 - 12:00', 'Prof. Alan Turing', 4),
(1, 'WD101-SEC2', 'Tue/Thu 13:00 - 16:00', 'Dr. Ada Lovelace', 2),
(2, 'PHP201-SEC1', 'Mon/Wed 13:00 - 16:00', 'Prof. Rasmus Lerdorf', 3),
(2, 'PHP201-SEC2', 'Sat 08:00 - 14:00', 'Prof. Grace Hopper', 0), -- Full class with 0 slots to demonstrate transaction rollback!
(3, 'DB301-SEC1', 'Fri 09:00 - 15:00', 'Dr. Edgar Codd', 5)
ON DUPLICATE KEY UPDATE schedule = VALUES(schedule);

INSERT INTO students (full_name, email, phone) VALUES
('Katherine Johnson', 'katherine.j@example.com', '555-0101'),
('Claude Shannon', 'claude.shannon@example.com', '555-0102'),
('Tim Berners-Lee', 'tim.bl@example.com', '555-0103')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO enrollments (student_id, class_id, status) VALUES
(1, 1, 'active'),
(2, 1, 'active'),
(3, 3, 'active')
ON DUPLICATE KEY UPDATE status = VALUES(status);
