<?php
session_start();
require_once "../common/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $topic_id   = $_POST['topic_id'];
    $reply_text = trim($_POST['reply_text']);
    $user_id    = $_SESSION['user_id'];

    if (empty($reply_text)) {
        die("Reply cannot be empty.");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO replies (topic_id, user_id, reply_text) VALUES (?, ?, ?)"
    );
    $stmt->execute([$topic_id, $user_id, $reply_text]);

    header("Location: view_topic.php?id=" . $topic_id);
    exit;
}
