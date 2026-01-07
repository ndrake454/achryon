<?php
// Start output buffering to catch any accidental output
ob_start();

// Catch all errors including fatal ones
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

// Set error reporting but don't display errors (they break JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Load configuration and start session
require_once '../includes/config.php';

// Clear any output that happened before this point
ob_clean();

header('Content-Type: application/json');

// Check if database connection exists
if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit;
}

// Check if user is logged in and is DM
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dm') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

// Get parameters
$entity_type = $_POST['entity_type'] ?? ''; // 'character', 'monster', 'item', 'spell'
$entity_id = intval($_POST['entity_id'] ?? 0);

// Validate entity type
$valid_types = ['character', 'monster', 'item', 'spell'];
if (!in_array($entity_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid entity type']);
    exit;
}

// Validate entity ID
if ($entity_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid entity ID']);
    exit;
}

// File validation
$file = $_FILES['image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 50 * 1024 * 1024; // 50MB

// Check file type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit;
}

// Check file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 50MB']);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = __DIR__ . '/../uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = $entity_type . '_' . $entity_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Update database based on entity type
$table_map = [
    'character' => 'characters',
    'monster'   => 'monsters',
    'item'      => 'items',
    'spell'     => 'spells'
];

try {
    $table = $table_map[$entity_type];
    $image_url = '/uploads/' . $filename;
    
    $stmt = $conn->prepare("UPDATE $table SET image_url = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $image_url, $entity_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'image_url' => $image_url,
            'message' => 'Image uploaded successfully'
        ]);
    } else {
        // Delete uploaded file if database update fails
        unlink($filepath);
        echo json_encode(['success' => false, 'message' => 'Failed to update database: ' . $stmt->error]);
    }
} catch (Exception $e) {
    // Delete uploaded file on any error
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Ensure output buffer is flushed
ob_end_flush();
