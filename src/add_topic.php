<?php
include 'db.php';

//  عشان اتأكد من تسجيل الدخول
if(!isset($_SESSION['user_id'])){
    die("You must be logged in to add a topic.");
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO topics (user_id, subject, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $subject, $message]);

    header("Location: index.php");
    exit;
}
?>

<h2>Add New Topic</h2>
<form method="post">
    <input type="text" name="subject" placeholder="Subject" required><br><br>
    <textarea name="message" placeholder="Message" required></textarea><br><br>
    <button type="submit">Add Topic</button>
</form>
