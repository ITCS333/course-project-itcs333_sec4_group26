<?php
session_start();
require "../common/db.php"; // تأكد أن ملف الاتصال بالقاعدة مضبوط

header("Content-Type: application/json; charset=UTF-8");

// يسمح بالـ CORS إذا احتجت
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get PDO instance
$db = $pdo;

// Helper functions
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    if (!is_string($data)) return $data;
    $data = trim($data);
    $data = strip_tags($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function isValidResource($resource) {
    $allowed = ['topics', 'replies'];
    return in_array($resource, $allowed);
}

// Get request method and input
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$resource = $_GET['resource'] ?? null;
$id = $_GET['id'] ?? null;
$topic_id = $_GET['topic_id'] ?? null;

if (!isValidResource($resource)) {
    sendResponse(['error' => 'Invalid resource'], 400);
}

// =========================================
// TOPICS
// =========================================
if ($resource === 'topics') {
    if ($method === 'GET') {
        if ($id) {
            $stmt = $db->prepare("SELECT topic_id, subject, message, author, created_at FROM topics WHERE topic_id = ?");
            $stmt->execute([$id]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($topic) sendResponse(['success' => true, 'data' => $topic]);
            sendResponse(['error' => 'Topic not found'], 404);
        } else {
            $stmt = $db->query("SELECT topic_id, subject, message, author, created_at FROM topics ORDER BY created_at DESC");
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(['success' => true, 'data' => $topics]);
        }
    } elseif ($method === 'POST') {
        $topic_id = sanitizeInput($input['topic_id'] ?? '');
        $subject = sanitizeInput($input['subject'] ?? '');
        $message = sanitizeInput($input['message'] ?? '');
        $author = sanitizeInput($input['author'] ?? '');

        if (!$topic_id || !$subject || !$message || !$author) {
            sendResponse(['error' => 'Missing required fields'], 400);
        }

        // Check duplicate
        $stmt = $db->prepare("SELECT COUNT(*) FROM topics WHERE topic_id = ?");
        $stmt->execute([$topic_id]);
        if ($stmt->fetchColumn() > 0) sendResponse(['error' => 'Topic ID already exists'], 409);

        $stmt = $db->prepare("INSERT INTO topics (topic_id, subject, message, author, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$topic_id, $subject, $message, $author])) {
            sendResponse(['success' => true, 'topic_id' => $topic_id], 201);
        } else {
            sendResponse(['error' => 'Failed to create topic'], 500);
        }
    } elseif ($method === 'PUT') {
        $topic_id = sanitizeInput($input['topic_id'] ?? '');
        if (!$topic_id) sendResponse(['error' => 'topic_id required'], 400);

        $stmt = $db->prepare("SELECT * FROM topics WHERE topic_id = ?");
        $stmt->execute([$topic_id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['error' => 'Topic not found'], 404);

        $fields = [];
        $params = [];

        if (!empty($input['subject'])) { $fields[] = 'subject=?'; $params[] = sanitizeInput($input['subject']); }
        if (!empty($input['message'])) { $fields[] = 'message=?'; $params[] = sanitizeInput($input['message']); }

        if (empty($fields)) sendResponse(['error' => 'No fields to update'], 400);

        $params[] = $topic_id;
        $stmt = $db->prepare("UPDATE topics SET " . implode(',', $fields) . " WHERE topic_id=?");
        if ($stmt->execute($params)) {
            sendResponse(['success' => true]);
        } else {
            sendResponse(['error' => 'Failed to update topic'], 500);
        }
    } elseif ($method === 'DELETE') {
        if (!$id) sendResponse(['error' => 'id required'], 400);

        // Delete replies first
        $stmt = $db->prepare("DELETE FROM replies WHERE topic_id=?");
        $stmt->execute([$id]);

        $stmt = $db->prepare("DELETE FROM topics WHERE topic_id=?");
        if ($stmt->execute([$id])) {
            sendResponse(['success' => true]);
        } else {
            sendResponse(['error' => 'Failed to delete topic'], 500);
        }
    } else {
        sendResponse(['error' => 'Method not allowed'], 405);
    }
}

// =========================================
// REPLIES
// =========================================
if ($resource === 'replies') {
    if ($method === 'GET') {
        if (!$topic_id) sendResponse(['error' => 'topic_id required'], 400);
        $stmt = $db->prepare("SELECT reply_id, topic_id, text, author, created_at FROM replies WHERE topic_id=? ORDER BY created_at ASC");
        $stmt->execute([$topic_id]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(['success' => true, 'data' => $replies]);
    } elseif ($method === 'POST') {
        $reply_id = sanitizeInput($input['reply_id'] ?? '');
        $topic_id = sanitizeInput($input['topic_id'] ?? '');
        $text = sanitizeInput($input['text'] ?? '');
        $author = sanitizeInput($input['author'] ?? '');
        if (!$reply_id || !$topic_id || !$text || !$author) sendResponse(['error' => 'Missing required fields'], 400);

        // Check parent topic exists
        $stmt = $db->prepare("SELECT * FROM topics WHERE topic_id=?");
        $stmt->execute([$topic_id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['error' => 'Parent topic not found'], 404);

        // Check duplicate reply_id
        $stmt = $db->prepare("SELECT COUNT(*) FROM replies WHERE reply_id=?");
        $stmt->execute([$reply_id]);
        if ($stmt->fetchColumn() > 0) sendResponse(['error' => 'Reply ID already exists'], 409);

        $stmt = $db->prepare("INSERT INTO replies (reply_id, topic_id, text, author, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$reply_id, $topic_id, $text, $author])) {
            sendResponse(['success' => true, 'reply_id' => $reply_id], 201);
        } else {
            sendResponse(['error' => 'Failed to create reply'], 500);
        }
    } elseif ($method === 'DELETE') {
        if (!$id) sendResponse(['error' => 'id required'], 400);
        $stmt = $db->prepare("DELETE FROM replies WHERE reply_id=?");
        if ($stmt->execute([$id])) {
            sendResponse(['success' => true]);
        } else {
            sendResponse(['error' => 'Failed to delete reply'], 500);
        }
    } else {
        sendResponse(['error' => 'Method not allowed'], 405);
    }
}
?>
