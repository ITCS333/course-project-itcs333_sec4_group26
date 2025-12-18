<?php
require_once "../common/db.php";
session_start();

// سحب معرف الموضوع من الرابط
if (!isset($_GET['id'])) {
    die("No topic ID specified.");
}

$topic_id = $_GET['id'];

// سحب بيانات الموضوع
$stmt = $pdo->prepare("SELECT * FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    die("Topic not found.");
}

// معالجة تحديث الموضوع
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $stmt = $pdo->prepare("UPDATE topics SET subject = ?, message = ? WHERE id = ?");
    $stmt->execute([$subject, $message, $topic_id]);

    header("Location: index.php");
    exit;
}
?>

<h1>Edit Topic</h1>
<form method="POST">
    <label>Subject:</label><br>
    <input type="text" name="subject" value="<?= htmlspecialchars($topic['subject']) ?>" required><br><br>

    <label>Message:</label><br>
    <textarea name="message" rows="5" cols="40" required><?= htmlspecialchars($topic['message']) ?></textarea><br><br>

    <input type="submit" value="Update Topic">
</form>
<br>
<a href="index.php">Back to Discussion Board</a>
