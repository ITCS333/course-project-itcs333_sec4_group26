<?php
session_start();
require"../common/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please login.");
}

if (!isset($_GET['id'])) {
    die("No topic ID specified.");
}

$topic_id = $_GET['id'];
$user_id  = $_SESSION['user_id'];
$role     = $_SESSION['role'];

// سحب الموضوع + اسم صاحبه
$stmt = $pdo->prepare("
    SELECT topics.*, users.name 
    FROM topics
    JOIN users ON topics.user_id = users.id
    WHERE topics.id = ?
");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    die("Topic not found.");
}

// سحب التعليقات + أسماء أصحابها
$stmt = $pdo->prepare("
    SELECT comments.*, users.name 
    FROM comments
    JOIN users ON comments.user_id = users.id
    WHERE comments.topic_id = ?
    ORDER BY comments.created_at ASC
");
$stmt->execute([$topic_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($topic['subject']) ?></title>
</head>
<body>

<h1><?= htmlspecialchars($topic['subject']) ?></h1>
<p><?= nl2br(htmlspecialchars($topic['message'])) ?></p>
<p><small>Posted by <?= htmlspecialchars($topic['name']) ?></small></p>

<hr>

<h3>Comments</h3>

<?php if (empty($comments)): ?>
    <p>No comments yet.</p>
<?php endif; ?>

<?php foreach ($comments as $comment): ?>
    <p>
        <?= nl2br(htmlspecialchars($comment['message'])) ?><br>
        <small>By <?= htmlspecialchars($comment['name']) ?></small>
    </p>

    <?php
    // حذف التعليق (Admin أو صاحبه فقط)
    if ($role === 'Admin' || $comment['user_id'] == $user_id):
    ?>
        <a href="delete_comment.php?id=<?= $comment['id'] ?>&topic_id=<?= $topic_id ?>"
           onclick="return confirm('Delete this comment?')">
           Delete
        </a>
    <?php endif; ?>

    <hr>
<?php endforeach; ?>

<h3>Add a Comment</h3>

<form action="insert_comment.php" method="POST">
    <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
    <textarea name="message" required></textarea><br><br>
    <button type="submit">Add Comment</button>
</form>

<br>
<a href="index.php">⬅ Back</a>

</body>
</html>
