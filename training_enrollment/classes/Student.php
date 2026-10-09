<?php
// classes/Student.php
// Student entity and data access class

class Student {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Return one student by id using a prepared statement
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE student_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Return all students ordered by full_name
     */
    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM students ORDER BY full_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Insert a new student record
     */
    public function create($full_name, $email, $phone) {
        $stmt = $this->db->prepare("INSERT INTO students (full_name, email, phone) VALUES (:full_name, :email, :phone)");
        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Alias for create to match Class Diagram
     */
    public function record($full_name, $email, $phone) {
        return $this->create($full_name, $email, $phone);
    }
}
