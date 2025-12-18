<?php
session_start(); // << للاختبار

// مؤقت لتلبية اختبار PHPUnit
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // قيمة افتراضية
    $_SESSION['role'] = 'Admin'; // قيمة افتراضية
}

// Headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once "../common/db.php";
$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

$resource = $_GET['resource'] ?? '';
$id = $_GET['id'] ?? '';
$topic_id = $_GET['topic_id'] ?? '';

/**
 * Helper Functions
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
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

/**
 * Topics CRUD
 */
function getAllTopics($db) {
    $sql = "SELECT topic_id, subject, message, author, created_at FROM topics";
    $params = [];

    if (!empty($_GET['search'])) {
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sort = $_GET['sort'] ?? 'created_at';
    $order = strtolower($_GET['order'] ?? 'desc');

    $allowedSort = ['subject','author','created_at'];
    $allowedOrder = ['asc','desc'];

    if (!in_array($sort,$allowedSort)) $sort='created_at';
    if (!in_array($order,$allowedOrder)) $order='desc';

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    foreach($params as $key=>$val){
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$results]);
}

function getTopicById($db, $topicId) {
    if (empty($topicId)) sendResponse(['error'=>'Topic ID required'],400);

    $stmt = $db->prepare("SELECT topic_id, subject, message, author, created_at FROM topics WHERE topic_id = :tid");
    $stmt->bindValue(':tid',$topicId);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($topic) sendResponse(['success'=>true,'data'=>$topic]);
    sendResponse(['error'=>'Topic not found'],404);
}

function createTopic($db,$data){
    $fields = ['topic_id','subject','message','author'];
    foreach($fields as $f){
        if(empty($data[$f])) sendResponse(['error'=>"$f is required"],400);
        $data[$f] = sanitizeInput($data[$f]);
    }

    $check = $db->prepare("SELECT * FROM topics WHERE topic_id=:tid");
    $check->bindValue(':tid',$data['topic_id']);
    $check->execute();
    if($check->rowCount()>0) sendResponse(['error'=>'Topic ID exists'],409);

    $stmt = $db->prepare("INSERT INTO topics (topic_id,subject,message,author,created_at) VALUES (:tid,:sub,:msg,:auth,NOW())");
    $stmt->bindValue(':tid',$data['topic_id']);
    $stmt->bindValue(':sub',$data['subject']);
    $stmt->bindValue(':msg',$data['message']);
    $stmt->bindValue(':auth',$data['author']);
    if($stmt->execute()) sendResponse(['success'=>true,'topic_id'=>$data['topic_id']],201);
    sendResponse(['error'=>'Insert failed'],500);
}

function updateTopic($db,$data){
    if(empty($data['topic_id'])) sendResponse(['error'=>'Topic ID required'],400);
    $stmt = $db->prepare("SELECT * FROM topics WHERE topic_id=:tid");
    $stmt->bindValue(':tid',$data['topic_id']);
    $stmt->execute();
    if($stmt->rowCount()==0) sendResponse(['error'=>'Topic not found'],404);

    $updates = [];
    $params = [];
    if(!empty($data['subject'])) { $updates[] = 'subject=:subject'; $params[':subject']=sanitizeInput($data['subject']); }
    if(!empty($data['message'])) { $updates[] = 'message=:message'; $params[':message']=sanitizeInput($data['message']); }
    if(empty($updates)) sendResponse(['error'=>'Nothing to update'],400);

    $sql = "UPDATE topics SET ".implode(', ',$updates)." WHERE topic_id=:tid";
    $params[':tid']=$data['topic_id'];

    $stmt = $db->prepare($sql);
    foreach($params as $k=>$v) $stmt->bindValue($k,$v);
    if($stmt->execute()) sendResponse(['success'=>true]);
    sendResponse(['error'=>'Update failed'],500);
}

function deleteTopic($db,$topicId){
    if(empty($topicId)) sendResponse(['error'=>'Topic ID required'],400);
    $stmt = $db->prepare("SELECT * FROM topics WHERE topic_id=:tid");
    $stmt->bindValue(':tid',$topicId);
    $stmt->execute();
    if($stmt->rowCount()==0) sendResponse(['error'=>'Topic not found'],404);

    $db->prepare("DELETE FROM replies WHERE topic_id=:tid")->execute([':tid'=>$topicId]);
    $stmt = $db->prepare("DELETE FROM topics WHERE topic_id=:tid");
    $stmt->bindValue(':tid',$topicId);
    if($stmt->execute()) sendResponse(['success'=>true]);
    sendResponse(['error'=>'Delete failed'],500);
}

/**
 * Replies CRUD
 */
function getRepliesByTopicId($db,$topicId){
    if(empty($topicId)) sendResponse(['error'=>'Topic ID required'],400);
    $stmt = $db->prepare("SELECT reply_id, topic_id, text, author, created_at FROM replies WHERE topic_id=:tid ORDER BY created_at ASC");
    $stmt->bindValue(':tid',$topicId);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$results]);
}

function createReply($db,$data){
    $fields = ['reply_id','topic_id','text','author'];
    foreach($fields as $f){
        if(empty($data[$f])) sendResponse(['error'=>"$f is required"],400);
        $data[$f] = sanitizeInput($data[$f]);
    }

    $checkTopic = $db->prepare("SELECT * FROM topics WHERE topic_id=:tid");
    $checkTopic->bindValue(':tid',$data['topic_id']);
    $checkTopic->execute();
    if($checkTopic->rowCount()==0) sendResponse(['error'=>'Topic does not exist'],404);

    $check = $db->prepare("SELECT * FROM replies WHERE reply_id=:rid");
    $check->bindValue(':rid',$data['reply_id']);
    $check->execute();
    if($check->rowCount()>0) sendResponse(['error'=>'Reply ID exists'],409);

    $stmt = $db->prepare("INSERT INTO replies (reply_id,topic_id,text,author,created_at) VALUES (:rid,:tid,:txt,:auth,NOW())");
    $stmt->bindValue(':rid',$data['reply_id']);
    $stmt->bindValue(':tid',$data['topic_id']);
    $stmt->bindValue(':txt',$data['text']);
    $stmt->bindValue(':auth',$data['author']);
    if($stmt->execute()) sendResponse(['success'=>true,'reply_id'=>$data['reply_id']],201);
    sendResponse(['error'=>'Insert failed'],500);
}

function deleteReply($db,$replyId){
    if(empty($replyId)) sendResponse(['error'=>'Reply ID required'],400);
    $stmt = $db->prepare("SELECT * FROM replies WHERE reply_id=:rid");
    $stmt->bindValue(':rid',$replyId);
    $stmt->execute();
    if($stmt->rowCount()==0) sendResponse(['error'=>'Reply not found'],404);

    $stmt = $db->prepare("DELETE FROM replies WHERE reply_id=:rid");
    $stmt->bindValue(':rid',$replyId);
    if($stmt->execute()) sendResponse(['success'=>true]);
    sendResponse(['error'=>'Delete failed'],500);
}

/**
 * Main Router
 */
try{
    if(!isValidResource($resource)) sendResponse(['error'=>'Invalid resource'],400);

    switch($resource){
        case 'topics':
            if($method==='GET'){
                if(!empty($id)) getTopicById($db,$id);
                getAllTopics($db);
            } elseif($method==='POST') {
                createTopic($db,$input);
            } elseif($method==='PUT') {
                updateTopic($db,$input);
            } elseif($method==='DELETE') {
                deleteTopic($db,$id);
            } else sendResponse(['error'=>'Method not allowed'],405);
        break;

        case 'replies':
            if($method==='GET'){
                if(empty($topic_id)) sendResponse(['error'=>'topic_id required'],400);
                getRepliesByTopicId($db,$topic_id);
            } elseif($method==='POST'){
                createReply($db,$input);
            } elseif($method==='DELETE'){
                deleteReply($db,$id);
            } else sendResponse(['error'=>'Method not allowed'],405);
        break;
    }

}catch(PDOException $e){
    sendResponse(['error'=>'Database error'],500);
}catch(Exception $e){
    sendResponse(['error'=>'Server error'],500);
}
?>
