<?php
session_start();
require_once "../common/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['topic_id'])) {
        die("Topic ID missing.");
    }

    $topic_id = $_POST['topic_id'];
    $message  = trim($_POST['message']);
    $user_id  = $_SESSION['user_id'];

    if (empty($message)) {
        die("Comment cannot be empty.");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO comments (topic_id, user_id, message)
         VALUES (?, ?, ?)"
    );

    $stmt->execute([$topic_id, $user_id, $message]);

    header("Location: view_topic.php?id=" . $topic_id);
    exit;
}
?>
