<?php
session_start();
require "../common/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

if (!isset($_GET['id'])) {
    die("No topic ID specified.");
}

$topic_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // Admin أو Student

// إذا Admin → حذف مباشر
if ($role === 'Admin') {
    $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
    $stmt->execute([$topic_id]);
} else {
    // Student → يحذف فقط مواضيعه
    $stmt = $pdo->prepare(
        "DELETE FROM topics WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$topic_id, $user_id]);
}

header("Location: index.php");
exit;
?>
