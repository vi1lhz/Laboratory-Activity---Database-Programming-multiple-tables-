<?php
// classes/EnrollmentRepository.php
// Data-access class that records students, enrollments and slot updates
// inside a single database transaction.

class EnrollmentRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Record a NEW student AND enroll them into a class in a single transaction.
     * 1. beginTransaction()
     * 2. check that the class still has available slots (with row locking)
     * 3. INSERT INTO students (full_name, email, phone)
     * 4. $student_id = lastInsertId()
     * 5. INSERT INTO enrollments (student_id, class_id)
     * 6. UPDATE classes SET slots = slots - 1
     * 7. commit(); otherwise rollBack()
     * 
     * @return bool True on success, false if no slots available
     * @throws Exception On transactional database error
     */
    public function recordStudent($full_name, $email, $phone, $class_id) {
        try {
            $this->db->beginTransaction();

            // 1. Check if class exists and verify available slots
            $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :class_id FOR UPDATE");
            $stmt->execute([':class_id' => $class_id]);
            $class = $stmt->fetch();

            if (!$class || (int)$class['slots'] <= 0) {
                // No slots available -> rollback immediately to guarantee integrity
                $this->db->rollBack();
                return false;
            }

            // 2. Insert new student
            $stmt = $this->db->prepare("INSERT INTO students (full_name, email, phone) VALUES (:full_name, :email, :phone)");
            $stmt->execute([
                ':full_name' => $full_name,
                ':email'     => $email,
                ':phone'     => $phone
            ]);
            $student_id = $this->db->lastInsertId();

            // 3. Insert enrollment with 'active' status
            $stmt = $this->db->prepare("INSERT INTO enrollments (student_id, class_id, status) VALUES (:student_id, :class_id, 'active')");
            $stmt->execute([
                ':student_id' => $student_id,
                ':class_id'   => $class_id
            ]);

            // 4. Decrement available slots in the class
            $stmt = $this->db->prepare("UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id");
            $stmt->execute([':class_id' => $class_id]);

            // 5. Commit all changes atomically
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Enroll an EXISTING student into a class inside a transaction.
     * Prevents duplicate active enrollment and manages slot decrement.
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function enroll($student_id, $class_id) {
        try {
            $this->db->beginTransaction();

            // Check if student is already enrolled in this exact class section
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = :student_id AND class_id = :class_id AND status = 'active'");
            $stmt->execute([
                ':student_id' => $student_id,
                ':class_id'   => $class_id
            ]);
            if ($stmt->fetchColumn() > 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Student is already enrolled in this class.'];
            }

            // Check that slots are available
            $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :class_id FOR UPDATE");
            $stmt->execute([':class_id' => $class_id]);
            $class = $stmt->fetch();

            if (!$class || (int)$class['slots'] <= 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'No slots available for this class.'];
            }

            // Insert enrollment
            $stmt = $this->db->prepare("INSERT INTO enrollments (student_id, class_id, status) VALUES (:student_id, :class_id, 'active')");
            $stmt->execute([
                ':student_id' => $student_id,
                ':class_id'   => $class_id
            ]);

            // Decrement slots
            $stmt = $this->db->prepare("UPDATE classes SET slots = slots - 1 WHERE class_id = :class_id");
            $stmt->execute([':class_id' => $class_id]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Student successfully enrolled!'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancel an enrollment inside a transaction.
     * 1. UPDATE enrollments SET status = 'cancelled'
     * 2. UPDATE classes SET slots = slots + 1
     * 
     * @return bool True on success, false if not found or already cancelled
     */
    public function cancel($enrollment_id) {
        try {
            $this->db->beginTransaction();

            // Check enrollment exists and is active
            $stmt = $this->db->prepare("SELECT class_id, status FROM enrollments WHERE enrollment_id = :id FOR UPDATE");
            $stmt->execute([':id' => $enrollment_id]);
            $enrollment = $stmt->fetch();

            if (!$enrollment || $enrollment['status'] !== 'active') {
                $this->db->rollBack();
                return false;
            }

            $class_id = $enrollment['class_id'];

            // 1. Update enrollment status to cancelled
            $stmt = $this->db->prepare("UPDATE enrollments SET status = 'cancelled' WHERE enrollment_id = :id");
            $stmt->execute([':id' => $enrollment_id]);

            // 2. Restore slot count on the class
            $stmt = $this->db->prepare("UPDATE classes SET slots = slots + 1 WHERE class_id = :class_id");
            $stmt->execute([':class_id' => $class_id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Fetch all enrollments joined with students, classes, and courses.
     * Supports search keywords, status filter, and class filter.
     */
    public function allWithDetails($search = '', $status = '', $class_id = 0) {
        $sql = "SELECT e.enrollment_id, e.enrollment_date, e.status,
                       s.student_id, s.full_name, s.email, s.phone,
                       c.class_id, c.class_code, c.schedule, c.instructor, c.slots,
                       co.course_id, co.course_code, co.course_name
                FROM enrollments e
                JOIN students s ON e.student_id = s.student_id
                JOIN classes c ON e.class_id = c.class_id
                JOIN courses co ON c.course_id = co.course_id
                WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (s.full_name LIKE :search OR s.email LIKE :search OR co.course_name LIKE :search OR c.class_code LIKE :search)";
            $params[':search'] = "%" . trim($search) . "%";
        }

        if (!empty($status)) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $status;
        }

        if ($class_id > 0) {
            $sql .= " AND c.class_id = :class_id";
            $params[':class_id'] = $class_id;
        }

        $sql .= " ORDER BY e.enrollment_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Summative reporting metrics
     */
    public function getSummaryStats() {
        $stats = [];

        $stats['total_students'] = (int)$this->db->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $stats['total_courses'] = (int)$this->db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        $stats['total_classes'] = (int)$this->db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
        $stats['active_enrollments'] = (int)$this->db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'active'")->fetchColumn();
        $stats['cancelled_enrollments'] = (int)$this->db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'cancelled'")->fetchColumn();
        $stats['total_available_slots'] = (int)$this->db->query("SELECT COALESCE(SUM(slots), 0) FROM classes")->fetchColumn();

        return $stats;
    }

    /**
     * Summative report: Class enrollment capacity breakdown
     */
    public function getClassOccupancyReport() {
        $sql = "SELECT c.class_id, c.class_code, c.schedule, c.instructor, c.slots AS remaining_slots,
                       co.course_code, co.course_name,
                       COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS active_enrolled,
                       COUNT(CASE WHEN e.status = 'cancelled' THEN 1 END) AS cancelled_count,
                       (COUNT(CASE WHEN e.status = 'active' THEN 1 END) + c.slots) AS total_capacity
                FROM classes c
                JOIN courses co ON c.course_id = co.course_id
                LEFT JOIN enrollments e ON c.class_id = e.class_id
                GROUP BY c.class_id, c.class_code, c.schedule, c.instructor, c.slots, co.course_code, co.course_name
                ORDER BY co.course_name ASC, c.class_code ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
