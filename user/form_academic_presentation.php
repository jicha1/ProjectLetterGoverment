<?php
// form_present.php
// เนื้อหาสำหรับ “นำเสนอผลงานทางวิชาการ”
?>

<!-- ⭐ ย่อหน้า 1 -->
<div class="content-block paragraph">
    ตามที่ ข้าพเจ้า
    <span class="chip" contenteditable="true" data-target="ownerName">
        <?= h($ownerName ?: '') ?>
    </span>
    ดำรงตำแหน่ง
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
    ได้รับการตอบรับให้นำเสนอผลงานวิจัยในงานประชุมวิชาการระดับนานาชาติ
    <span class="chip" contenteditable="true" data-target="confName">
        <?= h($confName ?: 'ชื่อการประชุมวิชาการ') ?>
    </span>
    ในหัวข้อ
    <span class="chip" contenteditable="true" data-target="confTopic">
        <?= h($confTopic ?: 'หัวข้อผลงานวิจัย') ?>
    </span>
    จัดขึ้นที่
    <span class="chip" contenteditable="true" data-target="location">
        <?= h($location ?: '') ?>
    </span>
    ในระหว่างวันที่
    <span class="chip" contenteditable="true" data-target="joinDates">
        <?= h($joinDates ?: '') ?>
    </span>
    โดยเอกสารงานประชุมวิชาการจะถูกตีพิมพ์อยู่ในฐานข้อมูล Scopus นั้น
</div>

<!-- ⭐ ย่อหน้า 2 -->
<div class="content-block paragraph">
    การนี้ ข้าพเจ้าจึงมีความประสงค์ขออนุมัติเดินทางเพื่อนำเสนอผลงานวิจัยในงานประชุมวิชาการดังกล่าว
    ซึ่งจะจัดขึ้นในระหว่างวันที่
    <span class="chip" contenteditable="true" data-target="travelDates">
        <?= h($travelDates ?: '') ?>
    </span>
    ตามวัน เวลา และสถานที่ข้างต้น
    โดยการนำเสนอผลงานวิจัยในครั้งนี้เป็นประโยชน์ต่อการพัฒนาการเรียนการสอน งานวิจัย และสร้างชื่อเสียงให้กับมหาวิทยาลัย
    โดยขอใช้เงินจัดสรรให้หน่วยงาน ประจำปีงบประมาณ พ.ศ.
    <?= h($thaiYear ?: '....') ?>
    หมวดค่าใช้สอย (รายละเอียดตามเอกสารแนบ)
</div>

<!-- ⭐ ย่อหน้า 3 -->
<div class="content-block paragraph" style="text-indent:2.5cm;">
    จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
</div>