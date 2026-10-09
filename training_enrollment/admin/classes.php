<?php
// admin/classes.php -- Manage class schedules and slots
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Course.php';

$classModel = new ClassSection($db);
$courseModel = new Course($db);

$message = '';
$messageType = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        if ($classModel->delete($delId)) {
            $message = "Class section removed successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to remove class section.";
            $messageType = "danger";
        }
    } catch (PDOException $e) {
        $message = "Cannot delete class: Associated student enrollments exist.";
        $messageType = "danger";
    }
}

// Handle Add / Edit
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editClass = $editId > 0 ? $classModel->find($editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $course_id = (int)($_POST['course_id'] ?? 0);
    $code = trim($_POST['class_code'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $slots = (int)($_POST['slots'] ?? 0);

    if ($course_id <= 0 || empty($code) || empty($schedule) || empty($instructor)) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";
    } elseif ($slots < 0) {
        $message = "Slots cannot be negative.";
        $messageType = "danger";
    } else {
        try {
            if ($action === 'edit') {
                $targetId = (int)$_POST['class_id'];
                $classModel->update($targetId, $course_id, $code, $schedule, $instructor, $slots);
                $message = "Class section updated successfully!";
                $messageType = "success";
                $editId = 0;
                $editClass = null;
            } else {
                $classModel->create($course_id, $code, $schedule, $instructor, $slots);
                $message = "Class section created successfully!";
                $messageType = "success";
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Fetch lists
$classes = $classModel->allWithCourse();
$courses = $courseModel->all();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Class Schedules & Slots</h1>
        <div class="page-subtitle">Schedule offerings, instructors, and monitor real-time seat capacities</div>
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
        <span><?= $editClass ? 'Edit Class Section: ' . htmlspecialchars($editClass['class_code']) : 'Add New Class Section' ?></span>
        <?php if ($editClass): ?>
            <a href="classes.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h2>
    <form method="POST" action="classes.php<?= $editClass ? '?edit=' . $editClass['class_id'] : '' ?>">
        <input type="hidden" name="action" value="<?= $editClass ? 'edit' : 'add' ?>">
        <?php if ($editClass): ?>
            <input type="hidden" name="class_id" value="<?= $editClass['class_id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label for="course_id">Course Offering *</label>
                <select id="course_id" name="course_id" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $co): ?>
                        <option value="<?= $co['course_id'] ?>" 
                            <?= ($editClass && $editClass['course_id'] == $co['course_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($co['course_code']) ?> — <?= htmlspecialchars($co['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="class_code">Section / Class Code *</label>
                <input type="text" id="class_code" name="class_code" required
                       placeholder="e.g. WD101-SEC1" 
                       value="<?= htmlspecialchars($editClass['class_code'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="schedule">Schedule / Timings *</label>
                <input type="text" id="schedule" name="schedule" required
                       placeholder="e.g. Mon/Wed 09:00 - 12:00" 
                       value="<?= htmlspecialchars($editClass['schedule'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="instructor">Lead Instructor *</label>
                <input type="text" id="instructor" name="instructor" required
                       placeholder="e.g. Dr. Alan Turing" 
                       value="<?= htmlspecialchars($editClass['instructor'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="slots">Available Slots *</label>
                <input type="number" id="slots" name="slots" min="0" required
                       placeholder="e.g. 15" 
                       value="<?= htmlspecialchars($editClass['slots'] ?? '10') ?>">
                <div class="form-hint">Number of student seats currently available</div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <?= $editClass ? 'Update Class Section' : 'Create Class Section' ?>
        </button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">Scheduled Class Sections (<?= count($classes) ?> Total)</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Class Code</th>
                    <th>Course</th>
                    <th>Schedule</th>
                    <th>Instructor</th>
                    <th>Available Slots</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No class sections created yet. Add your first class section above.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $cls): ?>
                        <tr>
                            <td><span class="code-badge">#<?= $cls['class_id'] ?></span></td>
                            <td><strong><?= htmlspecialchars($cls['class_code']) ?></strong></td>
                            <td>
                                <div><strong><?= htmlspecialchars($cls['course_code']) ?></strong></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($cls['course_name']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($cls['schedule']) ?></td>
                            <td><?= htmlspecialchars($cls['instructor']) ?></td>
                            <td>
                                <?php if ($cls['slots'] <= 0): ?>
                                    <span class="badge badge-slots-full">Full (0 slots)</span>
                                <?php elseif ($cls['slots'] <= 2): ?>
                                    <span class="badge badge-slots-low"><?= $cls['slots'] ?> slot(s) left</span>
                                <?php else: ?>
                                    <span class="badge badge-slots-available"><?= $cls['slots'] ?> slots open</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="classes.php?edit=<?= $cls['class_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <a href="classes.php?delete=<?= $cls['class_id'] ?>" 
                                       class="btn btn-danger-outline btn-sm"
                                       onclick="return confirm('Are you sure you want to delete class <?= htmlspecialchars(addslashes($cls['class_code'])) ?>?');">
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
