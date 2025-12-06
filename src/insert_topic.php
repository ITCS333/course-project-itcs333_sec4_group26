<?php
require 'db.php';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $stmt = $pdo->prepare("INSERT INTO topics(subject,message) VALUES(?,?)");
    $stmt->execute([$_POST['subject'], $_POST['message']]);
    header('Location: index.php');
}
?>
