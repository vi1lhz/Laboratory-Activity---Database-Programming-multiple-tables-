<?php
// index.php -- Landing page
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);
$stats = $repo->getSummaryStats();

require_once __DIR__ . '/includes/header.php';
?>

<div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); color: white; border-radius: var(--radius-xl); padding: 3rem 2.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-xl); position: relative; overflow: hidden;">
    <div style="max-width: 720px; position: relative; z-index: 2;">
        <span style="display: inline-block; background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 1rem;">
            IPT — Integrative Programming and Technology
        </span>
        <h1 style="font-size: 2.5rem; font-weight: 800; line-height: 1.15; margin-bottom: 1rem; letter-spacing: -0.03em;">
            Training Enrollment System
        </h1>
        <p style="font-size: 1.05rem; opacity: 0.9; margin-bottom: 1.75rem; line-height: 1.6;">
            A multi-table, database-driven PHP PDO web application implementing transactional data integrity, 
            the Repository design pattern, Singleton database management, and relational JOIN operations.
        </p>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="admin/students.php" class="btn btn-primary" style="background: #ffffff; color: #3730a3; font-weight: 700; box-shadow: 0 4px 14px rgba(0,0,0,0.15);">
                + Record & Enroll Student
            </a>
            <a href="admin/enrollments.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.25);">
                View Enrollments
            </a>
            <a href="admin/reports.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.15); color: white; border-color: rgba(255,255,255,0.25);">
                Summary Reports
            </a>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Courses Catalog</div>
            <div class="stat-value"><?= $stats['total_courses'] ?></div>
        </div>
        <div class="stat-icon secondary">📚</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Scheduled Classes</div>
            <div class="stat-value"><?= $stats['total_classes'] ?></div>
        </div>
        <div class="stat-icon primary">🏛️</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Registered Students</div>
            <div class="stat-value"><?= $stats['total_students'] ?></div>
        </div>
        <div class="stat-icon warning">👤</div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Active Enrollments</div>
            <div class="stat-value"><?= $stats['active_enrollments'] ?></div>
        </div>
        <div class="stat-icon success">✓</div>
    </div>
</div>

<div class="card">
    <h2 class="card-title">System Modules & Management</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
        <div style="padding: 1.25rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--primary);">📚 Course Catalog</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                Manage training courses, curriculum descriptions, and unique course codes with full CRUD operations.
            </p>
            <a href="admin/courses.php" class="btn btn-secondary btn-sm">Manage Courses &rarr;</a>
        </div>

        <div style="padding: 1.25rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--secondary);">🏛️ Class Schedules & Slots</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                Schedule class sections, assign instructors, set timings, and monitor real-time available seat capacities.
            </p>
            <a href="admin/classes.php" class="btn btn-secondary btn-sm">Manage Classes &rarr;</a>
        </div>

        <div style="padding: 1.25rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #10b981;">⚡ Record Student (Transaction)</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                Coordinated atomic enrollment: creates student, inserts enrollment junction record, and decrements slot.
            </p>
            <a href="admin/students.php" class="btn btn-secondary btn-sm">Record Student &rarr;</a>
        </div>

        <div style="padding: 1.25rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #f59e0b;">➕ Existing Student Enrollment</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                Enroll existing registered participants into additional classes while checking duplicate enrollment and slots.
            </p>
            <a href="admin/enroll.php" class="btn btn-secondary btn-sm">Enroll Student &rarr;</a>
        </div>

        <div style="padding: 1.25rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #6366f1;">📋 Enrollments & Cancellations</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                Multi-table JOIN records with live search, status filters, and transactional seat restoration upon cancellation.
            </p>
            <a href="admin/enrollments.php" class="btn btn-secondary btn-sm">View Enrollments &rarr;</a>
        </div>

        <div style="padding: 1.25rem; border: 1px solid var(--card-border); border-radius: var(--radius-md); background: #ffffff;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #ec4899;">📊 Analytics & Summative Reports</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                View occupancy percentages, class utilization rates, and course popularity breakdown across the system.
            </p>
            <a href="admin/reports.php" class="btn btn-secondary btn-sm">View Reports &rarr;</a>
        </div>
    </div>
</div>

<div class="workflow-box">
    <strong>Architectural Highlights:</strong>
    <ul style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem; padding-left: 1.25rem; line-height: 1.8;">
        <li><strong>Database Singleton Pattern:</strong> Single shared PDO instance prevents connection proliferation and maintains central connection attributes.</li>
        <li><strong>Enrollment Repository Pattern:</strong> Encapsulates multi-table operations and transactional boundaries cleanly away from presentation scripts.</li>
        <li><strong>ACID Transactions:</strong> Uses <code>beginTransaction()</code>, <code>commit()</code>, and <code>rollBack()</code> to ensure no orphaned students or inaccurate slot counts.</li>
        <li><strong>Prepared Statements:</strong> 100% of SQL queries utilize parameterized PDO statements to completely neutralize SQL injection vulnerabilities.</li>
    </ul>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
