<?php
// classes/ClassSection.php
// Class section entity and data access class

class ClassSection {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * JOIN classes with courses so each row shows course name and course code
     */
    public function allWithCourse() {
        $sql = "SELECT c.*, co.course_name, co.course_code 
                FROM classes c 
                JOIN courses co ON c.course_id = co.course_id 
                ORDER BY c.class_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Find a class section by ID with joined course information
     */
    public function find($id) {
        $sql = "SELECT c.*, co.course_name, co.course_code 
                FROM classes c 
                JOIN courses co ON c.course_id = co.course_id 
                WHERE c.class_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Insert a new class section
     */
    public function create($course_id, $code, $schedule, $instructor, $slots) {
        $sql = "INSERT INTO classes (course_id, class_code, schedule, instructor, slots) 
                VALUES (:course_id, :code, :schedule, :instructor, :slots)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':course_id'  => $course_id,
            ':code'       => $code,
            ':schedule'   => $schedule,
            ':instructor' => $instructor,
            ':slots'      => (int)$slots
        ]);
    }

    /**
     * Update an existing class section
     */
    public function update($class_id, $course_id, $code, $schedule, $instructor, $slots) {
        $sql = "UPDATE classes 
                SET course_id = :course_id, class_code = :code, schedule = :schedule, instructor = :instructor, slots = :slots 
                WHERE class_id = :class_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':class_id'   => $class_id,
            ':course_id'  => $course_id,
            ':code'       => $code,
            ':schedule'   => $schedule,
            ':instructor' => $instructor,
            ':slots'      => (int)$slots
        ]);
    }

    /**
     * Delete a class section by ID
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM classes WHERE class_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Return remaining slots for a class
     */
    public function getSlots($class_id) {
        $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $class_id]);
        $row = $stmt->fetch();
        return $row ? (int)$row['slots'] : 0;
    }

    /**
     * Class diagram method alias
     */
    public function availableSlots($class_id = null) {
        if ($class_id !== null) {
            return $this->getSlots($class_id);
        }
        return 0;
    }

    /**
     * Decrement available slots by 1 (ensuring slots > 0)
     */
    public function decrementSlot($class_id) {
        $stmt = $this->db->prepare("UPDATE classes SET slots = slots - 1 WHERE class_id = :id AND slots > 0");
        $stmt->execute([':id' => $class_id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Restore slot count by 1 (e.g. upon cancellation)
     */
    public function restoreSlot($class_id) {
        $stmt = $this->db->prepare("UPDATE classes SET slots = slots + 1 WHERE class_id = :id");
        return $stmt->execute([':id' => $class_id]);
    }
}
