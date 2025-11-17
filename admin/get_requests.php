<?php
session_start();
require_once __DIR__ . '/../functions.php';
$pdo = getPDO();

$userId = $_SESSION['user_id'] ?? 0;
$roleId = $_SESSION['role_id'] ?? 0;

/* ---------------------------------------------
   ROLE:
   1 = Admin → เห็นทั้งหมด
   2 = Officer → เห็นทั้งหมด
   3 = User → เห็นเฉพาะของตัวเอง
----------------------------------------------*/

if ($roleId == 1 || $roleId == 2) {

    // 🟢 admin + officer ทั้งคู่เห็นเอกสารทุกอัน
    $sql = "
        SELECT 
          d.document_id,
          d.doc_date,
          d.status,
          MAX(CASE WHEN f.field_key = 'join_type' THEN v.value_text END) AS join_type,
          MAX(CASE WHEN f.field_key = 'course_name' THEN v.value_text END) AS course_name
        FROM documents d
        LEFT JOIN document_values v ON d.document_id = v.document_id
        LEFT JOIN template_fields f ON v.field_id = f.field_id
        GROUP BY d.document_id, d.doc_date, d.status
        ORDER BY d.created_at DESC
    ";
    $stmt = $pdo->query($sql);

} else {

    // 🔒 user เห็นเฉพาะของตัวเอง
    $sql = "
        SELECT 
          d.document_id,
          d.doc_date,
          d.status,
          MAX(CASE WHEN f.field_key = 'join_type' THEN v.value_text END) AS join_type,
          MAX(CASE WHEN f.field_key = 'course_name' THEN v.value_text END) AS course_name
        FROM documents d
        LEFT JOIN document_values v ON d.document_id = v.document_id
        LEFT JOIN template_fields f ON v.field_id = f.field_id
        WHERE d.owner_id = :u
        GROUP BY d.document_id, d.doc_date, d.status
        ORDER BY d.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u' => $userId]);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows, JSON_UNESCAPED_UNICODE);