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

// ===============================
// LOAD DATA (เฉพาะโหมดแก้ไข)
// ===============================
if ($isEdit) {
    $pdo = db();

    // ตรวจเอกสาร
    $stmt = $pdo->prepare("
        SELECT document_id, owner_id, status
        FROM documents
        WHERE document_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) exit("ไม่พบเอกสาร");
    if ($doc['owner_id'] != $_SESSION['user_id']) exit("ไม่มีสิทธิ์แก้ไข");
    if (!in_array($doc['status'], ['draft','rejected'])) exit("เอกสารแก้ไม่ได้");

    // โหลดค่า document_values
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

// ===============================
// MAP VARIABLES (ต้องอยู่หลัง load DB เท่านั้น)
// ===============================
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

// ===============================
// FIX BUG ❌ ข้อ 5 : วันที่ (radio)
// ===============================
$isRangeDate = !empty($joinDates)
    && (str_contains($joinDates, '-') || str_contains($joinDates, 'ถึง'));

// ===============================
// FIX BUG ❌ ข้อ 6 : ออนไลน์ / ออนไซต์
// ===============================
$isOnline = ($location === 'เข้าร่วมรูปแบบออนไลน์');

// ===============================
// PURPOSE (map เป็น code สำหรับ radio)
// ===============================
$purpose = 'other';
if ($joinType === 'นำเสนอผลงานทางวิชาการ') {
    $purpose = 'academic';
} elseif ($joinType === 'เข้าร่วมประชุมวิชาการในงาน') {
    $purpose = 'meeting';
} elseif ($joinType === 'เข้ารับการฝึกอบรมหลักสูตร') {
    $purpose = 'training';
}

// กรณีอื่น ๆ
$purposeOther = ($purpose === 'other') ? $joinType : '';


?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>แบบฟอร์มบันทึกข้อความ</title>

  <!-- ✅ เพิ่มส่วนนี้ -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
  <!-- ✅ จบส่วนที่เพิ่ม -->

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

  /* error styles */
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

  /* floating hint bubble */
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
      <a href="home.php">
        <div class="px-4 py-2 rounded-[11px] font-bold transition text-white">
          หน้าหลัก
        </div>
      </a>

      <?php 
                if (isset($_SESSION['permissions']) && in_array(3, $_SESSION['permissions'])) {
                    renderAdminExtraMenus(); 
                }
            ?>

      <a href="documents/form_Memo.php">
        <div class="px-4 py-2 rounded-[11px] font-bold transition bg-white text-teal-500 shadow">
          แบบฟอร์มบันทึกข้อความ
        </div>
      </a>

      <div class="relative">
        <!-- ปุ่ม Profile -->
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

        <!-- เมนู Dropdown -->
        <div id="profileMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg z-50">
          <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">ออกจากระบบ</a>
          <button onclick="closeMenu()"
            class="w-full text-left px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">อยู่ต่อ</button>
        </div>
      </div>
    </div>
  </header>

  <form method="post" action="save_memo.php" id="memoForm">
    <?php if ($isEdit): ?>
    <input type="hidden" name="document_id" value="<?= (int)$docId ?>">
    <input type="hidden" name="mode" value="update">
    <?php else: ?>
    <input type="hidden" name="mode" value="create">
    <?php endif; ?>

    <!-- กล่องเนื้อหา -->
    <div class="w-[900px] mx-auto mt-16 mb-6 bg-white shadow-md rounded-md p-8" style="min-height: 1122px">
      <h1 class="text-center font-bold mb-6 text-black">
        แบบฟอร์มบันทึกข้อความ
      </h1>

      <!-- หมวดหมู่ -->
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
              <option value="train" <?= ($CURRENT_MAIN=="train"?"selected":"") ?>>ฝึกอบรม</option>
              <option value="academic" <?= ($CURRENT_MAIN=="academic"?"selected":"") ?>>
                ประชุมวิชาการ/ศึกษาดูงาน/สัมมนาวิชาการ</option>
              <option value="external" <?= ($CURRENT_MAIN=="external"?"selected":"") ?>>ภายนอก</option>
              <option value="internal" <?= ($CURRENT_MAIN=="internal"?"selected":"") ?>>ภายใน(บันทึกข้อความ)</option>
            </select>

          </div>
        </div>

        <div class="flex items-center gap-3">
          <label class="lbl text-gray-800 w-28 text-right">หมวดย่อย:</label>
          <div class="relative w-full">
            <select name="sub_category" class="custom-select w-full" id="subCategory"
              <?= ($CURRENT_MAIN!="internal"?"disabled":"") ?>>
              <option value="">-- เลือกหมวดย่อย --</option>

              <?php if ($CURRENT_MAIN == "internal"): ?>
              <option value="ขอใช้อาคารวันหยุดราชการ" <?= ($CURRENT_SUB=="ขอใช้อาคารวันหยุดราชการ"?"selected":"") ?>>
                ขอใช้อาคารวันหยุดราชการ
              </option>

              <option value="ขอห้องพักรับรอง" <?= ($CURRENT_SUB=="ขอห้องพักรับรอง"?"selected":"") ?>>
                ขอห้องพักรับรอง
              </option>

              <option value="ขออนุมัติตัวบุคคลเป็นวิทยากร"
                <?= ($CURRENT_SUB=="ขออนุมัติตัวบุคคลเป็นวิทยากร"?"selected":"") ?>>
                ขออนุมัติตัวบุคคลเป็นวิทยากร
              </option>

              <option value="ขออนุมัติไม่เข้าร่วมโครงการ"
                <?= ($CURRENT_SUB=="ขออนุมัติไม่เข้าร่วมโครงการ"?"selected":"") ?>>
                ขออนุมัติไม่เข้าร่วมโครงการ
              </option>

              <option value="การเผยแพร่งานวิจัยและเบิกค่าตอบแทนการตีพิมพ์"
                <?= ($CURRENT_SUB=="การเผยแพร่งานวิจัยและเบิกค่าตอบแทนการตีพิมพ์"?"selected":"") ?>>
                การเผยแพร่งานวิจัยและเบิกค่าตอบแทนการตีพิมพ์
              </option>

              <option value="ขอแจ้งเรียนการเป็นผู้ร่วมวิจัย"
                <?= ($CURRENT_SUB=="ขอแจ้งเรียนการเป็นผู้ร่วมวิจัย"?"selected":"") ?>>
                ขอแจ้งเรียนการเป็นผู้ร่วมวิจัย
              </option>
              <?php endif; ?>
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

      <!-- ข้อ 1 -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 items-end">
        <div class="flex items-center gap-3">
          <label class="lbl text-gray-800 whitespace-nowrap" for="docDate">1.วัน เดือน ปี :</label>
          <div class="flex-1">
            <input type="date" name="doc_date" class="w-full border rounded-md p-2" id="docDate"
              value="<?= $isEdit ? h($formData[1] ?? '') : '' ?>" />

          </div>
          <label class="lbl text-gray-800 whitespace-nowrap">ที่ต้องการให้ปรากฎบนบันทึกข้อความ</label>
        </div>
      </div>

      <!-- ข้อ 2 -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 items-end">
        <div class="flex items-center gap-3">
          <label class="lbl text-gray-800 whitespace-nowrap" for="fullname">2.ชื่อ - นามสกุล :</label>
          <select name="fullname" class="flex-1 border rounded-md p-2" id="fullname">
            <option value="<?= h($formData[2] ?? 'อาจารย์ ดร.พิทย์พิมล ชูรอด') ?>" selected>
              <?= h($formData[2] ?? 'อาจารย์ ดร.พิทย์พิมล ชูรอด') ?>
            </option>
          </select>

        </div>
        <div class="flex items-center gap-3">
          <label class="lbl text-gray-800 whitespace-nowrap" for="position">ตำแหน่ง :</label>
          <input type="text" name="position" class="flex-1 border rounded-md p-2" id="position"
            value="<?= $isEdit ? h($formData[3] ?? '') : 'อาจารย์ประจำภาควิชาเทคโนโลยีสารสนเทศ' ?>" />

        </div>
      </div>

      <!-- ข้อ 3 -->
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

      <!-- ข้อ 4 -->
      <div class="mb-4 flex items-start gap-4">
        <label class="lbl text-gray-800 whitespace-nowrap pt-2" for="eventTitle">
          4.ชื่อของงานประชุมวิชาการ /<br />ชื่อหลักสูตรอบรม :
        </label>
        <div class="w-full">
          <textarea name="event_title" rows="2" class="w-full border rounded-md p-2 shadow-sm"
            id="eventTitle"><?= h($formData[5] ?? '') ?></textarea>
        </div>
      </div>

      <!-- ข้อ 5 -->
      <div class="mb-6">
        <label class="lbl text-gray-800 block mb-2" id="dateLabel">5. วันที่เข้าร่วม</label>

        <div class="space-y-4 ml-6 text-gray-800">
          <!-- วันเดียว -->
          <div class="flex items-center gap-2">
            <input type="radio" name="date_option" value="single" id="optSingle" class="accent-[#11C2B9]"
              <?= !$isRangeDate ? 'checked' : '' ?> />

            <span>วันเดียว :</span>
            <div class="relative">
              <input type="text" name="single_date" id="singleDate"
                class="border rounded-md p-2 shadow-sm w-48 pr-10 cursor-pointer" placeholder="เลือกวันที่" readonly />
              <svg class="absolute right-3 top-2.5 w-5 h-5 text-[#11C2B9]" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
              </svg>
            </div>
          </div>

          <!-- หลายวัน -->
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

            <input type="hidden" name="range_date" id="rangeDate" value="" />
            <input type="hidden" name="join_date_range" id="joinDateValue" value="<?= h($formData[6] ?? '') ?>">

          </div>
        </div>
      </div>

      <!-- ข้อ 6 -->
      <div class="mb-6">
        <label class="lbl text-gray-800 block mb-2">
          6. ชื่อสถานที่จัดประชุมวิชาการ / สถานที่จัดอบรม / เข้าร่วมรูปแบบออนไลน์
        </label>

        <!-- ออนไลน์ -->
        <div class="flex items-center ml-6 gap-2 mb-3">
          <input type="radio" name="is_online" value="1" id="onlineCheckbox" class="accent-black"
            <?= $isOnline ? 'checked' : '' ?>>

          <label for="onlineCheckbox">เข้าร่วมในรูปแบบออนไลน์</label>
        </div>

        <!-- ออนไซต์ -->
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


      <!-- ข้อ 7 -->
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

      <!-- ข้อ 8 -->
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


      <!-- ปุ่ม -->
      <div class="relative mt-20">
        <div class="absolute right-0 bottom-0">
          <button type="submit" id="submitBtn"
            class="bg-[#11C2B9] hover:bg-[#0fa39c] text-white font-bold w-[130px] h-[35px] rounded-md transition">
            ดำเนินการ
          </button>
        </div>
      </div>
    </div>
  </form>
  <script>
  document.addEventListener("DOMContentLoaded", () => {

    /* ================= FORM SUBMIT ================= */
    const memoForm = document.getElementById("memoForm");
    const amountInput = document.getElementById("amountInput");
    if (memoForm) {
      memoForm.addEventListener("submit", () => {
        amountInput.value = amountInput.value.replace(/,/g, "");
      });
    }

    /* ================= INIT ================= */
    flatpickr.localize(flatpickr.l10ns.th);

    const monthsTH = [
      "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
      "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    ];

    /* ================= PURPOSE ================= */
    const purposeRadios = document.querySelectorAll('input[name="purpose"]');
    const purposeOtherRadio = document.getElementById("purposeOtherRadio");
    const purposeOtherInput = document.getElementById("purposeOtherInput");

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

    /* ================= PLACE ================= */
    const onlineCheckbox = document.getElementById("onlineCheckbox");
    const onsiteCheckbox = document.getElementById("onsiteCheckbox");
    const placeOnsite = document.getElementById("placeOnsite");

    function syncPlaceUI() {
      if (onlineCheckbox?.checked) {
        placeOnsite.value = "";
        placeOnsite.disabled = true;
        placeOnsite.classList.add("bg-gray-100", "text-gray-400");
      }
      if (onsiteCheckbox?.checked) {
        placeOnsite.disabled = false;
        placeOnsite.classList.remove("bg-gray-100", "text-gray-400");
      }
    }
    onlineCheckbox?.addEventListener("change", syncPlaceUI);
    onsiteCheckbox?.addEventListener("change", syncPlaceUI);

    /* ================= COST ================= */
    const noCostCheckbox = document.getElementById("noCostCheckbox");

    function syncCostUI() {
      if (noCostCheckbox?.checked) {
        amountInput.value = "0.00";
        amountInput.disabled = true;
        amountInput.classList.add("bg-gray-100", "text-gray-400");
      } else {
        amountInput.disabled = false;
        amountInput.classList.remove("bg-gray-100", "text-gray-400");
      }
    }
    noCostCheckbox?.addEventListener("change", syncCostUI);

    /* ================= CAR ================= */
    const carCheckbox = document.getElementById("carCheckbox");
    const carPlateInput = document.getElementById("carPlateInput");

    function syncCarUI() {
      if (carCheckbox?.checked) {
        carPlateInput.disabled = false;
        carPlateInput.classList.remove("bg-gray-100", "text-gray-400");
      } else {
        carPlateInput.value = "";
        carPlateInput.disabled = true;
        carPlateInput.classList.add("bg-gray-100", "text-gray-400");
      }
    }
    carCheckbox?.addEventListener("change", syncCarUI);

    /* ================= DATE ================= */
    const optSingle = document.getElementById("optSingle");
    const optRange = document.getElementById("optRange");
    const singleDate = document.getElementById("singleDate");
    const startDate = document.getElementById("startDate");
    const endDate = document.getElementById("endDate");
    const rangeDisplay = document.getElementById("rangeDisplay");
    const rangeDate = document.getElementById("rangeDate");
    const joinDateValue = document.getElementById("joinDateValue");

    const singlePicker = flatpickr("#singleDate", {
      disableMobile: true,
      onChange: ([d], _, inst) => {
        if (d) inst.input.value =
          `${d.getDate()} ${monthsTH[d.getMonth()]} ${d.getFullYear()+543}`;
      }
    });

    const startPicker = flatpickr("#startDate", {
      disableMobile: true
    });
    const endPicker = flatpickr("#endDate", {
      disableMobile: true
    });

    function toggleDatePickers() {
      const single = optSingle.checked;
      singleDate.disabled = !single;
      startDate.disabled = single;
      endDate.disabled = single;
      rangeDisplay.disabled = single;
    }
    optSingle.addEventListener("change", toggleDatePickers);
    optRange.addEventListener("change", toggleDatePickers);

    function parseThaiDate(str) {
      const map = Object.fromEntries(monthsTH.map((m, i) => [m, i]));
      const p = str.trim().split(" ");
      if (p.length < 3) return null;
      return new Date(p[2] - 543, map[p[1]], parseInt(p[0], 10));
    }

    /* ================= RESTORE EDIT MODE (ตัวจริง) ================= */
    if (joinDateValue && joinDateValue.value.trim()) {
      const raw = joinDateValue.value.trim();

      // 🔥 หลายวัน ( - หรือ ถึง )
      if (raw.includes("-") || raw.includes("ถึง")) {
        optRange.checked = true;
        toggleDatePickers();

        const parts = raw.includes("ถึง") ?
          raw.split("ถึง") :
          raw.split("-");

        const d1 = parseThaiDate(parts[0]);
        const d2 = parseThaiDate(parts[1]);

        if (d1 && d2) {
          startPicker.setDate(d1, false);
          endPicker.setDate(d2, false);
          rangeDisplay.value = raw;
          rangeDate.value = raw;
        }
      }
      // 🔥 วันเดียว
      else {
        optSingle.checked = true;
        toggleDatePickers();
        const d = parseThaiDate(raw);
        if (d) {
          singlePicker.setDate(d, false);
          singleDate.value = raw;
        }
      }
    }

    /* ================= INIT ALL ================= */
    syncPurposeUI();
    syncPlaceUI();
    syncCostUI();
    syncCarUI();
    toggleDatePickers();

  });
  </script>





</body>

</html>