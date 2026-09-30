<?php
require_once __DIR__ . '/../../src/core/bootstrap.php';

echo "=== ALL attempts for candidate 1 (latest first) ===\n";
$res = $conn->query("SELECT a.id, a.candidate_id, a.exam_slot_id, a.status, a.start_time, a.end_time, sl.start_time as slot_start, sl.end_time as slot_end, es.exam_date 
                     FROM attempts a 
                     LEFT JOIN exam_slots sl ON sl.id = a.exam_slot_id
                     LEFT JOIN exam_schedules es ON es.id = sl.exam_schedule_id
                     WHERE a.candidate_id = 1
                     ORDER BY a.id DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo "ID:{$row['id']} | slot:{$row['exam_slot_id']} | status:{$row['status']} | date:{$row['exam_date']} | slot_time:{$row['slot_start']}-{$row['slot_end']}\n";
}

echo "\n=== ALL attempts for candidate 501 (latest first) ===\n";
$res2 = $conn->query("SELECT a.id, a.candidate_id, a.exam_slot_id, a.status, a.start_time, a.end_time, sl.start_time as slot_start, sl.end_time as slot_end, es.exam_date 
                     FROM attempts a 
                     LEFT JOIN exam_slots sl ON sl.id = a.exam_slot_id
                     LEFT JOIN exam_schedules es ON es.id = sl.exam_schedule_id
                     WHERE a.candidate_id = 501
                     ORDER BY a.id DESC LIMIT 10");
while($row = $res2->fetch_assoc()) {
    echo "ID:{$row['id']} | slot:{$row['exam_slot_id']} | status:{$row['status']} | date:{$row['exam_date']} | slot_time:{$row['slot_start']}-{$row['slot_end']}\n";
}
