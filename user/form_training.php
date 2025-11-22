<?php
// form_training.php
// ใช้สำหรับแสดงเนื้อหาเฉพาะกรณี “เข้ารับการฝึกอบรม”
?>

<!-- ⭐ ย่อหน้า 1 -->
<div class="content-block paragraph">
    ตามที่
    <span class="chip" contenteditable="true" data-target="organization">
        <?= h($organization ?? 'สมาคมสหกิจศึกษาไทย') ?>
    </span>
    กำหนดจัดอบรมหลักสูตร
    “<span class="chip" contenteditable="true" data-target="courseName">
        <?= h($courseName ?: 'ชื่อหลักสูตร') ?>
    </span>”
    <span class="chip" contenteditable="true" data-target="course_detail">
        <?= h($course_detail ?? 'สำหรับผู้ที่ไม่เคยอบรม (ฉบับปรับปรุง)') ?>
    </span>
    รุ่นที่
    <span class="chip" contenteditable="true" data-target="course_gen">
        <?= h($course_gen ?? '…') ?>
    </span>
    ระหว่างวันที่
    <span class="chip" contenteditable="true" data-target="joinDates">
        <?= h($joinDates ?: '') ?>
    </span>
    ณ
    <span class="chip" contenteditable="true" data-target="location">
        <?= h($location ?: '') ?>
    </span>
    ซึ่งหลักสูตรดังกล่าวเป็นประโยชน์ต่อการพัฒนากระบวนการจัดการเรียนการสอนในรูปแบบสหกิจศึกษา
</div>

<!-- ⭐ ย่อหน้า 2 -->
<div class="content-block paragraph">
    การนี้ ข้าพเจ้า
    <span class="chip" contenteditable="true" data-target="ownerName">
        <?= h($ownerName ?: '') ?>
    </span>
    ตำแหน่ง
    <span class="chip" contenteditable="true" data-target="position">
        <?= h($position ?: '') ?>
    </span>
    สังกัดภาควิชา
    <span class="chip" contenteditable="true" data-target="department">
        <?= h($department ?: '') ?>
    </span>
    คณะ
    <span class="chip" contenteditable="true" data-target="faculty">
        <?= h($faculty ?: '') ?>
    </span>
    มหาวิทยาลัยเทคโนโลยีพระจอมเกล้าพระนครเหนือ วิทยาเขตปราจีนบุรี

    จึงมีความประสงค์ที่จะขออนุมัติเข้ารับการฝึกอบรมหลักสูตร
    “<span class="chip" contenteditable="true" data-target="courseName">
        <?= h($courseName ?: 'ชื่อหลักสูตร') ?>
    </span>”
    ระหว่างวันที่
    <span class="chip" contenteditable="true" data-target="joinDates">
        <?= h($joinDates ?: '') ?>
    </span>
    ณ
    <span class="chip" contenteditable="true" data-target="location">
        <?= h($location ?: '') ?>
    </span>
    รวมเป็นเงินทั้งสิ้น
    <span class="chip" contenteditable="true" data-target="amountStr">
        <?= h($prettyAmount ?: '') ?>
    </span>
    บาท
    โดยขอใช้แหล่งเงินจัดสรรให้หน่วยงานประจำปีงบประมาณ พ.ศ.
    <?= h($thaiYear ?: '....') ?>
    แผนงานจัดการศึกษาระดับอุดมศึกษา กองทุนพัฒนาบุคลากร หมวดค่าใช้สอย
    (รายละเอียดตามเอกสารแนบ)
</div>

<!-- ⭐ ย่อหน้า 3 -->
<div class="content-block paragraph" style="text-indent:2.5cm;">
    จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
</div>