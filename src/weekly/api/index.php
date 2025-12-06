<?php
/**
 * Weekly Course Breakdown API (JSON-backed)
 *
 * This implements CRUD for weeks and comments using weeks.json and comments.json.
 * It exposes a simple RESTful interface:
 *   - GET /index.php?resource=weeks
 *   - GET /index.php?resource=weeks&week_id=week_1
 *   - POST /index.php?resource=weeks  (JSON body)
 *   - PUT /index.php?resource=weeks   (JSON body)
 *   - DELETE /index.php?resource=weeks&week_id=week_1
 *
 *   - GET /index.php?resource=comments&week_id=week_1
 *   - POST /index.php?resource=comments (JSON body)
 *   - DELETE /index.php?resource=comments&id=3
 */

// -----------------------------------------------------------------------------
// HEADERS / CORS / PRE-FLIGHT
// -----------------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');

// Allow CORS for development (adjust origins as needed for production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header("Access-Control-Allow-Origin: *");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -----------------------------------------------------------------------------
// CONFIG: JSON file paths
// -----------------------------------------------------------------------------
define('WEEKS_FILE', __DIR__ . '/weeks.json');
define('COMMENTS_FILE', __DIR__ . '/comments.json');

// Ensure files exist
if (!file_exists(WEEKS_FILE)) file_put_contents(WEEKS_FILE, json_encode([], JSON_PRETTY_PRINT));
if (!file_exists(COMMENTS_FILE)) file_put_contents(COMMENTS_FILE, json_encode([], JSON_PRETTY_PRINT));

// -----------------------------------------------------------------------------
// UTILITIES: load/save JSON with locking
// -----------------------------------------------------------------------------
function loadJsonFile($path) {
    $json = @file_get_contents($path);
    if ($json === false) return [];
    $data = json_decode($json, true);
    return $data === null ? [] : $data;
}

function saveJsonFile($path, $data) {
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // atomic write with rename
    file_put_contents($tmp, $json, LOCK_EX);
    rename($tmp, $path);
    return true;
}

// -----------------------------------------------------------------------------
// HELPER FUNCTIONS
// -----------------------------------------------------------------------------
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $statusCode = 400) {
    sendResponse(['success' => false, 'error' => $message], $statusCode);
}

function validateDateStr($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($val) {
    if (is_array($val)) {
        return array_map('sanitizeInput', $val);
    }
    $v = trim($val);
    $v = strip_tags($v);
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isValidSortField($field, $allowed) {
    return in_array($field, $allowed, true);
}

// -----------------------------------------------------------------------------
// REQUEST PARSING
// -----------------------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true) ?? [];

$resource = isset($_GET['resource']) ? $_GET['resource'] : ($body['resource'] ?? 'weeks');

// -----------------------------------------------------------------------------
// WEEKS OPERATIONS (JSON-backed)
// -----------------------------------------------------------------------------
function getAllWeeksHandler() {
    $weeks = loadJsonFile(WEEKS_FILE);

    // Query params: search, sort, order
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'startDate';
    $order = isset($_GET['order']) ? strtolower($_GET['order']) : 'asc';

    // Filter by search (title or description)
    if ($search !== '') {
        $s = mb_strtolower($search);
        $weeks = array_filter($weeks, function ($w) use ($s) {
            $title = isset($w['title']) ? mb_strtolower($w['title']) : '';
            $desc = isset($w['description']) ? mb_strtolower($w['description']) : '';
            return mb_strpos($title, $s) !== false || mb_strpos($desc, $s) !== false;
        });
        // reindex
        $weeks = array_values($weeks);
    }

    // Sort: only allow certain fields
    $allowedSort = ['title', 'startDate', 'id'];
    if (!isValidSortField($sort, $allowedSort)) $sort = 'startDate';
    usort($weeks, function ($a, $b) use ($sort, $order) {
        $va = $a[$sort] ?? '';
        $vb = $b[$sort] ?? '';
        if ($va == $vb) return 0;
        if ($order === 'desc') return ($va < $vb) ? 1 : -1;
        return ($va < $vb) ? -1 : 1;
    });

    // ensure links are arrays
    foreach ($weeks as &$w) {
        if (!isset($w['links']) || !is_array($w['links'])) $w['links'] = [];
    }
    unset($w);

    sendResponse(['success' => true, 'data' => $weeks]);
}

function getWeekByIdHandler($weekId) {
    if (!$weekId) sendError('week_id is required', 400);
    $weeks = loadJsonFile(WEEKS_FILE);
    foreach ($weeks as $w) {
        if (($w['id'] ?? '') === $weekId) {
            if (!isset($w['links']) || !is_array($w['links'])) $w['links'] = [];
            sendResponse(['success' => true, 'data' => $w]);
        }
    }
    sendError('Week not found', 404);
}

function createWeekHandler($data) {
    // required: id, title, startDate, description
    $id = $data['id'] ?? '';
    $title = $data['title'] ?? '';
    $startDate = $data['startDate'] ?? '';
    $description = $data['description'] ?? '';
    $links = $data['links'] ?? [];

    $id = sanitizeInput($id);
    $title = sanitizeInput($title);
    $startDate = sanitizeInput($startDate);
    $description = sanitizeInput($description);
    $links = is_array($links) ? array_map('sanitizeInput', $links) : [];

    if ($id === '' || $title === '' || $startDate === '' || $description === '') {
        sendError('Missing required fields: id, title, startDate, description', 400);
    }

    if (!validateDateStr($startDate)) {
        sendError('Invalid startDate format. Use YYYY-MM-DD', 400);
    }

    $weeks = loadJsonFile(WEEKS_FILE);
    // check duplicate id
    foreach ($weeks as $w) {
        if (($w['id'] ?? '') === $id) sendError('week_id already exists', 409);
    }

    $newWeek = [
        'id' => $id,
        'title' => $title,
        'startDate' => $startDate,
        'description' => $description,
        'links' => array_values($links),
    ];
    $weeks[] = $newWeek;
    saveJsonFile(WEEKS_FILE, $weeks);
    sendResponse(['success' => true, 'data' => $newWeek], 201);
}

function updateWeekHandler($data) {
    $weekId = $data['id'] ?? '';
    if (!$weekId) sendError('week_id (id) required in body', 400);

    $weeks = loadJsonFile(WEEKS_FILE);
    $found = false;
    foreach ($weeks as &$w) {
        if (($w['id'] ?? '') === $weekId) {
            $found = true;
            // update allowed fields if provided
            if (isset($data['title'])) $w['title'] = sanitizeInput($data['title']);
            if (isset($data['startDate'])) {
                if (!validateDateStr($data['startDate'])) sendError('Invalid startDate format', 400);
                $w['startDate'] = sanitizeInput($data['startDate']);
            }
            if (isset($data['description'])) $w['description'] = sanitizeInput($data['description']);
            if (isset($data['links']) && is_array($data['links'])) $w['links'] = array_map('sanitizeInput', $data['links']);
            // done; break
            break;
        }
    }
    unset($w);

    if (!$found) sendError('Week not found', 404);

    saveJsonFile(WEEKS_FILE, $weeks);
    // return updated resource
    foreach ($weeks as $w) {
        if ($w['id'] === $weekId) {
            sendResponse(['success' => true, 'data' => $w]);
        }
    }
    sendError('Unexpected error updating week', 500);
}

function deleteWeekHandler($weekId) {
    if (!$weekId) sendError('week_id is required', 400);
    $weeks = loadJsonFile(WEEKS_FILE);
    $newWeeks = [];
    $found = false;
    foreach ($weeks as $w) {
        if (($w['id'] ?? '') === $weekId) {
            $found = true;
            continue;
        }
        $newWeeks[] = $w;
    }
    if (!$found) sendError('Week not found', 404);

    // delete associated comments
    $comments = loadJsonFile(COMMENTS_FILE);
    $comments = array_filter($comments, function ($c) use ($weekId) {
        return ($c['week_id'] ?? '') !== $weekId;
    });
    $comments = array_values($comments);

    saveJsonFile(WEEKS_FILE, array_values($newWeeks));
    saveJsonFile(COMMENTS_FILE, $comments);

    sendResponse(['success' => true, 'message' => "Week '{$weekId}' and its comments deleted"]);
}

// -----------------------------------------------------------------------------
// COMMENTS OPERATIONS (JSON-backed)
// -----------------------------------------------------------------------------
function getCommentsByWeekHandler($weekId) {
    if (!$weekId) sendError('week_id is required', 400);
    $comments = loadJsonFile(COMMENTS_FILE);
    $result = array_filter($comments, function ($c) use ($weekId) {
        return isset($c['week_id']) && $c['week_id'] === $weekId;
    });
    // sort by created_at if present
    usort($result, function ($a, $b) {
        $ta = $a['created_at'] ?? '';
        $tb = $b['created_at'] ?? '';
        return strcmp($ta, $tb);
    });
    sendResponse(['success' => true, 'data' => array_values($result)]);
}

function createCommentHandler($data) {
    $weekId = $data['week_id'] ?? '';
    $author = $data['author'] ?? '';
    $text = $data['text'] ?? '';

    $weekId = sanitizeInput($weekId);
    $author = sanitizeInput($author);
    $text = sanitizeInput($text);

    if ($weekId === '' || $author === '' || $text === '') {
        sendError('Missing required fields: week_id, author, text', 400);
    }

    // verify week exists
    $weeks = loadJsonFile(WEEKS_FILE);
    $exists = false;
    foreach ($weeks as $w) {
        if (($w['id'] ?? '') === $weekId) { $exists = true; break; }
    }
    if (!$exists) sendError('Week not found', 404);

    $comments = loadJsonFile(COMMENTS_FILE);

    // generate an auto-increment id for comment (numeric)
    $maxId = 0;
    foreach ($comments as $c) {
        $cid = intval($c['id'] ?? 0);
        if ($cid > $maxId) $maxId = $cid;
    }
    $newId = $maxId + 1;

    $newComment = [
        'id' => $newId,
        'week_id' => $weekId,
        'author' => $author,
        'text' => $text,
        'created_at' => (new DateTime())->format(DateTime::ATOM)
    ];

    $comments[] = $newComment;
    saveJsonFile(COMMENTS_FILE, $comments);

    sendResponse(['success' => true, 'data' => $newComment], 201);
}

function deleteCommentHandler($commentId) {
    if (!$commentId) sendError('comment id is required', 400);
    $comments = loadJsonFile(COMMENTS_FILE);
    $found = false;
    $new = [];
    foreach ($comments as $c) {
        if ((string)($c['id'] ?? '') === (string)$commentId) {
            $found = true;
            continue;
        }
        $new[] = $c;
    }
    if (!$found) sendError('Comment not found', 404);
    saveJsonFile(COMMENTS_FILE, array_values($new));
    sendResponse(['success' => true, 'message' => 'Comment deleted']);
}

// -----------------------------------------------------------------------------
// MAIN ROUTING
// -----------------------------------------------------------------------------
try {
    $resource = strtolower($resource);

    if ($resource === 'weeks') {
        if ($method === 'GET') {
            $weekId = $_GET['week_id'] ?? null;
            if ($weekId) {
                getWeekByIdHandler($weekId);
            } else {
                getAllWeeksHandler();
            }
        } elseif ($method === 'POST') {
            // create new week
            createWeekHandler($body);
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            // update week - body must include "id" field
            updateWeekHandler($body);
        } elseif ($method === 'DELETE') {
            $weekId = $_GET['week_id'] ?? ($body['id'] ?? null);
            deleteWeekHandler($weekId);
        } else {
            sendError('Method Not Allowed', 405);
        }
    } elseif ($resource === 'comments') {
        if ($method === 'GET') {
            $weekId = $_GET['week_id'] ?? null;
            if (!$weekId) sendError('week_id parameter is required', 400);
            getCommentsByWeekHandler($weekId);
        } elseif ($method === 'POST') {
            createCommentHandler($body);
        } elseif ($method === 'DELETE') {
            $commentId = $_GET['id'] ?? ($body['id'] ?? null);
            deleteCommentHandler($commentId);
        } else {
            sendError('Method Not Allowed', 405);
        }
    } else {
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
} catch (Exception $e) {
    // Log server-side error for debugging (file-based)
    error_log($e->getMessage());
    sendError('Server error occurred', 500);
}
