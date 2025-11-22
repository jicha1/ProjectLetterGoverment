<?php 
session_start();

// DEV LOGIN
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

require_once __DIR__ . '/functions.php';
$pdo = db();

// โหลดข้อมูลเอกสาร (ถ้ามี)
$docId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// โหลดข้อมูลผู้ใช้
$userId = $_SESSION['user_id'];

// โหลดข้อมูลเดิมจาก document_values
$valueMap = [];
if ($docId > 0) {
    $vals = $pdo->prepare("SELECT field_id, value_text FROM document_values WHERE document_id = :id");
    $vals->execute([':id' => $docId]);
    foreach ($vals->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $valueMap[(int)$r['field_id']] = (string)$r['value_text'];
    }
}

// Helper
function h($s){
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Map fields
$name = $valueMap[1] ?? '';
$position = $valueMap[2] ?? '';
$faculty = $valueMap[3] ?? '';
$department = $valueMap[4] ?? '';
$conference = $valueMap[5] ?? '';
$country = $valueMap[6] ?? '';
$paper_title = $valueMap[7] ?? '';
$date_range = $valueMap[8] ?? '';
$table_data = $valueMap[9] ?? ''; // JSON ตารางค่าใช้จ่าย

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ประมาณค่าใช้จ่ายในการนำเสนอผลงาน</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
    @import url("https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap");

    body {
        background: #f3f3f3;
        font-family: "TH SarabunPSK", sans-serif;
    }

    .page {
        width: 794px;
        min-height: 1123px;
        margin: 40px auto;
        background: #fff;
        padding: 50px 60px 20px;
        /* 👈 เปลี่ยนจาก 60px → 20px */
        box-shadow: 0 0 5px rgba(0, 0, 0, .15);
        border: 2px solid #fff;
        position: relative;
    }

    .no-print {
        position: absolute;
        bottom: 15px;
        /* ⭐ ขยับลงจนเกือบชิดขอบล่าง A4 */
        right: 50px;
        /* ⭐ หรือปรับเองได้ */
    }



    h1 {
        text-align: center;
        font-size: 26pt;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .doc-label {
        font-size: 18pt;
    }

    .dot-line {
        flex: 1;
        height: 24px;
        position: relative;
        margin-left: 8px;
    }

    .dot-line::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background-image: radial-gradient(circle, #000 1px, transparent 1px);
        background-size: 6px 2px;
        background-repeat: repeat-x;
    }

    .chip {
        font-size: 18pt;
        padding: 0 3px;
        background: #fff;
        position: relative;
        z-index: 2;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th,
    td {
        border: 1px solid #000;
        padding: 6px;
        font-size: 15pt;
        /* 🔽 จาก 16pt เหลือ 14pt */
        text-align: center;
    }


    th {
        font-weight: bold;
        background: #f8f8f8;
    }

    @media print {
        body {
            background: white;
        }

        .page {
            width: 21cm;
            min-height: 29.7cm;
            padding: 2cm;
            box-shadow: none;
            border: none;
        }

        .no-print {
            display: none;
        }
    }
    </style>
</head>

<body>

    <div class="page">
        <form action="save_expense.php" method="post" id="expenseForm">

            <!-- ส่วนข้อมูลผู้ขอ (จัดบรรทัดแบบ Word เป๊ะ) -->
            <div class="mt-4 mb-10">

                <h2 class="text-[16pt] font-bold text-center leading-[1.1] mb-6">
                    ประมาณการค่าใช้จ่าย<br>
                    การนำเสนอผลงานวิจัยในการประชุมวิชาการระดับนานาชาติ
                </h2>


                <div class="text-[16pt] leading-[1.15]">

                    <!-- row 1 -->
                    <div class="flex mb-1">
                        <div class="w-[180px]">ชื่อ–สกุล</div>
                        <div class="flex-1">
                            <?= h($name ?: 'รองศาสตราจารย์ ดร.อนิราช มิ่งขวัญ') ?>
                        </div>
                    </div>

                    <!-- row 2 -->
                    <div class="flex mb-1">
                        <div class="w-[180px]">มหาวิทยาลัยต้นสังกัด</div>
                        <div class="flex-1">
                            ภาควิชาเทคโนโลยีสารสนเทศ คณะเทคโนโลยีและการจัดการอุตสาหกรรม<br>
                            มหาวิทยาลัยเทคโนโลยีพระจอมเกล้าพระนครเหนือ วิทยาเขตปราจีนบุรี
                        </div>
                    </div>

                    <!-- row 3 -->
                    <div class="flex mb-1">
                        <div class="w-[180px]">ชื่อการประชุมวิชาการ</div>
                        <div class="flex-1">
                            2024 8th International Conference on Natural Language Processing and<br>
                            Information Retrieval (NLPIR 2024)
                        </div>
                    </div>

                    <!-- row 4 -->
                    <div class="flex mb-1">
                        <div class="w-[180px]">วันที่</div>
                        <div class="flex-1">12 – 15 ธันวาคม 2567</div>
                    </div>

                    <!-- row 5 -->
                    <div class="flex mb-1">
                        <div class="w-[180px]">สถานที่</div>
                        <div class="flex-1">Okayama, Japan</div>
                    </div>

                    <!-- row 6 -->
                    <div class="flex mb-1">
                        <div class="w-[180px]">ชื่อผลงานวิจัย</div>
                        <div class="flex-1">
                            “Enhancing Retrieval-Augmented Generation Systems by<br>
                            Text-Representing Centroid”
                        </div>
                    </div>

                </div>
            </div>

            <h2 class="text-[16pt] font-bold mt-4 mb-3 text-left">
                ตารางสรุปค่าใช้จ่ายในการไปนำเสนอผลงานวิจัย
            </h2>

            <table id="expenseTable" style="width:100%; border-collapse:collapse; font-family:'TH SarabunPSK';
              font-size:16pt; line-height:1.25; table-layout:fixed;">

                <!-- HEADER -->
                <tr style="height:28px;">

                    <!-- ลำดับ -->
                    <th style="
            width:55px;
            border:1px solid #000; 
            padding:3px 4px; 
            text-align:center; 
            font-weight:bold;">
                        ลำดับ<br>ที่
                    </th>
                    <!-- รายการ (บังคับให้แคบลง) -->
                    <th style="
    width:65%; 
    border:1px solid #000; 
    padding:3px 6px; 
    text-align:center; 
    font-weight:bold;
    vertical-align: top;
">
                        รายการ
                    </th>

                    <!-- จำนวนเงิน (แคบกว่าเดิม) -->
                    <th style="
    width:120px; 
    border:1px solid #000; 
    padding:3px 4px; 
    text-align:center; 
    font-weight:bold;
    vertical-align: top;
">
                        จำนวนเงิน (บาท)
                    </th>


                </tr>

                <!-- ROW 1 -->
                <tr>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:center; vertical-align: top;">1</td>
                    <td style="border:1px solid #000; padding:3px 8px; text-align:left;" contenteditable="true">
                        ค่าลงทะเบียน (1 คน × 540 USD) (ตามที่จ่ายจริง)<br>
                        - (1 USD = 35.00 บาท)
                    </td>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:right; vertical-align: top;"
                        contenteditable="true">
                        18,900.00
                    </td>
                </tr>

                <!-- ROW 2 -->
                <tr>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:center; vertical-align: top;">2</td>
                    <td style="border:1px solid #000; padding:3px 8px; text-align:left;" contenteditable="true">
                        ค่าเดินทางระหว่างประเทศ (ตามที่จ่ายจริง)<br>
                        - ตั๋วเครื่องบิน ไป–กลับ ชั้นประหยัด กรุงเทพฯ → ญี่ปุ่น<br>
                        &nbsp;&nbsp;&nbsp;(25,000.00 บาท × 1 คน)
                    </td>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:right; vertical-align: top;"
                        contenteditable="true">
                        25,000.00
                    </td>
                </tr>

                <!-- ROW 3 -->
                <tr>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:center; vertical-align: top;">3</td>
                    <td style="border:1px solid #000; padding:3px 8px; text-align:left;" contenteditable="true">
                        ค่าที่พัก ตามที่จ่ายจริง (ไม่เกิน 2 คืน)<br>
                        - ค่าที่พัก 13–14 ธันวาคม 2567 (7,000.00 บาท × 2 คืน)
                    </td>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:right; vertical-align: top;"
                        contenteditable="true">
                        14,000.00
                    </td>
                </tr>

                <!-- ROW 4 -->
                <tr>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:center; vertical-align: top;">4</td>
                    <td style="border:1px solid #000; padding:3px 8px; text-align:left;" contenteditable="true">
                        ค่าพาหนะ<br>

                        - วันที่ 13 ธ.ค. 67 ค่าโดยสารรถแท็กซี่ ไป–กลับที่พัก–งาน NLPIR 2024<br>
                        &nbsp;&nbsp;&nbsp;(2,500.00 × 2 เที่ยว)<br>

                        - วันที่ 14 ธ.ค. 67 ค่าโดยสารรถแท็กซี่ ไป–กลับที่พัก–งาน NLPIR 2024<br>
                        &nbsp;&nbsp;&nbsp;(2,500.00 × 2 เที่ยว)<br>

                        - วันที่ 15 ธ.ค. 67 ค่าโดยสารรถแท็กซี่ ไป–กลับที่พัก–งาน NLPIR 2024<br>
                        &nbsp;&nbsp;&nbsp;(2,500.00 × 2 เที่ยว)
                    </td>

                    <td style="border:1px solid #000; padding:3px 4px; text-align:right; vertical-align: top;"
                        contenteditable="true">
                        15,000.00
                    </td>
                </tr>


                <!-- ROW 5 -->
                <tr>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:center; vertical-align: top;">5</td>
                    <td style="border:1px solid #000; padding:3px 8px; text-align:left;" contenteditable="true">
                        ค่าเบี้ยเลี้ยง วันละ 3,100.00 บาท รวม 3 วัน (ไม่เกิน 3 วัน)<br>
                        - ค่าเบี้ย 13–14–15 ธันวาคม 2567 (3,100.00 × 3 วัน)
                    </td>
                    <td style="border:1px solid #000; padding:3px 4px; text-align:right; vertical-align: top;"
                        contenteditable="true">
                        9,300.00
                    </td>
                </tr>
                <!-- TOTAL -->
                <tr>

                    <!-- ช่องเปล่าด้านซ้าย (เพื่อให้เหมือน PDF) -->
                    <th style="
        width:55px;
        border:1px solid #000;
        padding:3px 4px;
        background:#ffffff;
    "></th>

                    <!-- ช่องข้อความ รวมเป็นเงิน -->
                    <th colspan="1" style="
        border:1px solid #000;
        padding:3px 6px;
        text-align:right;
        font-weight:bold;
        background:#f8f8f8;
    ">
                        รวมเป็นเงิน * (เบิกได้ไม่เกิน 80,000.00 บาท)
                    </th>

                    <!-- ช่องจำนวนเงิน -->
                    <th style="
        width:150px;
        border:1px solid #000;
        padding:3px 4px;
        text-align:right;
        font-weight:bold;
        background:#ffffff;
    ">
                        82,200.00
                    </th>

                </tr>

            </table>

            <!-- Hidden -->
            <input type="hidden" name="doc_id" value="<?= $docId ?>">
            <input type="hidden" name="table_data" id="table_data">

            <!-- ปุ่ม -->
            <div class="no-print mt-8 flex justify-end gap-4">
                <button type="button" onclick="window.print()"
                    class="px-8 py-2 bg-blue-500 text-white rounded-lg text-[16pt] font-bold shadow-sm">
                    พิมพ์
                </button>

                <button type="submit"
                    class="px-8 py-2 bg-green-600 text-white rounded-lg text-[16pt] font-bold shadow-sm">
                    บันทึก
                </button>

        </form>
    </div>

    <script>
    // ป้องกันขึ้นบรรทัดใหม่ใน chip
    document.querySelectorAll("[contenteditable]").forEach(el => {
        el.addEventListener("keydown", e => {
            if (e.key === "Enter") e.preventDefault();
        });
    });

    // เก็บตารางเป็น JSON ก่อน submit
    document.getElementById("expenseForm").addEventListener("submit", () => {
        const rows = [];
        document.querySelectorAll("#expenseTable tr").forEach((tr, index) => {
            const cells = [...tr.children].map(td => td.innerText.trim());
            rows.push(cells);
        });
        document.getElementById("table_data").value = JSON.stringify(rows);
    });
    </script>

</body>

</html>