<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
require_once 'Database.php';
$database = new Database();
$db = $database->getConnection();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Get input for POST/PUT
$input = json_decode(file_get_contents('php://input'), true);

// Get resource and id from query
$resource = isset($_GET['resource']) ? $_GET['resource'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';
$topic_id = isset($_GET['topic_id']) ? $_GET['topic_id'] : '';

// =================== HELPER FUNCTIONS ===================
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function sanitizeInput($data) {
    if(!is_string($data)) return $data;
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function isValidResource($res) {
    $allowed = ['topics', 'replies'];
    return in_array($res, $allowed);
}

// =================== TOPICS ===================
function getAllTopics($db) {
    $sql = "SELECT topic_id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics";
    $params = [];
    if(isset($_GET['search']) && $_GET['search'] != '') {
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    $sortFields = ['subject', 'author', 'created_at'];
    $order = 'DESC';
    $sort = 'created_at';
    if(isset($_GET['sort']) && in_array($_GET['sort'], $sortFields)) $sort = $_GET['sort'];
    if(isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC','DESC'])) $order = strtoupper($_GET['order']);
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    foreach($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$topics]);
}

function getTopicById($db, $topicId) {
    if(empty($topicId)) sendResponse(['success'=>false,'message'=>'Topic ID required'],400);
    $stmt = $db->prepare("SELECT topic_id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics WHERE topic_id=:id");
    $stmt->bindValue(':id', $topicId);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if($topic) sendResponse(['success'=>true,'data'=>$topic]);
    else sendResponse(['success'=>false,'message'=>'Topic not found'],404);
}

function createTopic($db, $data) {
    $required = ['topic_id','subject','message','author'];
    foreach($required as $field) if(!isset($data[$field])) sendResponse(['success'=>false,'message'=>$field.' required'],400);
    $topic_id = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);

    // Check duplicate
    $stmt = $db->prepare("SELECT * FROM topics WHERE topic_id=:id");
    $stmt->bindValue(':id',$topic_id);
    $stmt->execute();
    if($stmt->fetch()) sendResponse(['success'=>false,'message'=>'Duplicate topic_id'],409);

    $stmt = $db->prepare("INSERT INTO topics (topic_id, subject, message, author) VALUES (:tid, :sub, :msg, :auth)");
    $stmt->bindValue(':tid',$topic_id);
    $stmt->bindValue(':sub',$subject);
    $stmt->bindValue(':msg',$message);
    $stmt->bindValue(':auth',$author);
    if($stmt->execute()) sendResponse(['success'=>true,'topic_id'=>$topic_id],201);
    else sendResponse(['success'=>false,'message'=>'Insert failed'],500);
}

function updateTopic($db,$data) {
    if(!isset($data['topic_id'])) sendResponse(['success'=>false,'message'=>'topic_id required'],400);
    $topic_id = sanitizeInput($data['topic_id']);
    $stmt = $db->prepare("SELECT * FROM topics WHERE topic_id=:id");
    $stmt->bindValue(':id',$topic_id);
    $stmt->execute();
    if(!$stmt->fetch()) sendResponse(['success'=>false,'message'=>'Topic not found'],404);

    $updates = [];
    $params = [];
    if(isset($data['subject'])) { $updates[]='subject=:subject'; $params[':subject']=sanitizeInput($data['subject']); }
    if(isset($data['message'])) { $updates[]='message=:message'; $params[':message']=sanitizeInput($data['message']); }
    if(empty($updates)) sendResponse(['success'=>false,'message'=>'No fields to update'],400);
    $sql = "UPDATE topics SET ".implode(',', $updates)." WHERE topic_id=:topic_id";
    $params[':topic_id']=$topic_id;
    $stmt = $db->prepare($sql);
    foreach($params as $k=>$v) $stmt->bindValue($k,$v);
    if($stmt->execute()) sendResponse(['success'=>true,'message'=>'Topic updated']);
    else sendResponse(['success'=>false,'message'=>'Update failed'],500);
}

function deleteTopic($db,$topicId) {
    if(empty($topicId)) sendResponse(['success'=>false,'message'=>'Topic ID required'],400);
    $stmt = $db->prepare("SELECT * FROM topics WHERE topic_id=:id");
    $stmt->bindValue(':id',$topicId);
    $stmt->execute();
    if(!$stmt->fetch()) sendResponse(['success'=>false,'message'=>'Topic not found'],404);

    // Delete replies first
    $stmt = $db->prepare("DELETE FROM replies WHERE topic_id=:id");
    $stmt->bindValue(':id',$topicId);
    $stmt->execute();

    // Delete topic
    $stmt = $db->prepare("DELETE FROM topics WHERE topic_id=:id");
    $stmt->bindValue(':id',$topicId);
    if($stmt->execute()) sendResponse(['success'=>true,'message'=>'Topic deleted']);
    else sendResponse(['success'=>false,'message'=>'Delete failed'],500);
}

// =================== ROUTER ===================
try {
    if(!isValidResource($resource)) sendResponse(['success'=>false,'message'=>'Invalid resource'],400);

    if($resource==='topics') {
        switch($method){
            case 'GET':
                if($id) getTopicById($db,$id);
                else getAllTopics($db);
                break;
            case 'POST':
                createTopic($db,$input);
                break;
            case 'PUT':
                updateTopic($db,$input);
                break;
            case 'DELETE':
                deleteTopic($db,$id);
                break;
            default:
                sendResponse(['success'=>false,'message'=>'Method not allowed'],405);
        }
    } else {
        sendResponse(['success'=>false,'message'=>'Resource not implemented'],400);
    }

} catch(PDOException $e) {
    sendResponse(['success'=>false,'message'=>'Database error'],500);
} catch(Exception $e) {
    sendResponse(['success'=>false,'message'=>'Server error'],500);
}
?>
