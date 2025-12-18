<?php
require_once "../common/db.php";
session_start();

if (!isset($_GET['id']) || !isset($_GET['topic_id'])) {
    die("Comment ID or Topic ID missing.");
}

$comment_id = $_GET['id'];
$topic_id = $_GET['topic_id'];

// حذف التعليق
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);

header("Location: view_topic.php?id=" . $topic_id);
exit;
?>
