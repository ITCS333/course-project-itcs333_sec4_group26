<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'itcs333_course');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        sendResponse(['success' => false, 'message' => 'Database connection failed'], 500);
        exit;
    }
}

$db = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Get request body for POST and PUT requests
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$queryParams = $_GET;


/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    $sql = "SELECT id, student_id, name, email, created_at FROM users WHERE role = 'student'";
    $params = [];
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " AND (name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
        $params = [$search, $search, $search];
    }
    
    // Validate and apply sorting
    $allowedSortFields = ['name', 'student_id', 'email', 'created_at'];
    $allowedOrders = ['asc', 'desc'];
    
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields) ? $_GET['sort'] : 'created_at';
    $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), $allowedOrders) ? $_GET['order'] : 'DESC';
    
    $sql .= " ORDER BY $sort $order";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    sendResponse([
        'success' => true,
        'count' => count($students),
        'data' => $students
    ]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    $sql = "SELECT id, student_id, name, email, created_at FROM users WHERE student_id = ? AND role = 'student'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if ($student) {
        sendResponse([
            'success' => true,
            'data' => $student
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // Validate required fields
    if (empty($data['student_id']) || empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendResponse([
            'success' => false,
            'message' => 'All fields are required: student_id, name, email, password'
        ], 400);
    }
    
    // Sanitize and validate input data
    $studentId = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];
    
    if (!validateEmail($email)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid email format'
        ], 400);
    }
    
    // Check for duplicates
    $checkSql = "SELECT COUNT(*) FROM users WHERE student_id = ? OR email = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$studentId, $email]);
    $count = $checkStmt->fetchColumn();
    
    if ($count > 0) {
        sendResponse([
            'success' => false,
            'message' => 'Student ID or email already exists'
        ], 409);
    }
    
    // Hash password and insert student
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (student_id, name, email, password, role) VALUES (?, ?, ?, ?, 'student')";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([$studentId, $name, $email, $hashedPassword]);
    if ($result) {
        sendResponse([
            'success' => true,
            'message' => 'Student created successfully',
            'student_id' => $studentId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create student'
        ], 500);
    }
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // Validate student_id is provided
    if (empty($data['student_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required'
        ], 400);
    }
    
    $studentId = sanitizeInput($data['student_id']);
    
    // Check if student exists
    $checkSql = "SELECT id FROM users WHERE student_id = ? AND role = 'student'";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$studentId]);
    $student = $checkStmt->fetch();
    
    if (!$student) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }
    
    // Build UPDATE query dynamically
    $updateFields = [];
    $params = [];
    
    if (isset($data['name']) && !empty($data['name'])) {
        $updateFields[] = "name = ?";
        $params[] = sanitizeInput($data['name']);
    }
    
    if (isset($data['email']) && !empty($data['email'])) {
        $email = sanitizeInput($data['email']);
        
        if (!validateEmail($email)) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid email format'
            ], 400);
        }
        
        // Check if email already exists
        $emailCheckSql = "SELECT COUNT(*) FROM users WHERE email = ? AND student_id != ?";
        $emailCheckStmt = $db->prepare($emailCheckSql);
        $emailCheckStmt->execute([$email, $studentId]);
        $emailExists = $emailCheckStmt->fetchColumn();
        
        if ($emailExists > 0) {
            sendResponse([
                'success' => false,
                'message' => 'Email already exists'
            ], 409);
        }
        
        $updateFields[] = "email = ?";
        $params[] = $email;
    }
    
    if (empty($updateFields)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields to update'
        ], 400);
    }
    
    $params[] = $studentId;
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE student_id = ? AND role = 'student'";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);
    if ($result) {
        sendResponse([
            'success' => true,
            'message' => 'Student updated successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update student'
        ], 500);
    }
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // Validate student_id is provided
    if (empty($studentId)) {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required'
        ], 400);
    }
    
    $studentId = sanitizeInput($studentId);
    
    // Check if student exists
    $checkSql = "SELECT id FROM users WHERE student_id = ? AND role = 'student'";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$studentId]);
    $student = $checkStmt->fetch();
    
    if (!$student) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }
    
    $sql = "DELETE FROM users WHERE student_id = ? AND role = 'student'";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([$studentId]);
    if ($result) {
        sendResponse([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete student'
        ], 500);
    }
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // Validate required fields
    if (empty($data['student_id']) || empty($data['current_password']) || empty($data['new_password'])) {
        sendResponse([
            'success' => false,
            'message' => 'student_id, current_password, and new_password are required'
        ], 400);
    }
    
    $studentId = sanitizeInput($data['student_id']);
    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];
    
    // Validate password strength
    if (strlen($newPassword) < 8) {
        sendResponse([
            'success' => false,
            'message' => 'New password must be at least 8 characters long'
        ], 400);
    }
    
    // Retrieve current password hash
    $sql = "SELECT password FROM users WHERE student_id = ? AND role = 'student'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$studentId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        sendResponse([
            'success' => false,
            'message' => 'Current password is incorrect'
        ], 401);
    }
    
    // Hash and update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateSql = "UPDATE users SET password = ? WHERE student_id = ? AND role = 'student'";
    $updateStmt = $db->prepare($updateSql);
    $result = $updateStmt->execute([$hashedPassword, $studentId]);
    if ($result) {
        sendResponse([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to change password'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // Route requests based on HTTP method
    if ($method === 'GET') {
        if (isset($queryParams['student_id']) && !empty($queryParams['student_id'])) {
            getStudentById($db, $queryParams['student_id']);
        } else {
            getStudents($db);
        }
        
    } elseif ($method === 'POST') {
        if (isset($queryParams['action']) && $queryParams['action'] === 'change_password') {
            changePassword($db, $data);
        } else {
            createStudent($db, $data);
        }
        
    } elseif ($method === 'PUT') {
        updateStudent($db, $data);
        
    } elseif ($method === 'DELETE') {
        $studentId = isset($queryParams['student_id']) ? $queryParams['student_id'] : ($data['student_id'] ?? null);
        deleteStudent($db, $studentId);
        
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed'
        ], 405);
    }
    
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred'
    ], 500);
    
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An error occurred'
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

?>
