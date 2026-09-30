<?php
require_once __DIR__ . '/../../src/core/bootstrap.php';
$res = $conn->query("SELECT * FROM candidate_assessments WHERE candidate_id = 1");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
