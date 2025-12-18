<?php
session_start();
require "../common/db.php";

if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please login.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Topic</title>
</head>
<body>

<h1>Add New Topic</h1>

<form action="insert_topic.php" method="POST">
    <label>Subject:</label><br>
    <input type="text" name="subject" required><br><br>

    <label>Message:</label><br>
    <textarea name="message" required></textarea><br><br>

    <button type="submit">Add Topic</button>
</form>

<br>
<a href="index.php">⬅ Back</a>

</body>
</html>
