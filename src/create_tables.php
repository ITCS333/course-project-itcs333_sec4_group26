<?php
require 'db.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS topics(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject TEXT,
    message TEXT
);");
echo "Tables created.";
?>
