<?php
// admin/courses.php -- Manage courses (CRUD)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Course.php';

$courseModel = new Course($db);
$message = '';
$messageType = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        if ($courseModel->delete($delId)) {
            $message = "Course deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to delete course.";
            $messageType = "danger";
        }
    } catch (PDOException $e) {
        $message = "Cannot delete course: Associated class sections or enrollments exist.";
        $messageType = "danger";
    }
}

// Handle Add / Edit Course Submission
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editCourse = $editId > 0 ? $courseModel->find($editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $code = trim($_POST['course_code'] ?? '');
    $name = trim($_POST['course_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($code) || empty($name)) {
        $message = "Course code and course name are required fields.";
        $messageType = "danger";
    } else {
        try {
            if ($action === 'edit') {
                $targetId = (int)$_POST['course_id'];
                $courseModel->update($targetId, $code, $name, $desc);
                $message = "Course updated successfully!";
                $messageType = "success";
                $editId = 0;
                $editCourse = null;
            } else {
                $courseModel->create($code, $name, $desc);
                $message = "Course registered successfully!";
                $messageType = "success";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Error: Course code '{$code}' already exists. Please use a unique code.";
            } else {
                $message = "Database Error: " . $e->getMessage();
            }
            $messageType = "danger";
        }
    }
}

// Fetch all courses
$courses = $courseModel->all();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Course Management</h1>
        <div class="page-subtitle">Define and maintain the academic course catalog</div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?>">
        <div class="alert-icon"><?= $messageType === 'success' ? '✓' : '⚠' ?></div>
        <div><?= htmlspecialchars($message) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">
        <span><?= $editCourse ? 'Edit Course: ' . htmlspecialchars($editCourse['course_code']) : 'Add New Course' ?></span>
        <?php if ($editCourse): ?>
            <a href="courses.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h2>
    <form method="POST" action="courses.php<?= $editCourse ? '?edit=' . $editCourse['course_id'] : '' ?>">
        <input type="hidden" name="action" value="<?= $editCourse ? 'edit' : 'add' ?>">
        <?php if ($editCourse): ?>
            <input type="hidden" name="course_id" value="<?= $editCourse['course_id'] ?>">
        <?php endif; ?>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="course_code">Course Code *</label>
                <input type="text" id="course_code" name="course_code" required
                       placeholder="e.g. WD101" 
                       value="<?= htmlspecialchars($editCourse['course_code'] ?? '') ?>">
                <div class="form-hint">Unique identifier for this course</div>
            </div>

            <div class="form-group">
                <label for="course_name">Course Name *</label>
                <input type="text" id="course_name" name="course_name" required
                       placeholder="e.g. Web Development Fundamentals" 
                       value="<?= htmlspecialchars($editCourse['course_name'] ?? '') ?>">
            </div>

            <div class="form-group full-width">
                <label for="description">Course Description</label>
                <textarea id="description" name="description" placeholder="Provide syllabus, objectives, or requirements..."><?= htmlspecialchars($editCourse['description'] ?? '') ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <?= $editCourse ? 'Update Course' : 'Create Course' ?>
        </button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">Active Courses Catalog (<?= count($courses) ?> Total)</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No courses registered yet. Add your first course above.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $c): ?>
                        <tr>
                            <td><span class="code-badge">#<?= $c['course_id'] ?></span></td>
                            <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                            <td><?= htmlspecialchars($c['course_name']) ?></td>
                            <td><?= htmlspecialchars($c['description'] ?? '—') ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="courses.php?edit=<?= $c['course_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <a href="courses.php?delete=<?= $c['course_id'] ?>" 
                                       class="btn btn-danger-outline btn-sm"
                                       onclick="return confirm('Are you sure you want to delete course <?= htmlspecialchars(addslashes($c['course_code'])) ?>?');">
                                       Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
