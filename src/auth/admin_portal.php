<?php
/**
 * Admin Portal
 * Accessible only to admin users for managing students and changing password
 */

require_once 'config.php';


requireAdmin();


try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id, student_id, name, email, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC");
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching students: ' . $e->getMessage());
    $students = [];
}


$passwordMessage = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $passwordError = 'All password fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $passwordError = 'New password must be at least 8 characters long.';
    } else {
        try {
            $db = getDBConnection();
            
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($currentPassword, $user['password'])) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $_SESSION['user_id']]);
                
                $passwordMessage = 'Password changed successfully!';
            } else {
                $passwordError = 'Current password is incorrect.';
            }
        } catch (PDOException $e) {
            error_log('Password change error: ' . $e->getMessage());
            $passwordError = 'Failed to change password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - ITCS333 Course</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <script src="admin_portal.js" defer></script>
    <style>
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .header-nav h1 {
            margin: 0;
        }
        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            margin-top: 1rem;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .action-buttons button {
            margin: 0;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        .edit-btn {
            background-color: #007bff;
        }
        .delete-btn {
            background-color: #dc3545;
        }
        details {
            margin: 1.5rem 0;
        }
        summary {
            cursor: pointer;
            font-weight: bold;
            padding: 0.75rem;
            background-color: var(--primary);
            color: white;
            border-radius: 4px;
        }
        summary:hover {
            opacity: 0.9;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-info span {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header class="container">
        <div class="header-nav">
            <h1>Admin Portal</h1>
            <div class="nav-links">
                <div class="user-info">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
                </div>
                <a href="index.php" role="button" class="secondary">Home</a>
                <a href="logout.php" role="button" class="contrast">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <section>
            <h2>Change Your Password</h2>

            <?php if ($passwordMessage): ?>
                <div class="success-message"><?= htmlspecialchars($passwordMessage) ?></div>
            <?php endif; ?>

            <?php if ($passwordError): ?>
                <div class="error-message"><?= htmlspecialchars($passwordError) ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_portal.php" id="password-form">
                <input type="hidden" name="action" value="change_password">
                <fieldset>
                    <legend>Password Update</legend>

                    <label for="current-password">Current Password</label>
                    <input type="password" id="current-password" name="current_password" required>

                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new_password" minlength="8" required>

                    <label for="confirm-password">Confirm New Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" required>

                    <button type="submit" id="change">Update Password</button>
                </fieldset>
            </form>
        </section>

        <section>
            <h2>Manage Students</h2>
            
            <label for="search-input">Search Students:</label>
            <input type="text" id="search-input" placeholder="Search by name, student ID, or email...">

            <details>
                <summary>Add New Student</summary>

                <form action="#" id="add-student-form">
                    <fieldset>
                        <legend>New Student Information</legend>

                        <label for="student-name">Student Name</label>
                        <input type="text" id="student-name" name="student-name" required>

                        <label for="student-id">Student ID</label>
                        <input type="text" id="student-id" name="student-id" required>

                        <label for="student-email">Student Email</label>
                        <input type="email" id="student-email" name="student-email" required>
                        
                        <label for="default-password">Default Password</label>
                        <input type="text" id="default-password" name="default-password" value="password123">

                        <button type="submit" id="add">Add Student</button>
                    </fieldset>
                </form>
            </details>

            <h3>Registered Students (<?= count($students) ?>)</h3>

            <?php if (empty($students)): ?>
                <p><em>No students registered yet.</em></p>
            <?php else: ?>
                <table id="student-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['email']) ?></td>
                                <td><?= date('M d, Y', strtotime($student['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="edit-btn" data-id="<?= htmlspecialchars($student['student_id']) ?>">Edit</button>
                                        <button class="delete-btn" data-id="<?= htmlspecialchars($student['student_id']) ?>">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <footer class="container" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #ccc; text-align: center;">
        <p><small>&copy; <?= date('Y') ?> ITCS333 Course Management System</small></p>
    </footer>
</body>
</html>
