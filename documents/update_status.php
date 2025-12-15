<?php // <pro_letter>
session_start();
require_once __DIR__ . './functions.php';
$pdo = getPDO();

$docId = $_POST['document_id'] ?? 0;
$status = $_POST['status'] ?? '';
$roleId = $_SESSION['role_id'] ?? 0;


// Admin = 1, Officer = 2 → อนุญาตทั้งคู่
if (!in_array($roleId, [1, 2])) {
  echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เปลี่ยนสถานะ']);
  exit;
}


// ✅ ใช้ชื่อสถานะตามฐานข้อมูลจริง
if (!$docId || !in_array($status, ['approved', 'rejected'])) {
  echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
  exit;
}

// ✅ อัปเดตสถานะ
$stmt = $pdo->prepare("UPDATE documents SET status = :status, updated_at = NOW() WHERE document_id = :id");
$stmt->execute([':status' => $status, ':id' => $docId]);

$statusText = match($status) {
  'approved' => 'อนุมัติแล้ว',
  'rejected' => 'รอการแก้ไข',
  default => 'ไม่ทราบสถานะ'
};

echo json_encode(['success' => true, 'status_text' => $statusText]);
?>