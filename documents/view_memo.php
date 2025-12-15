<?php    //pro_letter/documents/view_memo.php
session_start();
require_once __DIR__ . '/../functions.php';

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$referer = trim(str_replace(["\r","\n"],"", $referer));

/* --------------------------------------------------
   ตรวจ session
-------------------------------------------------- */
if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  exit("Unauthorized");
}

$userId = (int) $_SESSION['user_id'];
$role = strtolower($_SESSION['role_name'] ?? 'user');

/* --------------------------------------------------

   ตั้ง homePath ตาม role
-------------------------------------------------- */
$roleId = $_SESSION['role_id'] ?? 0;
$roleId = (int) ($_SESSION['role_id'] ?? 0);
$isAdmin = ($roleId === 1);
$isOfficer = ($roleId === 2);


if ($roleId == 1) {
  $homePath = "/Pro_letter/admin/home.php";
} elseif ($roleId == 2) {
  $homePath = "/Pro_letter/officer/home.php";
} else {
  $homePath = "/Pro_letter/user/home.php";
}


/* --------------------------------------------------
   รับ document_id
-------------------------------------------------- */
$pdo = db();
$docId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($docId <= 0) {
  $q = $pdo->prepare("
        SELECT document_id 
        FROM documents 
        WHERE owner_id = :uid
        ORDER BY document_id DESC
        LIMIT 1
    ");
  $q->execute([':uid' => $userId]);
  $docId = (int) ($q->fetchColumn() ?: 0);

  if ($docId <= 0)
    exit("ยังไม่มีเอกสารของคุณ");
}

/* --------------------------------------------------
   โหลดข้อมูลเอกสาร
-------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT document_id, template_id, owner_id, department_id, 
           doc_no, doc_date, subject, header_text, status
    FROM documents 
    WHERE document_id = :id
    LIMIT 1
");
$stmt->execute([':id' => $docId]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);
$docStatus = $document['status'];

$allowEditByStatus = in_array($docStatus, ['draft', 'rejected']);




if (!$document)
  exit("ไม่พบเอกสาร");

/* --------------------------------------------------
   สิทธิ์ดูเอกสาร
-------------------------------------------------- */
// Officer: role_id = 2
// Admin:   role_id = 1
$roleId = (int) ($_SESSION['role_id'] ?? 0);

// officer & admin ดูได้ทุกอัน
if ($roleId !== 1 && $roleId !== 2) {
  // user: ดูเฉพาะของตัวเอง
  if ($document['owner_id'] != $userId) {
    header("Location: {$homePath}?err=no_view");
    exit;
  }
}


/* --------------------------------------------------
   สิทธิ์แก้ไขเอกสาร
-------------------------------------------------- */
$sql = "
    SELECT COUNT(*) 
    FROM user_permissions up
    JOIN permissions p ON p.perm_id = up.perm_id
    WHERE up.user_id = :uid
    AND p.perm_code = 'document.edit'
";
$st = $pdo->prepare($sql);
$st->execute([':uid' => $userId]);

$hasEditPermission = $st->fetchColumn() > 0;   // สิทธิ์จาก permission
$allowEditByStatus = in_array($docStatus, ['draft', 'rejected']);

$canEdit = $hasEditPermission && $allowEditByStatus;
$readonly = !$canEdit;





/* --------------------------------------------------
   ดึงค่า field จาก document_values
-------------------------------------------------- */
$q = $pdo->prepare("SELECT field_id, value_text FROM document_values WHERE document_id = :id");
$q->execute([':id' => $docId]);

$valueMap = [];
foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $valueMap[(int) $row['field_id']] = $row['value_text'];
}

/* --------------------------------------------------
   ฟังก์ชัน helper
-------------------------------------------------- */


function thai_date($ymd)
{
  if (!$ymd || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd))
    return "";
  [$y, $m, $d] = explode("-", $ymd);
  $months = [
    1 => "มกราคม",
    2 => "กุมภาพันธ์",
    3 => "มีนาคม",
    4 => "เมษายน",
    5 => "พฤษภาคม",
    6 => "มิถุนายน",
    7 => "กรกฎาคม",
    8 => "สิงหาคม",
    9 => "กันยายน",
    10 => "ตุลาคม",
    11 => "พฤศจิกายน",
    12 => "ธันวาคม"
  ];
  return intval($d) . " " . $months[intval($m)] . " " . (intval($y) + 543);
}

/* --------------------------------------------------
   Mapping ตัวแปรหลักจาก document_values
-------------------------------------------------- */
$docDate = $valueMap[1] ?? $document['doc_date'];
$ownerName = $valueMap[2] ?? "";
$position = $valueMap[3] ?? "";
$joinType = $valueMap[4] ?? "";
$courseName = $valueMap[5] ?? "";
$joinDates = $valueMap[6] ?? "";
$location = $valueMap[7] ?? "";
$amountStr = $valueMap[8] ?? "";
$vehicle = $valueMap[9] ?? "";
$faculty = $valueMap[10] ?? "";
$department = $valueMap[11] ?? "";
/* --------------------------------------------------
   Mapping joinType → purposeCode (รหัส)
-------------------------------------------------- */
$purposeCode = 'other';

switch (trim($joinType)) {
  case 'นำเสนอผลงานทางวิชาการ':
    $purposeCode = 'academic';
    break;
  case 'เข้าร่วมประชุมวิชาการในงาน':
    $purposeCode = 'meeting';
    break;
  case 'เข้ารับการฝึกอบรมหลักสูตร':
    $purposeCode = 'training';
    break;
}

/* --------------------------------------------------
   ⭐⭐⭐ สำคัญที่สุด — แก้ให้ส่วนหัวขึ้น ⭐⭐⭐
-------------------------------------------------- */

$header_text = $document["header_text"] ?? "";
$doc_no = $document["doc_no"] ?? "";
$subject = $document["subject"] ?? "";

/* --------------------------------------------------
   คำนวณวันที่ไทย, งบประมาณ
-------------------------------------------------- */
$thaiDocDate = thai_date($docDate);
$prettyAmount = $amountStr !== "" ? number_format((float) $amountStr, 2) : "";

/* --------------------------------------------------
   สร้างข้อความส่วนหัวที่ใช้ในเนื้อหา
-------------------------------------------------- */
$hdr_agency = trim(
  ($faculty ?: "คณะ..................................") . " " .
  ($department ? "ภาควิชา" . $department : "ภาควิชา........................")
);

$hdr_subject = $joinType ?: "เข้ารับการฝึกอบรมหลักสูตร";
$hdr_to = "คณบดี" . ($faculty ?: "คณะ..................................");

/* --------------------------------------------------
   ปีไทย
-------------------------------------------------- */
$thaiYear = "";
if ($docDate && preg_match('/^\d{4}/', $docDate)) {
  $thaiYear = ((int) substr($docDate, 0, 4) + 543);
}

/* --------------------------------------------------
   ความกว้างของช่อง “เรื่อง”
-------------------------------------------------- */
$len = mb_strlen($subject, "UTF-8");
$len = max(20, $len);

?>



<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>บันทึกข้อความ #<?= h($document['document_id']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link rel="stylesheet" href="/Pro_letter/documents/memo-styles.css">

</head>

<body class="view-document">


  <?php if ($readonly): ?>
  <script>
  document.addEventListener("DOMContentLoaded", () => {



    // ซ่อนปุ่ม submit
    const submitBtn = document.querySelector("button[type=submit]");
    if (submitBtn) submitBtn.style.display = "none";

    // เปลี่ยนข้อความของปุ่มพิมพ์ให้อยู่ในโหมดตัวอย่าง
    const printBtn = document.querySelector("button[onclick='window.print()']");
    if (printBtn) printBtn.innerText = "พิมพ์/ดูตัวอย่าง";

    // แจ้งเตือนแสดง read-only
    Swal.fire({
      title: "โหมดอ่านอย่างเดียว",
      text: "คุณไม่มีสิทธิ์แก้ไขเอกสารนี้",
      icon: "info",
      confirmButtonText: "ตกลง"
    });
  });
  </script>
  <?php endif; ?>

  <?php if (isset($_GET['saved']) && $_GET['saved'] == '1'): ?>
  <div id="alertBox" class="bg-green-500 text-white px-4 py-2 rounded-md text-center mb-4 shadow-md">
    ✅ บันทึกสำเร็จ
  </div>
  <?php elseif (isset($_GET['err']) && $_GET['err'] == 'validate'): ?>
  <div id="alertBox" class="bg-red-500 text-white px-4 py-2 rounded-md text-center mb-4 shadow-md">
    ❌ กรุณากรอกข้อมูลให้ครบถ้วน
  </div>
  <?php elseif (isset($_GET['err']) && $_GET['err'] == 'server'): ?>
  <div id="alertBox" class="bg-red-600 text-white px-4 py-2 rounded-md text-center mb-4 shadow-md">
    ⚠️ เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง
  </div>
  <?php endif; ?>

  <main class="page">


    <!-- หัวบันทึก -->
    <div style="display:flex; align-items:flex-end; justify-content:flex-start; gap:20px; margin-bottom:0.5em;">
      <img src="https://i.pinimg.com/474x/bd/55/cc/bd55ccc4416012910a723da8f810658b.jpg"
        style="height:1.6cm; width:auto; margin-top:0;" />

      <h1 class="doc-title" style="font-size:30pt;font-weight:bold;font-family:'TH SarabunPSK';
      line-height:1.0;margin-bottom:-10px;text-align:center;flex:1;
      transform: translateX(-0.3cm);">
        บันทึกข้อความ
      </h1>
    </div>

    <!-- ส่วนราชการ -->
    <div class="doc-row">
      <div class="doc-label" style="font-size:20pt;font-weight:bold;">ส่วนราชการ</div>
      <div class="dot-line">
        <span class="chip">
          <?= h($header_text ?: 'คณะ... ภาค... โทร...') ?>
        </span>
      </div>
    </div>

    <div class="doc-row row-ty-date">
      <div class="doc-label" style="font-size:20pt;font-weight:bold;">ที่</div>

      <div class="dot-line ty-left">
        <span class="chip">
          <?= h($doc_no ?: 'ทส.486/2568') ?>
        </span>
      </div>

      <div class="doc-label" style="font-size:20pt;font-weight:bold;margin-left:1cm;">วันที่</div>

      <div class="dot-line ty-right">
        <span class="chip">
          <?= h($thaiDocDate ?: '') ?>
        </span>
      </div>
    </div>



    <!-- เรื่อง -->
    <div class="doc-row">
      <div class="doc-label" style="font-size:20pt;font-weight:bold;">เรื่อง</div>
      <div class="dot-line">
        <span class="chip">
          <?= h($subject ?: 'ขออนุมัติ...') ?>
        </span>
      </div>
    </div>



    <!-- เนื้อหา -->
    <div class="content-block single ">
      เรียน คณบดีคณะเทคโนโลยีและการจัดการอุตสาหกรรม
    </div>

    <div class="content-block paragraph">
      ตามที่ สมาคมสหกิจศึกษาไทย กำหนดจัดอบรมหลักสูตร
      <span class="chip"><?= h($courseName ?: 'ชื่อหลักสูตร') ?></span>
      ระหว่างวันที่ <span class="chip"><?= h($joinDates ?: '...') ?></span>
      ณ <span class="chip"><?= h($location ?: '...') ?></span> นั้น
      ซึ่งหลักสูตรดังกล่าวเป็นประโยชน์ต่อการพัฒนาทั้งกระบวนการจัดการเรียนการสอนในรูปแบบสหกิจศึกษา
    </div>

    <div class="content-block paragraph">
      การนี้ ข้าพเจ้า
      <span class="chip"><?= h($ownerName ?: 'ชื่อ-นามสกุล') ?></span>
      <span class="chip"><?= h($position ?: '') ?></span>
      สังกัดภาควิชา <span class="chip"><?= h($department ?: '...') ?></span>
      คณะ <span class="chip"><?= h($faculty ?: '...') ?></span>
      มหาวิทยาลัยเทคโนโลยีพระจอมเกล้าพระนครเหนือ วิทยาเขตปราจีนบุรี
      จึงมีความประสงค์ที่จะขออนุมัติ เข้ารับการอบรมหลักสูตร
      <span class="chip"><?= h($courseName ?: 'ชื่อหลักสูตร') ?></span>
      ระหว่างวันที่ <span class="chip"><?= h($joinDates ?: '') ?></span>
      ณ <span class="chip"><?= h($location ?: '') ?></span>
      วงเงินทั้งสิ้น <span class="chip"><?= h($prettyAmount ?: '') ?></span> บาท
      โดยขอใช้แหล่งเงินจัดสรรให้หน่วยงาน ประจำปีงบประมาณ
      <span class="chip">
        <?= h($thaiYear ? 'พ.ศ. ' . $thaiYear : 'พ.ศ. ....') ?>
      </span>

      แผนงานจัดการศึกษาระดับอุดมศึกษา กองทุนพัฒนาบุคลากร หมวดค่าใช้สอย (รายละเอียดตามเอกสารแนบ)
    </div>

    <div class="content-block paragraph">
      จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
    </div>

    <div class="signature-wrapper">
      <div class="signature-block" id="signatureBlock">
        <div class="sig-name">(<?= h($ownerName ?: '') ?>)</div>
        <div class="sig-position"><?= h($position ?: '') ?></div>
      </div>
    </div>

    <div style="font-family:'TH SarabunPSK'; font-size:16pt; line-height:1.2;"> เรียน <?= h($hdr_to) ?> </div>
    <div class="content-block single align-to-dean"> เพื่อโปรดพิจารณาอนุมัติ </div>
    <div class="content-block single align-to-dean" style="margin-top:50px;;"> (ผู้ช่วยศาสตราจารย์ ดร. ขนิษฐา
      นามี)<br /> หัวหน้าภาควิชาเทคโนโลยีสารสนเทศ </div>

    <div class="footer-actions">

      <!-- พิมพ์ -->
      <button type="button" onclick="window.print()"
        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md text-xl font-bold">
        พิมพ์/ดูตัวอย่าง
      </button>

      <!-- ปุ่มแก้ไข (ทุก role เห็นเหมือนกัน) -->
      <!-- ปุ่มแก้ไข -->
      <a href="/Pro_letter/documents/form_Memo.php?id=<?= $docId ?>" id="editBtn"
        data-can-edit="<?= $canEdit ? '1' : '0' ?>" class="px-6 py-2 rounded-md text-xl font-bold
   <?= $canEdit
      ? "bg-teal-500 hover:bg-teal-600 text-white"
      : "bg-gray-300 text-gray-600 cursor-not-allowed" ?>">
        แก้ไข
      </a>



      <!-- กลับ -->
      <a href="<?= $homePath ?>"
        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md text-xl font-bold">
        กลับหน้าหลัก
      </a>

    </div>


  </main>
  <<script>
    /* ===============================
    GLOBAL HELPERS
    =============================== */
    function getQuery(name) {
    const url = new URL(window.location.href);
    return url.searchParams.get(name);
    }

    /* ===============================
    DOM READY
    =============================== */
    document.addEventListener("DOMContentLoaded", () => {

    /* ---------------------------------
    1) LOCK VIEW DOCUMENT (chip)
    --------------------------------- */
    document.querySelectorAll(".view-document .chip").forEach(el => {
    el.removeAttribute("contenteditable");
    el.removeAttribute("tabindex");
    el.style.pointerEvents = "none";
    el.style.userSelect = "none";
    el.style.caretColor = "transparent";
    el.blur();
    });

    /* ---------------------------------
    2) READONLY MODE (PHP inject)
    ใช้ได้เฉพาะตอน $readonly = true
    --------------------------------- */
    <?php if ($readonly && !($isAdmin || $isOfficer)): ?>
    document.querySelectorAll("input, textarea, select").forEach(el => {
    el.disabled = true;
    el.style.background = "#f0f0f0";
    });

    const submitBtn = document.querySelector("button[type=submit]");
    if (submitBtn) submitBtn.style.display = "none";

    Swal.fire({
    title: "โหมดอ่านอย่างเดียว",
    text: "คุณไม่มีสิทธิ์แก้ไขเอกสารนี้",
    icon: "info",
    confirmButtonText: "ตกลง"
    });
    <?php endif; ?>

    /* ---------------------------------
    3) ALERT BOX AUTO HIDE
    --------------------------------- */
    const alertBox = document.getElementById("alertBox");
    if (alertBox) {
    setTimeout(() => {
    alertBox.style.transition = "opacity 0.5s ease";
    alertBox.style.opacity = 0;
    setTimeout(() => alertBox.remove(), 500);
    }, 3000);
    }

    /* ---------------------------------
    4) NO PERMISSION ALERT (?err=no_permission)
    --------------------------------- */
    const errType = getQuery("err");
    if (errType === "no_permission") {
    Swal.fire({
    title: "ไม่มีสิทธิ์แก้ไขเอกสารนี้",
    html: `
    <div style="font-size: 1.15rem; line-height: 1.6;">
      คุณไม่มีสิทธิ์ในการแก้ไขเอกสารนี้<br>
      ต้องการกลับหน้าหลักหรืออยู่ต่อ?
    </div>
    `,
    icon: "error",
    showCancelButton: true,
    confirmButtonText: "กลับหน้าหลัก",
    cancelButtonText: "อยู่หน้านี้ต่อ",
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#aaa",
    }).then((result) => {
    if (result.isConfirmed) {
    window.location.href = "<?= $homePath ?>";
    }
    });
    }

    /* ---------------------------------
    5) EDIT BUTTON PERMISSION CHECK
    --------------------------------- */
    const editBtn = document.getElementById("editBtn");
    if (editBtn) {
    editBtn.addEventListener("click", function (e) {
    const canEdit = this.dataset.canEdit === "1";
    if (!canEdit) {
    e.preventDefault();
    Swal.fire({
    title: "ไม่สามารถแก้ไขได้",
    text: "คุณไม่มีสิทธิ์แก้ไขเอกสารนี้",
    icon: "warning",
    confirmButtonText: "ตกลง"
    });
    }
    });
    }

    /* ---------------------------------
    6) SAVED FROM UPDATE ALERT
    --------------------------------- */
    if (getQuery("saved") === "1" && getQuery("from") === "update") {
    Swal.fire({
    title: "บันทึกสำเร็จ",
    text: "คุณต้องการกลับไปที่หน้าหลักหรือไม่?",
    icon: "success",
    showCancelButton: true,
    confirmButtonText: "กลับหน้าหลัก",
    cancelButtonText: "อยู่หน้านี้ต่อ",
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#aaa",
    }).then((result) => {
    if (result.isConfirmed) {
    window.location.href = "<?= $homePath ?>";
    }
    });
    }

    });
    </script>




</body>


</html>