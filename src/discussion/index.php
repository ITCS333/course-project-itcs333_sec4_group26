<?php
session_start();
require "../common/db.php";

// تأكد أن المستخدم مسجل دخول
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Access denied. Please login.");
}

// تحقق من نوع الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    die("Only GET requests are allowed.");
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Board</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; }
        a { color: blue; text-decoration: none; }
    </style>
</head>
<body>

<h1>Discussion Board</h1>

<a href="add_topic.php">➕ Add New Topic</a>

<table>
    <tr>
        <th>Subject</th>
        <th>Created At</th>
        <th>Action</th>
    </tr>

<?php
// جلب المواضيع من قاعدة البيانات
$stmt = $pdo->query("
    SELECT topics.*, users.name 
    FROM topics 
    JOIN users ON topics.user_id = users.id
    ORDER BY created_at DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>
            <a href='view_topic.php?id={$row['id']}'>" .
            htmlspecialchars($row['subject']) .
         "</a><br>
         <small>by " . htmlspecialchars($row['name']) . "</small>
          </td>";

    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";

    echo "<td>";

    // Admin أو صاحب الموضوع فقط
    if ($role === 'Admin' || $row['user_id'] == $user_id) {
        echo "<a href='edit_topic.php?id={$row['id']}'>Edit</a> | ";
        echo "<a href='delete_topic.php?id={$row['id']}'
              onclick=\"return confirm('Are you sure?')\">
              Delete</a>";
    } else {
        echo "-";
    }

    echo "</td>";
    echo "</tr>";
}
?>

</table>

</body>
</html>
