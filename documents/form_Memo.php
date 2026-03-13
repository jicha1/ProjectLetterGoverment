<?php
// pro_letter/documents/form_Memo.php
$CURRENT_MAIN = "train";
$CURRENT_SUB  = "ฝึกอบรม";

session_start();
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$docId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $docId > 0;
$formData = [];

if ($isEdit) {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT document_id, owner_id, status
        FROM documents
        WHERE document_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) exit("ไม่พบเอกสาร");
   $roleId = (int)($_SESSION['role_id'] ?? 0);
    $isAdmin   = ($roleId === 1);
    $isOfficer = ($roleId === 2);
    if (!$isAdmin && !$isOfficer) {
        if ($doc['owner_id'] != $_SESSION['user_id']) {
            header("Location: view_memo.php?id={$docId}&err=no_permission");
            exit;

        }
        if (!in_array($doc['status'], ['draft','rejected'])) {
           header("Location: view_memo.php?id={$docId}&err=no_permission");
          exit;

        }
    }
    $q = $pdo->prepare("
        SELECT field_id, value_text
        FROM document_values
        WHERE document_id = :id
    ");
    $q->execute([':id' => $docId]);

    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $formData[(int)$row['field_id']] = $row['value_text'];
    }
}

$docDate     = $formData[1]  ?? '';
$ownerName   = $formData[2]  ?? '';
$position    = $formData[3]  ?? '';
$joinType    = $formData[4]  ?? '';   // purpose (ข้อความไทย)
$courseName  = $formData[5]  ?? '';
$joinDates   = $formData[6]  ?? '';
$location    = $formData[7]  ?? '';
$amountStr   = $formData[8]  ?? '';
$vehicle     = $formData[9]  ?? '';
$faculty     = $formData[10] ?? '';
$department  = $formData[11] ?? '';

$isRangeDate = preg_match('/\d+\s*-\s*\d+/', $joinDates);

$isOnline = ($location === 'เข้าร่วมรูปแบบออนไลน์');

$purpose = 'other';
if ($joinType === 'นำเสนอผลงานทางวิชาการ') {
    $purpose = 'academic';
} elseif ($joinType === 'เข้าร่วมประชุมวิชาการในงาน') {
    $purpose = 'meeting';
} elseif ($joinType === 'เข้ารับการฝึกอบรมหลักสูตร') {
    $purpose = 'training';
}
$purposeOther = ($purpose === 'other') ? $joinType : '';


?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>แบบฟอร์มบันทึกข้อความ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
  @import url("https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap");

  html,
  :root {
    --base-fs: 16px;
  }

  body,
  label,
  input,
  textarea,
  select,
  option,
  button,
  span,
  div {
    font-size: var(--base-fs);
  }

  select,
  input,
  textarea {
    line-height: 1.4;
  }

  select option {
    font-size: var(--base-fs);
  }

  #requestListContainer {
    flex: 1;
    overflow-y: auto;
  }

  .custom-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background: white;
    border: 2px solid #11c2b9;
    border-radius: 1rem;
    padding: 0.5rem 2.5rem 0.5rem 0.75rem;
    background-image: url('data:image/svg+xml;utf8,<svg fill="%23000000" height="16" viewBox="0 0 20 20" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M5.516 7.548l4.486 4.448 4.486-4.448L15.56 9l-5.558 5.5L4.444 9z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1rem;
  }

  .custom-select:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(17, 194, 185, 0.35);
  }

  .error {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15);
  }

  .lbl.asterisk::after {
    content: " *";
    color: #ef4444;
    font-weight: 700;
    margin-left: 4px;
  }

  .hint {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
    padding: 4px 8px;
    border-radius: 8px;
    margin-top: 6px;
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.03);
  }

  .hint svg {
    min-width: 16px;
    min-height: 16px;
  }

  .hint:before {
    content: "";
    position: absolute;
    top: -6px;
    left: 16px;
    border-width: 6px;
    border-style: solid;
    border-color: transparent transparent #ef4444 transparent;
  }

  .hint:after {
    content: "";
    position: absolute;
    top: -5px;
    left: 16px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent transparent #fee2e2 transparent;
  }

  .shake {
    animation: shake 0.2s linear 0s 2;
  }

  @keyframes shake {

    0%,
    100% {
      transform: translateX(0);
    }

    25% {
      transform: translateX(-3px);
    }

    75% {
      transform: translateX(3px);
    }
  }
  </style>
</head>

<body class="bg-gray-100">
  <header class="bg-teal-500 text-white p-4 flex justify-between items-center shadow-md"
    style="font-family: Arial, Helvetica, sans-serif">
    <div class="flex items-center space-x-3">
      <div class="w-[56px] h-[56px] flex items-center justify-center relative overflow-visible">
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute scale-[1.4] text-white"
          style="width: 60px; height: 60px" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m0 0a2 2 0 00-2-2H5a2 2 0 00-2 2m18 0v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8" />
        </svg>
      </div>
      <div class="leading-tight">
        <div class="text-[16px] font-bold">Smart</div>
        <div class="text-[16px] font-bold -mt-[2px]">Government</div>
        <div class="text-[13px] mt-[0px]">Letter Management System</div>
      </div>
    </div>
    <div class="flex items-center space-x-4">
      <a href="/Pro_letter/user/home.php">
        <div class="px-4 py-2 rounded-[11px] font-bold transition text-white">
          หน้าหลัก
        </div>
      </a>
      <?php if (isset($_SESSION['permissions']) && in_array(3, $_SESSION['permissions'])) {
                    renderAdminExtraMenus(); }?>
      <a href="form_Memo.php">
        <div class="px-4 py-2 rounded-[11px] font-bold transition bg-white text-teal-500 shadow">
          แบบฟอร์มบันทึกข้อความ
        </div>
      </a>
      <div class="relative">
        <button id="profileBtn"
          class="bg-white text-teal-500 px-4 py-2 rounded-[11px] shadow flex items-center space-x-2 hover:bg-gray-100">
          <div class="text-right leading-tight">
            <div class="font-bold text-[14px]">
              <?= htmlspecialchars($_SESSION['fullname'] ?? 'Guest') ?>
            </div>
            <div class="text-[12px]">
              <?= htmlspecialchars($_SESSION['role_name'] ?? '') ?>
            </div>
          </div>
          <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M5.121 17.804A13.937 13.937 0 0112 15c2.33 0 4.487.577 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
        </button>
        <div id="profileMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg z-50">
          <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">ออกจากระบบ</a>
          <button onclick="closeMenu()"
            class="w-full text-left px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">อยู่ต่อ</button>
        </div>
      </div>
    </div>
  </header>
  <form method="post" action="save_memo.php" id="memoForm">
    <input type="hidden" name="template_id" value="1">
    <input type="hidden" name="department_id" value="1">
    <?php if ($isEdit): ?>
    <input type="hidden" name="document_id" value="<?= (int)$docId ?>">
    <input type="hidden" name="mode" value="update">
    <?php else: ?>
    <input type="hidden" name="mode" value="create">
    <?php endif; ?>
    <div id="step1">
      <div class="w-[900px] mx-auto mt-16 mb-6 bg-white shadow-md rounded-md p-8" style="min-height: 1122px">
        <h1 class="text-center font-bold mb-6 text-black">
          แบบฟอร์มบันทึกข้อความ
        </h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-6 rounded-[25px] border-2" style="
            background-color: #e3f9f8;
            border-color: #11c2b9;
            min-height: 170px;
          ">
          <div class="flex items-center gap-3">
            <label class="lbl text-gray-800 w-28 text-right">หมวดหลัก:</label>
            <div class="relative w-full">
              <select name="main_category" class="custom-select w-full" id="mainCategory">
                <option value="">-- เลือกหมวดหลัก --</option>
                <option value="external" <?= ($CURRENT_MAIN=="external"?"selected":"") ?>>ภายใน(บันทึกข้อความ)</option>
                <option value="internal" <?= ($CURRENT_MAIN=="internal"?"selected":"") ?>>ภายนอก</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <label class="lbl text-gray-800 w-28 text-right">หมวดย่อย:</label>
            <div class="relative w-full">
              <select name="sub_category" class="custom-select w-full" id="subCategory"
                data-current="<?= h($CURRENT_SUB ?? '') ?>" disabled>
                <option value="">-- เลือกหมวดย่อย --</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <label class="lbl text-gray-800 w-28 text-right">คณะ:</label>
            <div class="relative w-full">
              <select name="faculty" class="custom-select w-full" id="faculty">
                <option>คณะเทคโนโลยีและการจัดการอุตสาหกรรม</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <label class="lbl text-gray-800 w-28 text-right">ภาควิชา:</label>
            <div class="relative w-full">
              <select name="department" class="custom-select w-full" id="dept">
                <option>เทคโนโลยีสารสนเทศ</option>
              </select>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 items-end">
          <div class="flex items-center gap-3">
            <label class="lbl text-gray-800 whitespace-nowrap" for="docDateDisplay">1.วัน เดือน ปี :</label>
            <div class="relative">
              <input type="text" id="docDateDisplay" value="<?= h($formData[1] ?? '') ?>"
                class="border rounded-md p-2 shadow-sm w-48 pr-10 cursor-pointer" placeholder="เลือกวันที่" readonly />
              <input type="hidden" name="doc_date" id="docDate" value="<?= h($formData[1] ?? '') ?>" />
              <svg class="pointer-events-none absolute right-3 top-2.5 w-5 h-5 text-[#11C2B9]"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
              </svg>
            </div>
            <label class="lbl text-gray-800 whitespace-nowrap">ที่ต้องการให้ปรากฎบนบันทึกข้อความ</label>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 items-end">
          <div class="flex items-center gap-3">
            <label class="lbl text-gray-800 whitespace-nowrap" for="fullname">2.ชื่อ - นามสกุล :</label>
            <input type="text" name="fullname" class="flex-1 border rounded-md p-2" id="fullname"
              value="<?= htmlspecialchars($_SESSION['fullname'] ) ?>" />
          </div>
          <div class="flex items-center gap-3">
            <label class="lbl text-gray-800 whitespace-nowrap" for="position">ตำแหน่ง :</label>
            <input type="text" name="position" class="flex-1 border rounded-md p-2" id="position"
              value="<?= $isEdit ? htmlspecialchars($_SESSION['role_name'] ?? '') : 'อาจารย์ประจำภาควิชาเทคโนโลยีสารสนเทศ' ?>" />
          </div>
        </div>
        <div class="mb-4">
          <div class="flex items-start gap-2">
            <label class="lbl text-gray-800 whitespace-nowrap mt-1" id="purposeLabel">
              3.ขออนุมัติไปเข้าร่วม
            </label>
            <div class="space-y-1 text-gray-800" id="purposeGroup" role="radiogroup" aria-labelledby="purposeLabel">
              <label class="flex items-center gap-2">
                <input type="radio" name="purpose" value="academic" class="accent-black"
                  <?= ($purpose === 'academic') ? 'checked' : '' ?> />
                นำเสนอผลงานทางวิชาการ
              </label>
              <label class="flex items-center gap-2">
                <input type="radio" name="purpose" value="training" class="accent-black"
                  <?= ($purpose === 'training') ? 'checked' : '' ?> />
                เข้ารับการฝึกอบรมหลักสูตร
              </label>
              <label class="flex items-center gap-2">
                <input type="radio" name="purpose" value="meeting" class="accent-black"
                  <?= ($purpose === 'meeting') ? 'checked' : '' ?> />
                เข้าร่วมประชุมวิชาการในงาน
              </label>
              <label class="flex items-center gap-2">
                <input type="radio" name="purpose" value="other" class="accent-black" id="purposeOtherRadio"
                  <?= ($purpose === 'other') ? 'checked' : '' ?> />
                อื่น ๆ
                (ระบุ)
                <input type="text" name="purpose_other_detail" id="purposeOtherInput"
                  class="border rounded-md p-2 w-[260px] ml-3 <?= ($purpose === 'other') ? '' : 'bg-gray-100 text-gray-400' ?>"
                  placeholder="โปรดระบุ" value="<?= h($purposeOther) ?>"
                  <?= ($purpose === 'other') ? '' : 'disabled' ?> />
              </label>
            </div>
          </div>
        </div>
        <div class="mb-4 flex items-start gap-4">
          <label class="lbl text-gray-800 whitespace-nowrap pt-2" for="eventTitle">
            4.ชื่อของงานประชุมวิชาการ /<br />ชื่อหลักสูตรอบรม :
          </label>
          <div class="w-full">
            <textarea name="event_title" rows="2" class="w-full border rounded-md p-2 shadow-sm"
              id="eventTitle"><?= h($formData[5] ?? '') ?></textarea>
          </div>
        </div>
        <div class="mb-6">
          <label class="lbl text-gray-800 block mb-2" id="dateLabel">5. วันที่เข้าร่วม</label>
          <div class="space-y-4 ml-6 text-gray-800">
            <div class="flex items-center gap-2">
              <input type="radio" name="date_option" value="single" id="optSingle" class="accent-[#11C2B9]"
                <?= !$isRangeDate ? 'checked' : '' ?> />
              <span>วันเดียว :</span>
              <div class="relative">
                <input type="text" name="single_date" id="singleDate"
                  class="border rounded-md p-2 shadow-sm w-48 pr-10 cursor-pointer" placeholder="เลือกวันที่"
                  readonly />
                <svg class="absolute right-3 top-2.5 w-5 h-5 text-[#11C2B9]" xmlns="http://www.w3.org/2000/svg"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <input type="radio" name="date_option" value="range" id="optRange" class="accent-[#11C2B9]"
                <?= $isRangeDate ? 'checked' : '' ?> />
              <span>หลายวัน :</span>
              <div class="relative">
                <input type="text" id="startDate" class="border rounded-md p-2 shadow-sm w-44 pr-10 cursor-pointer"
                  placeholder="เริ่มต้น" readonly />
                <svg class="absolute right-3 top-2.5 w-5 h-5 text-[#11C2B9]" xmlns="http://www.w3.org/2000/svg"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                </svg>
              </div>
              <span>ถึง</span>
              <div class="relative">
                <input type="text" id="endDate" class="border rounded-md p-2 shadow-sm w-44 pr-10 cursor-pointer"
                  placeholder="สิ้นสุด" readonly />
                <svg class="absolute right-3 top-2.5 w-5 h-5 text-[#11C2B9]" xmlns="http://www.w3.org/2000/svg"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                </svg>
              </div>
              <input type="text" id="rangeDisplay" class="border rounded-md p-2 shadow-sm w-64 bg-gray-50 text-gray-600"
                placeholder="10 - 11 กรกฎาคม 2568" readonly />
              <input type="hidden" name="join_date" id="joinDate" value="<?= h($formData[6] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="mb-6">
          <label class="lbl text-gray-800 block mb-2">
            6. ชื่อสถานที่จัดประชุมวิชาการ / สถานที่จัดอบรม / เข้าร่วมรูปแบบออนไลน์
          </label>
          <div class="flex items-center ml-6 gap-2 mb-3">
            <input type="radio" name="is_online" value="1" id="onlineCheckbox" class="accent-black"
              <?= $isOnline ? 'checked' : '' ?>>
            <label for="onlineCheckbox">เข้าร่วมในรูปแบบออนไลน์</label>
          </div>
          <div class="flex items-center ml-6 gap-2">
            <input type="radio" name="is_online" value="0" id="onsiteCheckbox" class="accent-black"
              <?= !$isOnline ? 'checked' : '' ?>>
            <label for="onsiteCheckbox">เข้าร่วมในรูปแบบออนไซต์</label>
            <label class="lbl text-gray-800 ml-4 mr-2" for="placeOnsite">ระบุสถานที่ไป :</label>
            <input type="text" name="place" id="placeOnsite" class="border rounded-md p-2 w-[400px]
  <?= !$isOnline ? '' : 'bg-gray-100 text-gray-400' ?>" value="<?= !$isOnline ? h($location) : '' ?>"
              <?= !$isOnline ? '' : 'disabled' ?>>
          </div>
        </div>
        <div class="mb-6">
          <div class="flex items-center gap-2 mb-2">
            <label class="lbl text-gray-800" for="amountInput">7.รวมยอดประมาณการค่าใช้จ่าย :</label>
            <div class="flex items-center gap-2">
              <input type="text" name="amount" class="border rounded-md p-2 w-36" id="amountInput"
                value="<?= h($formData[8] ?? '0.00') ?>" />
              <span>บาท</span>
            </div>
          </div>
          <?php $noCostChecked = (!empty($formData[8]) && (float)$formData[8] == 0.0) ? 'checked' : ''; ?>
          <label class="flex items-center gap-2 ml-6 mt-2">
            <input type="checkbox" name="no_cost" value="1" class="accent-black" id="noCostCheckbox"
              <?= $noCostChecked ?> />
            โดยไม่เบิกค่าใช้จ่ายใดๆทั้งสิ้น
          </label>
        </div>
        <div class="mb-6">
          <label class="lbl block text-gray-800 mb-2" id="carLabel">
            8. กรณีไปรถยนต์ส่วนบุคคล
          </label>
          <div class="flex items-center gap-3 ml-6">
            <input type="checkbox" id="carCheckbox" name="car_used" class="accent-black"
              <?= !empty($formData[9]) ? 'checked' : '' ?> />
            <label for="carCheckbox" class="lbl">ใช้รถยนต์ส่วนบุคคล</label>
            <input type="text" name="car_plate" id="carPlateInput"
              class="border rounded-md p-2 w-[260px] bg-gray-100 text-gray-400" placeholder="เช่น กร 1234 กรุงเทพมหานคร"
              value="<?= h($formData[9] ?? '') ?>" disabled>
          </div>
        </div>
        <div class="relative mt-20">
          <div class="absolute right-0 bottom-0 flex gap-3">
            <button type="button" id="nextBtn"
              class="bg-[#11C2B9] hover:bg-[#0fa39c] text-white font-bold w-[130px] h-[35px] rounded-md transition">
              ถัดไป
            </button>
            <button type="submit" id="submitBtn"
              class="bg-[#11C2B9] hover:bg-[#0fa39c] text-white font-bold w-[130px] h-[35px] rounded-md transition hidden">
              ดำเนินการ
            </button>
          </div>
        </div>
      </div>
    </div>
    <div id="step2" class="hidden">
      <div class="w-[900px] mx-auto mt-16 mb-6 bg-white shadow-md rounded-md p-8" style="min-height: 1122px">
        <h1 class="text-center font-bold mb-6 text-black">แบบฟอร์มประมาณการค่าใช้จ่าย</h1>
        <div class="mb-8 p-6 rounded-[25px] border-2" style="background-color:#e3f9f8;border-color:#11c2b9;">
          <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="text-gray-800 font-bold">สรุปยอดรวมรายจ่าย</div>
            <div class="flex items-center gap-2">
              <input id="totalAmount" type="text" class="border rounded-md p-2 w-40 bg-gray-50 text-gray-700"
                value="0.00" readonly>
              <span class="text-gray-800 font-bold">บาท</span>
            </div>
          </div>
          <div class="text-gray-600 text-sm mt-2">* ระบบคำนวณให้อัตโนมัติ (บันทึกยอดรวมลงเอกสารให้)</div>
        </div>
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div class="font-bold text-gray-800">1. ค่าตอบแทน</div>
            <button type="button" id="addCompBtn"
              class="bg-[#11C2B9] hover:bg-[#0fa39c] text-white font-bold px-4 py-2 rounded-md transition">
              + เพิ่มรายการ
            </button>
          </div>
          <div id="compList" class="mt-3 space-y-3"></div>
          <div id="compEmpty" class="mt-3 text-gray-500">
            1.1 <span class="italic">ไม่มีรายการ</span> — 0.00 บาท
          </div>
        </div>
        <div class="mb-8">
          <div class="font-bold text-gray-800 mb-3">2. ค่าใช้สอย</div>
          <div class="p-4 rounded-[20px] border-2 mb-4" style="border-color:#11c2b9;background:#f7fffe;">
            <div class="flex items-center justify-between">
              <label class="font-bold text-gray-800 flex items-center gap-2">
                <input type="checkbox" id="regEnabled" class="accent-black">
                2.1 ค่าลงทะเบียน
              </label>
              <div class="text-gray-800 font-bold">
                <span id="regTotal">0.00</span> บาท
              </div>
            </div>
            <div id="regForm" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label class="text-gray-700">ราคา (บาท)</label>
                <input type="number" id="regPrice" class="w-full border rounded-md p-2" min="0" step="0.01" value="0">
              </div>
              <div>
                <label class="text-gray-700">จำนวนคน</label>
                <input type="number" id="regPeople" class="w-full border rounded-md p-2" min="1" step="1" value="1">
              </div>
              <div class="text-gray-600 flex items-end">
                <span>(ราคา × คน)</span>
              </div>
            </div>
          </div>
          <div class="p-4 rounded-[20px] border-2 mb-4" style="border-color:#11c2b9;background:#f7fffe;">
            <div class="flex items-center justify-between">
              <label class="font-bold text-gray-800 flex items-center gap-2">
                <input type="checkbox" id="lodEnabled" class="accent-black">
                2.2 ค่าที่พักค้างคืน
              </label>
              <div class="text-gray-800 font-bold">
                <span id="lodTotal">0.00</span> บาท
              </div>
            </div>
            <div id="lodForm" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
              <div class="md:col-span-2">
                <label class="text-gray-700">ช่วงวันที่ (เช่น 2 – 3 ก.พ. 68)</label>
                <input type="text" id="lodDateText" class="w-full border rounded-md p-2" placeholder="2 – 3 ก.พ. 68">
              </div>
              <div>
                <label class="text-gray-700">ราคา/คืน</label>
                <input type="number" id="lodUnit" class="w-full border rounded-md p-2" min="0" step="0.01" value="0">
              </div>
              <div>
                <label class="text-gray-700">จำนวนคืน</label>
                <input type="number" id="lodNights" class="w-full border rounded-md p-2" min="1" step="1" value="1">
              </div>
              <div>
                <label class="text-gray-700">จำนวนคน</label>
                <input type="number" id="lodPeople" class="w-full border rounded-md p-2" min="1" step="1" value="1">
              </div>
              <div class="text-gray-600 md:col-span-4">
                <span>(ราคา/คืน × คืน × คน)</span>
              </div>
            </div>
          </div>
          <div class="p-4 rounded-[20px] border-2 mb-4" style="border-color:#11c2b9;background:#f7fffe;">
            <div class="flex items-center justify-between">
              <label class="font-bold text-gray-800 flex items-center gap-2">
                <input type="checkbox" id="perEnabled" class="accent-black">
                2.3 ค่าเบี้ยเลี้ยง
              </label>
              <div class="text-gray-800 font-bold">
                <span id="perTotal">0.00</span> บาท
              </div>
            </div>
            <div id="perForm" class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label class="text-gray-700">ราคา/มื้อ</label>
                <input type="number" id="perUnit" class="w-full border rounded-md p-2" min="0" step="0.01" value="0">
              </div>
              <div>
                <label class="text-gray-700">จำนวนมื้อ</label>
                <input type="number" id="perMeals" class="w-full border rounded-md p-2" min="1" step="1" value="1">
              </div>
              <div>
                <label class="text-gray-700">จำนวนคน</label>
                <input type="number" id="perPeople" class="w-full border rounded-md p-2" min="1" step="1" value="1">
              </div>
              <div class="text-gray-600 md:col-span-3">
                <span>(ราคา/มื้อ × มื้อ × คน)</span>
              </div>
            </div>
          </div>
          <div class="p-4 rounded-[20px] border-2 mb-4" style="border-color:#11c2b9;background:#f7fffe;">
            <div class="flex items-center justify-between">
              <label class="font-bold text-gray-800 flex items-center gap-2">
                <input type="checkbox" id="trEnabled" class="accent-black">
                2.4 ค่าพาหนะ
              </label>
              <div class="text-gray-800 font-bold">
                <span id="trTotal">0.00</span> บาท
              </div>
            </div>
            <div class="mt-3">
              <button type="button" id="addTrItemBtn"
                class="bg-white border-2 border-[#11C2B9] text-[#0f766e] font-bold px-4 py-2 rounded-md hover:bg-gray-50 transition">
                + เพิ่มรายการย่อยพาหนะ
              </button>
            </div>
            <div id="trList" class="mt-3 space-y-3"></div>
            <div id="trEmpty" class="mt-3 text-gray-500">
              - ไม่มีรายการพาหนะ — 0.00 บาท
            </div>
          </div>
        </div>
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div class="font-bold text-gray-800">3. ค่าวัสดุ</div>
            <button type="button" id="addMatBtn"
              class="bg-[#11C2B9] hover:bg-[#0fa39c] text-white font-bold px-4 py-2 rounded-md transition">
              + เพิ่มรายการ
            </button>
          </div>
          <div id="matList" class="mt-3 space-y-3"></div>
          <div id="matEmpty" class="mt-3 text-gray-500">
            3.1 <span class="italic">ไม่มีรายการ</span> — 0.00 บาท
          </div>
        </div>
        <div class="mt-6 text-gray-800">
          <span class="font-bold">หมายเหตุ</span> ขอถัวจ่ายทุกรายการ
        </div>
        <div class="flex justify-between items-end mt-20">
          <input type="hidden" name="total_amount" id="totalAmountHidden" value="0.00">
          <button type="button" id="backBtn"
            class="bg-[#11C2B9] hover:bg-[#0fa39c] text-white font-bold w-[130px] h-[35px] rounded-md transition">
            ย้อนกลับ
          </button>
          <button type="submit" id="finalSubmitBtn"
            class="bg-[#11C2B9] hover:bg-[#0fa39c] text-white font-bold w-[130px] h-[35px] rounded-md transition">
            ดำเนินการ
          </button>
        </div>
      </div>
    </div>
  </form>
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    if (typeof flatpickr === "undefined") {
      console.error("flatpickr not loaded");
      return;
    }
    flatpickr.localize(flatpickr.l10ns.th);
    const monthsTH = [
      "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
      "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];
    const memoForm = document.getElementById("memoForm");

    const mainCategory = document.getElementById("mainCategory");
    const subCategory = document.getElementById("subCategory");


    const docDateDisplay = document.getElementById("docDateDisplay"); // ไทย พ.ศ.
    const docDateHidden = document.getElementById("docDate"); // YYYY-MM-DD (ส่ง DB)

    const fullname = document.getElementById("fullname");
    const position = document.getElementById("position");

    const purposeRadios = document.querySelectorAll('input[name="purpose"]');
    const purposeOtherRadio = document.getElementById("purposeOtherRadio");
    const purposeOtherInput = document.getElementById("purposeOtherInput");

    const eventTitle = document.getElementById("eventTitle");

    const optSingle = document.getElementById("optSingle");
    const optRange = document.getElementById("optRange");
    const singleDate = document.getElementById("singleDate");
    const startDate = document.getElementById("startDate");
    const endDate = document.getElementById("endDate");
    const rangeDisplay = document.getElementById("rangeDisplay");
    const joinDate = document.getElementById("joinDate"); // hidden (ส่งเข้า PHP)

    const onlineCheckbox = document.getElementById("onlineCheckbox");
    const onsiteCheckbox = document.getElementById("onsiteCheckbox");
    const placeOnsite = document.getElementById("placeOnsite");

    const amountInput = document.getElementById("amountInput");
    const noCostCheckbox = document.getElementById("noCostCheckbox");

    const carCheckbox = document.getElementById("carCheckbox");
    const carPlateInput = document.getElementById("carPlateInput");

    function clearError(el) {
      if (!el) return;
      el.classList.remove("error", "shake");
      const old = el.parentElement?.querySelector(".hint");
      if (old) old.remove();
    }

    function setError(el, msg) {
      if (!el) return;
      clearError(el);
      el.classList.add("error", "shake");

      const hint = document.createElement("div");
      hint.className = "hint";
      hint.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24">
        <path d="M12 9v4m0 4h.01M10.29 3.86l-7.5 13A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.71-3.14l-7.5-13a2 2 0 0 0-3.42 0Z"
          stroke="#991b1b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>${msg}</span>
    `;
      (el.parentElement || el).appendChild(hint);
    }

    function scrollToFirstError(firstEl) {
      if (!firstEl) return;
      firstEl.scrollIntoView({
        behavior: "smooth",
        block: "center"
      });
      setTimeout(() => firstEl.focus?.(), 150);
    }

    function toThaiDisplay(dateObj) {
      const d = dateObj.getDate();
      const m = monthsTH[dateObj.getMonth()];
      const y = dateObj.getFullYear() + 543;
      return `${d} ${m} ${y}`;
    }

    function parseYMD(ymd) {

      const m = (ymd || "").match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (!m) return null;
      const y = parseInt(m[1], 10);
      const mo = parseInt(m[2], 10) - 1;
      const d = parseInt(m[3], 10);
      return new Date(y, mo, d);
    }

    function parseThaiSingle(raw) {

      const match = (raw || "").match(/(\d+)\s*-\s*(\d+)\s*(.+)\s*(\d{4})/);
      if (!match) return null;

      const d1 = parseInt(match[1], 10);
      const d2 = parseInt(match[2], 10);
      const monthName = match[3].trim();
      const year = parseInt(match[4], 10) - 543;

      const monthIndex = monthsTH.indexOf(monthName);
      if (monthIndex === -1) return null;

      return [new Date(year, monthIndex, d1), new Date(year, monthIndex, d2)];
    }

    function syncPurposeUI() {
      if (purposeOtherRadio && purposeOtherRadio.checked) {
        purposeOtherInput.disabled = false;
        purposeOtherInput.classList.remove("bg-gray-100", "text-gray-400");
      } else if (purposeOtherInput) {
        purposeOtherInput.value = "";
        purposeOtherInput.disabled = true;
        purposeOtherInput.classList.add("bg-gray-100", "text-gray-400");
      }
    }
    purposeRadios.forEach(r => r.addEventListener("change", syncPurposeUI));

    function syncPlaceUI() {
      if (onlineCheckbox?.checked) {
        placeOnsite.value = "เข้าร่วมรูปแบบออนไลน์";
        placeOnsite.readOnly = true;
        placeOnsite.disabled = false;
        placeOnsite.classList.add("bg-gray-100", "text-gray-400");
      }
      if (onsiteCheckbox?.checked) {
        placeOnsite.readOnly = false;
        placeOnsite.disabled = false;
        placeOnsite.classList.remove("bg-gray-100", "text-gray-400");
        if (placeOnsite.value === "เข้าร่วมรูปแบบออนไลน์") placeOnsite.value = "";
      }
    }
    onlineCheckbox?.addEventListener("change", syncPlaceUI);
    onsiteCheckbox?.addEventListener("change", syncPlaceUI);

    function syncCostUI() {
      if (noCostCheckbox?.checked) {
        amountInput.value = "0.00";
        amountInput.readOnly = true;
        amountInput.disabled = false;
        amountInput.classList.add("bg-gray-100", "text-gray-400");
      } else {
        amountInput.readOnly = false;
        amountInput.disabled = false;
        amountInput.classList.remove("bg-gray-100", "text-gray-400");
      }
    }
    noCostCheckbox?.addEventListener("change", syncCostUI);

    function syncCarUI() {
      if (carCheckbox?.checked) {
        carPlateInput.disabled = false;
        carPlateInput.classList.remove("bg-gray-100", "text-gray-400");
      } else {
        carPlateInput.value = "";
        carPlateInput.disabled = true;
        carPlateInput.classList.add("bg-gray-100", "text-gray-400");
        clearError(carPlateInput);
      }
    }
    carCheckbox?.addEventListener("change", syncCarUI);
    const docPicker = flatpickr(docDateDisplay, {
      disableMobile: true,
      allowInput: false,
      clickOpens: true,
      dateFormat: "Y-m-d", // internal (เราเอาไปใส่ hidden)
      onReady: (selectedDates, dateStr, inst) => {
        const d = parseYMD(docDateHidden?.value);
        if (d) {
          inst.setDate(d, false);
          docDateDisplay.value = toThaiDisplay(d);
          docDateHidden.value = inst.formatDate(d, "Y-m-d");
        }
      },
      onChange: (selectedDates, dateStr, inst) => {
        const d = selectedDates[0];
        if (!d) return;
        docDateDisplay.value = toThaiDisplay(d); // ไทย พ.ศ.
        docDateHidden.value = inst.formatDate(d, "Y-m-d"); // ✅ ส่ง DB
        clearError(docDateDisplay);
      }
    });
    docDateDisplay?.addEventListener("click", () => docPicker.open());

    const singlePicker = flatpickr("#singleDate", {
      disableMobile: true,
      allowInput: false,
      clickOpens: true,
      onChange: ([d], _, inst) => {
        if (d) {
          inst.input.value = toThaiDisplay(d);
          joinDate.value = toThaiDisplay(d);
        }
      }
    });

    const startPicker = flatpickr("#startDate", {
      disableMobile: true,
      allowInput: false,
      clickOpens: true,
      onChange: updateRangeDisplay
    });
    const endPicker = flatpickr("#endDate", {
      disableMobile: true,
      allowInput: false,
      clickOpens: true,
      onChange: updateRangeDisplay
    });

    function updateRangeDisplay() {
      if (!startPicker.selectedDates[0] || !endPicker.selectedDates[0]) return;

      const d1 = startPicker.selectedDates[0];
      const d2 = endPicker.selectedDates[0];

      const y1 = d1.getFullYear() + 543;
      const y2 = d2.getFullYear() + 543;
      const m1 = monthsTH[d1.getMonth()];
      const m2 = monthsTH[d2.getMonth()];

      let text = "";
      if (m1 === m2 && y1 === y2) text = `${d1.getDate()} - ${d2.getDate()} ${m1} ${y1}`;
      else text = `${d1.getDate()} ${m1} ${y1} - ${d2.getDate()} ${m2} ${y2}`;

      rangeDisplay.value = text;
      joinDate.value = text;
    }

    function toggleDatePickers() {
      const single = optSingle.checked;
      singleDate.disabled = !single;
      startDate.disabled = single;
      endDate.disabled = single;
      rangeDisplay.disabled = single;

      clearError(singleDate);
      clearError(rangeDisplay);
      clearError(startDate);
      clearError(endDate);
    }
    optSingle?.addEventListener("change", toggleDatePickers);
    optRange?.addEventListener("change",
      toggleDatePickers);

    if (joinDate && joinDate.value.trim()) {
      const raw = joinDate.value.trim();
      if (raw.includes("-") || raw.includes("ถึง")) {
        optRange.checked = true;
        toggleDatePickers();
        const dates = parseThaiRange(raw);
        if (dates) {
          startPicker.setDate(dates[0], false);
          endPicker.setDate(dates[1], false);
          rangeDisplay.value = raw;
        }
      } else {
        optSingle.checked = true;
        toggleDatePickers();
        const d = parseThaiSingle(raw);
        if (d) {
          singlePicker.setDate(d, false);
          singleDate.value = raw;
        }
      }
    }

    memoForm?.addEventListener("submit", (event) => {
      // บังคับ sync ค่า display -> hidden ก่อน submit
      if (docDateDisplay?.value && !docDateHidden?.value) {
        const d = docPicker.selectedDates[0];
        if (d) {
          docDateHidden.value = docPicker.formatDate(d, "Y-m-d");
        }
      }
      [
        mainCategory, subCategory,
        docDateDisplay, fullname, position,
        purposeOtherInput, eventTitle,
        singleDate, startDate, endDate, rangeDisplay,
        placeOnsite, amountInput, carPlateInput
      ].forEach(clearError);

      let firstError = null;

      if (!mainCategory?.value?.trim()) {
        firstError = firstError || mainCategory;
        setError(mainCategory, "กรุณาเลือกหมวดหลัก");
      }

      if (!docDateHidden?.value?.trim()) {
        firstError = firstError || docDateDisplay;
        setError(docDateDisplay, "กรุณาเลือกวัน เดือน ปี");
      }

      if (!fullname?.value?.trim()) {
        firstError = firstError || fullname;
        setError(fullname, "กรุณาเลือกชื่อ - นามสกุล");
      }

      if (!position?.value?.trim()) {
        firstError = firstError || position;
        setError(position, "กรุณากรอกตำแหน่ง");
      }

      const chosenPurpose = document.querySelector('input[name="purpose"]:checked');
      if (!chosenPurpose) {
        firstError = firstError || (purposeOtherRadio || purposeRadios[0]);
        setError((purposeOtherRadio || purposeRadios[0]), "กรุณาเลือกข้อ 3");
      } else if (chosenPurpose.value === "other") {
        if (!purposeOtherInput?.value?.trim()) {
          firstError = firstError || purposeOtherInput;
          setError(purposeOtherInput, "กรุณาระบุรายละเอียด (อื่น ๆ)");
        }
      }

      if (!eventTitle?.value?.trim()) {
        firstError = firstError || eventTitle;
        setError(eventTitle, "กรุณากรอกชื่อของงาน/หลักสูตรอบรม");
      }

      if (optSingle?.checked) {
        if (!singleDate?.value?.trim()) {
          firstError = firstError || singleDate;
          setError(singleDate, "กรุณาเลือกวันที่ (วันเดียว)");
        } else {
          joinDate.value = singleDate.value.trim();
        }
      }

      if (optRange?.checked) {
        if (!rangeDisplay?.value?.trim()) {
          firstError = firstError || rangeDisplay;
          setError(rangeDisplay, "กรุณาเลือกช่วงวันที่ (หลายวัน)");
        } else {
          joinDate.value = rangeDisplay.value.trim();
        }
      }

      if (onlineCheckbox?.checked) {
        placeOnsite.value = "เข้าร่วมรูปแบบออนไลน์";
      } else if (onsiteCheckbox?.checked) {
        if (!placeOnsite?.value?.trim()) {
          firstError = firstError || placeOnsite;
          setError(placeOnsite, "กรุณาระบุสถานที่ไป (ออนไซต์)");
        }
      } else {
        firstError = firstError || (onlineCheckbox || onsiteCheckbox);
        setError((onlineCheckbox || onsiteCheckbox), "กรุณาเลือก ออนไลน์ หรือ ออนไซต์");
      }

      amountInput.value = (amountInput.value || "").replace(/,/g, "").trim();
      if (!noCostCheckbox?.checked) {
        if (!amountInput.value) {
          firstError = firstError || amountInput;
          setError(amountInput, "กรุณากรอกยอดค่าใช้จ่าย");
        } else if (isNaN(Number(amountInput.value)) || Number(amountInput.value) < 0) {
          firstError = firstError || amountInput;
          setError(amountInput, "ยอดค่าใช้จ่ายต้องเป็นตัวเลขที่ถูกต้อง");
        }
      } else {
        amountInput.value = "0.00";
      }

      if (carCheckbox?.checked) {
        if (!carPlateInput?.value?.trim()) {
          firstError = firstError || carPlateInput;
          setError(carPlateInput, "กรุณากรอกทะเบียนรถ");
        }
      }

      if (firstError) {
        event.preventDefault();
        scrollToFirstError(firstError);
        return;
      }
    });

    const step1 = document.getElementById("step1");
    const step2 = document.getElementById("step2");

    const nextBtn = document.getElementById("nextBtn");
    const backBtn = document.getElementById("backBtn");


    const submitBtnStep1 = document.getElementById("submitBtn"); // ปุ่ม submit ใน step1
    const finalSubmitBtn = document.getElementById("finalSubmitBtn"); // ปุ่ม submit ใน step2

    function showStep1() {
      step1.classList.remove("hidden");
      step2.classList.add("hidden");
      window.scrollTo({
        top: 0,
        behavior: "smooth"
      });
    }

    function showStep2() {
      step1.classList.add("hidden");
      step2.classList.remove("hidden");
      window.scrollTo({
        top: 0,
        behavior: "smooth"
      });
    }


    function syncStepButtons() {
      const noCost = !!noCostCheckbox?.checked;

      if (noCost) {
        nextBtn?.classList.add("hidden");
        submitBtnStep1?.classList.remove("hidden");
        if (amountInput) amountInput.value = "0.00";

        if (!step2.classList.contains("hidden")) showStep1();
      } else {

        nextBtn?.classList.remove("hidden");
        submitBtnStep1?.classList.add("hidden");
      }
    }

    noCostCheckbox?.addEventListener("change", syncStepButtons);
    syncStepButtons();

    function validateStep1Minimal() {
      [
        docDateDisplay, fullname, position, eventTitle,
        singleDate, startDate, endDate, rangeDisplay, placeOnsite, amountInput
      ].forEach(clearError);

      let firstError = null;

      if (!docDateHidden?.value?.trim()) {
        firstError = firstError || docDateDisplay;
        setError(docDateDisplay, "กรุณาเลือกวัน เดือน ปี");
      }
      if (!fullname?.value?.trim()) {
        firstError = firstError || fullname;
        setError(fullname, "กรุณาเลือกชื่อ - นามสกุล");
      }
      if (!position?.value?.trim()) {
        firstError = firstError || position;
        setError(position, "กรุณากรอกตำแหน่ง");
      }
      if (!eventTitle?.value?.trim()) {
        firstError = firstError || eventTitle;
        setError(eventTitle, "กรุณากรอกชื่อของงาน/หลักสูตรอบรม");
      }
      if (optSingle?.checked) {
        if (!singleDate?.value?.trim()) {
          firstError = firstError || singleDate;
          setError(singleDate, "กรุณาเลือกวันที่ (วันเดียว)");
        } else {
          joinDate.value = singleDate.value.trim();
        }
      } else if (optRange?.checked) {
        if (!rangeDisplay?.value?.trim()) {
          firstError = firstError || rangeDisplay;
          setError(rangeDisplay, "กรุณาเลือกช่วงวันที่ (หลายวัน)");
        } else {
          joinDate.value = rangeDisplay.value.trim();
        }
      } else {
        firstError = firstError || optSingle;
        setError(optSingle, "กรุณาเลือก วันเดียว หรือ หลายวัน");
      }
      if (onlineCheckbox?.checked) {
        placeOnsite.value = "เข้าร่วมรูปแบบออนไลน์";
      } else if (onsiteCheckbox?.checked) {
        if (!placeOnsite?.value?.trim()) {
          firstError = firstError || placeOnsite;
          setError(placeOnsite, "กรุณาระบุสถานที่ไป (ออนไซต์)");
        }
      } else {
        firstError = firstError || onlineCheckbox;
        setError(onlineCheckbox, "กรุณาเลือก ออนไลน์ หรือ ออนไซต์");
      }
      amountInput.value = (amountInput.value || "").replace(/,/g, "").trim();
      if (!noCostCheckbox?.checked) {
        if (!amountInput.value) {
          firstError = firstError || amountInput;
          setError(amountInput, "กรุณากรอกยอดค่าใช้จ่าย");
        } else if (isNaN(Number(amountInput.value)) || Number(amountInput.value) < 0) {
          firstError = firstError || amountInput;
          setError(amountInput, "ยอดค่าใช้จ่ายต้องเป็นตัวเลขที่ถูกต้อง");
        }
      } else {
        amountInput.value = "0.00";
      }
      if (firstError) {
        scrollToFirstError(firstError);
        return false;
      }
      return true;
    }

    nextBtn?.addEventListener("click", () => {
      if (noCostCheckbox?.checked) return;
      if (optSingle?.checked && singleDate?.value?.trim()) {
        joinDate.value = singleDate.value.trim();
      }
      if (optRange?.checked && rangeDisplay?.value?.trim()) {
        joinDate.value = rangeDisplay.value.trim();
      }
      if (noCostCheckbox?.checked) return;
      if (!validateStep1Minimal()) return;
      showStep2();
      if (!validateStep1Minimal()) return;
      showStep2();
    });
    backBtn?.addEventListener("click", () => {
      showStep1();
    });

    syncPurposeUI();
    syncPlaceUI();
    syncCostUI();
    syncCarUI();
    toggleDatePickers();

    const totalAmountEl = document.getElementById("totalAmount");
    const totalAmountHidden = document.getElementById("totalAmountHidden");

    const addCompBtn = document.getElementById("addCompBtn");
    const compList = document.getElementById("compList");
    const compEmpty = document.getElementById("compEmpty");

    const addMatBtn = document.getElementById("addMatBtn");
    const matList = document.getElementById("matList");
    const matEmpty = document.getElementById("matEmpty");

    const regEnabled = document.getElementById("regEnabled");
    const regPrice = document.getElementById("regPrice");
    const regPeople = document.getElementById("regPeople");
    const regTotal = document.getElementById("regTotal");

    const lodEnabled = document.getElementById("lodEnabled");
    const lodUnit = document.getElementById("lodUnit");
    const lodNights = document.getElementById("lodNights");
    const lodPeople = document.getElementById("lodPeople");
    const lodDateText = document.getElementById("lodDateText");
    const lodTotal = document.getElementById("lodTotal");

    const perEnabled = document.getElementById("perEnabled");
    const perUnit = document.getElementById("perUnit");
    const perMeals = document.getElementById("perMeals");
    const perPeople = document.getElementById("perPeople");
    const perTotal = document.getElementById("perTotal");

    const trEnabled = document.getElementById("trEnabled");
    const addTrItemBtn = document.getElementById("addTrItemBtn");
    const trList = document.getElementById("trList");
    const trEmpty = document.getElementById("trEmpty");
    const trTotal = document.getElementById("trTotal");

    function n(v) {
      const x = Number(String(v ?? "").replace(/,/g, ""));
      return Number.isFinite(x) ? x : 0;
    }

    function money(x) {
      return (Math.round((x + Number.EPSILON) * 100) / 100).toFixed(2);
    }

    function toggleBlock(enabledEl, blockEl) {
      if (!enabledEl || !blockEl) return;
      blockEl.style.opacity = enabledEl.checked ? "1" : "0.55";
      blockEl.style.pointerEvents = enabledEl.checked ? "auto" : "none";
    }

    function makeRow({
      type,
      container,
      emptyEl,
      placeholder
    }) {
      const row = document.createElement("div");
      row.className = "p-3 rounded-[16px] border-2 flex flex-wrap gap-3 items-end";
      row.style.borderColor = "#11c2b9";
      row.style.background = "#ffffff";
      row.innerHTML = `
    <div class="flex-1 min-w-[260px]">
      <label class="text-gray-700">รายละเอียด</label>
      <input type="text" class="w-full border rounded-md p-2 js-desc" placeholder="${placeholder}">
    </div>
    <div class="w-[180px]">
      <label class="text-gray-700">จำนวนเงิน (บาท)</label>
      <input type="number" class="w-full border rounded-md p-2 js-amt" min="0" step="0.01" value="0">
    </div>
    <div>
      <button type="button" class="js-del bg-white border-2 border-red-400 text-red-600 font-bold px-3 py-2 rounded-md hover:bg-red-50">
        ลบ
      </button>
    </div>
    <input type="hidden" class="js-type" value="${type}">
  `;
      row.querySelector(".js-del").addEventListener("click", () => {
        row.remove();
        syncEmpty(container, emptyEl);
        calcAll();
      });
      row.querySelector(".js-desc").addEventListener("input", calcAll);
      row.querySelector(".js-amt").addEventListener("input", calcAll);
      container.appendChild(row);
      syncEmpty(container, emptyEl);
      calcAll();
    }

    function syncEmpty(container, emptyEl) {
      if (!container || !emptyEl) return;
      emptyEl.style.display = container.children.length ? "none" : "block";
    }
    addCompBtn?.addEventListener("click", () => {
      makeRow({
        type: "other",
        container: compList,
        emptyEl: compEmpty,
        placeholder: "เช่น ค่าตอบแทนวิทยากร"
      });
    });
    addMatBtn?.addEventListener("click", () => {
      makeRow({
        type: "other",
        container: matList,
        emptyEl: matEmpty,
        placeholder: "เช่น วัสดุสิ้นเปลือง"
      });
    });

    addTrItemBtn?.addEventListener("click", () => {
      // transport รองรับใน enum ตรง ๆ
      makeRow({
        type: "transport",
        container: trList,
        emptyEl: trEmpty,
        placeholder: "เช่น รถตู้/แท็กซี่/น้ำมัน"
      });
    });
    regEnabled?.addEventListener("change", () => {
      calcAll();
    });
    lodEnabled?.addEventListener("change", () => {
      calcAll();
    });
    perEnabled?.addEventListener("change", () => {
      calcAll();
    });
    trEnabled?.addEventListener("change", () => {
      calcAll();
    });

    function calcReg() {
      if (!regEnabled?.checked) return 0;
      return n(regPrice?.value) * n(regPeople?.value || 1);
    }

    function calcLod() {
      if (!lodEnabled?.checked) return 0;
      return n(lodUnit?.value) * n(lodNights?.value || 1) * n(lodPeople?.value || 1);
    }

    function calcPer() {
      if (!perEnabled?.checked) return 0;
      return n(perUnit?.value) * n(perMeals?.value || 1) * n(perPeople?.value || 1);
    }

    function calcDynamic(container, requiredType = null) {
      let sum = 0;
      if (!container) return 0;
      [...container.children].forEach(row => {
        const type = row.querySelector(".js-type")?.value || "other";
        if (requiredType && type !== requiredType) return;
        const amt = n(row.querySelector(".js-amt")?.value);
        sum += amt;
      });
      return sum;
    }

    function calcAll() {
      const compSum = calcDynamic(compList);
      const matSum = calcDynamic(matList);

      const regSum = calcReg();
      const lodSum = calcLod();
      const perSum = calcPer();

      let trSum = 0;
      if (trEnabled?.checked) trSum = calcDynamic(trList, "transport");
      regTotal.textContent = money(regSum);
      lodTotal.textContent = money(lodSum);
      perTotal.textContent = money(perSum);
      trTotal.textContent = money(trSum);
      const total = compSum + matSum + regSum + lodSum + perSum + trSum;
      if (totalAmountEl) totalAmountEl.value = money(total);
      if (totalAmountHidden) totalAmountHidden.value = money(total);
      if (amountInput && !noCostCheckbox?.checked) {
        amountInput.value = money(total);
      }
      buildBudgetHiddenInputs();
    }
    [regPrice, regPeople, lodUnit, lodNights, lodPeople, perUnit, perMeals, perPeople, lodDateText]
    .forEach(el => el?.addEventListener("input", calcAll));

    function clearOldHidden(prefix) {
      memoForm.querySelectorAll(`input[data-budget="${prefix}"]`).forEach(el => el.remove());
    }

    function addHidden(name, value) {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = name;
      input.value = value;
      input.setAttribute("data-budget", "1");
      memoForm.appendChild(input);
    }

    function buildBudgetHiddenInputs() {
      memoForm.querySelectorAll('input[data-budget="1"]').forEach(el => el.remove());
      if (regEnabled?.checked) {
        const amt = money(calcReg());
        if (amt !== "0.00") {
          addHidden("budget_type[]", "registration");
          addHidden("budget_desc[]", `ค่าลงทะเบียน (${n(regPrice.value)} × ${n(regPeople.value || 1)} คน)`);
          addHidden("budget_amount[]", amt);
        }
      }
      if (lodEnabled?.checked) {
        const amt = money(calcLod());
        if (amt !== "0.00") {
          addHidden("budget_type[]", "accommodation");
          addHidden("budget_desc[]",
            `ค่าที่พัก ${lodDateText?.value || ""} (${n(lodUnit.value)} × ${n(lodNights.value || 1)} คืน × ${n(lodPeople.value || 1)} คน)`
          );
          addHidden("budget_amount[]", amt);
        }
      }
      if (perEnabled?.checked) {
        const amt = money(calcPer());
        if (amt !== "0.00") {
          addHidden("budget_type[]", "per_diem");
          addHidden("budget_desc[]",
            `ค่าเบี้ยเลี้ยง (${n(perUnit.value)} × ${n(perMeals.value || 1)} มื้อ × ${n(perPeople.value || 1)} คน)`
          );
          addHidden("budget_amount[]", amt);
        }
      }
      if (trEnabled?.checked) {
        [...(trList?.children || [])].forEach(row => {
          const desc = (row.querySelector(".js-desc")?.value || "").trim();
          const amt = money(n(row.querySelector(".js-amt")?.value));
          if (!desc && amt === "0.00") return;
          addHidden("budget_type[]", "transport");
          addHidden("budget_desc[]", desc || "ค่าพาหนะ");
          addHidden("budget_amount[]", amt);
        });
      }
      [...(matList?.children || [])].forEach(row => {
        const desc = (row.querySelector(".js-desc")?.value || "").trim();
        const amt = money(n(row.querySelector(".js-amt")?.value));
        if (!desc && amt === "0.00") return;
        addHidden("budget_type[]", "other");
        addHidden("budget_desc[]", desc || "ค่าวัสดุ");
        addHidden("budget_amount[]", amt);
      });
    }
    syncEmpty(compList, compEmpty);
    syncEmpty(matList, matEmpty);
    syncEmpty(trList, trEmpty);
    calcAll();

    finalSubmitBtn?.addEventListener("click", () => {
      calcAll();
    });
  });
  </script>
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    const main = document.getElementById("mainCategory");
    const sub = document.getElementById("subCategory");
    if (!main || !sub) return;

    const SUB_OPTIONS = {
      external: [
        "ฝึกอบรม ",
        "ประชุมวิชาการ/ศึกษาดูงาน/สัมมนาวิชาการ ",
        "ขออนุมัติตัวบุคคลเป็นวิทยากร ",
        "ขอห้องพักรับรอง ",
        "หนังสือยินยอมให้นำเสนอผลงานทางวิชาการ",
      ],
      internal: [
        "หนังสือเรียนเชิญวิทยากร",
        "หนังสือขอความอนุเคราะห์ข้อมูลจัดทำปริญญานิพนธ์ ",
        "ขอเข้าเยี่ยมศึกษาดูงาน ",
        "ขอเข้าไปจัดกิจกรรมโครงการ",
        "ขอประเมินสถานประกอบการสหกิจ(ประเมินเด็กสหกิจ) ",
      ],
    };

    const ROUTE_MAIN = {
      train: "/Pro_letter/documents/form_Memo.php",
      academic: "/Pro_letter/form_Memo/Request/infor_approve_pro.php",
    };

    const ROUTE_SUB = {
      "ระบบขอความอนุเคราะห์หนังสือฝึกงาน (ของนักศึกษา)": "/Pro_letter/form_Memo/Request/infor_intership.php",
      "หนังสือเรียนเชิญวิทยากร (ของนักศึกษา)": "/Pro_letter/form_Memo/Request/infor_invite.php",
      "ส่งตัวหนังสือขอออกฝึกงาน(ของนักศึกษา)": "#",
      "หนังสือขอบคุณ (ของนักศึกษา)": "/Pro_letter/form_Memo/Request/infor_thankyou.php",
      "หนังสือขอความอนุเคราะห์ข้อมูลจัดทำปริญญานิพนธ์ (ของนักศึกษา)": "/Pro_letter/form_Memo/Request/infor_research_data.php",
      "หนังสือเรียนเชิญปริญญา(ของนักศึกษา)": "#",

      "ขอเปลี่ยนแปลงตารางสอน (ของอาจารย์)": "#",
      "ขอเปลี่ยนแปลงตารางสอบ (ของอาจารย์)": "/Pro_letter/form_Memo/Request/infor_change_exam.php",
      "ขอสอบนอกตาราง (ของอาจารย์)": "/Pro_letter/form_Memo/Request/infor_extra_exam.php",
      "ขอใช้อาคารวันหยุดราชการ (ของอาจารย์)": "/Pro_letter/user/Request_2.php",
      "ขอสอนชดเชย (ของอาจารย์)": "#",
      "ขอห้องพักรับรอง (ของอาจารย์)": "/Pro_letter/user/Request_3.php",
      "ขออนุมัติตัวบุคคลเป็นวิทยากร (ของอาจารย์)": "/Pro_letter/user/Request_4.php",
      "ขออนุมัติไม่เข้าร่วมโครงการ (ของอาจารย์)": "/Pro_letter/user/Request_5.php",
      "การเผยแพร่งานวิจัยและเบิกค่าตอบแทนการตีพิมพ์ (ของอาจารย์)": "#",
      "ขออนุมัติจัดทำโครงการ (ของอาจารย์)": "#",
      "หนังสือยินยอมให้นำเสนอผลงานทางวิชาการ (ของอาจารย์)": "#",
      "ขอแจ้งเรียนการเป็นผู้ร่วมวิจัย (ของอาจารย์)": "/Pro_letter/user/Request_7.php",
    };

    function renderSubOptions(list, selectedValue = "") {
      sub.innerHTML = '<option value="">-- เลือกหมวดย่อย --</option>';
      list.forEach(text => {
        const opt = document.createElement("option");
        opt.value = text;
        opt.textContent = text;
        if (text === selectedValue) opt.selected = true;
        sub.appendChild(opt);
      });
    }

    function syncUI() {
      const mainVal = (main.value || "").trim();
      const currentSub = (sub.dataset.current || "").trim();
      if (mainVal === "external" || mainVal === "internal") {
        sub.disabled = false;
        renderSubOptions(SUB_OPTIONS[mainVal] || [], currentSub);
      } else {
        sub.disabled = true;
        sub.innerHTML = '<option value="">-- เลือกหมวดย่อย --</option>';
      }
    }

    function goMain() {
      const mainVal = (main.value || "").trim();
      const target = ROUTE_MAIN[mainVal];
      if (target && target !== "#") window.location.href = target;
    }

    function goSub() {
      const subVal = (sub.value || "").trim();
      sub.dataset.current = subVal;
      const target = ROUTE_SUB[subVal];
      if (!target || target === "#") return;
      window.location.href = target;
    }
    main.addEventListener("change", () => {
      sub.dataset.current = "";
      syncUI();
      goMain();
    });
    sub.addEventListener("change", goSub);
    syncUI();
  });
  </script>
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("profileBtn");
    const menu = document.getElementById("profileMenu");
    if (!btn || !menu) return;

    function openMenu() {
      menu.classList.remove("hidden");
    }

    function closeMenu() {
      menu.classList.add("hidden");
    }
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      menu.classList.toggle("hidden");
    });
    document.addEventListener("click", () => closeMenu());
    menu.addEventListener("click", (e) => e.stopPropagation());
    window.closeMenu = closeMenu;
  });
  </script>
</body>

</html>