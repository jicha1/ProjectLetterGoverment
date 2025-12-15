<?php
// documents/save_memo.php
session_start();

/** ==== DEV FLAGS ==== */
$DEV_AUTO_LOGIN = true;   // เปิดทดสอบ: ผ่านแม้ยังไม่ล็อกอิน (ตั้ง user_id=1)
$DEBUG_ERRORS = true;   // ส่งรายละเอียด error (อย่าเปิดในโปรดักชัน)

if ($DEV_AUTO_LOGIN && empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // ผู้ใช้ทดสอบ ที่มีอยู่ในตาราง users
}

require_once __DIR__ . '/../functions.php';

try {
    // ต้องมี user_id ใน session (ถ้า DEV_AUTO_LOGIN=false ต้องล็อกอินจริง)
  if (empty($_SESSION['user_id'])) {
    header('Location: /Pro_letter/documents/form_Memo.php?err=unauthorized');
    exit;
}

    $userId = (int) $_SESSION['user_id'];

    /** ===== รับค่า POST ===== */
    $templateId = (int) ($_POST['template_id'] ?? 1);
    $departmentId = (int) ($_POST['department_id'] ?? 1);

    $docDate = trim($_POST['doc_date'] ?? '');   // YYYY-MM-DD
    $fullname = trim($_POST['fullname'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $purpose = $_POST['purpose'] ?? '';    // academic|training|meeting|other
    $eventTitle = trim($_POST['event_title'] ?? '');

    $dateOption = $_POST['date_option'] ?? '';    // single|range
    $singleDate = trim($_POST['single_date'] ?? '');
    $rangeDate = trim($_POST['range_date'] ?? '');

    $isOnline = ($_POST['is_online'] ?? '1') === '1' ? 1 : 0;

    $place = trim($_POST['place'] ?? '');

    $noCost = isset($_POST['no_cost']) ? 1 : 0;
    $amountRaw = str_replace(',', '', trim($_POST['amount'] ?? '0'));
    $amount = $noCost ? 0.00 : (is_numeric($amountRaw) ? (float) $amountRaw : 0.00);

    $carUsed = isset($_POST['car_used']) ? 1 : 0;
    $carPlate = trim($_POST['car_plate'] ?? '');

    // เก็บข้อความคณะ/ภาควิชา ใน document_values (field_id 10,11)
    $faculty = trim($_POST['faculty'] ?? '');
    $department = trim($_POST['department'] ?? '');

    $mode = $_POST['mode'] ?? 'create';   // create | update
$documentId = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;

    /** ===== ตรวจฝั่งเซิร์ฟเวอร์ ===== */
    $errors = [];
    if ($docDate === '')
        $errors['doc_date'] = 'required';
    if ($purpose === '')
        $errors['purpose'] = 'required';
    if ($eventTitle === '')
        $errors['event_title'] = 'required';
    if ($dateOption === 'single' && $singleDate === '')
        $errors['single_date'] = 'required';
    if ($dateOption === 'range' && $rangeDate === '')
        $errors['range_date'] = 'required';
    if (!$isOnline && $place === '')
        $errors['place'] = 'required';
    if (!$noCost && !is_numeric($amountRaw))
        $errors['amount'] = 'number';
    if ($carUsed && $carPlate === '')
        $errors['car_plate'] = 'required';

    

    if (!empty($errors)) {
    header('Location: /Pro_letter/documents/form_Memo.php?err=validate');
    exit;
}


    /** ===== เขียนฐานข้อมูล ===== */
    $pdo = db();
    $pdo->beginTransaction();

    // 1) map ฟิลด์
    $joinType = match ($purpose) {
        'academic' => 'นำเสนอผลงานทางวิชาการ',
        'training' => 'เข้ารับการฝึกอบรมหลักสูตร',
        'meeting' => 'เข้าร่วมประชุมวิชาการในงาน',
        default => 'อื่นๆ',
    };
    $subject = trim($joinType . $eventTitle);
    $q = $pdo->prepare("SELECT d.department_name, d.phone, f.faculty_name
                    FROM departments d
                    JOIN faculties f ON d.faculty_id = f.faculty_id
                    WHERE d.department_id = :id LIMIT 1");
    $q->execute([':id' => $departmentId]);
    $row = $q->fetch(PDO::FETCH_ASSOC);

    $hdrAgency = '';
    if ($row) {
        $hdrAgency = $row['faculty_name'] . ' ภาค' . $row['department_name'] . ' โทร. ' . $row['phone'];
    }


    if ($mode === 'update' && $documentId <= 0) {
    throw new Exception("Invalid document id for update");
}

   if ($mode === 'update') {

    // ตรวจว่าเป็นเจ้าของ + สถานะแก้ได้
    $chk = $pdo->prepare("
        SELECT owner_id, status
        FROM documents
        WHERE document_id = :id
        LIMIT 1
    ");
    $chk->execute([':id' => $documentId]);
    $doc = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        throw new Exception("Document not found");
    }
    if ($doc['owner_id'] != $userId) {
        throw new Exception("No permission");
    }
    if (!in_array($doc['status'], ['draft','rejected'])) {
        throw new Exception("Document locked");
    }

    // 🔄 UPDATE documents
    $stmt = $pdo->prepare("
        UPDATE documents
        SET
            template_id   = :template_id,
            department_id = :department_id,
            doc_date      = :doc_date,
            subject       = :subject,
            header_text   = :header_text,
            updated_at    = NOW()
        WHERE document_id = :id
    ");
    $stmt->execute([
        ':template_id' => $templateId,
        ':department_id' => $departmentId,
        ':doc_date' => $docDate,
        ':subject' => $subject,
        ':header_text' => $hdrAgency,
        ':id' => $documentId
    ]);

} else {
    // 🆕 CREATE
    $stmt = $pdo->prepare("
        INSERT INTO documents
        (template_id, owner_id, department_id, doc_no, doc_date, subject, header_text, status, remark)
        VALUES
        (:template_id, :owner_id, :department_id, NULL, :doc_date, :subject, :header_text, 'draft', NULL)
    ");
    $stmt->execute([
        ':template_id' => $templateId,
        ':owner_id' => $userId,
        ':department_id' => $departmentId,
        ':doc_date' => $docDate,
        ':subject' => $subject,
        ':header_text' => $hdrAgency
    ]);
    $documentId = (int) $pdo->lastInsertId();
}





    $values = [
        1 => $docDate,
        2 => $fullname,
        3 => $position,
        4 => $joinType,
        5 => $eventTitle,
        6 => ($dateOption === 'single') ? $singleDate : $rangeDate,
        7 => $isOnline ? 'เข้าร่วมรูปแบบออนไลน์' : $place,
        8 => number_format($amount, 2, '.', ''),
        9 => $carUsed ? $carPlate : '',
        10 => $faculty,
        11 => $department,
    ];

    // อนุญาตเฉพาะ field_id ที่ template นี้มีจริง
    $q = $pdo->prepare("SELECT field_id FROM template_fields WHERE template_id = :tid");
    $q->execute([':tid' => $templateId]);
    $allowIds = array_flip($q->fetchAll(PDO::FETCH_COLUMN));

    $ins = $pdo->prepare("
        INSERT INTO document_values (document_id, field_id, value_text)
        VALUES (:document_id, :field_id, :value_text)
        ON DUPLICATE KEY UPDATE value_text = VALUES(value_text)
    ");

    foreach ($values as $fieldId => $val) {
        if (!isset($allowIds[$fieldId]))
            continue;
        $ins->execute([
            ':document_id' => $documentId,
            ':field_id' => $fieldId,
            ':value_text' => $val
        ]);
    }

    $pdo->commit();

header(
  'Location: /Pro_letter/documents/view_memo.php?id='
  . $documentId
  . '&saved=1&from='
  . $mode
);
exit;




} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($DEBUG_ERRORS) {
        echo "<pre>";
        echo htmlspecialchars($e->getMessage());
        echo "</pre>";
        exit;
    }

    header('Location: /Pro_letter/documents/form_Memo.php?err=server');
    exit;
}