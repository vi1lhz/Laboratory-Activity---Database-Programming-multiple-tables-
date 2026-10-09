<?php
// admin/reports.php -- Summary reports and analytics
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);
$stats = $repo->getSummaryStats();
$occupancy = $repo->getClassOccupancyReport();

// Additional summative aggregation: Course popularity
$courseStatsSql = "
    SELECT co.course_code, co.course_name,
           COUNT(DISTINCT c.class_id) AS total_classes,
           COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS active_students,
           COALESCE(SUM(c.slots), 0) AS total_open_slots
    FROM courses co
    LEFT JOIN classes c ON co.course_id = c.course_id
    LEFT JOIN enrollments e ON c.class_id = e.class_id
    GROUP BY co.course_id, co.course_code, co.course_name
    ORDER BY active_students DESC, co.course_name ASC
";
$courseStats = $db->query($courseStatsSql)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Executive Summary & Reports</h1>
        <div class="page-subtitle">Real-time summative queries, enrollment metrics, and capacity utilization</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Students</div>
            <div class="stat-value"><?= $stats['total_students'] ?></div>
        </div>
        <div class="stat-icon primary">👤</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Active Enrollments</div>
            <div class="stat-value"><?= $stats['active_enrollments'] ?></div>
        </div>
        <div class="stat-icon success">✓</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Cancellations</div>
            <div class="stat-value"><?= $stats['cancelled_enrollments'] ?></div>
        </div>
        <div class="stat-icon danger">✕</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Courses Catalog</div>
            <div class="stat-value"><?= $stats['total_courses'] ?></div>
        </div>
        <div class="stat-icon secondary">📚</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Open Slots</div>
            <div class="stat-value"><?= $stats['total_available_slots'] ?></div>
        </div>
        <div class="stat-icon warning">🪑</div>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Class Section Capacity & Occupancy Utilization</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Class Section</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Enrolled (Active)</th>
                    <th>Slots Left</th>
                    <th>Total Capacity</th>
                    <th>Occupancy Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($occupancy)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No class data available.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($occupancy as $oc): 
                        $capacity = (int)$oc['total_capacity'];
                        $enrolled = (int)$oc['active_enrolled'];
                        $pct = $capacity > 0 ? round(($enrolled / $capacity) * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($oc['class_code']) ?></strong></td>
                            <td><?= htmlspecialchars($oc['course_code']) ?> — <?= htmlspecialchars($oc['course_name']) ?></td>
                            <td><?= htmlspecialchars($oc['instructor']) ?></td>
                            <td><strong style="color: var(--primary);"><?= $enrolled ?></strong></td>
                            <td>
                                <?php if ($oc['remaining_slots'] <= 0): ?>
                                    <span class="badge badge-slots-full">0 (Full)</span>
                                <?php else: ?>
                                    <span class="badge badge-slots-available"><?= $oc['remaining_slots'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $capacity ?></td>
                            <td style="min-width: 140px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="flex: 1; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                                        <div style="height: 100%; width: <?= $pct ?>%; background: <?= $pct >= 100 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#10b981') ?>; border-radius: 999px;"></div>
                                    </div>
                                    <span style="font-size: 0.8rem; font-weight: 700; width: 36px; text-align: right;"><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Course Offerings & Enrollment Summary</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Total Scheduled Classes</th>
                    <th>Active Enrolled Students</th>
                    <th>Available Open Seats</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courseStats as $cs): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cs['course_code']) ?></strong></td>
                        <td><?= htmlspecialchars($cs['course_name']) ?></td>
                        <td><span class="code-badge"><?= $cs['total_classes'] ?> classes</span></td>
                        <td><strong><?= $cs['active_students'] ?> students</strong></td>
                        <td><?= $cs['total_open_slots'] ?> seats open</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
