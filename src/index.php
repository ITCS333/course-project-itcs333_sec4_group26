<?php
session_start();
require 'db.php';
?>
<h1>Team26 Discussion Board - Task 5</h1>

<h2>Create Topic</h2>
<form method="post" action="insert_topic.php">
    <input type="text" name="subject" placeholder="Subject" required><br>
    <textarea name="message" placeholder="Message" required></textarea><br>
    <button type="submit">Add Topic</button>
</form>

<h2>Topics</h2>
<?php
$stmt = $pdo->query("SELECT * FROM topics ORDER BY id DESC");
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($topics as $topic){
    echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
    echo "<strong>" . htmlspecialchars($topic['subject']) . "</strong><br>";
    echo htmlspecialchars($topic['message']) . "<br>";
    echo "<a href='delete_topic.php?id=" . $topic['id'] . "'>Delete</a> ";
    echo "<a href='edit_topic.php?id=" . $topic['id'] . "'>Edit</a>";
    echo "</div>";
}
?>
