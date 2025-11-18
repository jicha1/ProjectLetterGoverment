<div class="content-block paragraph">
    ข้าพเจ้า
    <span class="chip" contenteditable="true" data-target="ownerName">
        <?= h($ownerName ?: 'ชื่อ-นามสกุล') ?>
    </span>
    <span class="chip" contenteditable="true" data-target="position">
        <?= h($position ?: '') ?>
    </span>
    มีความประสงค์
    <span class="chip" contenteditable="true" data-target="courseName">
        <?= h($courseName ?: 'ระบุรายละเอียด') ?>
    </span>
    ระหว่างวันที่
    <span class="chip" contenteditable="true" data-target="joinDates">
        <?= h($joinDates ?: '') ?>
    </span>
    ณ
    <span class="chip" contenteditable="true" data-target="location">
        <?= h($location ?: '') ?>
    </span>
</div>