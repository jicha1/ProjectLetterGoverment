<?php
// pro_letter/documents/submit_document.php
session_start();
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'not_login']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$docId = (int)($data['document_id'] ?? 0);

if ($docId <= 0) {
    echo json_encode(['success' => false, 'message' => 'invalid_id']);
    exit;
}

$pdo = db();

// ตรวจเอกสาร
$stmt = $pdo->prepare("
    SELECT owner_id, status
    FROM documents
    WHERE document_id = :id
    LIMIT 1
");
$stmt->execute([':id' => $docId]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    echo json_encode(['success' => false, 'message' => 'not_found']);
    exit;
}

if ($doc['owner_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'no_permission']);
    exit;
}

if ($doc['status'] !== 'draft') {
    echo json_encode(['success' => false, 'message' => 'already_submitted']);
    exit;
}

// ✅ อัปเดตสถานะ
$upd = $pdo->prepare("
    UPDATE documents
    SET status = 'submitted'
    WHERE document_id = :id
");

$upd->execute([':id' => $docId]);

echo json_encode(['success' => true]);