<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connection.php';
require_once 'audit_trail_helper.php'; // Add this line

// Check if admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || !isset($data['position'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    // Get the next display order
    $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM barangay_officials");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextOrder = ($result['max_order'] ?? 0) + 1;
    
    // Insert new official
    $stmt = $pdo->prepare("INSERT INTO barangay_officials (name, position, display_order) VALUES (?, ?, ?)");
    $stmt->execute([$data['name'], $data['position'], $nextOrder]);
    
    // Log to audit trail - Add these lines
$admin_id = $_SESSION['user_id'];
$admin_email = $_SESSION['user_email'] ?? 'Unknown';
logOfficialAdd($admin_id, $admin_email, $data['name'], $data['position']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Official added successfully',
        'id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>