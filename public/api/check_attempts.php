<?php
require_once __DIR__ . '/../../src/core/bootstrap.php';
$res = $conn->query("SHOW COLUMNS FROM attempts");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
