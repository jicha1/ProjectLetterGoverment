<?php // <pro_letter/documents/update_status.php>
session_start();
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json');

$pdo = getPDO();

// ✅ รับ JSON
$data = json_decode(file_get_contents("php://input"), true);

$docId  = $data['id'] ?? 0;
$status = $data['status'] ?? '';
$roleId = $_SESSION['role_id'] ?? 0;

// Admin = 1, Officer = 2
if (!in_array($roleId, [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์']);
    exit;
}

// ตรวจข้อมูล
if (!$docId || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

// อัปเดต
$stmt = $pdo->prepare("
    UPDATE documents
    SET status = :status, updated_at = NOW()
    WHERE document_id = :id
");
$stmt->execute([
    ':status' => $status,
    ':id' => $docId
]);

echo json_encode(['success' => true]);