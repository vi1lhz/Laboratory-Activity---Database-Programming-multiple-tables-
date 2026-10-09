<?php
// admin/enroll.php -- Enroll an EXISTING student into a class
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);
$studentModel = new Student($db);
$classModel = new ClassSection($db);

$students = $studentModel->all();
$classes = $classModel->allWithCourse();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);

    // Validate IDs
    if ($student_id <= 0 || $class_id <= 0) {
        $message = "Please select both a valid student and class section.";
        $messageType = "danger";
    } else {
        try {
            $result = $repo->enroll($student_id, $class_id);

            if ($result['success']) {
                $targetStudent = $studentModel->find($student_id);
                $targetClass = $classModel->find($class_id);
                $message = "Enrollment successful! '{$targetStudent['full_name']}' is now enrolled in [{$targetClass['class_code']} - {$targetClass['course_name']}]. Remaining slots: {$targetClass['slots']}.";
                $messageType = "success";
                // Refresh class list to update displayed slots
                $classes = $classModel->allWithCourse();
            } else {
                $message = "Enrollment Failed: " . $result['message'];
                $messageType = "danger";
            }
        } catch (Exception $e) {
            $message = "Database Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Enroll Existing Student</h1>
        <div class="page-subtitle">Assign an existing student to an additional class offering</div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?>">
        <div class="alert-icon"><?= $messageType === 'success' ? '✓' : '⚠' ?></div>
        <div><?= htmlspecialchars($message) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Enrollment Form</h2>
    <form method="POST" action="enroll.php">
        <div class="form-grid">
            <div class="form-group">
                <label for="student_id">Select Student *</label>
                <select id="student_id" name="student_id" required>
                    <option value="">-- Choose Existing Student --</option>
                    <?php foreach ($students as $st): ?>
                        <option value="<?= $st['student_id'] ?>">
                            #<?= $st['student_id'] ?> — <?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['email'] ?: 'No email') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">Need to add a new student? Use <a href="students.php" style="color: var(--primary);">Record Student</a> instead.</div>
            </div>

            <div class="form-group">
                <label for="class_id">Select Class Section *</label>
                <select id="class_id" name="class_id" required>
                    <option value="">-- Choose Target Class Section --</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?= $cls['class_id'] ?>">
                            [<?= htmlspecialchars($cls['class_code']) ?>] <?= htmlspecialchars($cls['course_name']) ?> 
                            — <?= htmlspecialchars($cls['schedule']) ?> (<?= $cls['slots'] ?> slots available)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            Confirm Enrollment
        </button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">Available Class Sections & Slots</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Class Code</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Schedule</th>
                    <th>Available Slots</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $cls): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cls['class_code']) ?></strong></td>
                        <td><?= htmlspecialchars($cls['course_name']) ?></td>
                        <td><?= htmlspecialchars($cls['instructor']) ?></td>
                        <td><?= htmlspecialchars($cls['schedule']) ?></td>
                        <td>
                            <?php if ($cls['slots'] <= 0): ?>
                                <span class="badge badge-slots-full">Full (0)</span>
                            <?php elseif ($cls['slots'] <= 2): ?>
                                <span class="badge badge-slots-low"><?= $cls['slots'] ?> left</span>
                            <?php else: ?>
                                <span class="badge badge-slots-available"><?= $cls['slots'] ?> available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
