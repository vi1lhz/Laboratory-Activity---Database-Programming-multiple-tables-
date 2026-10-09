<?php
// classes/Course.php
// Course entity and data access class

class Course {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Return all courses ordered by course_name
     */
    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM courses ORDER BY course_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Return one course by id using a prepared statement
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE course_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Insert a new course
     */
    public function create($code, $name, $description) {
        $stmt = $this->db->prepare("INSERT INTO courses (course_code, course_name, description) VALUES (:code, :name, :description)");
        return $stmt->execute([
            ':code' => $code,
            ':name' => $name,
            ':description' => $description
        ]);
    }

    /**
     * Class diagram method alias
     */
    public function addCourse($code, $name, $description) {
        return $this->create($code, $name, $description);
    }

    /**
     * Update an existing course
     */
    public function update($id, $code, $name, $description) {
        $stmt = $this->db->prepare("UPDATE courses SET course_code = :code, course_name = :name, description = :description WHERE course_id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':code' => $code,
            ':name' => $name,
            ':description' => $description
        ]);
    }

    /**
     * Class diagram method alias
     */
    public function updateCourse($id, $code, $name, $description) {
        return $this->update($id, $code, $name, $description);
    }

    /**
     * Delete a course by id
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE course_id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
