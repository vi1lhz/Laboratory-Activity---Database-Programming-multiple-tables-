<?php
// admin/enrollments.php -- List enrollments (multi-table JOINs) & cancellation
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);
$classModel = new ClassSection($db);

$message = '';
$messageType = '';

// Handle Cancellation action
if (isset($_GET['cancel'])) {
    $cancelId = (int)$_GET['cancel'];
    try {
        if ($repo->cancel($cancelId)) {
            $message = "Enrollment #{$cancelId} was cancelled successfully, and 1 available slot was restored to the class section.";
            $messageType = "success";
        } else {
            $message = "Unable to cancel enrollment #{$cancelId}. It may already be cancelled or does not exist.";
            $messageType = "danger";
        }
    } catch (Exception $e) {
        $message = "Database Error during cancellation: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Dynamic Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$filterClass = (int)($_GET['class_id'] ?? 0);

// Fetch joined enrollment rows
$rows = $repo->allWithDetails($search, $status, $filterClass);
$allClasses = $classModel->allWithCourse();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Enrollment Records</h1>
        <div class="page-subtitle">Multi-table relational JOINs across enrollments, students, classes, and courses</div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?>" id="alert-message">
        <div class="alert-icon"><?= $messageType === 'success' ? '✓' : '⚠' ?></div>
        <div><?= htmlspecialchars($message) ?></div>
    </div>
<?php endif; ?>

<div class="card">
    <form method="GET" action="enrollments.php" class="filter-bar">
        <input type="text" name="search" placeholder="Search student, course, class..." 
               value="<?= htmlspecialchars($search) ?>">

        <select name="status">
            <option value="">All Statuses</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Only</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled Only</option>
        </select>

        <select name="class_id">
            <option value="0">All Classes</option>
            <?php foreach ($allClasses as $c): ?>
                <option value="<?= $c['class_id'] ?>" <?= $filterClass === (int)$c['class_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['class_code']) ?> (<?= htmlspecialchars($c['course_code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if (!empty($search) || !empty($status) || $filterClass > 0): ?>
            <a href="enrollments.php" class="btn btn-secondary btn-sm">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Enrollment ID</th>
                    <th>Student Information</th>
                    <th>Course</th>
                    <th>Class Section & Instructor</th>
                    <th>Date Enrolled</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                            No enrollment records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><span class="code-badge">#<?= $r['enrollment_id'] ?></span></td>
                            <td>
                                <div><strong><?= htmlspecialchars($r['full_name']) ?></strong></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($r['email'] ?: 'No email') ?> | <?= htmlspecialchars($r['phone'] ?: 'No phone') ?>
                                </div>
                            </td>
                            <td>
                                <div><strong><?= htmlspecialchars($r['course_code']) ?></strong></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($r['course_name']) ?></div>
                            </td>
                            <td>
                                <div><strong><?= htmlspecialchars($r['class_code']) ?></strong></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($r['schedule']) ?> • <?= htmlspecialchars($r['instructor']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($r['enrollment_date']) ?></td>
                            <td>
                                <?php if ($r['status'] === 'active'): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-cancelled">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'active'): ?>
                                    <a href="enrollments.php?cancel=<?= $r['enrollment_id'] ?>" 
                                       class="btn btn-danger-outline btn-sm"
                                       onclick="return confirm('Cancel this enrollment for <?= htmlspecialchars(addslashes($r['full_name'])) ?>? The class slot will be restored.');">
                                       Cancel
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Slot Restored</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
