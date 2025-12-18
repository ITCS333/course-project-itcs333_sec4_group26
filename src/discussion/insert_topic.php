<?php
session_start();
require "../common/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare(
        "INSERT INTO topics (user_id, subject, message) 
         VALUES (?, ?, ?)"
    );

    $stmt->execute([$user_id, $subject, $message]);

    header("Location: index.php");
    exit;
}
?>
