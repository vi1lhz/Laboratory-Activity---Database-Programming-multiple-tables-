<?php
// admin/students.php -- Student recording form (multi-table atomic transaction)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Student.php';

$repo = new EnrollmentRepository($db);
$classes = new ClassSection($db);
$studentModel = new Student($db);

$classList = $classes->allWithCourse(); // for the class dropdown

$message = '';
$messageType = '';
$lastRegistered = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);

    // Validate inputs
    if (empty($full_name)) {
        $message = "Validation Error: Student full name is required.";
        $messageType = "danger";
    } elseif ($class_id <= 0) {
        $message = "Validation Error: Please select a valid class section.";
        $messageType = "danger";
    } else {
        // Execute atomic multi-table transaction via repository
        try {
            $success = $repo->recordStudent($full_name, $email, $phone, $class_id);

            if ($success) {
                // Find selected class info for detailed feedback
                $targetClass = $classes->find($class_id);
                $message = "Success! Student '{$full_name}' was registered and enrolled into [{$targetClass['class_code']} - {$targetClass['course_name']}]. Class slot decremented (Remaining: {$targetClass['slots']}).";
                $messageType = "success";
                $lastRegistered = [
                    'name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'class' => $targetClass['class_code'] . ' (' . $targetClass['course_name'] . ')'
                ];
                // Refresh class list to display updated slot counts
                $classList = $classes->allWithCourse();
            } else {
                $targetClass = $classes->find($class_id);
                $className = $targetClass ? $targetClass['class_code'] : "ID #$class_id";
                $message = "Transaction Rolled Back: No slots available in class '{$className}'. The transaction was rolled back and no student or enrollment records were created.";
                $messageType = "danger";
            }
        } catch (Exception $e) {
            $message = "System Transaction Failure: " . $e->getMessage() . " (All operations were rolled back)";
            $messageType = "danger";
        }
    }
}

// Fetch all registered students
$allStudents = $studentModel->all();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Record Student & Enroll</h1>
        <div class="page-subtitle">Multi-table coordinated operation wrapped in an ACID database transaction</div>
    </div>
</div>

<div class="workflow-box">
    <strong>Transactional Enrollment Workflow:</strong>
    <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.25rem;">
        This single action executes a 3-step atomic transaction: verifies seat availability, creates student record, creates enrollment junction record, and decrements available class slots. If seats are full or any step fails, the entire transaction rolls back.
    </p>
    <div class="workflow-steps">
        <div class="workflow-step">
            <div class="step-num">1</div>
            <div><strong>Verify Capacity</strong></div>
            <div style="font-size: 0.775rem; color: var(--text-muted);">Row-locked slot check</div>
        </div>
        <div class="workflow-step">
            <div class="step-num">2</div>
            <div><strong>Insert Student</strong></div>
            <div style="font-size: 0.775rem; color: var(--text-muted);">Generate new student_id</div>
        </div>
        <div class="workflow-step">
            <div class="step-num">3</div>
            <div><strong>Create Enrollment</strong></div>
            <div style="font-size: 0.775rem; color: var(--text-muted);">Link student to class</div>
        </div>
        <div class="workflow-step">
            <div class="step-num">4</div>
            <div><strong>Decrement Slot</strong></div>
            <div style="font-size: 0.775rem; color: var(--text-muted);">Atomic seat deduction</div>
        </div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?>" id="alert-message">
        <div class="alert-icon"><?= $messageType === 'success' ? '✓' : '⚠' ?></div>
        <div>
            <strong><?= $messageType === 'success' ? 'Enrollment Confirmed' : 'Action Failed / Rolled Back' ?>:</strong>
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Student Registration & Class Enrollment Form</h2>
    <form method="POST" action="students.php">
        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Student Full Name *</label>
                <input type="text" id="full_name" name="full_name" required
                       placeholder="e.g. Marie Curie" 
                       value="<?= isset($_POST['full_name']) && $messageType !== 'success' ? htmlspecialchars($_POST['full_name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" 
                       placeholder="e.g. marie.curie@example.com" 
                       value="<?= isset($_POST['email']) && $messageType !== 'success' ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       placeholder="e.g. +1 555-0192" 
                       value="<?= isset($_POST['phone']) && $messageType !== 'success' ? htmlspecialchars($_POST['phone']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="class_id">Select Class Section *</label>
                <select id="class_id" name="class_id" required>
                    <option value="">-- Choose Class Section --</option>
                    <?php foreach ($classList as $cls): ?>
                        <option value="<?= $cls['class_id'] ?>" 
                            <?= (isset($_POST['class_id']) && (int)$_POST['class_id'] === (int)$cls['class_id']) ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($cls['class_code']) ?>] <?= htmlspecialchars($cls['course_name']) ?> 
                            — Instructor: <?= htmlspecialchars($cls['instructor']) ?> 
                            — (<?= $cls['slots'] ?> slots available <?= $cls['slots'] <= 0 ? '• FULL' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">Classes with 0 slots will trigger the transaction rollback protection</div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" id="btn-submit-student">
            Record Student & Enroll
        </button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">Registered Students (<?= count($allStudents) ?> Total)</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allStudents)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No students registered in the database yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allStudents as $st): ?>
                        <tr>
                            <td><span class="code-badge">#<?= $st['student_id'] ?></span></td>
                            <td><strong><?= htmlspecialchars($st['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($st['email'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($st['phone'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($st['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
