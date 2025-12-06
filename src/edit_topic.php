<?php
require 'db.php';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $stmt = $pdo->prepare("UPDATE topics SET subject=?, message=? WHERE id=?");
    $stmt->execute([$_POST['subject'], $_POST['message'], $_POST['id']]);
    header('Location: index.php');
} else if(isset($_GET['id'])){
    $stmt = $pdo->prepare("SELECT * FROM topics WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<h2>Edit Topic</h2>
<form method="post" action="edit_topic.php">
    <input type="hidden" name="id" value="<?= $topic['id'] ?>">
    <input type="text" name="subject" value="<?= htmlspecialchars($topic['subject']) ?>" required><br>
    <textarea name="message" required><?= htmlspecialchars($topic['message']) ?></textarea><br>
    <button type="submit">Update Topic</button>
</form>
