<?php
// Path: scripts/apply-schema.php

require_once __DIR__ . '/../src/core/bootstrap.php';

$force = in_array('--force', $argv, true);

echo "Reading schema.sql...\n";
$schemaFile = __DIR__ . '/../schema.sql';
if (!file_exists($schemaFile)) {
    die("Error: schema.sql not found at {$schemaFile}\n");
}

$sqlContent = file_get_contents($schemaFile);
if ($sqlContent === false) {
    die("Error: Could not read schema.sql\n");
}

// Very basic statement splitting (does not handle semicolons inside strings properly, 
// but sufficient for our simple schema.sql scaffold)
$statements = explode(';', $sqlContent);
$totalStatements = 0;
$executedStatements = 0;
$skippedStatements = 0;

echo "Parsed " . count($statements) . " statements. Skipping DROP TABLE safety check if --force is used...\n";

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') {
        continue;
    }

    $totalStatements++;

    // Simple destructive check
    if (stripos($stmt, 'DROP TABLE') !== false && !$force) {
        echo "[SKIP] Destructive statement detected: " . substr(str_replace(["\n", "\r"], ' ', $stmt), 0, 60) . "...\n";
        $skippedStatements++;
        continue;
    }

    // Attempt to execute
    try {
        if (!$conn->query($stmt)) {
            echo "[WARN] Statement failed: " . $conn->error . "\n";
            echo "       " . substr(str_replace(["\n", "\r"], ' ', $stmt), 0, 60) . "...\n";
        } else {
            $executedStatements++;
        }
    } catch (Exception $e) {
        echo "[WARN] Exception on statement: " . $e->getMessage() . "\n";
    }
}

echo "=================================================\n";
echo "Schema application complete.\n";
echo "Total statements: {$totalStatements}\n";
echo "Executed: {$executedStatements}\n";
echo "Skipped: {$skippedStatements}\n";
echo "=================================================\n";
if ($skippedStatements > 0) {
    echo "Note: Use --force to execute skipped DROP TABLE statements (DANGEROUS).\n";
}
