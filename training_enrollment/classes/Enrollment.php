<?php
// classes/Enrollment.php
// Enrollment entity class matching domain class diagram

class Enrollment {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Find enrollment by ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM enrollments WHERE enrollment_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Enroll a student into a class
     */
    public function enroll($student_id, $class_id) {
        $stmt = $this->db->prepare("INSERT INTO enrollments (student_id, class_id, status) VALUES (:student_id, :class_id, 'active')");
        return $stmt->execute([
            ':student_id' => $student_id,
            ':class_id'   => $class_id
        ]);
    }

    /**
     * Cancel an enrollment
     */
    public function cancel($enrollment_id) {
        $stmt = $this->db->prepare("UPDATE enrollments SET status = 'cancelled' WHERE enrollment_id = :id");
        return $stmt->execute([':id' => $enrollment_id]);
    }

    /**
     * Get all enrollments
     */
    public function all() {
        $stmt = $this->db->query("SELECT * FROM enrollments ORDER BY enrollment_id DESC");
        return $stmt->fetchAll();
    }
}
