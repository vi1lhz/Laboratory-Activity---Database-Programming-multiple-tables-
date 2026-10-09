<?php
// includes/header.php
// Common header and navigation bar

$in_admin = (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false);
$rel_base = $in_admin ? '../' : './';
$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Enrollment System - IPT</title>
    <link rel="stylesheet" href="<?= $rel_base ?>css/style.css">
</head>
<body>
<header>
    <div class="nav-container">
        <a href="<?= $rel_base ?>index.php" class="brand-logo">
            <div class="brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <span>Training Enrollment</span>
        </a>
        <nav>
            <a href="<?= $rel_base ?>index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="<?= $rel_base ?>admin/courses.php" class="<?= $current_page === 'courses.php' ? 'active' : '' ?>">Courses</a>
            <a href="<?= $rel_base ?>admin/classes.php" class="<?= $current_page === 'classes.php' ? 'active' : '' ?>">Classes</a>
            <a href="<?= $rel_base ?>admin/students.php" class="<?= $current_page === 'students.php' ? 'active' : '' ?>">Record Student</a>
            <a href="<?= $rel_base ?>admin/enroll.php" class="<?= $current_page === 'enroll.php' ? 'active' : '' ?>">Enroll</a>
            <a href="<?= $rel_base ?>admin/enrollments.php" class="<?= $current_page === 'enrollments.php' ? 'active' : '' ?>">Enrollments</a>
            <a href="<?= $rel_base ?>admin/reports.php" class="<?= $current_page === 'reports.php' ? 'active' : '' ?>">Reports</a>
        </nav>
    </div>
</header>
<main>
