<?php
/** Minimal dependency-free PDF writer for certificate downloads. */
function pdf_escape(string $text): string
{
    $text = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $text);
}

function output_certificate_pdf(array $data): void
{
    global $conn;

    $name = pdf_escape((string)($data['candidate'] ?? 'Candidate Name'));
    $cert = pdf_escape((string)($data['certificate_number'] ?? 'IB-2026-000000'));
    $assessment = pdf_escape((string)($data['assessment'] ?? 'Assessment Test'));
    $levelNum = (int)($data['level'] ?? 1);
    
    $levelNameStr = '';
    if (!empty($data['level_name'])) {
        $levelNameStr = (string)$data['level_name'];
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT level_name FROM levels WHERE level_number = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $levelNum);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) $levelNameStr = (string)$row['level_name'];
        }
    }
    if ($levelNameStr === '') {
        $levelNameStr = "Level {$levelNum}";
    }

    $levelDisplay = pdf_escape("Level {$levelNum} - {$levelNameStr}");
    $percentage = pdf_escape((string)($data['percentage'] ?? '0') . '%');
    $date = pdf_escape((string)($data['issue_date'] ?? date('Y-m-d')));

    // A4 Landscape: 842 pt x 595 pt
    $nameLen = strlen((string)($data['candidate'] ?? 'Candidate Name'));
    $nameX = max(60, (int)(421 - ($nameLen * 28 * 0.28)));

    $assLen = strlen((string)($data['assessment'] ?? 'Assessment Test'));
    $assX = max(60, (int)(421 - ($assLen * 18 * 0.28)));

    $levelLen = strlen("Qualification: Level {$levelNum} - {$levelNameStr}");
    $levelX = max(60, (int)(421 - ($levelLen * 13 * 0.27)));

    $streamBytes = [];
    
    // 1. Outer Border: Dark Blue (#2563eb -> 0.145 0.388 0.922)
    $streamBytes[] = "q";
    $streamBytes[] = "0.145 0.388 0.922 RG 2.5 w 25 25 792 545 re S";
    
    // 2. Inner Border: Thin Slate Line (#94a3b8 -> 0.58 0.64 0.72)
    $streamBytes[] = "0.58 0.64 0.72 RG 0.75 w 31 31 780 533 re S";

    // 3. Top Decorative Header Bar
    $streamBytes[] = "0.145 0.388 0.922 rg 35 558 772 12 re f";
    $streamBytes[] = "0.851 0.467 0.024 rg 35 554 772 4 re f";

    // 4. Header Text: Brand & Title
    $streamBytes[] = "BT";
    $streamBytes[] = "/F2 15 Tf 0.118 0.161 0.231 rg 1 0 0 1 345 520 Tm (INTERNBOOT PLATFORM) Tj";
    $streamBytes[] = "/F2 26 Tf 0.145 0.388 0.922 rg 1 0 0 1 230 475 Tm (CERTIFICATE OF ACHIEVEMENT) Tj";
    $streamBytes[] = "ET";

    // Accent line under title
    $streamBytes[] = "0.145 0.388 0.922 RG 1.5 w 260 462 m 582 462 l S";

    // 5. Certification Statement
    $streamBytes[] = "BT";
    $streamBytes[] = "/F1 12 Tf 0.392 0.455 0.545 rg 1 0 0 1 345 430 Tm (THIS IS TO CERTIFY THAT) Tj";
    
    // Candidate Name
    $streamBytes[] = "/F2 28 Tf 0.06 0.09 0.16 rg 1 0 0 1 {$nameX} 378 Tm ({$name}) Tj";
    $streamBytes[] = "ET";

    // Accent line under candidate name
    $streamBytes[] = "0.851 0.467 0.024 RG 1.5 w 280 365 m 562 365 l S";

    // 6. Assessment Details
    $streamBytes[] = "BT";
    $streamBytes[] = "/F1 12 Tf 0.392 0.455 0.545 rg 1 0 0 1 200 330 Tm (has successfully demonstrated proficiency and completed the assessment:) Tj";
    $streamBytes[] = "/F2 18 Tf 0.145 0.388 0.922 rg 1 0 0 1 {$assX} 295 Tm ({$assessment}) Tj";
    $streamBytes[] = "ET";

    // 7. Qualification Badge Box
    $streamBytes[] = "0.941 0.965 1.0 rg 0.753 0.847 0.988 RG 1 w 180 195 482 65 re B";
    $streamBytes[] = "BT";
    $streamBytes[] = "/F2 13 Tf 0.118 0.161 0.231 rg 1 0 0 1 {$levelX} 235 Tm (Qualification: {$levelDisplay}) Tj";
    $streamBytes[] = "/F1 12 Tf 0.145 0.388 0.922 rg 1 0 0 1 350 212 Tm (Final Evaluation Score: {$percentage}) Tj";
    $streamBytes[] = "ET";

    // 8. Footer Section
    $streamBytes[] = "0.85 0.88 0.92 RG 1 w 50 115 m 792 115 l S";
    $streamBytes[] = "BT";
    $streamBytes[] = "/F1 10 Tf 0.392 0.455 0.545 rg 1 0 0 1 60 90 Tm (Certificate No: {$cert}) Tj";
    $streamBytes[] = "/F3 10 Tf 0.145 0.388 0.922 rg 1 0 0 1 305 90 Tm (Verified by InternBoot Assessment Engine) Tj";
    $streamBytes[] = "/F1 10 Tf 0.392 0.455 0.545 rg 1 0 0 1 660 90 Tm (Issue Date: {$date}) Tj";
    $streamBytes[] = "ET";
    $streamBytes[] = "Q";

    $stream = implode("\n", $streamBytes) . "\n";

    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
    $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 5 0 R /F2 6 0 R /F3 7 0 R >> >> /Contents 4 0 R >>';
    $objects[] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique >>';

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $object) {
        $objectNumber = $i + 1;
        $offsets[$objectNumber] = strlen($pdf);
        $pdf .= $objectNumber . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

    if (!headers_sent()) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($data['certificate_number'] ?? 'certificate')) . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
    }
    echo $pdf;
    exit;
}
