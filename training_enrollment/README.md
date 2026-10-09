# Training Enrollment System (Multi-Table PHP PDO Application)
**Course:** IPT — Integrative Programming and Technology  
**Assignment:** Laboratory Activity: Training Enrollment System  

---

## 1. Project Overview
The **Training Enrollment System** is a database-driven web application built with PHP and PDO. It models an academic/vocational training program enrollment workflow where entities (**Students**, **Courses**, **Classes**, and **Enrollments**) are relationally linked and must remain consistent under high concurrency and multi-step transactions.

Key capabilities include:
- **Relational Integrity:** Multi-table relational schema utilizing primary keys, foreign keys with cascading integrity constraints, and unique constraints.
- **ACID Database Transactions:** Coordinated multi-operation workflows (`recordStudent`, `enroll`, and `cancel`) guaranteeing data consistency.
- **Slot Capacity Enforcement & Rollbacks:** Strict checking of available seats preventing over-enrollment. If capacity is exhausted or any step fails, the entire transaction is rolled back cleanly.
- **SQL Injection Defense:** 100% prepared parameterized PDO statements across all queries.
- **Summative Reporting:** Aggregate metrics, seat utilization percentages, and course popularity insights.

---

## 2. Design Patterns Implemented & Architectural Rationale

### A. Singleton Pattern (`classes/Database.php`)
* **Implementation:** The `Database` class extends PHP's native `PDO` class with a private constructor and a static `getInstance()` method.
* **Why it was used:**
  1. **Single Shared Connection:** Prevents redundant database handshakes and connection leaks across multiple models during a single HTTP request lifecycle.
  2. **Consistent Attribute Configuration:** Guarantees that global PDO attributes (such as `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` and `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`) are uniformly enforced across the application.
  3. **Resource Efficiency:** Minimizes socket overhead on the MySQL server daemon.

### B. Repository Pattern (`classes/EnrollmentRepository.php`)
* **Implementation:** Mediates between the domain entities/presentation layer and the data mapping layer for complex multi-table actions.
* **Why it was used:**
  1. **Separation of Concerns:** Business logic (such as checking slot capacity, initiating transactions, updating junction records, and decrementing seats) is completely isolated from HTTP presentation scripts in `admin/`.
  2. **Atomic Transaction Encapsulation:** Encapsulates `beginTransaction()`, `commit()`, and `rollBack()` within coherent repository methods (`recordStudent`, `enroll`, `cancel`).
  3. **Centralized Query Maintenance:** Multi-table `JOIN` statements and summative reporting aggregations are maintained in one place, avoiding duplicate SQL strings.

---

## 3. Database Schema & Relational Structure

The relational database `training_db` consists of four normalized tables:

1. **`courses`**:
   - `course_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
   - `course_code` (VARCHAR(20), UNIQUE, NOT NULL)
   - `course_name` (VARCHAR(100), NOT NULL)
   - `description` (TEXT)

2. **`classes`**:
   - `class_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
   - `course_id` (INT, FOREIGN KEY &rarr; `courses.course_id`)
   - `class_code` (VARCHAR(20), NOT NULL)
   - `schedule` (VARCHAR(100))
   - `instructor` (VARCHAR(100))
   - `slots` (INT, NOT NULL DEFAULT 0)

3. **`students`**:
   - `student_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
   - `full_name` (VARCHAR(100), NOT NULL)
   - `email` (VARCHAR(100))
   - `phone` (VARCHAR(30))
   - `created_at` (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)

4. **`enrollments`**:
   - `enrollment_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
   - `student_id` (INT, FOREIGN KEY &rarr; `students.student_id`)
   - `class_id` (INT, FOREIGN KEY &rarr; `classes.class_id`)
   - `enrollment_date` (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
   - `status` (VARCHAR(20) DEFAULT 'active')

---

## 4. Transactional Mechanics & Workflow

### Multi-Step Student Recording (`recordStudent`)
When an administrator records a student and enrolls them into a class:
1. `beginTransaction()` is initiated.
2. The target class section is queried using `SELECT slots FROM classes WHERE class_id = :class_id FOR UPDATE`.
3. If `slots <= 0`, `rollBack()` is executed immediately and the operation fails safely.
4. If seats are available:
   - `INSERT INTO students (full_name, email, phone)` &rarr; retrieves new `student_id`.
   - `INSERT INTO enrollments (student_id, class_id, status)` with status `'active'`.
   - `UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id`.
5. `commit()` applies all changes atomically.

### Transactional Cancellation (`cancel`)
When an enrollment is cancelled:
1. `beginTransaction()` is initiated.
2. The active enrollment is verified and locked (`FOR UPDATE`).
3. `UPDATE enrollments SET status = 'cancelled' WHERE enrollment_id = :id`.
4. `UPDATE classes SET slots = slots + 1 WHERE class_id = :class_id` restores the class capacity.
5. `commit()` completes the operation.

---

## 5. Folder Organization
```
training_enrollment/
|-- config/
|   `-- db.php                 # Creates the single PDO connection instance
|-- classes/
|   |-- Database.php           # CORE: PDO wrapper (Singleton pattern)
|   |-- Pet.php                # CORE: Reference PDO CRUD example
|   |-- Student.php            # Student entity data access
|   |-- Course.php             # Course entity data access
|   |-- ClassSection.php       # Class section data access
|   |-- Enrollment.php         # Enrollment junction entity
|   `-- EnrollmentRepository.php # Repository with atomic transactions & JOINs
|-- admin/
|   |-- courses.php            # Manage courses (CRUD)
|   |-- classes.php            # Manage class schedules and slots
|   |-- students.php           # Student recording form (multi-table transaction)
|   |-- enroll.php             # Enroll existing student (transactional)
|   |-- enrollments.php        # List enrollments (multi-table JOINs) & cancellation
|   `-- reports.php            # Summary analytics & capacity utilization
|-- includes/
|   |-- header.php             # Common header & navigation
|   `-- footer.php             # Common footer
|-- css/
|   `-- style.css              # Custom modern UI styling
|-- sql/
|   `-- schema.sql             # Database schema + seed data
|-- training_db.sql            # Full SQL export
|-- README.md                  # Project documentation & setup guide
`-- index.php                  # Landing page dashboard
```

---

## 6. Installation & Setup Instructions

### Prerequisites
- **XAMPP** (with Apache and MySQL / MariaDB installed and running)
- PHP 8.0+

### Step-by-Step Setup
1. **Start XAMPP Services:**
   - Launch the **XAMPP Control Panel**.
   - Start **Apache** and **MySQL** modules.

2. **Deploy the Codebase:**
   - Ensure the project folder `training_enrollment` is placed inside `C:\xampp\htdocs\`:
     ```
     C:\xampp\htdocs\training_enrollment\
     ```

3. **Verify Database:**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`).
   - If `training_db` is not already imported, create database `training_db` and import `sql/schema.sql` or `training_db.sql`.

4. **Access Application in Browser:**
   - Open your browser and navigate to:
     ```
     http://localhost/training_enrollment/
     ```

---

## 7. Evidence & Screenshot Verification Checklist (Section 11)

| # | Required Screenshot | URL to Capture | Verification Notes |
|---|-------------------|----------------|-------------------|
| **1** | Course Management | `http://localhost/training_enrollment/admin/courses.php` | Shows the active course list table with course codes and descriptions. |
| **2** | Add / Edit Course form | `http://localhost/training_enrollment/admin/courses.php` | The form filled in, submitted, and the new/updated course visible in the list. |
| **3** | Class Management | `http://localhost/training_enrollment/admin/classes.php` | Class sections listed with course name, instructor, schedule, and slots. |
| **4** | Student recording form | `http://localhost/training_enrollment/admin/students.php` | The form filled in with Name, Email, Phone, and Class selection dropdown. |
| **5** | Successful recording | `http://localhost/training_enrollment/admin/students.php` | Confirmation banner displayed, student in table, and class slot decremented by 1. |
| **6** | Enrollments list | `http://localhost/training_enrollment/admin/enrollments.php` | Relational table showing enrollments joined with student, course, and class details. |
| **7** | Cancelled enrollment | `http://localhost/training_enrollment/admin/enrollments.php` | Enrollment shows status `cancelled` and the class slot count has been restored (+1). |
| **8** | Rollback case | `http://localhost/training_enrollment/admin/students.php` | Select the full class (`PHP201-SEC2` which has 0 slots). Shows error banner, and no partial student/enrollment rows are created. |
| **9** | Database evidence | `http://localhost/phpmyadmin` | phpMyAdmin view of `students`, `enrollments`, and `classes` tables verifying data consistency. |
